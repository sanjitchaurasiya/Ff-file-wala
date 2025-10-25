<?php

// © 2025 Developed by t.me/SANJIT_CHAURASIYA
//Do not Sell This Code 

session_start();

// ✅ Change this to your own secure password
$USERNAME = "SANJIT";
$PASSWORD = "@SANJIT_CHAURASIYA";

if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $USERNAME && $_POST['password'] === $PASSWORD) {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - Python Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(120deg, #667eea, #764ba2);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            width: 300px;
            text-align: center;
        }
        h2 {margin-bottom: 20px;}
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {background: #5a67d8;}
        .error {color: red; margin-top: 10px;}
    </style>
</head>
<body>
<div class="login-box">
    <h2>Login</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
</div>
</body>
</html>