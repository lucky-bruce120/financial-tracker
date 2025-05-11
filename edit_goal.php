<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$goal_id = intval($_GET['id'] ?? 0);

// Handle update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = $_POST['title'];
    $amount   = floatval($_POST['amount']);
    $deadline = $_POST['deadline'];

    $stmt = $conn->prepare("UPDATE goals SET title = ?, amount = ?, deadline = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sdsii", $title, $amount, $deadline, $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: goals.php");
    exit;
}

// Load existing goal
$stmt = $conn->prepare("SELECT * FROM goals WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $goal_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$goal = $result->fetch_assoc();
$stmt->close();

if (!$goal) {
    echo "Goal not found.";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Goal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
  <header><div>Edit Goal</div></header>
  <div class="container">
    <div class="card">
      <form method="POST">
        <input type="text" name="title" value="<?= htmlspecialchars($goal['title']) ?>" required>
        <input type="number" name="amount" value="<?= $goal['amount'] ?>" required>
        <input type="date" name="deadline" value="<?= $goal['deadline'] ?>" required>
        <button class="btn">Update Goal</button>
        <a href="goals.php" class="btn small-btn">Cancel</a>
      </form>
    </div>
  </div>
</div>
</body>
</html>
