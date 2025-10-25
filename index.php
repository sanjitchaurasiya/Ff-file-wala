<?php
// © 2025 Developed by t.me/SANJIT_CHAURASIYA
//Do not Sell This Code 
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<?php
$uploadDir = __DIR__ . '/uploads/';
$logDir = __DIR__ . '/logs/';

// ✅ Ensure directories exist
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($logDir)) mkdir($logDir, 0777, true);

function isRunning($file) {
    global $uploadDir; 
    $pidFile = $uploadDir . $file . '.pid';
    if (file_exists($pidFile)) {
        $pid = file_get_contents($pidFile);
        $output = shell_exec("ps -p $pid 2>/dev/null");
        return strpos($output, $pid) !== false;
    }
    return false;
}

// ✅ Handle file upload safely
if (isset($_FILES['python_file'])) {
    $fileName = basename($_FILES['python_file']['name']);
    $targetFile = $uploadDir . $fileName;

    if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'py') {
        echo "<script>alert('Only .py files are allowed!');</script>";
    } else {
        if (move_uploaded_file($_FILES['python_file']['tmp_name'], $targetFile)) {
            echo "<script>alert('File uploaded successfully!');</script>";
        } else {
            echo "<script>alert('Failed to upload file. Please check permissions.');</script>";
        }
    }
}

// ✅ List uploaded scripts
$files = array_diff(scandir($uploadDir), ['.', '..']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Python Script Dashboard</title>
    <style>
        body {font-family: 'Segoe UI', sans-serif; background: #f5f7fa; margin: 0; padding: 0;}
        header {background: linear-gradient(90deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; font-size: 2em; font-weight: bold;}
        .container {max-width: 1200px; margin: 30px auto; padding: 0 20px;}
        .card {background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);}
        .card h2 {margin-top: 0;}
        table {width: 100%; border-collapse: collapse; margin-top: 15px;}
        th, td {padding: 12px; text-align: center; border-bottom: 1px solid #ddd;}
        th {background: #f1f1f1;}
        .status-running {color: green; font-weight: bold;}
        .status-stopped {color: red; font-weight: bold;}
        button {padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin: 2px; transition: 0.3s;}
        button.start {background: #28a745; color: white;}
        button.stop {background: #dc3545; color: white;}
        button.restart {background: #ffc107; color: white;}
        button.delete {background: #6c757d; color: white;}
        button:hover {opacity: 0.8;}
        #logContainer {height: 300px; overflow-y: scroll; background: #1e1e1e; color: #00ff00; padding: 10px; font-family: monospace; border-radius: 8px; margin-top: 10px;}
    </style>
</head>
<body>
<header>
    Python Script Dashboard
    <div style="position:absolute; top:20px; right:30px;">
        <a href="logout.php" style="color:white; text-decoration:none; font-size:0.8em;">Logout</a>
    </div>
</header>
<div class="container">

    <div class="card">
        <h2>Upload Python Script</h2>
        <form id="uploadForm" method="post" enctype="multipart/form-data">
            <input type="file" name="python_file" accept=".py" required>
            <button type="submit">Upload</button>
        </form>
    </div>

    <div class="card">
        <h2>Scripts</h2>
        <table id="scriptsTable">
            <thead>
                <tr>
                    <th>Script</th>
                    <th>Status</th>
                    <th>Actions</th>
                    <th>Logs</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($files as $file): ?>
                <?php if (pathinfo($file, PATHINFO_EXTENSION) !== 'py') continue; ?>
                <tr data-file="<?php echo htmlspecialchars($file); ?>">
                    <td><?php echo htmlspecialchars($file); ?></td>
                    <td class="status"><?php echo isRunning($file) ? '<span class="status-running">Running</span>' : '<span class="status-stopped">Stopped</span>'; ?></td>
                    <td>
                        <button class="start">Start</button>
                        <button class="stop">Stop</button>
                        <button class="restart">Restart</button>
                        <button class="delete">Delete</button>
                    </td>
                    <td><button class="view-log">View Log</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div id="logContainer" style="display:none;"></div>
    </div>

</div>

<script>
// ✅ Upload handler
document.getElementById('uploadForm').addEventListener('submit', function(e){
    e.preventDefault();
    let formData = new FormData(this);
    fetch('', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            alert('Upload complete!');
            location.reload();
        });
});

// ✅ Action handler with delete confirmation
document.querySelectorAll('#scriptsTable button').forEach(btn => {
    btn.addEventListener('click', function(){
        let row = this.closest('tr');
        let file = row.dataset.file;
        let action = this.className;

        if (action === 'delete' && !confirm('Are you sure you want to permanently delete ' + file + '?')) return;

        if(action === 'view-log'){
            document.getElementById('logContainer').style.display = 'block';
            fetchLog(file);
            return;
        }

        fetch('scripts.php?action=' + action + '&file=' + encodeURIComponent(file))
            .then(res => res.text())
            .then(data => {
                alert(data);
                updateStatus(row, file);
            });
    });
});

// ✅ Live status updater
function updateStatus(row, file) {
    fetch('scripts.php?action=status&file=' + encodeURIComponent(file))
        .then(res => res.text())
        .then(data => {
            row.querySelector('.status').innerHTML = data == 'running' 
                ? '<span class="status-running">Running</span>'
                : '<span class="status-stopped">Stopped</span>';
        });
}

setInterval(() => {
    document.querySelectorAll('#scriptsTable tbody tr').forEach(row => {
        let file = row.dataset.file;
        updateStatus(row, file);
    });
}, 5000);

// ✅ Live log updater
let currentLogFile = '';
function fetchLog(file){
    currentLogFile = file;
    let container = document.getElementById('logContainer');
    fetch('scripts.php?action=logs&file=' + encodeURIComponent(file))
        .then(res => res.text())
        .then(data => {
            container.innerHTML = data;
            container.scrollTop = container.scrollHeight;
        });
}

setInterval(() => {
    if(currentLogFile){
        fetchLog(currentLogFile);
    }
}, 2000);
</script>
</body>
</html>