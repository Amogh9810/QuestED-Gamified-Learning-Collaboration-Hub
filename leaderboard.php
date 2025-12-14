<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: http://localhost/login.php");
    exit;
}

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

$stmt = $pdo->query("
    SELECT u.user_name, u.xp_total, u.quizzes_attempted, u.questions_attempted, u.materials_uploaded,
           b.badge_type
    FROM users u
    LEFT JOIN badges b ON u.user_id = b.user_id
    ORDER BY u.xp_total DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leaderboard</title>
<style>
body {
    margin:0;
    padding:0;
    font-family:"Poppins",sans-serif;
    background:url('TRIAL2.gif') no-repeat center/cover;
    color:#fff;
}
.container {
    max-width:900px;
    margin:50px auto;
    padding:20px;
    background:rgba(0,0,0,0.7);
    border-radius:15px;
    backdrop-filter:blur(5px);
    box-shadow:0 0 20px rgba(0,0,0,0.5);
}
h1 {
    text-align:center;
    font-family:"monogram",sans-serif;
    font-size:2.5rem;
    margin-bottom:20px;
}
.back-btn {
    display:inline-block;
    margin-bottom:20px;
    padding:8px 16px;
    background:#ffdb4d;
    color:#000;
    text-decoration:none;
    border-radius:8px;
    font-weight:bold;
    transition:0.3s;
}
.back-btn:hover {
    background:#ffd633;
}
.leaderboard {
    width:100%;
    border-collapse:collapse;
}
.leaderboard th, .leaderboard td {
    padding:12px 15px;
    border-radius:8px;
    background: rgba(255,255,255,0.1);
    text-align:left;
}
.leaderboard th {
    background: rgba(255,255,255,0.2);
}
.badge {
    display:inline-block;
    padding:5px 10px;
    border-radius:8px;
    font-weight:bold;
    color:#000;
}
.badge.silver { background:#C0C0C0; }
.badge.gold { background:#FFD700; }
.badge.platinum { background:#E5E4E2; }
.button-container {
    margin-bottom:20px;
}
</style>
</head>
<body>

<div class="container">
    <h1>Leaderboard</h1>
    <div class="button-container">
        <a href="index1.html" class="back-btn">Back</a>
        <a href="front.html" class="back-btn">Logout</a>
    </div>

    <?php if (!$users): ?>
        <p>No users found.</p>
    <?php else: ?>
        <table class="leaderboard">
            <tr>
                <th>Rank</th>
                <th>User</th>
                <th>XP</th>
                <th>Quizzes Attempted</th>
                <th>Questions Attempted</th>
                <th>Materials Uploaded</th>
                <th>Badge</th>
            </tr>
            <?php $rank=1; foreach ($users as $u): ?>
            <tr>
                <td><?= $rank ?></td>
                <td><?= htmlspecialchars($u['user_name']) ?></td>
                <td><?= $u['xp_total'] ?></td>
                <td><?= $u['quizzes_attempted'] ?></td>
                <td><?= $u['questions_attempted'] ?></td>
                <td><?= $u['materials_uploaded'] ?></td>
                <td>
                    <?php if ($u['badge_type']): ?>
                        <span class="badge <?= $u['badge_type'] ?>"><?= ucfirst($u['badge_type']) ?></span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php $rank++; endforeach; ?>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
