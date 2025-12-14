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

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pbm_id'])) {
    $pbm_id = intval($_POST['pbm_id']);
    $user_code = trim($_POST['user_code']);

    $stmt = $pdo->prepare("SELECT * FROM coding_questions WHERE pbm_id=?");
    $stmt->execute([$pbm_id]);
    $problem = $stmt->fetch();

    if (!$problem) {
        $errors[] = "Problem not found.";
    } elseif (!$user_code) {
        $errors[] = "You must enter some code/output.";
    } else {
        $expected = trim($problem['expected_output']);
        $user_output = trim(strip_tags($user_code));

        $expected_clean = preg_replace('/\s+/', '', $expected);
        $user_clean = preg_replace('/\s+/', '', $user_output);

        if ($user_clean === $expected_clean) {
            $status = "pass";
            $total_xp = intval($problem['xp']);
        } else {
            $status = "failed";
            $total_xp = 0;
        }

        $stmt = $pdo->prepare("
            INSERT INTO coding_solution (pbm_id, user_id, status, output, total_xp)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$pbm_id, $_SESSION['user_id'], $status, htmlspecialchars($user_output), $total_xp]);

        if ($status === 'pass') {
            $update = $pdo->prepare("UPDATE users SET xp_total = xp_total + ? WHERE user_id=?");
            $update->execute([$total_xp, $_SESSION['user_id']]);
            $success = "Solution submitted successfully! You earned $total_xp XP.";
        } else {
            $success = "Solution submitted but failed. No XP earned.";
        }
    }
}

$stmt = $pdo->query("SELECT * FROM coding_questions ORDER BY pbm_id DESC");
$problems = $stmt->fetchAll();

$user_solutions = [];
$stmt = $pdo->prepare("SELECT * FROM coding_solution WHERE user_id=?");
$stmt->execute([$_SESSION['user_id']]);
foreach ($stmt->fetchAll() as $sol) {
    $user_solutions[$sol['pbm_id']] = $sol;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Coding Challenges</title>
<style>
body { margin:0; padding:0; font-family:"Poppins",sans-serif; background:url('TRIAL2.gif') no-repeat center/cover; color:#fff; }
.container { max-width:900px; margin:50px auto; padding:20px; background:rgba(0,0,0,0.7); border-radius:15px; backdrop-filter:blur(5px);}
h1 { text-align:center; font-family:"monogram",sans-serif; font-size:2.5rem; margin-bottom:20px;}
.back-btn { display:inline-block; margin-bottom:20px; padding:8px 16px; background:#ffdb4d; color:#000; text-decoration:none; border-radius:8px; font-weight:bold;}
.back-btn:hover { background:#ffd633; }
.problem { background: rgba(255,255,255,0.1); padding:15px; margin-bottom:20px; border-radius:10px;}
.problem h2 { margin:0 0 10px 0; }
.problem .meta { font-size:0.9rem; color:#ffd; margin-bottom:10px; }
form textarea { width:100%; padding:8px; border-radius:8px; border:none; resize:none; margin-bottom:10px;}
form input { padding:8px; border-radius:8px; border:none; margin-bottom:10px;}
button { padding:10px 20px; border:none; border-radius:8px; background:#ff6f61; color:white; cursor:pointer;}
button:hover { background:#ff3b2e; }
.error { background: rgba(255,0,0,0.5); padding:8px; border-radius:8px; margin-bottom:10px;}
.success { background: rgba(0,255,0,0.5); padding:8px; border-radius:8px; margin-bottom:10px;}
.submission { background: rgba(255,255,255,0.05); padding:8px; border-radius:8px; margin-top:8px;}
.submission .status { font-weight:bold; color:#ffd; }
</style>
</head>
<body>
<div class="container">
<h1>Coding Challenges</h1>
<a href="index1.html" class="back-btn">Back</a>

<?php if ($success) echo "<div class='success'>$success</div>"; ?>
<?php if ($errors) { echo "<div class='error'>"; foreach ($errors as $err) echo "<p>$err</p>"; echo "</div>"; } ?>

<?php if (!$problems): ?>
    <p>No coding challenges available yet.</p>
<?php else: ?>
    <?php foreach ($problems as $pbm): ?>
        <div class="problem">
            <h2>Problem #<?= $pbm['pbm_id'] ?> - <?= htmlspecialchars($pbm['difficulty_level']) ?> (XP: <?= $pbm['xp'] ?>)</h2>
            <div class="meta"><?= nl2br(htmlspecialchars($pbm['pbm_statement'])) ?></div>
            <p><strong>Input:</strong> <?= nl2br(htmlspecialchars($pbm['input_desc'])) ?></p>

            <?php if (isset($user_solutions[$pbm['pbm_id']])): 
                $sol = $user_solutions[$pbm['pbm_id']];
            ?>
                <div class="submission">
                    <p class="status">Status: <?= htmlspecialchars($sol['status']) ?></p>
                    <pre><?= htmlspecialchars($sol['output']) ?></pre>
                    <p>XP earned: <?= $sol['total_xp'] ?></p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <textarea name="user_code" rows="2" placeholder="Enter answer/output here..." required></textarea>
                    <input type="hidden" name="pbm_id" value="<?= $pbm['pbm_id'] ?>">
                    <button type="submit">Submit Solution</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>
</body>
</html>
