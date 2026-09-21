<?php
/*
Comprehensive Bandwidth & Server Dashboard
Single-file PHP for Ubuntu 22.04
Drop as /var/www/html/index.php
Requirements: PHP 7+, Apache/Nginx+PHP-FPM
SECURITY: Protect with firewall / Basic Auth and HTTPS before exposing publicly.
*/

// --- Configuration ---
$IS_WINDOWS = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$IFACE = getenv('BW_IFACE') ?: 'eth0';
$PERSIST_FILE = sys_get_temp_dir() . "/bw_prev_{$IFACE}.json";
$HUMAN_PRECISION = 2;
$DB_FILE = sys_get_temp_dir() . '/bandwidth_history.db';
$HISTORY_RETENTION_HOURS = 24; // Keep 24 hours of history

// Cloudflare API credentials
$CLOUDFLARE_ZONE_ID = "7bf4f0063e2d73a68e0138b6d078373d";
$CLOUDFLARE_API_TOKEN = "esg5oMslL39GWmMb2qkY-feoemcn3vpmULES3tgB";
$CLOUDFLARE_SERVER_IP = "84.46.244.95";
$USE_CLOUDFLARE_ANALYTICS = true; // Set to false to use local logs instead

// Access log paths (auto-detect Apache or Nginx)
function get_access_log_paths() {
    $paths = [
        '/var/log/apache2/access.log',
        '/var/log/apache2/other_vhosts_access.log',
        '/var/log/nginx/access.log',
        '/var/log/httpd/access_log',
        '/var/log/httpd/access.log',
        'C:/xampp/apache/logs/access.log',
        'C:/wamp/logs/access.log',
        'C:/wamp64/logs/access.log',
        getcwd() . '/access.log',
        dirname(__FILE__) . '/access.log',
    ];
    
    // Try to auto-detect from common vhost locations
    $vhost_dirs = [
        '/var/www/html',
        '/var/www',
        '/usr/share/nginx/html',
        '/home/*/public_html',
        '/var/www/vhosts',
    ];
    
    foreach ($vhost_dirs as $dir) {
        if (is_dir($dir)) {
            $paths[] = $dir . '/access.log';
            $paths[] = $dir . '/logs/access.log';
        }
    }
    
    // Check for logs in subdirectories of current location
    $current_dir = dirname(__FILE__);
    $paths[] = $current_dir . '/../logs/access.log';
    $paths[] = $current_dir . '/../../logs/access.log';
    
    // Glob for any access.log files
    $glob_results = glob('/var/log/*/access.log');
    if ($glob_results) {
        $paths = array_merge($paths, $glob_results);
    }
    
    return array_unique($paths);
}

$ACCESS_LOGS = get_access_log_paths();
$ACCESS_LOG_LINES = 10000; // Number of lines to analyze
// ---------------------

// Helper functions
function human_bytes($bytes, $precision = 2) {
    $units = ['B','KB','MB','GB','TB'];
    $bytes = max(0, (float)$bytes);
    $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    $pow = min($pow, count($units)-1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
function human_rate($bytes_per_sec, $precision=2) {
    $b = human_bytes($bytes_per_sec, $precision) . '/s';
    $mbps = ($bytes_per_sec * 8) / 1000 / 1000;
    return $b . ' (' . round($mbps, $precision) . ' Mbps)';
}

function init_db($db_file) {
    try {
        $db = new PDO('sqlite:' . $db_file);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE IF NOT EXISTS bandwidth_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp REAL NOT NULL,
            rx_bps REAL NOT NULL,
            tx_bps REAL NOT NULL,
            rx_total INTEGER NOT NULL,
            tx_total INTEGER NOT NULL
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_timestamp ON bandwidth_history(timestamp)');
        return $db;
    } catch (PDOException $e) {
        return null;
    }
}

function save_bandwidth_history($db, $timestamp, $rx_bps, $tx_bps, $rx_total, $tx_total) {
    if (!$db) return;
    try {
        $stmt = $db->prepare('INSERT INTO bandwidth_history (timestamp, rx_bps, tx_bps, rx_total, tx_total) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$timestamp, $rx_bps, $tx_bps, $rx_total, $tx_total]);
        
        // Cleanup old data
        global $HISTORY_RETENTION_HOURS;
        $cutoff = $timestamp - ($HISTORY_RETENTION_HOURS * 3600);
        $db->exec("DELETE FROM bandwidth_history WHERE timestamp < $cutoff");
    } catch (PDOException $e) {
        // Silent fail
    }
}

function find_access_log() {
    global $ACCESS_LOGS;
    
    // First try exact paths
    foreach ($ACCESS_LOGS as $log) {
        if (file_exists($log) && is_readable($log) && filesize($log) > 0) {
            return $log;
        }
    }
    
    // Try to find via commands (Ubuntu specific)
    $detected_logs = [];
    
    // Check running web servers
    $apache_check = @shell_exec("ps aux | grep -E 'apache2|httpd' | grep -v grep | head -1");
    $nginx_check = @shell_exec("ps aux | grep nginx | grep -v grep | head -1");
    
    if ($apache_check) {
        // Try to find Apache config and get log location
        $apache_log = @shell_exec("apache2ctl -S 2>/dev/null | grep -i 'access log' | awk '{print $NF}' | head -1");
        if ($apache_log) {
            $apache_log = trim($apache_log);
            if (file_exists($apache_log) && is_readable($apache_log)) {
                return $apache_log;
            }
        }
        
        // Common Apache locations
        $detected_logs[] = '/var/log/apache2/access.log';
        $detected_logs[] = '/var/log/httpd/access_log';
    }
    
    if ($nginx_check) {
        // Try to find Nginx config
        $nginx_log = @shell_exec("nginx -t 2>&1 | grep 'access_log' | awk '{print $2}' | head -1");
        if ($nginx_log) {
            $nginx_log = trim(str_replace(';', '', $nginx_log));
            if (file_exists($nginx_log) && is_readable($nginx_log)) {
                return $nginx_log;
            }
        }
        
        $detected_logs[] = '/var/log/nginx/access.log';
    }
    
    // Try detected logs
    foreach ($detected_logs as $log) {
        if (file_exists($log) && is_readable($log) && filesize($log) > 0) {
            return $log;
        }
    }
    
    // Last resort - find any access.log
    $find_result = @shell_exec("find /var/log -name 'access.log' -type f -readable 2>/dev/null | head -1");
    if ($find_result) {
        $find_result = trim($find_result);
        if (file_exists($find_result) && is_readable($find_result)) {
            return $find_result;
        }
    }
    
    return null;
}

// Cloudflare API functions
function cloudflare_api_request($endpoint, $zone_id, $api_token, $params = []) {
    $url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/{$endpoint}";
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        return json_decode($response, true);
    }
    
    // Return error info for debugging
    return [
        'error' => true,
        'http_code' => $http_code,
        'curl_error' => $curl_error,
        'response' => $response ? substr($response, 0, 500) : 'No response'
    ];
}

function get_cloudflare_analytics($zone_id, $api_token, $since_minutes = 60) {
    // Get analytics for the last N minutes
    $since = date('c', strtotime("-{$since_minutes} minutes"));
    $until = date('c');
    
    $params = [
        'since' => $since,
        'until' => $until,
    ];
    
    // Get analytics overview
    $analytics = cloudflare_api_request('analytics/dashboard', $zone_id, $api_token, $params);
    
    // Check for API errors
    if (isset($analytics['error'])) {
        return [
            'error' => true,
            'message' => 'Cloudflare API Error',
            'details' => $analytics
        ];
    }
    
    // Get top visitor countries
    $countries_params = array_merge($params, ['limit' => 20]);
    
    $result = [
        'visitors' => [],
        'pages' => [],
        'status_codes' => [],
        'methods' => [],
        'total_requests' => 0,
        'unique_visitors' => 0,
        'countries' => [],
        'bandwidth' => 0,
    ];
    
    if ($analytics && isset($analytics['result'])) {
        $data = $analytics['result'];
        
        if (isset($data['totals'])) {
            $result['total_requests'] = $data['totals']['requests']['all'] ?? 0;
            $result['unique_visitors'] = $data['totals']['uniques']['all'] ?? 0;
            $result['bandwidth'] = $data['totals']['bandwidth']['all'] ?? 0;
            
            // Status codes
            if (isset($data['totals']['requests']['http_status'])) {
                foreach ($data['totals']['requests']['http_status'] as $code => $count) {
                    $result['status_codes'][$code] = $count;
                }
            }
            
            // Countries
            if (isset($data['totals']['requests']['country'])) {
                $result['countries'] = $data['totals']['requests']['country'];
            }
        }
        
        // Get top paths/pages from timeseries
        if (isset($data['timeseries']) && is_array($data['timeseries'])) {
            $pages_count = [];
            foreach ($data['timeseries'] as $entry) {
                if (isset($entry['requests']['all'])) {
                    // Aggregate by time period - we'll use this as a proxy
                    $time = $entry['since'] ?? 'unknown';
                    $pages_count[$time] = ($entry['requests']['all'] ?? 0);
                }
            }
            arsort($pages_count);
            $result['pages'] = array_slice($pages_count, 0, 20, true);
        }
    }
    
    // Get firewall events to see visitor IPs
    $firewall = cloudflare_api_request('firewall/events', $zone_id, $api_token, [
        'since' => $since,
        'until' => $until,
        'per_page' => 100
    ]);
    
    if ($firewall && isset($firewall['result']) && is_array($firewall['result'])) {
        $ip_counts = [];
        foreach ($firewall['result'] as $event) {
            $ip = $event['source']['ip'] ?? 'unknown';
            $user_agent = $event['source']['user_agent'] ?? 'Unknown';
            $timestamp = $event['occurred_at'] ?? date('c');
            
            if (!isset($ip_counts[$ip])) {
                $ip_counts[$ip] = [
                    'count' => 0,
                    'user_agent' => $user_agent,
                    'last_access' => $timestamp,
                    'country' => $event['source']['country'] ?? 'Unknown'
                ];
            }
            $ip_counts[$ip]['count']++;
            $ip_counts[$ip]['last_access'] = $timestamp;
        }
        
        uasort($ip_counts, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        $result['visitors'] = $ip_counts;
    }
    
    // Set default HTTP methods (Cloudflare doesn't provide this easily)
    $result['methods'] = ['GET' => $result['total_requests']];
    
    return $result;
}

function parse_access_log($log_file, $lines = 10000) {
    if (!$log_file || !file_exists($log_file)) {
        return [];
    }
    
    // Read last N lines
    global $IS_WINDOWS;
    if ($IS_WINDOWS) {
        // For Windows, read file directly and get last N lines
        $file_content = @file($log_file);
        if (!$file_content) return [];
        $log_lines = array_slice($file_content, -$lines);
        $content = implode("", $log_lines);
    } else {
        $content = shell_exec("tail -n {$lines} " . escapeshellarg($log_file));
    }
    if (!$content) return [];
    
    $log_lines = explode("\n", trim($content));
    $visitors = [];
    $pages = [];
    $status_codes = [];
    $user_agents = [];
    $methods = [];
    
    foreach ($log_lines as $line) {
        if (empty($line)) continue;
        
        // Parse common log formats (Apache/Nginx)
        // Format: IP - - [timestamp] "METHOD /path HTTP/1.1" STATUS SIZE "REFERER" "USER-AGENT"
        if (preg_match('/^([\d\.]+|[\da-f:]+)\s+-\s+-\s+\[([^\]]+)\]\s+"(\w+)\s+([^"\s]+)\s+[^"]+"\s+(\d+)\s+\d+\s+"([^"]*)"\s+"([^"]+)"/', $line, $matches)) {
            $ip = $matches[1];
            $timestamp = $matches[2];
            $method = $matches[3];
            $path = $matches[4];
            $status = $matches[5];
            $referer = $matches[6];
            $user_agent = $matches[7];
            
            // Count visitors by IP
            if (!isset($visitors[$ip])) {
                $visitors[$ip] = ['count' => 0, 'last_access' => $timestamp, 'user_agent' => $user_agent, 'paths' => []];
            }
            $visitors[$ip]['count']++;
            $visitors[$ip]['last_access'] = $timestamp;
            
            // Track pages
            if (!isset($pages[$path])) {
                $pages[$path] = 0;
            }
            $pages[$path]++;
            
            // Track status codes
            if (!isset($status_codes[$status])) {
                $status_codes[$status] = 0;
            }
            $status_codes[$status]++;
            
            // Track methods
            if (!isset($methods[$method])) {
                $methods[$method] = 0;
            }
            $methods[$method]++;
        }
    }
    
    // Sort visitors by count
    uasort($visitors, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Sort pages by visits
    arsort($pages);
    arsort($status_codes);
    arsort($methods);
    
    return [
        'visitors' => $visitors,
        'pages' => $pages,
        'status_codes' => $status_codes,
        'methods' => $methods,
        'total_requests' => count($log_lines),
        'unique_visitors' => count($visitors),
    ];
}

// --- API: web visitors ---
if (isset($_GET['api']) && $_GET['api'] === 'visitors') {
    header('Content-Type: application/json');
    
    global $USE_CLOUDFLARE_ANALYTICS, $CLOUDFLARE_ZONE_ID, $CLOUDFLARE_API_TOKEN;
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : 60;
    
    // Try Cloudflare first if enabled
    if ($USE_CLOUDFLARE_ANALYTICS && !empty($CLOUDFLARE_ZONE_ID) && !empty($CLOUDFLARE_API_TOKEN)) {
        $data = get_cloudflare_analytics($CLOUDFLARE_ZONE_ID, $CLOUDFLARE_API_TOKEN, $minutes);
        
        // Check for API errors
        if (isset($data['error'])) {
            // Log error but continue to fallback
            error_log("Cloudflare API Error: " . json_encode($data));
        } else if ($data && $data['total_requests'] > 0) {
            // Limit top results
            $top_visitors = array_slice($data['visitors'], 0, $limit, true);
            $top_pages = array_slice($data['pages'], 0, $limit, true);
            
            echo json_encode([
                'source' => 'Cloudflare Analytics',
                'log_file' => 'Cloudflare API',
                'top_visitors' => $top_visitors,
                'top_pages' => $top_pages,
                'status_codes' => $data['status_codes'],
                'methods' => $data['methods'],
                'total_requests' => $data['total_requests'],
                'unique_visitors' => $data['unique_visitors'],
                'countries' => $data['countries'],
                'bandwidth_bytes' => $data['bandwidth'],
                'analyzed_lines' => $data['total_requests'],
                'time_range' => "{$minutes} minutes",
            ]);
            exit;
        }
    }
    
    // Fallback to local logs
    $log_file = find_access_log();
    if (!$log_file) {
        // Provide helpful troubleshooting info
        $web_server = 'unknown';
        $apache_running = @shell_exec("ps aux | grep -E 'apache2|httpd' | grep -v grep | wc -l");
        $nginx_running = @shell_exec("ps aux | grep nginx | grep -v grep | wc -l");
        
        if (trim($apache_running) > 0) {
            $web_server = 'Apache (running)';
        } elseif (trim($nginx_running) > 0) {
            $web_server = 'Nginx (running)';
        }
        
        // Try to get more info about Cloudflare status
        $cf_status = 'Disabled';
        $cf_details = [];
        if ($USE_CLOUDFLARE_ANALYTICS) {
            $cf_test = get_cloudflare_analytics($CLOUDFLARE_ZONE_ID, $CLOUDFLARE_API_TOKEN, $minutes);
            if (isset($cf_test['error'])) {
                $cf_status = 'API Error: HTTP ' . ($cf_test['details']['http_code'] ?? 'unknown');
                $cf_details = $cf_test['details'];
            } else {
                $cf_status = 'Enabled but no data available';
            }
        }
        
        $suggestions = [];
        if ($USE_CLOUDFLARE_ANALYTICS) {
            $suggestions[] = "Cloudflare Analytics: {$cf_status}";
            if (isset($cf_details['response'])) {
                $suggestions[] = "API Response: " . substr($cf_details['response'], 0, 200);
            }
        }
        $suggestions[] = "Make sure your web server is logging access requests";
        $suggestions[] = "Check if PHP has permission to read log files (may need to add www-data to adm group)";
        $suggestions[] = "Run: sudo usermod -a -G adm www-data && sudo systemctl restart apache2";
        $suggestions[] = "Or temporarily: sudo chmod 644 /var/log/nginx/access.log or /var/log/apache2/access.log";
        
        echo json_encode([
            'error' => 'No accessible web server access log found',
            'web_server' => $web_server,
            'cloudflare_status' => $cf_status,
            'log_file' => null,
            'checked_paths' => array_values((array)$ACCESS_LOGS),
            'suggestions' => $suggestions,
            'cf_debug' => $cf_details,
            'help' => 'Visit ?api=debug to see detailed system information'
        ]);
        exit;
    }
    
    $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : $ACCESS_LOG_LINES;
    $data = parse_access_log($log_file, $lines);
    
    // Limit top results
    $top_visitors = array_slice($data['visitors'], 0, $limit, true);
    $top_pages = array_slice($data['pages'], 0, $limit, true);
    
    echo json_encode([
        'source' => 'Local Access Logs',
        'log_file' => $log_file,
        'top_visitors' => $top_visitors,
        'top_pages' => $top_pages,
        'status_codes' => $data['status_codes'],
        'methods' => $data['methods'],
        'total_requests' => $data['total_requests'],
        'unique_visitors' => $data['unique_visitors'],
        'analyzed_lines' => $lines,
    ]);
    exit;
}

// --- API: debug ---
if (isset($_GET['api']) && $_GET['api'] === 'debug') {
    header('Content-Type: application/json');
    
    $debug = [
        'os' => PHP_OS,
        'is_windows' => $IS_WINDOWS,
        'php_version' => phpversion(),
        'temp_dir' => sys_get_temp_dir(),
        'current_dir' => getcwd(),
        'script_dir' => dirname(__FILE__),
        'access_logs_checked' => [],
    ];
    
    foreach ($ACCESS_LOGS as $log) {
        $debug['access_logs_checked'][$log] = [
            'exists' => file_exists($log),
            'readable' => is_readable($log),
            'size' => file_exists($log) ? filesize($log) : 0,
        ];
    }
    
    // Try to detect actual log location
    if ($IS_WINDOWS) {
        $debug['xampp_check'] = file_exists('C:/xampp');
        $debug['wamp_check'] = file_exists('C:/wamp64') || file_exists('C:/wamp');
    }
    
    // Test CPU command
    if ($IS_WINDOWS) {
        $debug['cpu_test'] = trim(shell_exec('wmic cpu get loadpercentage 2>nul | findstr /v \"LoadPercentage\"'));
        $debug['mem_test'] = trim(shell_exec('wmic OS get FreePhysicalMemory 2>nul | findstr /v \"Free\"'));
    } else {
        $debug['cpu_test'] = trim(shell_exec("top -bn1 | grep 'Cpu(s)' | head -1"));
        $debug['mem_test'] = trim(shell_exec("free -m | grep Mem"));
    }
    
    echo json_encode($debug, JSON_PRETTY_PRINT);
    exit;
}

// --- API: connections ---
if (isset($_GET['api']) && $_GET['api'] === 'connections') {
    header('Content-Type: application/json');
    
    // Get active connections using ss (faster than netstat)
    global $IS_WINDOWS;
    if ($IS_WINDOWS) {
        $connections_raw = shell_exec('netstat -an 2>nul | findstr ESTABLISHED');
    } else {
        $connections_raw = shell_exec('ss -tunap 2>/dev/null | tail -n +2 || netstat -tunap 2>/dev/null | tail -n +3');
    }
    $connections = [];
    
    if ($connections_raw) {
        $lines = explode("\n", trim($connections_raw));
        foreach ($lines as $line) {
            if (empty($line)) continue;
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 5) {
                $connections[] = [
                    'proto' => $parts[0] ?? '',
                    'local' => $parts[4] ?? '',
                    'remote' => $parts[5] ?? '',
                    'state' => $parts[1] ?? '',
                ];
            }
        }
    }
    
    // Limit to 50 connections to avoid overwhelming the UI
    $connections = array_slice($connections, 0, 50);
    
    echo json_encode([
        'connections' => $connections,
        'count' => count($connections),
    ]);
    exit;
}

// --- API: bandwidth history ---
if (isset($_GET['api']) && $_GET['api'] === 'history') {
    header('Content-Type: application/json');
    
    $minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : 60;
    $db = init_db($DB_FILE);
    
    if (!$db) {
        echo json_encode(['error' => 'Database not available']);
        exit;
    }
    
    $cutoff = microtime(true) - ($minutes * 60);
    $stmt = $db->prepare('SELECT timestamp, rx_bps, tx_bps FROM bandwidth_history WHERE timestamp > ? ORDER BY timestamp ASC');
    $stmt->execute([$cutoff]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['history' => $history, 'count' => count($history)]);
    exit;
}

// --- API: server info ---
if (isset($_GET['api']) && $_GET['api'] === 'server') {
    header('Content-Type: application/json');

    $info = [
        'hostname' => trim(shell_exec('hostname')),
        'os' => trim(shell_exec('lsb_release -d | cut -f2-')),
        'kernel' => trim(shell_exec('uname -r')),
        'uptime' => trim(shell_exec('uptime -p')),
        'time' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
    ];

    // CPU
    global $IS_WINDOWS;
    if ($IS_WINDOWS) {
        $info['cpu_model'] = trim(shell_exec('wmic cpu get name 2>nul | findstr /v "Name"'));
        $info['cpu_cores'] = (int)trim(shell_exec('wmic cpu get NumberOfCores 2>nul | findstr /v "NumberOfCores"'));
        $cpu_usage = trim(shell_exec('wmic cpu get loadpercentage 2>nul | findstr /v "LoadPercentage"'));
        $info['cpu_usage_percent'] = $cpu_usage ? (float)$cpu_usage : null;
        $info['cpu_raw'] = 'Windows CPU: ' . ($cpu_usage ?: 'N/A');
    } else {
        $info['cpu_model'] = trim(shell_exec("awk -F: '/model name/ {print $2; exit}' /proc/cpuinfo"));
        $info['cpu_cores'] = (int)trim(shell_exec("grep -c ^processor /proc/cpuinfo"));
        $cpu_line = trim(shell_exec("top -bn1 | grep 'Cpu(s)'"));
        if ($cpu_line) {
            if (preg_match('/([0-9\\.]+)%id/', $cpu_line, $m)) {
                $idle = (float)$m[1];
                $info['cpu_usage_percent'] = round(100 - $idle, 2);
            } else {
                $info['cpu_usage_percent'] = null;
            }
            $info['cpu_raw'] = $cpu_line;
        }
    }

    // Memory
    if ($IS_WINDOWS) {
        $mem_info = shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /value 2>nul');
        preg_match('/FreePhysicalMemory=(\d+)/', $mem_info, $free_match);
        preg_match('/TotalVisibleMemorySize=(\d+)/', $mem_info, $total_match);
        $mem_total_kb = isset($total_match[1]) ? (int)$total_match[1] : 0;
        $mem_free_kb = isset($free_match[1]) ? (int)$free_match[1] : 0;
        $mem_available_kb = $mem_free_kb;
        $info['memory'] = [
            'total_kb' => $mem_total_kb,
            'free_kb' => $mem_free_kb,
            'available_kb' => $mem_available_kb,
            'used_kb' => max(0, $mem_total_kb - $mem_available_kb),
        ];
    } else {
        $mem_total_kb = (int)trim(shell_exec("grep MemTotal /proc/meminfo | awk '{print $2}'"));
        $mem_free_kb = (int)trim(shell_exec("grep MemFree /proc/meminfo | awk '{print $2}'"));
        $mem_available_kb = (int)trim(shell_exec("grep MemAvailable /proc/meminfo | awk '{print $2}'"));
        $info['memory'] = [
            'total_kb' => $mem_total_kb,
            'free_kb' => $mem_free_kb,
            'available_kb' => $mem_available_kb,
            'used_kb' => max(0, $mem_total_kb - $mem_available_kb),
        ];
    }

    // Disk
    $df = trim(shell_exec('df -h --output=source,size,used,avail,pcent,target / | tail -n1'));
    $info['disk_root'] = $df;

    // Load
    $info['load_average'] = trim(shell_exec("awk '{print $1, $2, $3}' /proc/loadavg"));

    // Interfaces and /proc/net/dev
    $ifaces = [];
    $ip_out = trim(shell_exec('ip -o addr show | awk "{print $2, $4}"'));
    $info['interfaces_raw'] = $ip_out;
    $dev = @file_get_contents('/proc/net/dev');
    if ($dev !== false) {
        $lines = explode("\n", $dev);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                $parts = preg_split('/[:\s]+/', trim($line));
                $iname = $parts[0];
                if ($iname === 'Inter-' || $iname === 'face') continue;
                $rx = isset($parts[1]) ? (int)$parts[1] : 0;
                $tx = isset($parts[9]) ? (int)$parts[9] : 0;
                $ifaces[$iname] = ['rx_bytes' => $rx, 'tx_bytes' => $tx];
            }
        }
    }
    $info['interfaces'] = $ifaces;

    // Bandwidth cap / speed info (ethtool)
    $info['bandwidth_info'] = trim(shell_exec('which ethtool > /dev/null 2>&1 && for i in $(ls /sys/class/net); do echo "$i: $(ethtool $i 2>/dev/null | grep Speed | awk -F: "{print $2}" || true)"; done'));

    // Top CPU processes (array)
    $top_cpu_raw = trim(shell_exec("ps aux --sort=-%cpu | awk 'NR<=6{printf \"%s %s %s%% %s %s\\n\", $1, $2, $3, $4, $11}'"));
    $info['top_processes'] = $top_cpu_raw ? explode("\n", $top_cpu_raw) : [];

    // Services
    $services = ['apache2','nginx','mysql','mariadb','fail2ban','ssh'];
    $svc_status = [];
    foreach ($services as $s) {
        $active = trim(shell_exec("systemctl is-active $s 2>/dev/null"));
        if ($active === '') $active = 'unknown';
        $svc_status[$s] = $active;
    }
    $info['services'] = $svc_status;

    // fail2ban
    $f2b = trim(shell_exec('which fail2ban-client > /dev/null 2>&1 && fail2ban-client status 2>/dev/null || true'));
    $info['fail2ban'] = $f2b ?: 'not installed';

    // MySQL/DB
    $mysql_active = trim(shell_exec('systemctl is-active mysql 2>/dev/null || systemctl is-active mariadb 2>/dev/null'));
    $info['mysql_active'] = $mysql_active ?: 'not installed';
    $info['mysql_version'] = trim(shell_exec('mysql --version 2>/dev/null || mysqld --version 2>/dev/null || true')) ?: 'unavailable';

    // Web servers
    $info['apache_version'] = trim(shell_exec('apache2 -v 2>/dev/null | head -n1')) ?: 'unavailable';
    $info['nginx_version'] = trim(shell_exec('nginx -v 2>&1 | head -n1')) ?: 'unavailable';

    // Docker
    $docker = trim(shell_exec('which docker > /dev/null 2>&1 && docker ps --format "{{.Names}}: {{.Status}}" || true'));
    $info['docker_containers'] = $docker ?: 'docker not installed or none running';

    // Public IP
    $info['public_ip'] = trim(@file_get_contents('http://ifconfig.me') ?: @file_get_contents('https://api.ipify.org') ?: 'unavailable');

    // Speedtest (if available)
    $speedtest = trim(shell_exec('which speedtest > /dev/null 2>&1 && speedtest --format=json 2>/dev/null || which speedtest-cli > /dev/null 2>&1 && speedtest-cli --json 2>/dev/null || true'));
    if ($speedtest) {
        $decoded = @json_decode($speedtest, true);
        $info['speedtest'] = $decoded ?: $speedtest;
    } else {
        $info['speedtest'] = 'not installed';
    }

    // Top memory processes
    $top_mem_raw = trim(shell_exec("ps aux --sort=-%mem | awk 'NR<=6{printf \"%s %s %s%% %s %s\\n\", $1, $2, $4, $11, $12}'"));
    $info['top_mem_processes'] = $top_mem_raw ? explode("\n", $top_mem_raw) : [];

    echo json_encode($info);
    exit;
}

// --- API: bandwidth (existing) ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $now = microtime(true);

    $dev = @file_get_contents('/proc/net/dev');
    if ($dev === false) {
        echo json_encode(['error' => 'Cannot read /proc/net/dev']);
        exit;
    }

    $lines = explode("\n", $dev);
    $rx = $tx = null;
    foreach ($lines as $line) {
        if (strpos($line, $IFACE . ':') !== false) {
            $parts = preg_split('/[:\s]+/', trim($line));
            $rx = (int)$parts[1];
            $tx = (int)$parts[9];
            break;
        }
    }

    if ($rx === null) {
        echo json_encode(['error' => "Interface {$IFACE} not found in /proc/net/dev"]);
        exit;
    }

    $prev = null;
    if (file_exists($PERSIST_FILE)) {
        $prev = json_decode(@file_get_contents($PERSIST_FILE), true);
    }

    $resp = [
        'iface' => $IFACE,
        'timestamp' => $now,
        'rx_total_bytes' => $rx,
        'tx_total_bytes' => $tx,
        'rx_total_human' => human_bytes($rx, $HUMAN_PRECISION),
        'tx_total_human' => human_bytes($tx, $HUMAN_PRECISION),
    ];

    if ($prev && isset($prev['timestamp']) && isset($prev['rx']) && isset($prev['tx'])) {
        $dt = max(0.0001, $now - (float)$prev['timestamp']);
        $rx_delta = $rx - (int)$prev['rx'];
        $tx_delta = $tx - (int)$prev['tx'];

        $rx_bps = $rx_delta / $dt;
        $tx_bps = $tx_delta / $dt;

        $resp['rx_bps'] = $rx_bps;
        $resp['tx_bps'] = $tx_bps;
        $resp['rx_rate_human'] = human_rate($rx_bps, $HUMAN_PRECISION);
        $resp['tx_rate_human'] = human_rate($tx_bps, $HUMAN_PRECISION);
    } else {
        $resp['rx_bps'] = 0;
        $resp['tx_bps'] = 0;
        $resp['rx_rate_human'] = '0 B/s';
        $resp['tx_rate_human'] = '0 B/s';
    }

    @file_put_contents($PERSIST_FILE, json_encode(['timestamp' => $now, 'rx' => $rx, 'tx' => $tx]));
    
    // Save to history database
    $db = init_db($DB_FILE);
    if ($db && isset($resp['rx_bps']) && isset($resp['tx_bps'])) {
        save_bandwidth_history($db, $now, $resp['rx_bps'], $resp['tx_bps'], $rx, $tx);
    }
    
    // Add CPU and Memory percentages
    global $IS_WINDOWS;
    
    // Set defaults
    $resp['cpu_percent'] = 0;
    $resp['memory_percent'] = 0;
    $resp['memory_used_mb'] = 0;
    $resp['memory_total_mb'] = 0;
    
    if ($IS_WINDOWS) {
        // Windows CPU - try multiple methods
        $cpu_usage = null;
        
        // Method 1: TypePerf (most reliable)
        $typeperf = @shell_exec('typeperf "\\Processor(_Total)\\% Processor Time" -sc 1 2>nul');
        if ($typeperf && preg_match('/"([0-9.]+)"/', $typeperf, $matches)) {
            $cpu_usage = round((float)$matches[1], 2);
        }
        
        // Method 2: WMIC (fallback)
        if ($cpu_usage === null) {
            $wmic_output = @shell_exec('wmic cpu get loadpercentage 2>nul');
            if ($wmic_output) {
                $lines = explode("\n", trim($wmic_output));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (is_numeric($line) && $line >= 0 && $line <= 100) {
                        $cpu_usage = (float)$line;
                        break;
                    }
                }
            }
        }
        
        // Method 3: PowerShell (most accurate but slower)
        if ($cpu_usage === null) {
            $ps_cmd = 'powershell -Command "Get-WmiObject Win32_Processor | Select-Object -ExpandProperty LoadPercentage" 2>nul';
            $ps_output = @shell_exec($ps_cmd);
            if ($ps_output && is_numeric(trim($ps_output))) {
                $cpu_usage = (float)trim($ps_output);
            }
        }
        
        if ($cpu_usage !== null) {
            $resp['cpu_percent'] = $cpu_usage;
        }
        
        // Windows Memory
        $mem_info = @shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /value 2>nul');
        if ($mem_info) {
            preg_match('/FreePhysicalMemory=(\d+)/', $mem_info, $free_match);
            preg_match('/TotalVisibleMemorySize=(\d+)/', $mem_info, $total_match);
            if (isset($total_match[1]) && isset($free_match[1])) {
                $mem_total_kb = (int)$total_match[1];
                $mem_free_kb = (int)$free_match[1];
                $mem_used_kb = $mem_total_kb - $mem_free_kb;
                if ($mem_total_kb > 0) {
                    $resp['memory_percent'] = round(($mem_used_kb / $mem_total_kb) * 100, 2);
                    $resp['memory_used_mb'] = round($mem_used_kb / 1024, 2);
                    $resp['memory_total_mb'] = round($mem_total_kb / 1024, 2);
                }
            }
        }
    } else {
        // Linux CPU - try multiple methods
        $cpu_percent = null;
        
        // Method 1: top command
        $cpu_line = @shell_exec("top -bn1 | grep 'Cpu(s)' 2>/dev/null");
        if ($cpu_line) {
            // Match different top output formats
            if (preg_match('/([0-9.]+)\s*%?\s*id/', $cpu_line, $m)) {
                $idle = (float)$m[1];
                $cpu_percent = round(100 - $idle, 2);
            } elseif (preg_match('/([0-9.]+)\s*%?\s*us/', $cpu_line, $m)) {
                // Some systems show user time directly
                $cpu_percent = round((float)$m[1], 2);
            }
        }
        
        // Method 2: mpstat (more accurate if available)
        if ($cpu_percent === null) {
            $mpstat = @shell_exec("mpstat 1 1 2>/dev/null | grep Average | awk '{print 100-\$NF}'");
            if ($mpstat && is_numeric(trim($mpstat))) {
                $cpu_percent = round((float)trim($mpstat), 2);
            }
        }
        
        // Method 3: /proc/stat (fallback)
        if ($cpu_percent === null) {
            $stat1 = @file_get_contents('/proc/stat');
            if ($stat1) {
                usleep(100000); // 0.1 second
                $stat2 = @file_get_contents('/proc/stat');
                if ($stat2) {
                    preg_match('/cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat1, $m1);
                    preg_match('/cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat2, $m2);
                    if (!empty($m1) && !empty($m2)) {
                        $idle1 = $m1[4];
                        $idle2 = $m2[4];
                        $total1 = array_sum(array_slice($m1, 1, 4));
                        $total2 = array_sum(array_slice($m2, 1, 4));
                        $total_diff = $total2 - $total1;
                        $idle_diff = $idle2 - $idle1;
                        if ($total_diff > 0) {
                            $cpu_percent = round(100 * ($total_diff - $idle_diff) / $total_diff, 2);
                        }
                    }
                }
            }
        }
        
        if ($cpu_percent !== null && $cpu_percent >= 0 && $cpu_percent <= 100) {
            $resp['cpu_percent'] = $cpu_percent;
        }
        
        // Linux Memory
        $mem_total_kb = (int)@trim(shell_exec("grep MemTotal /proc/meminfo | awk '{print \$2}' 2>/dev/null"));
        $mem_available_kb = (int)@trim(shell_exec("grep MemAvailable /proc/meminfo | awk '{print \$2}' 2>/dev/null"));
        
        // Fallback if MemAvailable not found (older kernels)
        if ($mem_total_kb > 0 && $mem_available_kb == 0) {
            $mem_free_kb = (int)@trim(shell_exec("grep MemFree /proc/meminfo | awk '{print \$2}' 2>/dev/null"));
            $mem_cached_kb = (int)@trim(shell_exec("grep -E '^Cached:' /proc/meminfo | awk '{print \$2}' 2>/dev/null"));
            $mem_buffers_kb = (int)@trim(shell_exec("grep Buffers /proc/meminfo | awk '{print \$2}' 2>/dev/null"));
            $mem_available_kb = $mem_free_kb + $mem_cached_kb + $mem_buffers_kb;
        }
        
        if ($mem_total_kb > 0) {
            $mem_used_kb = $mem_total_kb - $mem_available_kb;
            $resp['memory_percent'] = round(($mem_used_kb / $mem_total_kb) * 100, 2);
            $resp['memory_used_mb'] = round($mem_used_kb / 1024, 2);
            $resp['memory_total_mb'] = round($mem_total_kb / 1024, 2);
        }
    }

    echo json_encode($resp);
    exit;
}

// --- HTML Dashboard ---
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Server & Bandwidth Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
    }
  </script>
  <style>
    @keyframes pulse-slow {
      0%, 100% { opacity: 1; }
      50% { opacity: .5; }
    }
    .pulse-slow { animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    .gradient-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .gradient-blue {
      background: linear-gradient(135deg, #667eea 0%, #4f46e5 100%);
    }
    .gradient-green {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    .gradient-purple {
      background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    }
    .gradient-orange {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
  </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 min-h-screen transition-colors duration-300">
  
  <!-- Header -->
  <div class="gradient-bg shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white flex items-center gap-3">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
            Server Dashboard
          </h1>
          <p class="text-indigo-100 text-sm mt-1">Real-time monitoring and analytics</p>
        </div>
        <div class="flex items-center gap-4">
          <button onclick="toggleDarkMode()" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 transition-colors" title="Toggle Dark Mode">
            <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-white hidden" fill="currentColor" viewBox="0 0 20 20">
              <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
            </svg>
            <svg id="theme-toggle-light-icon" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
            </svg>
          </button>
          <div class="text-right">
            <div class="text-white text-sm opacity-90" id="last">Loading...</div>
            <div class="text-indigo-100 text-xs mt-1">Interface: <span class="font-semibold"><?php echo htmlspecialchars($IFACE); ?></span></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- System Resources Progress Bars -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wide flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            CPU Usage
          </h3>
          <span id="cpu-percent" class="text-lg font-bold text-slate-800 dark:text-slate-100">--</span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-4 overflow-hidden">
          <div id="cpu-bar" class="h-4 rounded-full transition-all duration-500 ease-out" style="width: 0%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);"></div>
        </div>
      </div>
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wide flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
            Memory Usage
          </h3>
          <span id="memory-percent" class="text-lg font-bold text-slate-800 dark:text-slate-100">--</span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-4 overflow-hidden">
          <div id="memory-bar" class="h-4 rounded-full transition-all duration-500 ease-out" style="width: 0%; background: linear-gradient(90deg, #10b981 0%, #059669 100%);"></div>
        </div>
        <div id="memory-detail" class="text-xs text-slate-500 dark:text-slate-400 mt-2">-- MB / -- MB</div>
      </div>
    </div>

    <!-- Bandwidth Real-time Chart -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
      <h2 class="text-lg font-bold text-slate-700 dark:text-slate-200 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
        Real-time Bandwidth
      </h2>
      <canvas id="bandwidthChart" height="80"></canvas>
    </div>

    <!-- Bandwidth Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <!-- Download Card -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 border border-slate-200 dark:border-slate-700 hover:shadow-2xl transition-shadow duration-300">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="gradient-blue p-3 rounded-xl">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Download</h3>
              <p class="text-xs text-slate-400 dark:text-slate-500">Incoming Traffic (RX)</p>
            </div>
          </div>
        </div>
        <div class="space-y-2">
          <div id="rx_rate" class="text-4xl font-bold text-slate-800 dark:text-slate-100 pulse-slow">-- B/s</div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 dark:text-slate-400">Total:</span>
            <span id="rx_total" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">-- B</span>
          </div>
        </div>
      </div>

      <!-- Upload Card -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 border border-slate-200 dark:border-slate-700 hover:shadow-2xl transition-shadow duration-300">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="gradient-green p-3 rounded-xl">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">Upload</h3>
              <p class="text-xs text-slate-400 dark:text-slate-500">Outgoing Traffic (TX)</p>
            </div>
          </div>
        </div>
        <div class="space-y-2">
          <div id="tx_rate" class="text-4xl font-bold text-slate-800 dark:text-slate-100 pulse-slow">-- B/s</div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 dark:text-slate-400">Total:</span>
            <span id="tx_total" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">-- B</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Connections -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 mb-8">
      <div class="gradient-blue p-4 rounded-t-2xl flex items-center justify-between">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          Active Connections
        </h2>
        <span id="conn-count" class="px-3 py-1 bg-white/20 rounded-full text-sm font-semibold">0</span>
      </div>
      <div id="connections" class="p-6 max-h-96 overflow-y-auto">
        <div class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
      </div>
    </div>

    <!-- Server Info & Processes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Server Details -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700">
        <div class="gradient-purple p-4 rounded-t-2xl">
          <h2 class="text-xl font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
            Server Details
          </h2>
        </div>
        <div id="server_summary" class="p-6">
          <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
          </div>
        </div>
      </div>

      <!-- Processes & Services -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700">
        <div class="gradient-orange p-4 rounded-t-2xl">
          <h2 class="text-xl font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Processes & Services
          </h2>
        </div>
        <div id="processes" class="p-6">
          <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Raw JSON (Collapsible) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
      <button onclick="toggleRaw()" class="w-full p-4 flex items-center justify-between bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors">
        <h2 class="text-lg font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
          </svg>
          Raw JSON Data
        </h2>
        <svg id="raw-icon" class="w-5 h-5 text-slate-600 dark:text-slate-300 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      <div id="raw-container" class="hidden">
        <pre id="raw" class="p-6 text-xs bg-slate-900 text-green-400 overflow-x-auto">Loading...</pre>
      </div>
    </div>

  </div>

<script>
const bwApi = window.location.pathname + '?api=1';
const serverApi = window.location.pathname + '?api=server';
const connectionsApi = window.location.pathname + '?api=connections';
const historyApi = window.location.pathname + '?api=history';
const visitorsApi = window.location.pathname + '?api=visitors';

// Dark mode toggle
function toggleDarkMode() {
  const html = document.documentElement;
  const isDark = html.classList.toggle('dark');
  localStorage.setItem('darkMode', isDark ? 'true' : 'false');
  
  // Toggle icons
  document.getElementById('theme-toggle-dark-icon').classList.toggle('hidden');
  document.getElementById('theme-toggle-light-icon').classList.toggle('hidden');
  
  // Update chart theme
  if (window.bandwidthChart) {
    updateChartTheme(isDark);
  }
}

// Initialize dark mode from localStorage
function initDarkMode() {
  const darkMode = localStorage.getItem('darkMode');
  if (darkMode === 'true' || (!darkMode && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
    document.getElementById('theme-toggle-dark-icon').classList.remove('hidden');
    document.getElementById('theme-toggle-light-icon').classList.add('hidden');
  }
}

function toggleRaw() {
  const container = document.getElementById('raw-container');
  const icon = document.getElementById('raw-icon');
  container.classList.toggle('hidden');
  icon.classList.toggle('rotate-180');
}

// Chart setup
let bandwidthChart = null;
const chartData = {
  labels: [],
  rx: [],
  tx: []
};
const MAX_DATA_POINTS = 60;

function initChart() {
  const ctx = document.getElementById('bandwidthChart').getContext('2d');
  const isDark = document.documentElement.classList.contains('dark');
  
  bandwidthChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartData.labels,
      datasets: [{
        label: 'Download (Mbps)',
        data: chartData.rx,
        borderColor: 'rgb(102, 126, 234)',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        tension: 0.4,
        fill: true
      }, {
        label: 'Upload (Mbps)',
        data: chartData.tx,
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          labels: {
            color: isDark ? '#e2e8f0' : '#334155'
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            color: isDark ? '#94a3b8' : '#64748b'
          },
          grid: {
            color: isDark ? '#334155' : '#e2e8f0'
          }
        },
        x: {
          ticks: {
            color: isDark ? '#94a3b8' : '#64748b',
            maxTicksLimit: 10
          },
          grid: {
            color: isDark ? '#334155' : '#e2e8f0'
          }
        }
      }
    }
  });
  
  window.bandwidthChart = bandwidthChart;
}

function updateChartTheme(isDark) {
  if (!bandwidthChart) return;
  
  bandwidthChart.options.plugins.legend.labels.color = isDark ? '#e2e8f0' : '#334155';
  bandwidthChart.options.scales.y.ticks.color = isDark ? '#94a3b8' : '#64748b';
  bandwidthChart.options.scales.y.grid.color = isDark ? '#334155' : '#e2e8f0';
  bandwidthChart.options.scales.x.ticks.color = isDark ? '#94a3b8' : '#64748b';
  bandwidthChart.options.scales.x.grid.color = isDark ? '#334155' : '#e2e8f0';
  bandwidthChart.update();
}

function updateChart(rxMbps, txMbps) {
  const now = new Date();
  const timeLabel = now.toLocaleTimeString();
  
  chartData.labels.push(timeLabel);
  chartData.rx.push(rxMbps);
  chartData.tx.push(txMbps);
  
  // Keep only last MAX_DATA_POINTS
  if (chartData.labels.length > MAX_DATA_POINTS) {
    chartData.labels.shift();
    chartData.rx.shift();
    chartData.tx.shift();
  }
  
  if (bandwidthChart) {
    bandwidthChart.update('none'); // No animation for smoother updates
  }
}

async function fetchStats(){
  try{
    const res = await fetch(bwApi,{cache:'no-store'});
    const j = await res.json();
    if(j.error){ document.getElementById('raw').textContent = JSON.stringify(j,null,2); return; }
    const now = new Date();
    document.getElementById('last').textContent = now.toLocaleString();
    document.getElementById('rx_rate').textContent = j.rx_rate_human;
    document.getElementById('tx_rate').textContent = j.tx_rate_human;
    document.getElementById('rx_total').textContent = j.rx_total_human;
    document.getElementById('tx_total').textContent = j.tx_total_human;
    
    // Update progress bars
    if (j.cpu_percent !== undefined) {
      document.getElementById('cpu-percent').textContent = j.cpu_percent + '%';
      document.getElementById('cpu-bar').style.width = j.cpu_percent + '%';
    }
    if (j.memory_percent !== undefined) {
      document.getElementById('memory-percent').textContent = j.memory_percent + '%';
      document.getElementById('memory-bar').style.width = j.memory_percent + '%';
      document.getElementById('memory-detail').textContent = j.memory_used_mb + ' MB / ' + j.memory_total_mb + ' MB';
    }
    
    // Update chart
    const rxMbps = (j.rx_bps * 8) / 1000000;
    const txMbps = (j.tx_bps * 8) / 1000000;
    updateChart(rxMbps, txMbps);
    
    document.getElementById('raw').textContent = JSON.stringify(j, null, 2);
  }catch(e){ document.getElementById('raw').textContent = 'Fetch error: '+e }
}

async function fetchServer(){
  try{
    const res = await fetch(serverApi,{cache:'no-store'});
    const j = await res.json();
    
    // Server summary with Tailwind styling
    const summary = [];
    summary.push('<div class="space-y-3">');
    
    const items = [
      {icon: '🖥️', label: 'Hostname', value: j.hostname},
      {icon: '💿', label: 'OS', value: j.os},
      {icon: '⚙️', label: 'Kernel', value: j.kernel},
      {icon: '🕐', label: 'Time', value: j.time},
      {icon: '⏱️', label: 'Uptime', value: j.uptime},
      {icon: '🔧', label: 'CPU', value: j.cpu_model + ' (' + j.cpu_cores + ' cores) - ' + (j.cpu_usage_percent !== null ? j.cpu_usage_percent + '%' : 'n/a')},
      {icon: '💾', label: 'Memory', value: j.memory ? ((j.memory.used_kb/1024).toFixed(2) + ' MB / ' + (j.memory.total_kb/1024).toFixed(2) + ' MB') : 'n/a'},
      {icon: '💽', label: 'Disk', value: j.disk_root},
      {icon: '🌐', label: 'Public IP', value: j.public_ip},
      {icon: '🐘', label: 'PHP', value: j.php_version}
    ];
    
    items.forEach(item => {
      summary.push('<div class="flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">');
      summary.push('<span class="text-2xl">' + item.icon + '</span>');
      summary.push('<div class="flex-1 min-w-0">');
      summary.push('<div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">' + item.label + '</div>');
      summary.push('<div class="text-sm text-slate-800 dark:text-slate-200 mt-1 break-words">' + item.value + '</div>');
      summary.push('</div></div>');
    });
    
    summary.push('</div>');
    document.getElementById('server_summary').innerHTML = summary.join('');

    // Processes & services with Tailwind styling
    let proc = '<div class="space-y-4">';
    
    // Top CPU
    proc += '<div><h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2">';
    proc += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>';
    proc += 'Top CPU Processes</h4>';
    proc += '<div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-3 space-y-1">';
    if(j.top_processes && j.top_processes.length){
      j.top_processes.forEach(p => {
        proc += '<div class="text-xs font-mono text-slate-700 dark:text-slate-300 truncate">' + p + '</div>';
      });
    }
    proc += '</div></div>';
    
    // Top Memory
    proc += '<div><h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2">';
    proc += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>';
    proc += 'Top Memory Processes</h4>';
    proc += '<div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-3 space-y-1">';
    if(j.top_mem_processes && j.top_mem_processes.length){
      j.top_mem_processes.forEach(p => {
        proc += '<div class="text-xs font-mono text-slate-700 dark:text-slate-300 truncate">' + p + '</div>';
      });
    }
    proc += '</div></div>';
    
    // Services
    proc += '<div><h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2">';
    proc += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>';
    proc += 'Services Status</h4>';
    proc += '<div class="grid grid-cols-2 gap-2">';
    for(const s in j.services){
      const status = j.services[s];
      const isActive = status === 'active';
      proc += '<div class="flex items-center justify-between p-2 rounded-lg ' + (isActive ? 'bg-green-50 dark:bg-green-900/30' : 'bg-red-50 dark:bg-red-900/30') + '">';
      proc += '<span class="text-xs font-medium text-slate-700 dark:text-slate-300">' + s + '</span>';
      proc += '<span class="px-2 py-1 text-xs font-semibold rounded-full ' + (isActive ? 'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100') + '">' + status + '</span>';
      proc += '</div>';
    }
    proc += '</div></div>';
    
    proc += '</div>';
    document.getElementById('processes').innerHTML = proc;

    document.getElementById('raw').textContent = JSON.stringify(j,null,2);
  }catch(e){ 
    document.getElementById('server_summary').innerHTML = '<div class="text-red-600 p-4">Error: ' + e + '</div>';
  }
}

async function fetchConnections(){
  try{
    const res = await fetch(connectionsApi,{cache:'no-store'});
    const j = await res.json();
    
    document.getElementById('conn-count').textContent = j.count;
    
    if (!j.connections || j.connections.length === 0) {
      document.getElementById('connections').innerHTML = '<div class="text-slate-500 dark:text-slate-400 text-center py-8">No active connections</div>';
      return;
    }
    
    let html = '<div class="overflow-x-auto">';
    html += '<table class="w-full text-sm">';
    html += '<thead class="bg-slate-50 dark:bg-slate-700"><tr>';
    html += '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Protocol</th>';
    html += '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Local Address</th>';
    html += '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Remote Address</th>';
    html += '<th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">State</th>';
    html += '</tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-600">';
    
    j.connections.forEach(conn => {
      html += '<tr class="hover:bg-slate-50 dark:hover:bg-slate-700">';
      html += '<td class="px-4 py-2 text-slate-700 dark:text-slate-300 font-mono">' + conn.proto + '</td>';
      html += '<td class="px-4 py-2 text-slate-700 dark:text-slate-300 font-mono text-xs">' + conn.local + '</td>';
      html += '<td class="px-4 py-2 text-slate-700 dark:text-slate-300 font-mono text-xs">' + conn.remote + '</td>';
      html += '<td class="px-4 py-2"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">' + conn.state + '</span></td>';
      html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    document.getElementById('connections').innerHTML = html;
  }catch(e){
    document.getElementById('connections').innerHTML = '<div class="text-red-600 dark:text-red-400 p-4">Error: ' + e + '</div>';
  }
}

async function fetchVisitors(){
  try{
    const res = await fetch(visitorsApi + '&limit=20&lines=10000',{cache:'no-store'});
    const j = await res.json();
    
    if (j.error) {
      let errorHtml = '<div class="p-4 space-y-3">';
      errorHtml += '<div class="flex items-start gap-2">';
      errorHtml += '<svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
      errorHtml += '<div class="flex-1">';
      errorHtml += '<div class="text-amber-600 dark:text-amber-400 font-semibold text-sm">Access Log Not Found</div>';
      errorHtml += '<div class="text-slate-600 dark:text-slate-400 text-xs mt-1">' + (j.web_server || 'Web server status unknown') + '</div>';
      errorHtml += '</div></div>';
      
      if (j.suggestions && j.suggestions.length > 0) {
        errorHtml += '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-xs">';
        errorHtml += '<div class="font-semibold text-blue-800 dark:text-blue-300 mb-2">💡 Quick Fix:</div>';
        errorHtml += '<ol class="list-decimal list-inside space-y-1 text-slate-700 dark:text-slate-300">';
        j.suggestions.forEach(s => {
          errorHtml += '<li>' + s + '</li>';
        });
        errorHtml += '</ol></div>';
      }
      
      if (j.checked_paths) {
        errorHtml += '<details class="text-xs"><summary class="cursor-pointer text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">Show checked paths</summary>';
        errorHtml += '<div class="mt-2 bg-slate-100 dark:bg-slate-700 rounded p-2 font-mono text-xs max-h-32 overflow-y-auto">';
        errorHtml += (Array.isArray(j.checked_paths) ? j.checked_paths.join('<br>') : j.checked_paths);
        errorHtml += '</div></details>';
      }
      errorHtml += '</div>';
      
      document.getElementById('top-visitors').innerHTML = errorHtml;
      document.getElementById('top-pages').innerHTML = '<div class="text-amber-600 dark:text-amber-400 p-4 text-sm text-center">⚠️ No access log available</div>';
      return;
    }
    
    // Update summary stats
    document.getElementById('unique-visitors').textContent = j.unique_visitors;
    document.getElementById('total-requests').textContent = j.total_requests;
    document.getElementById('analyzed-lines').textContent = j.analyzed_lines.toLocaleString();
    
    // Show data source with icon
    const sourceEl = document.getElementById('log-source');
    if (j.source && j.source.includes('Cloudflare')) {
      sourceEl.innerHTML = '<span class="inline-flex items-center gap-1"><svg class="w-3 h-3 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>Cloudflare</span>';
      sourceEl.title = 'Data from Cloudflare Analytics API';
    } else if (j.log_file) {
      sourceEl.textContent = j.log_file.split('/').pop();
      sourceEl.title = j.log_file;
    } else {
      sourceEl.textContent = '--';
    }
    
    // Top Visitors
    if (!j.top_visitors || Object.keys(j.top_visitors).length === 0) {
      document.getElementById('top-visitors').innerHTML = '<div class="text-slate-500 dark:text-slate-400 text-center py-8">No visitor data</div>';
    } else {
      let html = '<div class="space-y-2">';
      let rank = 1;
      for (const [ip, data] of Object.entries(j.top_visitors)) {
        const percentage = ((data.count / j.total_requests) * 100).toFixed(1);
        html += '<div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors">';
        html += '<div class="flex items-center gap-3 flex-1 min-w-0">';
        html += '<span class="text-lg font-bold text-slate-400 dark:text-slate-500 w-6">' + rank + '</span>';
        html += '<div class="flex-1 min-w-0">';
        html += '<div class="flex items-center gap-2">';
        html += '<span class="font-mono text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">' + ip + '</span>';
        if (data.country && data.country !== 'XX') {
          html += '<span class="px-2 py-0.5 text-xs font-semibold rounded bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">' + data.country + '</span>';
        }
        html += '</div>';
        html += '<div class="text-xs text-slate-500 dark:text-slate-400 truncate" title="' + data.user_agent + '">' + data.user_agent.substring(0, 50) + (data.user_agent.length > 50 ? '...' : '') + '</div>';
        html += '<div class="text-xs text-slate-400 dark:text-slate-500 mt-1">Last: ' + data.last_access + '</div>';
        html += '</div></div>';
        html += '<div class="text-right ml-3">';
        html += '<div class="text-lg font-bold text-orange-600 dark:text-orange-400">' + data.count + '</div>';
        html += '<div class="text-xs text-slate-500 dark:text-slate-400">' + percentage + '%</div>';
        html += '</div></div>';
        rank++;
      }
      html += '</div>';
      document.getElementById('top-visitors').innerHTML = html;
    }
    
    // Top Pages
    if (!j.top_pages || Object.keys(j.top_pages).length === 0) {
      document.getElementById('top-pages').innerHTML = '<div class="text-slate-500 dark:text-slate-400 text-center py-8">No page data</div>';
    } else {
      let html = '<div class="space-y-2">';
      let rank = 1;
      for (const [path, count] of Object.entries(j.top_pages)) {
        const percentage = ((count / j.total_requests) * 100).toFixed(1);
        html += '<div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors">';
        html += '<div class="flex items-center gap-3 flex-1 min-w-0">';
        html += '<span class="text-lg font-bold text-slate-400 dark:text-slate-500 w-6">' + rank + '</span>';
        html += '<div class="flex-1 min-w-0">';
        html += '<div class="font-mono text-sm font-semibold text-slate-800 dark:text-slate-100 truncate" title="' + path + '">' + path + '</div>';
        html += '<div class="text-xs text-slate-500 dark:text-slate-400">' + percentage + '% of total requests</div>';
        html += '</div></div>';
        html += '<div class="text-right ml-3">';
        html += '<div class="text-lg font-bold text-purple-600 dark:text-purple-400">' + count + '</div>';
        html += '<div class="text-xs text-slate-500 dark:text-slate-400">visits</div>';
        html += '</div></div>';
        rank++;
      }
      html += '</div>';
      document.getElementById('top-pages').innerHTML = html;
    }
    
    // HTTP Methods
    let methodsHtml = '';
    for (const [method, count] of Object.entries(j.methods)) {
      methodsHtml += '<div class="flex justify-between items-center"><span class="font-mono text-slate-700 dark:text-slate-300">' + method + '</span><span class="font-bold text-slate-800 dark:text-slate-100">' + count + '</span></div>';
    }
    document.getElementById('methods-stats').innerHTML = methodsHtml || 'No data';
    
    // Status Codes
    let statusHtml = '';
    for (const [code, count] of Object.entries(j.status_codes)) {
      const statusClass = code.startsWith('2') ? 'text-green-600 dark:text-green-400' : (code.startsWith('4') || code.startsWith('5') ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300');
      statusHtml += '<div class="flex justify-between items-center"><span class="font-mono ' + statusClass + '">' + code + '</span><span class="font-bold text-slate-800 dark:text-slate-100">' + count + '</span></div>';
    }
    document.getElementById('status-stats').innerHTML = statusHtml || 'No data';
    
    // Countries (from Cloudflare)
    if (j.countries && Object.keys(j.countries).length > 0) {
      let countriesHtml = '';
      const sortedCountries = Object.entries(j.countries).sort((a, b) => b[1] - a[1]).slice(0, 5);
      for (const [country, count] of sortedCountries) {
        countriesHtml += '<div class="flex justify-between items-center"><span class="font-semibold text-slate-700 dark:text-slate-300">' + country + '</span><span class="font-bold text-slate-800 dark:text-slate-100">' + count + '</span></div>';
      }
      document.getElementById('countries-stats').innerHTML = countriesHtml;
    } else {
      document.getElementById('countries-stats').innerHTML = '<div class="text-slate-500 dark:text-slate-400 text-xs">N/A</div>';
    }
    
  }catch(e){
    document.getElementById('top-visitors').innerHTML = '<div class="text-red-600 dark:text-red-400 p-4">Error: ' + e + '</div>';
    document.getElementById('top-pages').innerHTML = '<div class="text-red-600 dark:text-red-400 p-4">Error: ' + e + '</div>';
  }
}

// Initialize
initDarkMode();
initChart();

// Initial fetch
fetchStats();
fetchServer();
fetchConnections();
fetchVisitors();

// Polling
setInterval(fetchStats, 3000);
setInterval(fetchServer, 5000);
setInterval(fetchConnections, 10000);
setInterval(fetchVisitors, 15000); // Update visitors every 15 seconds
</script>
</body>
</html>
