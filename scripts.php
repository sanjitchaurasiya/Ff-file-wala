<?php
// © 2025 Developed by t.me/SANJIT_CHAURASIYA
//Do not Sell This Code 
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Unauthorized access!");
}
?>
<?php
$uploadDir = __DIR__ . '/uploads/';
$logDir = __DIR__ . '/logs/';

// ✅ Ensure directories exist
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($logDir)) mkdir($logDir, 0777, true);

$action = $_GET['action'] ?? '';
$file = $_GET['file'] ?? '';

if (!$file || !file_exists($uploadDir . $file)) {
    die("File not found!");
}

$logFile = $logDir . $file . '.log';
$pidFile = $uploadDir . $file . '.pid';

function isRunning($pidFile) {
    if (file_exists($pidFile)) {
        $pid = file_get_contents($pidFile);
        $output = shell_exec("ps -p $pid 2>/dev/null");
        return strpos($output, $pid) !== false;
    }
    return false;
}

// ✅ Automatically install missing Python modules
function autoInstallModules($pythonFile, $logFile) {
    $content = file_get_contents($pythonFile);
    preg_match_all('/^import (\S+)|^from (\S+) import/m', $content, $matches);
    $modules = array_unique(array_filter(array_merge($matches[1], $matches[2])));

    foreach ($modules as $mod) {
        $checkCmd = "python3 -c \"import $mod\" 2>/dev/null";
        exec($checkCmd, $output, $return_var);
        if ($return_var !== 0) {
            file_put_contents($logFile, "\nInstalling missing module: $mod\n", FILE_APPEND);
            $installCmd = "pip3 install " . escapeshellarg($mod) . " >> " . escapeshellarg($logFile) . " 2>&1";
            shell_exec($installCmd);
            file_put_contents($logFile, "\nInstalled module: $mod\n", FILE_APPEND);
        }
    }
}

switch ($action) {
    case 'start':
        if (isRunning($pidFile)) {
            echo "Script is already running!";
            exit;
        }
        autoInstallModules($uploadDir . $file, $logFile);
        $cmd = "nohup python3 " . escapeshellarg($uploadDir . $file) . " > " . escapeshellarg($logFile) . " 2>&1 & echo $! > " . escapeshellarg($pidFile);
        shell_exec($cmd);
        echo "Script started!";
        break;

    case 'stop':
        if (isRunning($pidFile)) {
            $pid = file_get_contents($pidFile);
            shell_exec("kill $pid");
            unlink($pidFile);
            echo "Script stopped!";
        } else {
            echo "Script is not running!";
        }
        break;

    case 'restart':
        if (isRunning($pidFile)) {
            $pid = file_get_contents($pidFile);
            shell_exec("kill $pid");
            unlink($pidFile);
        }
        autoInstallModules($uploadDir . $file, $logFile);
        $cmd = "nohup python3 " . escapeshellarg($uploadDir . $file) . " > " . escapeshellarg($logFile) . " 2>&1 & echo $! > " . escapeshellarg($pidFile);
        shell_exec($cmd);
        echo "Script restarted!";
        break;

    case 'delete':
        if (isRunning($pidFile)) {
            $pid = file_get_contents($pidFile);
            shell_exec("kill $pid");
            unlink($pidFile);
        }
        if (file_exists($uploadDir . $file)) unlink($uploadDir . $file);
        if (file_exists($logFile)) unlink($logFile);
        echo "File deleted!";
        break;

    case 'logs':
        if (file_exists($logFile)) {
            echo "<pre style='margin:0'>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
        } else {
            echo "<pre style='margin:0'>No logs available.</pre>";
        }
        break;

    case 'status':
        echo isRunning($pidFile) ? 'running' : 'stopped';
        break;

    default:
        echo "Invalid action!";
}