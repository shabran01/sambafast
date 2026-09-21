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

// Delete Cron Jobs Function
function deleteCronJobs($connection, $subdomain) {
    try {
        // Check if cron jobs exist
        $checkExisting = "crontab -l 2>/dev/null | grep -q 'cron for {$subdomain}' && echo 'exists' || echo 'not_found'";
        $stream = ssh2_exec($connection, $checkExisting);
        stream_set_blocking($stream, true);
        $existsResult = trim(stream_get_contents($stream));
        
        if ($existsResult === 'not_found') {
            return [
                'success' => true,
                'message' => "No cron jobs found for '{$subdomain}' (already clean)"
            ];
        }
        
        // Backup current crontab
        $backupFile = "/tmp/crontab_backup_delete_" . date('Y_m_d_H_i_s') . "_" . $subdomain;
        $stream = ssh2_exec($connection, "crontab -l > {$backupFile} 2>/dev/null");
        stream_set_blocking($stream, true);
        stream_get_contents($stream);
        
        // Remove cron jobs for this subdomain (remove the comment line and 3 cron entries + empty lines)
        $removeCommand = "crontab -l 2>/dev/null | grep -v 'cron for {$subdomain}' | grep -v '/subdomains/{$subdomain}/system' | crontab -";
        $stream = ssh2_exec($connection, $removeCommand);
        stream_set_blocking($stream, true);
        stream_get_contents($stream);
        
        return [
            'success' => true,
            'message' => "Cron jobs deleted successfully (backup saved: {$backupFile})"
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Failed to delete cron jobs: " . $e->getMessage()
        ];
    }
}

// Delete Apache Virtual Host Function
function deleteApacheVirtualHost($connection, $subdomain) {
    try {
        // Check if virtual host exists
        $checkCommand = "[ -f /etc/apache2/sites-available/{$subdomain}.conf ] && echo 'exists' || echo 'not_found'";
        $stream = ssh2_exec($connection, $checkCommand);
        stream_set_blocking($stream, true);
        $existsResult = trim(stream_get_contents($stream));
        
        if ($existsResult === 'not_found') {
            return [
                'success' => true,
                'message' => "Virtual host for '{$subdomain}' not found (already clean)"
            ];
        }
        
        // Disable the site first
        $stream = ssh2_exec($connection, "sudo a2dissite {$subdomain}.conf 2>&1");
        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        
        // Backup the config file
        $backupCommand = "sudo cp /etc/apache2/sites-available/{$subdomain}.conf /tmp/apache_{$subdomain}_backup_" . date('Y_m_d_H_i_s') . ".conf";
        $stream = ssh2_exec($connection, $backupCommand);
        stream_set_blocking($stream, true);
        stream_get_contents($stream);
        
        // Remove the config file
        $stream = ssh2_exec($connection, "sudo rm -f /etc/apache2/sites-available/{$subdomain}.conf");
        stream_set_blocking($stream, true);
        stream_get_contents($stream);
        
        // Reload Apache
        $stream = ssh2_exec($connection, "sudo systemctl reload apache2 2>&1");
        stream_set_blocking($stream, true);
        stream_get_contents($stream);
        
        return [
            'success' => true,
            'message' => "Apache virtual host deleted and Apache reloaded successfully"
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Failed to delete Apache virtual host: " . $e->getMessage()
        ];
    }
}

// Delete DNS Record from Cloudflare
function deleteDNSRecord($subdomain) {
    $zone_id = "7bf4f0063e2d73a68e0138b6d078373d";
    $api_token = "esg5oMslL39GWmMb2qkY-feoemcn3vpmULES3tgB";
    
    try {
        // First, get the record ID
        $url = "https://api.cloudflare.com/client/v4/zones/$zone_id/dns_records?name={$subdomain}.speedcomwifi.xyz";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $api_token",
                "Content-Type: application/json"
            ],
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $http_code !== 200) {
            return [
                'success' => false,
                'message' => "Failed to retrieve DNS record"
            ];
        }
        
        $response_data = json_decode($response, true);
        
        if (empty($response_data['result'])) {
            return [
                'success' => true,
                'message' => "DNS record not found (already deleted or never existed)"
            ];
        }
        
        // Delete the record
        $record_id = $response_data['result'][0]['id'];
        $delete_url = "https://api.cloudflare.com/client/v4/zones/$zone_id/dns_records/$record_id";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $delete_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $api_token",
                "Content-Type: application/json"
            ],
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return [
                'success' => true,
                'message' => "DNS record deleted from Cloudflare successfully"
            ];
        } else {
            return [
                'success' => false,
                'message' => "Failed to delete DNS record from Cloudflare"
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "DNS deletion error: " . $e->getMessage()
        ];
    }
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
    $confirm = isset($_POST["confirm"]) ? $_POST["confirm"] : '';

    // Validate inputs
    if (!preg_match('/^[a-z0-9]{2,62}$/', $subdomain)) {
        $messages[] = "Error: Invalid subdomain format. Only letters and numbers, 2-62 characters.";
        $success = false;
    }
    elseif ($confirm !== 'DELETE') {
        $messages[] = "Error: You must type 'DELETE' to confirm deletion.";
        $success = false;
    }
    else {
        // Step 1: Connect to SSH server
        if (!function_exists('ssh2_connect')) {
            $messages[] = "Error: SSH2 PHP extension is not installed.";
            $success = false;
        } else {
            // Auto-detect port if not set
            $port = $config['port'] ?: detectSSHPort($config['server_ip']);
            $messages[] = "Testing SSH connection on auto-detected port: {$port}";
            
            $connection = @ssh2_connect($config['server_ip'], $port);
            
            if (!$connection) {
                $messages[] = "Error: Failed to connect to server ({$config['server_ip']}:{$port}).";
                $success = false;
            } elseif (!@ssh2_auth_password($connection, $config['server_user'], $config['server_pass'])) {
                $messages[] = "Error: SSH authentication failed.";
                $success = false;
            } else {
                // Step 2: Delete Cron Jobs
                $cron_result = deleteCronJobs($connection, $subdomain);
                $messages[] = "Cron Jobs: " . $cron_result['message'];
                
                // Step 3: Delete Apache Virtual Host
                $apache_result = deleteApacheVirtualHost($connection, $subdomain);
                $messages[] = "Apache: " . $apache_result['message'];
                
                // Step 4: Delete Subdomain Folder
                $folder_path = "{$base_folder}/{$subdomain}";
                
                // Check if folder exists
                $checkFolder = "[ -d '{$folder_path}' ] && echo 'exists' || echo 'not_found'";
                $stream = ssh2_exec($connection, $checkFolder);
                stream_set_blocking($stream, true);
                $folderExists = trim(stream_get_contents($stream));
                
                if ($folderExists === 'exists') {
                    // Backup folder first
                    $backup_path = "/tmp/subdomain_backup_{$subdomain}_" . date('Y_m_d_H_i_s') . ".tar.gz";
                    $backupCommand = "tar -czf {$backup_path} -C {$base_folder} {$subdomain} 2>&1";
                    $stream = ssh2_exec($connection, $backupCommand);
                    stream_set_blocking($stream, true);
                    stream_get_contents($stream);
                    
                    // Delete folder
                    $deleteFolder = "rm -rf '{$folder_path}' 2>&1";
                    $stream = ssh2_exec($connection, $deleteFolder);
                    stream_set_blocking($stream, true);
                    $output = stream_get_contents($stream);
                    
                    $messages[] = "Folder: Deleted successfully (backup: {$backup_path})";
                } else {
                    $messages[] = "Folder: Not found (already deleted)";
                }
                
                // Step 5: Delete Database
                $db_name = $subdomain;
                $mysqli = new mysqli($db_host, $db_user, $db_password);
                
                if ($mysqli->connect_error) {
                    $messages[] = "Database Error: Connection failed - " . $mysqli->connect_error;
                } else {
                    // Check if database exists
                    $result = $mysqli->query("SHOW DATABASES LIKE '$db_name'");
                    
                    if ($result->num_rows > 0) {
                        // Backup database first
                        $backup_file = "/tmp/db_backup_{$db_name}_" . date('Y_m_d_H_i_s') . ".sql";
                        $backup_command = "mysqldump -u $db_user -p$db_password $db_name > $backup_file 2>&1";
                        system($backup_command);
                        
                        // Drop database
                        if ($mysqli->query("DROP DATABASE `$db_name`")) {
                            $messages[] = "Database: Deleted successfully (backup: {$backup_file})";
                        } else {
                            $messages[] = "Database Error: Failed to delete - " . $mysqli->error;
                            $success = false;
                        }
                    } else {
                        $messages[] = "Database: Not found (already deleted)";
                    }
                    
                    $mysqli->close();
                }
                
                // Step 6: Delete DNS Record from Cloudflare
                $dns_result = deleteDNSRecord($subdomain);
                $messages[] = "DNS: " . $dns_result['message'];
            }
        }
        
        if ($success) {
            $messages[] = "✅ Complete! Subdomain '{$subdomain}' has been completely removed from the system.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeedRadius - ISP Deletion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .gradient-background {
            background: linear-gradient(135deg, #1e293b, #7f1d1d);
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

        .glow-red {
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
        }

        .warning-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .7;
            }
        }
    </style>
</head>
<body class="gradient-background min-h-screen py-12 px-4 sm:px-6 lg:px-8 text-white">
    <!-- Animated background elements -->
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-0 -left-4 w-72 h-72 bg-red-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-orange-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-12 floating">
            <h1 class="text-5xl font-bold text-white mb-4 tracking-tight">🗑️ SpeedRadius ISP Deletion</h1>
            <p class="text-xl text-red-200">Permanently remove ISP instances and all associated data</p>
        </div>

        <!-- Main Content -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl space-y-8 relative overflow-hidden border-2 border-red-500/30">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-red-500 rounded-full opacity-10"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-orange-500 rounded-full opacity-10"></div>

            <!-- Warning Banner -->
            <div class="bg-red-900/50 border-2 border-red-500 rounded-lg p-6 backdrop-blur-sm warning-pulse">
                <div class="flex items-start">
                    <svg class="h-8 w-8 text-red-400 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h3 class="text-xl font-bold text-white mb-2">⚠️ DANGER ZONE - PERMANENT DELETION</h3>
                        <p class="text-red-200 mb-3">This action will PERMANENTLY delete:</p>
                        <ul class="list-disc list-inside text-red-200 space-y-1 ml-4">
                            <li>Complete subdomain folder and all files</li>
                            <li>Entire database with all customer data</li>
                            <li>All automated cron jobs</li>
                            <li>Apache virtual host configuration</li>
                            <li>DNS record from Cloudflare</li>
                        </ul>
                        <p class="text-yellow-300 font-semibold mt-3">⚠️ Backups will be created, but this action CANNOT be easily undone!</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($messages)): ?>
                <div class="space-y-3">
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $isError = stripos($msg, 'error') !== false || stripos($msg, 'failed') !== false;
                            $isSuccess = stripos($msg, '✅') !== false;
                            $bgClass = $isError ? 'bg-red-900/50 text-red-200' : ($isSuccess ? 'bg-green-900/50 text-green-200' : 'bg-blue-900/50 text-blue-200');
                        ?>
                        <div class="p-4 rounded-lg <?php echo $bgClass; ?> backdrop-blur-sm">
                            <p class="flex items-start">
                                <?php if ($isError): ?>
                                    <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                <?php elseif ($isSuccess): ?>
                                    <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($msg); ?></span>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" onsubmit="return confirmDeletion()">
                <!-- Subdomain Input -->
                <div class="relative group">
                    <label for="subdomain" class="block text-sm font-medium text-red-200 mb-2">
                        Subdomain Name to Delete
                    </label>
                    <div class="relative">
                        <input type="text" name="subdomain" id="subdomain" required 
                               class="block w-full px-4 py-3 rounded-lg bg-white/10 border border-red-500/30 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition duration-200"
                               placeholder="Enter subdomain name to delete">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-white/50">
                            .sambafast.com
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-red-200 opacity-80">This subdomain will be permanently removed</p>
                </div>

                <!-- SSH Connection Test -->
                <div class="border-t border-red-500/30 pt-6">
                    <button type="button" onclick="testSSHConnection()" 
                            class="w-full py-3 px-6 rounded-lg bg-gradient-to-r from-yellow-600 to-orange-600 text-white font-semibold hover:from-yellow-700 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transform hover:scale-[1.02] transition-all duration-200">
                        Test SSH Connection
                    </button>
                    <div id="ssh-test-result" class="mt-3 hidden"></div>
                </div>

                <!-- Confirmation Input -->
                <div class="relative group">
                    <label for="confirm" class="block text-sm font-medium text-red-200 mb-2">
                        Type "DELETE" to confirm (case-sensitive)
                    </label>
                    <input type="text" name="confirm" id="confirm" required 
                           class="block w-full px-4 py-3 rounded-lg bg-white/10 border border-red-500/30 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition duration-200"
                           placeholder="Type DELETE to confirm">
                    <p class="mt-2 text-sm text-red-200 opacity-80">This confirms you understand the action is permanent</p>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full py-4 px-6 rounded-lg bg-gradient-to-r from-red-600 to-red-800 text-white font-semibold text-lg hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transform hover:scale-[1.02] transition-all duration-200 glow-red">
                    🗑️ PERMANENTLY DELETE ISP INSTANCE
                </button>

                <div class="text-center">
                    <a href="ispsetup.php" class="text-blue-300 hover:text-blue-200 underline">
                        ← Back to ISP Setup
                    </a>
                </div>
            </form>

            <!-- What Gets Deleted Section -->
            <div class="mt-12 bg-white/5 border border-white/10 rounded-lg p-6 backdrop-blur-sm">
                <h3 class="text-lg font-semibold text-white mb-4">🔍 Deletion Process Details:</h3>
                <div class="space-y-3 text-red-200">
                    <div class="flex items-start">
                        <span class="mr-2">1️⃣</span>
                        <span><strong>Cron Jobs:</strong> All automated tasks removed from server crontab</span>
                    </div>
                    <div class="flex items-start">
                        <span class="mr-2">2️⃣</span>
                        <span><strong>Apache Virtual Host:</strong> Site disabled and configuration files deleted</span>
                    </div>
                    <div class="flex items-start">
                        <span class="mr-2">3️⃣</span>
                        <span><strong>Subdomain Folder:</strong> All files in /var/www/html/subdomains/{subdomain} deleted</span>
                    </div>
                    <div class="flex items-start">
                        <span class="mr-2">4️⃣</span>
                        <span><strong>Database:</strong> Complete database with all tables and data dropped</span>
                    </div>
                    <div class="flex items-start">
                        <span class="mr-2">5️⃣</span>
                        <span><strong>DNS Record:</strong> Cloudflare A record removed (subdomain will stop resolving)</span>
                    </div>
                </div>
                <p class="mt-4 text-yellow-300 text-sm">💾 Backups are created in /tmp/ before deletion</p>
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
            resultDiv.innerHTML = '<p>Testing SSH connection to server...</p>';
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
        
        function confirmDeletion() {
            const subdomain = document.getElementById('subdomain').value.trim();
            const confirm = document.getElementById('confirm').value;
            
            if (confirm !== 'DELETE') {
                alert('❌ You must type "DELETE" (exactly, case-sensitive) to confirm deletion.');
                return false;
            }
            
            return window.confirm(
                `⚠️ FINAL CONFIRMATION ⚠️\n\n` +
                `You are about to PERMANENTLY DELETE the subdomain "${subdomain}".\n\n` +
                `This will remove:\n` +
                `• All files and folders\n` +
                `• Complete database\n` +
                `• All cron jobs\n` +
                `• Apache configuration\n` +
                `• DNS records\n\n` +
                `Are you ABSOLUTELY SURE you want to continue?`
            );
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
