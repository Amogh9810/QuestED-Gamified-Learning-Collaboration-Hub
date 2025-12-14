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

$stmt = $pdo->query("SELECT * FROM quizzes ORDER BY quiz_id DESC");
$quizzes = $stmt->fetchAll();

$user_attempts = [];
$stmt = $pdo->prepare("SELECT quiz_id, total_xp FROM quiz_attempts WHERE user_id=?");
$stmt->execute([$_SESSION['user_id']]);
foreach ($stmt->fetchAll() as $row) {
    $user_attempts[$row['quiz_id']] = $row['total_xp'];
}

$success = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_id'])) {
    $quiz_id = intval($_POST['quiz_id']);

    if (isset($user_attempts[$quiz_id])) {
        $errors[] = "You have already attempted this quiz. You cannot submit it again.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id=?");
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll();

        $total_xp = 0;
        $questions_attempted = 0;

        foreach ($questions as $q) {
            $qid = $q['question_id'];
            if (isset($_POST['question_' . $qid])) {
                $questions_attempted++;
                $selected = $_POST['question_' . $qid];
                if ($selected == $q['correct_option']) {
                    $total_xp += $q['xp_per_question'];
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, total_xp) VALUES (?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $quiz_id, $total_xp])) {
            $update = $pdo->prepare("
                UPDATE users 
                SET xp_total = xp_total + ?,
                    quizzes_attempted = quizzes_attempted + 1,
                    questions_attempted = questions_attempted + ?
                WHERE user_id = ?
            ");
            $update->execute([$total_xp, $questions_attempted, $_SESSION['user_id']]);

            $success = "Quiz completed! You earned $total_xp XP. Questions attempted: $questions_attempted.";
            $user_attempts[$quiz_id] = $total_xp; 
        } else {
            $errors[] = "Failed to record quiz attempt.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quizzes</title>
<style>
body { margin:0; padding:0; font-family:"Poppins",sans-serif; background:url('TRIAL2.gif') no-repeat center/cover; color:#fff; }
.container { max-width:800px; margin:50px auto; padding:20px; background:rgba(0,0,0,0.7); border-radius:15px; backdrop-filter:blur(5px); box-shadow:0 0 20px rgba(0,0,0,0.5);}
h1 { text-align:center; font-family:"monogram",sans-serif; font-size:2.5rem; margin-bottom:20px;}
.back-btn { display:inline-block; margin-bottom:20px; padding:8px 16px; background:#ffdb4d; color:#000; text-decoration:none; border-radius:8px; font-weight:bold; transition:0.3s;}
.back-btn:hover { background:#ffd633; }
.quiz { background: rgba(255,255,255,0.1); padding:15px; margin-bottom:15px; border-radius:10px;}
.quiz h2 { margin:0 0 10px 0; }
.quiz button { padding:10px 20px; border:none; border-radius:8px; background:#ff6f61; color:white; font-size:1rem; cursor:pointer; transition:0.3s;}
.quiz button:hover { background:#ff3b2e;}
form .question { margin-bottom:15px; padding:10px; background:rgba(255,255,255,0.05); border-radius:8px;}
form .question label { display:block; margin-bottom:5px;}
.error { background: rgba(255,0,0,0.5); padding:8px; border-radius:8px; margin-bottom:10px;}
.success { background: rgba(0,255,0,0.5); padding:8px; border-radius:8px; margin-bottom:10px;}
</style>
</head>
<body>

<div class="container">
<h1>Quizzes</h1>
<a href="index1.html" class="back-btn">Back</a>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $err) echo "<p>".htmlspecialchars($err)."</p>"; ?>
    </div>
<?php endif; ?>

<?php if (!$quizzes): ?>
    <p>No quizzes available yet.</p>
<?php else: ?>
    <?php foreach ($quizzes as $quiz): ?>
        <div class="quiz">
            <h2><?= htmlspecialchars($quiz['title']) ?> (<?= htmlspecialchars($quiz['subject']) ?>)</h2>

            <?php if (isset($user_attempts[$quiz['quiz_id']])): ?>
                <p>You have already completed this quiz and earned <?= $user_attempts[$quiz['quiz_id']] ?> XP.</p>
            <?php else: ?>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id=?");
                $stmt->execute([$quiz['quiz_id']]);
                $questions = $stmt->fetchAll();
                ?>
                <form method="POST">
                    <?php foreach ($questions as $q): ?>
                        <div class="question">
                            <label><?= htmlspecialchars($q['question']) ?> (XP: <?= $q['xp_per_question'] ?>)</label>
                            <input type="radio" name="question_<?= $q['question_id'] ?>" value="1" required> <?= htmlspecialchars($q['option_1']) ?><br>
                            <input type="radio" name="question_<?= $q['question_id'] ?>" value="2"> <?= htmlspecialchars($q['option_2']) ?><br>
                            <input type="radio" name="question_<?= $q['question_id'] ?>" value="3"> <?= htmlspecialchars($q['option_3']) ?><br>
                            <input type="radio" name="question_<?= $q['question_id'] ?>" value="4"> <?= htmlspecialchars($q['option_4']) ?>
                        </div>
                    <?php endforeach; ?>
                    <input type="hidden" name="quiz_id" value="<?= $quiz['quiz_id'] ?>">
                    <button type="submit">Submit Quiz</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>
</body>
</html>
