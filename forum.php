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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_topic'])) {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $content = trim($_POST['content']);

    if (!$title || !$category || !$content) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO forum_topics (title, category, content, user_id) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $category, $content, $_SESSION['user_id']])) {
            $success = "Topic created successfully!";
        } else {
            $errors[] = "Failed to create topic.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $topic_id = intval($_POST['topic_id']);
    $reply_content = trim($_POST['reply_content']);
    if ($reply_content) {
        $stmt = $pdo->prepare("INSERT INTO forum_replies (topic_id, content, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$topic_id, $reply_content, $_SESSION['user_id']]);
    }
}

$stmt = $pdo->query("SELECT t.*, u.user_name FROM forum_topics t JOIN users u ON t.user_id = u.user_id ORDER BY topic_id DESC");
$topics = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forum</title>
<style>
body { margin:0; padding:0; font-family:"Poppins",sans-serif; background:url('TRIAL2.gif') no-repeat center/cover; color:#fff; }
.container { max-width:900px; margin:50px auto; padding:20px; background:rgba(0,0,0,0.7); border-radius:15px; backdrop-filter:blur(5px);}
h1 { text-align:center; font-family:"monogram",sans-serif; font-size:2.5rem; margin-bottom:20px;}
.back-btn { display:inline-block; margin-bottom:20px; padding:8px 16px; background:#ffdb4d; color:#000; text-decoration:none; border-radius:8px; font-weight:bold;}
.back-btn:hover { background:#ffd633; }
.topic { background: rgba(255,255,255,0.1); padding:15px; margin-bottom:20px; border-radius:10px;}
.topic h2 { margin:0 0 10px 0; }
.topic .meta { font-size:0.9rem; color:#ffd; margin-bottom:10px; }
form textarea { width:100%; padding:8px; border-radius:8px; border:none; resize:none;}
form input, form select { padding:8px; border-radius:8px; border:none; margin-bottom:10px;}
button { padding:10px 20px; border:none; border-radius:8px; background:#ff6f61; color:white; cursor:pointer;}
button:hover { background:#ff3b2e; }
.error { background: rgba(255,0,0,0.5); padding:8px; border-radius:8px; margin-bottom:10px;}
.success { background: rgba(0,255,0,0.5); padding:8px; border-radius:8px; margin-bottom:10px;}
.reply { background: rgba(255,255,255,0.05); padding:8px; border-radius:8px; margin:5px 0;}
.reply .meta { font-size:0.85rem; color:#ffd;}
</style>
</head>
<body>
<div class="container">
<h1>Forum</h1>
<a href="index1.html" class="back-btn">Back</a>

<?php if ($success) echo "<div class='success'>$success</div>"; ?>
<?php if ($errors) { echo "<div class='error'>"; foreach ($errors as $err) echo "<p>$err</p>"; echo "</div>"; } ?>


<div class="topic">
    <h2>Create New Topic</h2>
    <form method="POST">
        <input type="text" name="title" placeholder="Title" required><br>
        <input type="text" name="category" placeholder="Category" required><br>
        <textarea name="content" placeholder="Write your topic..." rows="4" required></textarea><br>
        <button type="submit" name="new_topic">Post Topic</button>
    </form>
</div>


<?php foreach ($topics as $t): ?>
<div class="topic">
    <h2><?= htmlspecialchars($t['title']) ?> (<?= htmlspecialchars($t['category']) ?>)</h2>
    <div class="meta">Posted by <?= htmlspecialchars($t['user_name']) ?></div>
    <p><?= nl2br(htmlspecialchars($t['content'])) ?></p>


    <?php
    $stmt = $pdo->prepare("SELECT r.*, u.user_name FROM forum_replies r JOIN users u ON r.user_id = u.user_id WHERE r.topic_id=? ORDER BY reply_id ASC");
    $stmt->execute([$t['topic_id']]);
    $replies = $stmt->fetchAll();
    foreach ($replies as $r):
    ?>
        <div class="reply">
            <div class="meta"><?= htmlspecialchars($r['user_name']) ?> replied:</div>
            <p><?= nl2br(htmlspecialchars($r['content'])) ?></p>
        </div>
    <?php endforeach; ?>

    <form method="POST">
        <textarea name="reply_content" placeholder="Write a reply..." rows="2" required></textarea>
        <input type="hidden" name="topic_id" value="<?= $t['topic_id'] ?>">
        <button type="submit" name="reply">Reply</button>
    </form>
</div>
<?php endforeach; ?>

</div>
</body>
</html>
