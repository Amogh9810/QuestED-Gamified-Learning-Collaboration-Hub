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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $type = $_POST['material_type'] ?? '';

    if (!$title || !$link || !$type) {
        $errors[] = "All fields are required!";
    } elseif (!in_array($type, ['link','video','image','document','pdf'])) {
        $errors[] = "Invalid material type!";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO study_material (user_id, title, link, material_type) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $title, $link, $type])) {
            $update = $pdo->prepare("UPDATE users SET materials_uploaded = materials_uploaded + 1 WHERE user_id = ?");
            $update->execute([$_SESSION['user_id']]);

            $success = "Material uploaded successfully!";
        } else {
            $errors[] = "Failed to upload material.";
        }
    }
}

$stmt = $pdo->query("
    SELECT sm.title, sm.link, sm.material_type, u.user_name
    FROM study_material sm
    JOIN users u ON sm.user_id = u.user_id
    ORDER BY sm.material_id DESC
");
$materials = $stmt->fetchAll();

$materialCount = count($materials);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Material</title>
<style>
body {
    margin:0;
    padding:0;
    font-family:"Poppins",sans-serif;
    background:url('TRIAL2.gif') no-repeat center/cover;
    color:#fff;
}
.container {
    max-width:800px;
    margin:50px auto;
    padding:20px;
    background:rgba(0,0,0,0.7);
    border-radius:15px;
    backdrop-filter:blur(5px);
    box-shadow:0 0 20px rgba(0,0,0,0.5);
}
h1 { text-align:center; font-family:"monogram",sans-serif; font-size:2.5rem; margin-bottom:20px;}
.back-btn { display:inline-block; margin-bottom:20px; padding:8px 16px; background:#ffdb4d; color:#000; text-decoration:none; border-radius:8px; font-weight:bold; transition:0.3s;}
.back-btn:hover { background:#ffd633; }

form input, form select {
    width:100%;
    padding:10px;
    margin:8px 0;
    border-radius:8px;
    border:none;
    outline:none;
    font-size:1rem;
}
form button {
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#ff6f61;
    color:white;
    font-size:1.1rem;
    cursor:pointer;
    transition:0.3s;
}
form button:hover { background:#ff3b2e; }
.error { background: rgba(255,0,0,0.5); padding:8px; border-radius:8px; margin-bottom:10px;}
.success { background: rgba(0,255,0,0.5); padding:8px; border-radius:8px; margin-bottom:10px;}

.materials { margin-top:30px; }
.material { background: rgba(255,255,255,0.1); padding:10px; margin-bottom:10px; border-radius:10px;}
.material a { color:#ffdb4d; text-decoration:none; }
.material a:hover { text-decoration:underline; }
.material strong { color:#ffd633; }
.material .uploader { font-size:0.9rem; color:#ccc; }
</style>
</head>
<body>

<div class="container">
<h1>Study Material</h1>
<a href="index1.html" class="back-btn">Back</a>

<?php if ($success): ?>
    <div class="success"><?= $success ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $err) echo "<p>$err</p>"; ?>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="text" name="title" placeholder="Material Title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
    <input type="text" name="link" placeholder="Material Link / URL" value="<?= htmlspecialchars($_POST['link'] ?? '') ?>">
    <select name="material_type">
        <option value="">Select Type</option>
        <option value="link" <?= (($_POST['material_type'] ?? '')=='link')?'selected':'' ?>>Link</option>
        <option value="video" <?= (($_POST['material_type'] ?? '')=='video')?'selected':'' ?>>Video</option>
        <option value="image" <?= (($_POST['material_type'] ?? '')=='image')?'selected':'' ?>>Image</option>
        <option value="document" <?= (($_POST['material_type'] ?? '')=='document')?'selected':'' ?>>Document</option>
        <option value="pdf" <?= (($_POST['material_type'] ?? '')=='pdf')?'selected':'' ?>>PDF</option>
    </select>
    <button type="submit">Upload Material</button>
</form>

<div class="materials">
    <h2>All Uploaded Materials (Total: <?= $materialCount ?>)</h2>
    <?php if (!$materials): ?>
        <p>No materials uploaded yet.</p>
    <?php else: ?>
        <?php foreach ($materials as $m): ?>
            <div class="material">
                <strong><?= htmlspecialchars($m['title']) ?></strong> (<?= $m['material_type'] ?>)<br>
                <a href="<?= htmlspecialchars($m['link']) ?>" target="_blank">View Material</a><br>
                <span class="uploader">Uploaded by: <?= htmlspecialchars($m['user_name']) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>
</body>
</html>
