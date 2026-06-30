<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ToolTrack Login</title>

<style>
body{
    margin:0;
    font-family:Arial, sans-serif;
    background:#0D2B52;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.login-box{
    width:380px;
    background:#fff;
    border-radius:15px;
    padding:35px;
    box-shadow:0 10px 25px rgba(0,0,0,.3);
    text-align:center;
}

.login-box h2{
    margin-bottom:5px;
    color:#0D2B52;
}

.login-box p{
    color:#666;
    margin-bottom:25px;
}

input{
    width:100%;
    padding:12px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:8px;
    box-sizing:border-box;
}

button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    background:#16B5E8;
    color:white;
    font-size:16px;
    cursor:pointer;
}

button:hover{
    background:#1096c4;
}

.error{
    background:#ffe5e5;
    color:#d8000c;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
}
</style>

</head>
<body>

<div class="login-box">

    <h2>ToolTrack Inventory System</h2>
    <p>DAZ Training Center Inc.</p>

    <?php
    if(isset($_GET['error'])){
        echo "<div class='error'>Invalid username or password.</div>";
    }
    ?>

    <form action="login_process.php" method="POST">

        <input type="text" name="username" placeholder="Username" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">LOGIN</button>

    </form>

</div>

</body>
</html>