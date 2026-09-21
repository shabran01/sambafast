<?php
// Secure session start
session_start();
ini_set('display_errors', 0);

// Database configuration
define('APP_URL', 'https://billing.sambafast.com');
$_app_stage = 'Live';

// Remote server configuration
$config = [
    'server_ip' => '86.48.1.246',
    'server_user' => 'root',
    'server_pass' => 'Samba234',
    'port' => null  // Will be auto-detected
];

// Auto-detect SSH port function
function detectSSHPort($ip, $timeout = 5) {
    $common_ports = [22, 2222, 2022, 2020, 22222, 10022];
    
    foreach ($common_ports as $port) {
        $connection = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if ($connection) {
            fclose($connection);
            return $port;
        }
    }
    return 22; // Default fallback
}

// Test SSH connection function
function testSSHConnection($config) {
    if (!function_exists('ssh2_connect')) {
        return ["success" => false, "message" => "SSH2 extension not installed"];
    }
    
    // Auto-detect port if not set
    $port = $config['port'] ?: detectSSHPort($config['server_ip']);
    
    $connection = @ssh2_connect($config['server_ip'], $port);
    
    if (!$connection) {
        return ["success" => false, "message" => "Cannot connect to {$config['server_ip']}:{$port} (auto-detected port)"];
    }
    
    if (!@ssh2_auth_password($connection, $config['server_user'], $config['server_pass'])) {
        return ["success" => false, "message" => "Authentication failed for user '{$config['server_user']}' on port {$port}"];
    }
    
    // Test a simple command
    $stream = @ssh2_exec($connection, 'whoami');
    if ($stream) {
        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        return ["success" => true, "message" => "Connected successfully on port {$port}. Current user: " . trim($output)];
    }
    
    return ["success" => false, "message" => "Connection established but command execution failed"];
}

// Database credentials
$db_host = 'localhost';
$db_user = 'pma_user';
$db_password = 'strong_password';
$db_name = 'master-installer';

// Folder paths
$base_folder = "/var/www/html/subdomains";
$source_folder = "/var/www/html/billing";

// Error reporting
if ($_app_stage != 'Live') {
    error_reporting(E_ERROR);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ERROR);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// WireGuard Functions
function getServerIPs($connection) {
    $command = "ip addr show wg0 | grep 'inet' | awk '{print $2}' | cut -d/ -f1";
    $stream = ssh2_exec($connection, $command);
    stream_set_blocking($stream, true);
    $output = stream_get_contents($stream);
    
    preg_match_all('/10\.7\.(\d+)\.(\d+)/', $output, $matches);
    return (isset($matches[1], $matches[2])) ? array_map(function($a, $b) { return "$a.$b"; }, $matches[1], $matches[2]) : [];
}

function getWireGuardIPs($connection) {
    $command = "sudo wg show all | grep allowed";
    $stream = ssh2_exec($connection, $command);
    stream_set_blocking($stream, true);
    $output = stream_get_contents($stream);
    
    preg_match_all('/10\.7\.(\d+)\.(\d+)/', $output, $matches);
    return (isset($matches[1], $matches[2])) ? array_map(function($a, $b) { return "$a.$b"; }, $matches[1], $matches[2]) : [];
}

function getUsedIPs($connection) {
    $server_ips = getServerIPs($connection);
    $wg_ips = getWireGuardIPs($connection);
    
    $used_ips = [];
    foreach ($server_ips as $ip) {
        $used_ips[$ip] = ['server' => true, 'config' => false];
    }
    foreach ($wg_ips as $ip) {
        if (isset($used_ips[$ip])) {
            $used_ips[$ip]['config'] = true;
        } else {
            $used_ips[$ip] = ['server' => false, 'config' => true];
        }
    }
    
    return $used_ips;
}

function getNextAvailableIP($connection) {
    $used_ips = getUsedIPs($connection);
    
    // Find next available IP, support up to 1000 IPs starting from 44
    // Using format 10.7.x.y to accommodate more IPs
    for ($octet3 = 0; $octet3 <= 3; $octet3++) {
        for ($octet4 = ($octet3 == 0 ? 44 : 1); $octet4 <= 254; $octet4++) {
            $index = ($octet3 * 254) + $octet4;
            if ($index > 1098) break; // Stop after 1000 IPs (44-254 + 1-254 + 1-254 + 1-254 = 1000 IPs)
            
            if (!isset($used_ips["$octet3.$octet4"])) {
                return "10.7.$octet3.$octet4";
            }
        }
    }
    
    return null;
}

function createWireGuardClient($connection, $client_name) {
    // Sync WireGuard configuration first
    syncWireGuardConfig($connection);
    
    // Get next available IP
    $ip_address = getNextAvailableIP($connection);
    
    if (!$ip_address) {
        return ["success" => false, "message" => "No available IP addresses"];
    }

    // Execute WireGuard script with full path
    $command = "cd /root && sudo bash wireguard.sh";
    $stream = ssh2_exec($connection, $command);
    stream_set_blocking($stream, true);
    
    // Send inputs
    $inputs = [
        "1\n",              // Add new client
        "$client_name\n",   // Client name
        "2\n",             // Google DNS
        "Y\n",             // Specify IP
        "$ip_address\n"    // IP address
    ];
    
    foreach ($inputs as $input) {
        fwrite($stream, $input);
        sleep(1);
    }
    
    $output = stream_get_contents($stream);
    
    // Sync configuration again after adding new client
    syncWireGuardConfig($connection);
    
    return [
        "success" => true,
        "message" => "WireGuard client created successfully with IP: $ip_address",
        "ip" => $ip_address
    ];
}

function syncWireGuardConfig($connection) {
    $command = "sudo wg-quick down wg0 2>/dev/null; sudo wg-quick up wg0";
    $stream = ssh2_exec($connection, $command);
    stream_set_blocking($stream, true);
    stream_get_contents($stream);
}

function createCronJobs($connection, $subdomain) {
    try {
        // Check if cron jobs for this subdomain already exist
        $checkExisting = "crontab -l 2>/dev/null | grep -q 'cron for {$subdomain}' && echo 'exists' || echo 'not_found'";
        $stream = ssh2_exec($connection, $checkExisting);
        stream_set_blocking($stream, true);
        $existsResult = trim(stream_get_contents($stream));
        
        if ($existsResult === 'exists') {
            return [
                'success' => true,
                'message' => "Cron jobs for '{$subdomain}' already exist (skipped)"
            ];
        }
        
        // Create temp file path
        $tempFile = "/tmp/new_cron_entries_" . uniqid();
        
        // Define cron jobs
        $cronJobs = [
            "# cron for {$subdomain}",
            "# Execute expired customers every 4 hours",
            "0 */4 * * * cd /var/www/html/subdomains/{$subdomain}/system && php -f cron.php",
            "",
            "# Run the script every 5 minutes (e.g., for creating hourly vouchers)",
            "*/5 * * * * cd /var/www/html/subdomains/{$subdomain}/system && php -f cron.php",
            "",
            "# Send reminder to users every day at 7 AM",
            "0 7 * * * cd /var/www/html/subdomains/{$subdomain}/system && php -f cron_reminder.php",
            ""
        ];
        
        // Add each line to temp file
        foreach ($cronJobs as $line) {
            $escapedLine = str_replace("'", "'\\''", $line);
            $addLine = "echo '{$escapedLine}' >> {$tempFile}";
            $stream = ssh2_exec($connection, $addLine);
            stream_set_blocking($stream, true);
            stream_get_contents($stream);
        }
        
        // Backup current crontab
        $backupFile = "/tmp/crontab_backup_" . date('Y_m_d_H_i_s');
        $stream = ssh2_exec($connection, "crontab -l > {$backupFile} 2>/dev/null || touch {$backupFile}");
        stream_set_blocking($stream, true);
        stream_get_contents($stream);
        
        // Append new entries to existing crontab
        $appendCommand = "crontab -l 2>/dev/null | cat - {$tempFile} | crontab -";
        $stream = ssh2_exec($connection, $appendCommand);
        stream_set_blocking($stream, true);
        stream_get_contents($stream);
        
        // Clean up temp file
        $stream = ssh2_exec($connection, "rm -f {$tempFile}");
        stream_set_blocking($stream, true);
        stream_get_contents($stream);
        
        return [
            'success' => true,
            'message' => "Cron jobs created successfully (3 jobs: every 4 hours, every 5 minutes, daily at 7 AM)"
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Failed to create cron jobs: " . $e->getMessage()
        ];
    }
}

function sendNotifications($subdomain, $wg_ip = null) {
    // Prepare notification message
    $system_url = "https://$subdomain.speedcomwifi.xyz/admin";
    $notification_message = "Welcome to SpeedRadius! Your system has been created successfully.\n\nSystem URL: $system_url\n\nDefault Login Credentials:\nUsername: admin\nPassword: admin1234";
    
    // Add WireGuard IP if available
    if ($wg_ip) {
        $notification_message .= "\n\nWireGuard Details:\nClient Name: $subdomain\nIP Address: $wg_ip";
    }
    
    $notification_message .= "\n\nWait for DNS propagation to take place 3 Mins.";
    
    $messages = [];
    
    // Since phone number is removed, just log the setup completion
    $messages[] = "ISP instance created successfully. No notifications sent (phone number disabled).";
    
    return $messages;
}

$messages = [];
$success = true;

// Test SSH connection if requested
if (isset($_GET['test_ssh'])) {
    $test_result = testSSHConnection($config);
    header('Content-Type: application/json');
    echo json_encode($test_result);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subdomain = strtolower(trim($_POST["subdomain"]));
    $new_db = $subdomain;
    $wg_ip = null;

    // Validate inputs
    if (!preg_match('/^[a-z0-9]{2,62}$/', $subdomain)) {
        $messages[] = "Error: Subdomain can only contain letters and numbers, minimum 2 characters and maximum 62 characters.";
        $success = false;
    }
    else {
        // Step 1: Create WireGuard Client
        // Check if SSH2 extension is loaded
        if (!function_exists('ssh2_connect')) {
            $messages[] = "Error: SSH2 PHP extension is not installed. Please install it first.";
            $success = false;
        } else {
            // Auto-detect port if not set
            $port = $config['port'] ?: detectSSHPort($config['server_ip']);
            $messages[] = "Testing SSH connection on auto-detected port: {$port}";
            
            $connection = @ssh2_connect($config['server_ip'], $port);
            
            if (!$connection) {
                $messages[] = "Error: Failed to connect to WireGuard server ({$config['server_ip']}:{$port}). Please check server IP, port, and firewall settings.";
                $success = false;
            } elseif (!@ssh2_auth_password($connection, $config['server_user'], $config['server_pass'])) {
                $messages[] = "Error: SSH authentication failed. Please check username and password.";
                $success = false;
            } else {
                $wg_result = createWireGuardClient($connection, $subdomain);
                if ($wg_result['success']) {
                    $wg_ip = $wg_result['ip'];
                    $messages[] = "WireGuard: " . $wg_result['message'];
                } else {
                    $messages[] = "WireGuard Error: " . $wg_result['message'];
                    $success = false;
                }
            }
        }

        if ($success) {
            // 2. Create Database
            $mysqli = new mysqli($db_host, $db_user, $db_password);
            if ($mysqli->connect_error) {
                $messages[] = "Database Connection Error: " . $mysqli->connect_error;
                $success = false;
            } else {
                if ($mysqli->query("CREATE DATABASE `$new_db`") === TRUE) {
                    $dump_command = "mysqldump -u $db_user -p$db_password $db_name | mysql -u $db_user -p$db_password $new_db";
                    system($dump_command, $return_var);

                    if ($return_var === 0) {
                        $messages[] = "Database created successfully: $new_db";
                        
                        // Connect to the new database to update configuration
                        $new_conn = new mysqli($db_host, $db_user, $db_password, $new_db);
                        if ($new_conn->connect_error) {
                            $messages[] = "Error connecting to new database: " . $new_conn->connect_error;
                            $success = false;
                        } else {
                            // Update company name only
                            $stmt = $new_conn->prepare("UPDATE tbl_appconfig SET value = ? WHERE setting = 'CompanyName'");
                            $stmt->bind_param("s", $subdomain);
                            if (!$stmt->execute()) {
                                $messages[] = "Error updating company name: " . $stmt->error;
                                $success = false;
                            }
                            $stmt->close();
                            
                            $new_conn->close();
                            $messages[] = "Database configuration updated successfully";
                        }
                    } else {
                        $messages[] = "Error duplicating database content";
                        $success = false;
                    }
                } else {
                    $messages[] = "Error creating database: " . $mysqli->error;
                    $success = false;
                }
                $mysqli->close();
            }
        }

        if ($success) {
            // 3. Create and Configure Subdomain Folder
            $new_folder = "${base_folder}/${subdomain}";
            
            if (!file_exists($new_folder)) {
                if (mkdir($new_folder, 0755, true)) {
                    $messages[] = "Subdomain folder created successfully";
                    
                    // Log copy command for debugging
                    $copy_command = "cp -r $source_folder/* $new_folder/";
                    error_log("Executing copy command: " . $copy_command);
                    system($copy_command, $return_var);
                    
                    if ($return_var === 0) {
                        $messages[] = "Files copied to subdomain folder successfully";
                        
                        // Update config.php
                        $config_file = "$new_folder/config.php";
                        if (file_exists($config_file)) {
                            $config_contents = file_get_contents($config_file);
                            $new_app_url = "https://$subdomain.sambafast.com";
                            
                            $patterns = [
                                '/\$db_name\s*=\s*[\'"][^\'"]+[\'"];/',
                                '/define\s*\(\s*[\'"]APP_URL[\'"]\s*,\s*[\'"]https:\/\/[^\/]+\.sambafast\.com[\'"]\s*\);/'
                            ];
                            
                            $replacements = [
                                "\$db_name = '$new_db';",
                                "define('APP_URL', '$new_app_url');"
                            ];
                            
                            $updated_contents = preg_replace($patterns, $replacements, $config_contents);
                            
                            if (file_put_contents($config_file, $updated_contents)) {
                                $messages[] = "Configuration updated successfully";
                            } else {
                                $messages[] = "Warning: Could not update configuration file";
                            }
                        } else {
                            $messages[] = "Warning: Configuration file not found";
                        }
                    } else {
                        $messages[] = "Error copying files to subdomain folder. Return var: " . $return_var;
                        error_log("Error copying files to subdomain folder: " . $return_var);
                        $success = false;
                    }
                } else {
                    $messages[] = "Error creating subdomain folder: Permission denied or invalid path.";
                    error_log("Error creating subdomain folder: " . error_get_last()['message']);
                    $success = false;
                }
            } else {
                $messages[] = "Subdomain folder already exists: $new_folder";
                // If folder exists, still try to copy files to ensure it's populated
                $copy_command = "cp -r $source_folder/* $new_folder/";
                error_log("Executing copy command to existing folder: " . $copy_command);
                system($copy_command, $return_var);
                
                if ($return_var === 0) {
                    $messages[] = "Files copied to existing subdomain folder successfully";
                    
                    // Update config.php for existing folder
                    $config_file = "$new_folder/config.php";
                    if (file_exists($config_file)) {
                        $config_contents = file_get_contents($config_file);
                        $new_app_url = "https://$subdomain.sambafast.com";
                        
                        $patterns = [
                            '/\$db_name\s*=\s*[\'"][^\'"]+[\'"];/',
                            '/define\s*\(\s*[\'"]APP_URL[\'"]\s*,\s*[\'"]https:\/\/[^\/]+\.sambafast\.com[\'"]\s*\);/'
                        ];
                        
                        $replacements = [
                            "\$db_name = '$new_db';",
                            "define('APP_URL', '$new_app_url');"
                        ];
                        
                        $updated_contents = preg_replace($patterns, $replacements, $config_contents);
                        
                        if (file_put_contents($config_file, $updated_contents)) {
                            $messages[] = "Configuration updated successfully";
                        } else {
                            $messages[] = "Warning: Could not update configuration file";
                        }
                    } else {
                        $messages[] = "Warning: Configuration file not found";
                    }
                } else {
                    $messages[] = "Error copying files to existing subdomain folder. Return var: " . $return_var;
                    error_log("Error copying files to existing subdomain folder: " . $return_var);
                    $success = false;
                }
            }
        }

        if ($success) {
            // 4. Configure Apache and DNS
            // Create Apache virtual host
            $apache_conf = <<<EOL
<VirtualHost *:80>
    ServerName ${subdomain}.sambafast.com
    ServerAlias www.${subdomain}.sambafast.com
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/subdomains/${subdomain}
    <Directory /var/www/html/subdomains/${subdomain}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/${subdomain}-error.log
    CustomLog \${APACHE_LOG_DIR}/${subdomain}-access.log combined
</VirtualHost>
EOL;

            $temp_file = tempnam(sys_get_temp_dir(), 'apache_conf_');
            file_put_contents($temp_file, $apache_conf);

            // Copy configuration to remote server
            if (!ssh2_scp_send($connection, $temp_file, "/etc/apache2/sites-available/${subdomain}.conf", 0644)) {
                $messages[] = "Error: Could not copy Apache configuration";
                $success = false;
            } else {
                // Enable site and reload Apache
                $commands = [
                    "sudo a2ensite ${subdomain}.conf",
                    "sudo systemctl reload apache2"
                ];

                foreach ($commands as $cmd) {
                    $stream = ssh2_exec($connection, $cmd);
                    stream_set_blocking($stream, true);
                    stream_get_contents($stream);
                }

                $messages[] = "Apache virtual host configured successfully";

                // Cloudflare credentials
                $zone_id = "8b61fbaf92787c3fc1bbf00629a45d21";
                $api_token = "GcfEsBfkVrMM1rC4tmWBrqfM1iQBD3QpgLJqFCUZ";

                // Configure DNS in Cloudflare
                $url = "https://api.cloudflare.com/client/v4/zones/$zone_id/dns_records";
                
                // For Cloudflare, we only need the subdomain part as the zone already includes the domain
                $data = [
                    "type" => "A",
                    "name" => $subdomain,  // Cloudflare automatically appends .sambafast.com
                    "content" => $config['server_ip'],
                    "ttl" => 1,
                    "proxied" => true
                ];

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => [
                        "Authorization: Bearer $api_token",
                        "Content-Type: application/json"
                    ],
                    CURLOPT_SSL_VERIFYPEER => true
                ]);

                $response = curl_exec($ch);
                
                if ($response === false) {
                    $messages[] = "Warning: Could not create DNS record - " . curl_error($ch);
                } else {
                    $response_data = json_decode($response, true);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    
                    if ($http_code === 200 && isset($response_data['success']) && $response_data['success'] === true) {
                        $messages[] = "DNS record created successfully";
                    } else {
                        $error_message = isset($response_data['errors'][0]['message']) ? 
                            $response_data['errors'][0]['message'] : "Unknown error";
                        $messages[] = "Warning: Could not create DNS record - $error_message";
                        // Log the full response for debugging
                        error_log("Cloudflare API Response: " . $response);
                        error_log("HTTP Code: " . $http_code);
                        
                        // Check if token is invalid
                        if ($http_code === 401 || strpos($response, 'invalid') !== false) {
                            $messages[] = "Error: Cloudflare API token is invalid or expired. Please update your API token.";
                        }
                    }
                }
                
                curl_close($ch);
                
                // 5. Create Cron Jobs
                $cron_result = createCronJobs($connection, $subdomain);
                if ($cron_result['success']) {
                    $messages[] = "Cron Jobs: " . $cron_result['message'];
                } else {
                    $messages[] = "Warning - Cron Jobs: " . $cron_result['message'];
                    // Don't fail the entire setup if cron jobs fail
                }

            }

            unlink($temp_file);
        }
    }
    
    // After successful setup, send notifications
    if ($success) {
        $notification_results = sendNotifications($subdomain, $wg_ip);
        $messages = array_merge($messages, $notification_results);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sambafast - ISP Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@heroicons/react@2.0.18/24/outline/index.min.js"></script>
    <style>
        .gradient-background {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .glow {
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
        }
    </style>
</head>
<body class="gradient-background min-h-screen py-12 px-4 sm:px-6 lg:px-8 text-white">
    <!-- Animated background elements -->
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-12 floating">
            <h1 class="text-5xl font-bold text-white mb-4 tracking-tight">SambaFast ISP Setup</h1>
            <p class="text-xl text-blue-200">Create your ISP instance with integrated WireGuard VPN</p>
        </div>

        <!-- Main Content -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl space-y-8 relative overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-blue-500 rounded-full opacity-10"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-purple-500 rounded-full opacity-10"></div>

            <?php if (!empty($messages)): ?>
                <div class="space-y-3">
                    <?php foreach ($messages as $msg): ?>
                        <div class="p-4 rounded-lg <?php echo $success ? 'bg-green-900/50 text-green-200' : 'bg-red-900/50 text-red-200'; ?> backdrop-blur-sm">
                            <p class="flex items-center">
                                <?php if ($success): ?>
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($msg); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Subdomain Input -->
                <div class="relative group">
                    <label for="subdomain" class="block text-sm font-medium text-blue-200 mb-2">
                        Subdomain Name
                    </label>
                    <div class="relative">
                        <input type="text" name="subdomain" id="subdomain" required 
                               class="block w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="Enter subdomain name">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-white/50">
                            .sambafast.com
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-blue-200 opacity-80">Letters and numbers only, 2-62 characters</p>
                </div>

                <!-- SSH Connection Test -->
                <div class="border-t border-white/20 pt-6">
                    <button type="button" onclick="testSSHConnection()" 
                            class="w-full py-3 px-6 rounded-lg bg-gradient-to-r from-yellow-600 to-orange-600 text-white font-semibold hover:from-yellow-700 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transform hover:scale-[1.02] transition-all duration-200">
                        Test SSH Connection
                    </button>
                    <div id="ssh-test-result" class="mt-3 hidden"></div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full py-4 px-6 rounded-lg bg-gradient-to-r from-blue-600 to-blue-800 text-white font-semibold text-lg hover:from-blue-700 hover:to-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:scale-[1.02] transition-all duration-200 glow">
                    Create ISP Instance
                </button>
            </form>

            <!-- Features Section -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-6 rounded-lg bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="flex items-center mb-4">
                        <svg class="h-6 w-6 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-white">Secure WireGuard VPN</h3>
                    </div>
                    <p class="text-blue-200 opacity-80">Automatically configured VPN client with modern encryption standards</p>
                </div>
                <div class="p-6 rounded-lg bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="flex items-center mb-4">
                        <svg class="h-6 w-6 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-white">Custom Domain Setup</h3>
                    </div>
                    <p class="text-blue-200 opacity-80">Automated subdomain configuration with SSL certificate</p>
                </div>
                <div class="p-6 rounded-lg bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="flex items-center mb-4">
                        <svg class="h-6 w-6 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-white">Automated Cron Jobs</h3>
                    </div>
                    <p class="text-blue-200 opacity-80">Auto-scheduled tasks: customer cleanup, hourly vouchers, and daily reminders</p>
                </div>
                <div class="p-6 rounded-lg bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="flex items-center mb-4">
                        <svg class="h-6 w-6 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-white">Database Isolation</h3>
                    </div>
                    <p class="text-blue-200 opacity-80">Each instance gets its own dedicated database with automatic configuration</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Test SSH Connection function
        function testSSHConnection() {
            const resultDiv = document.getElementById('ssh-test-result');
            const button = event.target;
            
            // Show loading state
            button.disabled = true;
            button.textContent = 'Testing Connection...';
            resultDiv.className = 'mt-3 p-4 rounded-lg bg-yellow-900/50 text-yellow-200';
            resultDiv.innerHTML = '<p>Testing SSH connection to WireGuard server...</p>';
            resultDiv.classList.remove('hidden');
            
            // Make AJAX request
            fetch('?test_ssh=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.className = 'mt-3 p-4 rounded-lg bg-green-900/50 text-green-200';
                        resultDiv.innerHTML = `<p class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            ${data.message}
                        </p>`;
                    } else {
                        resultDiv.className = 'mt-3 p-4 rounded-lg bg-red-900/50 text-red-200';
                        resultDiv.innerHTML = `<p class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            ${data.message}
                        </p>`;
                    }
                })
                .catch(error => {
                    resultDiv.className = 'mt-3 p-4 rounded-lg bg-red-900/50 text-red-200';
                    resultDiv.innerHTML = `<p class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Error testing connection: ${error.message}
                    </p>`;
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = 'Test SSH Connection';
                });
        }
        
        // Add smooth hover effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('transform', 'scale-[1.02]');
            });
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('transform', 'scale-[1.02]');
            });
        });
    </script>
</body>
</html>
