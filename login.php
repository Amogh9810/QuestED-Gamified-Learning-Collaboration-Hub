<?php
session_start();

$host = 'localhost';
$db   = 'mydb'; 
$user = 'root';
$pass = '';   
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $errors[] = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email!";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id, user_name, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['user_name'];

    header("Location: index1.html");
    exit;
} else {
    $errors[] = "Invalid email or password!";
}

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>QuestED - Login</title>
<style>
body {
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: "Poppins", sans-serif;
    background: url('TRIAL2.gif') no-repeat center/cover;
}
.glass-box {
    width: 350px;
    padding: 30px;
    border-radius: 20px;
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    box-shadow: 0 8px 35px rgba(0,0,0,0.2);
    text-align: center;
    animation: fadeIn 1s ease;
}
h2 { color: #f4efef; margin-bottom: 25px; }
input {
    width: 90%;
    padding: 12px;
    margin: 10px 0;
    border: none;
    border-radius: 10px;
    outline: none;
    font-size: 15px;
    background: rgba(255,255,255,0.6);
}
.btn {
    width: 95%;
    padding: 12px;
    background: rgba(255,255,255,0.4);
    cursor: pointer;
    border-radius: 10px;
    border: none;
    font-size: 16px;
    font-weight: bold;
    transition: 0.3s;
}
.btn:hover { background: rgba(255,255,255,0.7); }
.error { color: #ffcccc; font-size: 14px; margin-top: 5px; }
a { color: #ff73c9; font-size: 14px; text-decoration: underline; cursor: pointer; }
@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-5px); }
    100% { transform: translateX(0); }
}
.shake { animation: shake 0.35s; }
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
</head>
<body>

<div class="glass-box" id="box">
    <h2>Login</h2>
    <form action="" method="POST">
        <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Password">
        <button class="btn" type="submit">Login</button>
    </form>

    <?php if (!empty($errors)) : ?>
        <div class="error">
            <?php foreach ($errors as $err) : ?>
                <p><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <br>
    <a href="http://localhost/signup.php">Don't have an account? Sign Up</a>
</div>

<script>
function shake() {
    let box = document.getElementById("box");
    box.classList.add("shake");
    setTimeout(() => box.classList.remove("shake"), 350);
}

<?php if (!empty($errors)) : ?>
    shake();
<?php endif; ?>
</script>

</body>
</html>
