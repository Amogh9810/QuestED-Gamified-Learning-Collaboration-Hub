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
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');

    if (!$name || !$email || !$password || !$confirm) {
        $errors[] = "All fields are required!";
    } elseif (strlen($name) < 3) {
        $errors[] = "Full name must be at least 3 characters!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address!";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters!";
    } elseif ($password !== $confirm) {
        $errors[] = "Passwords do not match!";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email is already registered!";
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (user_name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashedPassword])) {
            header("Location: http://localhost/login.php");
            exit;
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>QuestED - Sign Up</title>
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
    width: 360px;
    padding: 30px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    box-shadow: 0 8px 35px rgba(0,0,0,0.25);
    text-align: center;
    animation: fadeIn 1s ease;
}
h2 { color: #fff; margin-bottom: 25px; }
input {
    width: 90%;
    padding: 12px;
    margin: 10px 0;
    border: none;
    border-radius: 10px;
    outline: none;
    font-size: 15px;
    background: rgba(255, 255, 255, 0.6);
}
.btn {
    width: 95%;
    padding: 12px;
    background: rgba(255, 255, 255, 0.4);
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
    <h2>Create Account</h2>

    <form action="" method="POST">
        <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($_POST['name'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Password">
        <input type="password" name="confirm" placeholder="Confirm Password">
        <button class="btn" type="submit">Sign Up</button>
    </form>

    <?php if (!empty($errors)) : ?>
        <div class="error">
            <?php foreach ($errors as $err) : ?>
                <p><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <br>
    <a href="http://localhost/login.php">Already have an account? Login</a>
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
