<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

include 'config/db.php';

$user_id = $_SESSION['user_id']; // ✅ Ensure this is defined BEFORE queries

// Fetch user info
$user_stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_stmt->bind_result($full_name, $profile_image);
$user_stmt->fetch();
$user_stmt->close();

// Fallback image if none uploaded
if (empty($profile_image)) {
    $profile_image = 'default_profile.jpeg'; // Make sure this file exists in the uploads/ folder
}

// Today's date
$today = date('Y-m-d');

// Handle Mark as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $goal_id = intval($_POST['mark_read_id']);
    $stmt = $conn->prepare("UPDATE goals SET read_status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Handle Add Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['amount'], $_POST['deadline'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $amount = floatval($_POST['amount']);
    $deadline = $conn->real_escape_string($_POST['deadline']);

    $stmt = $conn->prepare("INSERT INTO goals (user_id, title, amount, deadline, amount_contributed, read_status) VALUES (?, ?, ?, ?, 0, 'unread')");
    $stmt->bind_param("isds", $user_id, $title, $amount, $deadline);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php");
    exit;
}

// Totals
$income  = $conn->query("SELECT SUM(amount) AS total FROM income WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$expense = $conn->query("SELECT SUM(amount) AS total FROM expenses WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$savings = $income - $expense;

// Overdue goals
$overdue_res = $conn->query("
    SELECT * FROM goals 
    WHERE user_id = $user_id 
      AND deadline < '$today' 
      AND read_status != 'read'
    ORDER BY deadline ASC
");

// Upcoming goals
$upcoming_res = $conn->query("
    SELECT * FROM goals 
    WHERE user_id = $user_id 
      AND deadline >= '$today' 
    ORDER BY deadline ASC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard — My Wallet</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
  <header>
    <div class="header-left">
      <img src="uploads/<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="profile-pic">
      <span><?= htmlspecialchars($full_name) ?></span>
    </div>
    <div class="header-right">
      <a href="auth/logout.php" class="btn small-btn">Logout</a>
    </div>
  </header>

  <div class="container">
    <div class="card">
      <h3>Summary:</h3>
      <table>
        <tr><th>Total Income</th><td>+ <?= number_format($income, 2) ?> RWF</td></tr>
        <tr><th>Total Expenses</th><td>- <?= number_format($expense, 2) ?> RWF</td></tr>
        <tr><th>Savings</th><td><?= number_format($savings, 2) ?> RWF</td></tr>
      </table>
    </div>

    <h3 style="text-align:center; margin:16px 0;">
      Overdue Goals 
      <?php if ($overdue_res->num_rows): ?>
        <span class="badge"><?= $overdue_res->num_rows ?></span>
      <?php endif; ?>
    </h3>

    <div style="text-align:center; margin-top:20px;">
      <a href="goals.php?filter=pending" class="btn small-btn">📋 View Overdue Goals</a>
    </div>

    <div style="text-align:center; margin-top:20px;">
      <a href="goals.php?filter=completed" class="btn small-btn">📁 Goals Archived</a>
    </div>

    <h3 style="text-align:center; margin:24px 0;">Upcoming Goals</h3>
    <ul>
      <?php if ($upcoming_res->num_rows): ?>
        <?php while ($g = $upcoming_res->fetch_assoc()): ?>
          <li><?= htmlspecialchars($g['title']) ?> - <?= $g['deadline'] ?></li>
        <?php endwhile; ?>
      <?php else: ?>
        <li>No upcoming goals.</li>
      <?php endif; ?>
    </ul>

    
  </div>

  <!-- Goal Form -->
  <div id="goalForm" class="popup-form" style="display:none;">
    <form method="POST" class="card">
      <h3>Add New Goal</h3>
      <input type="text" name="title" placeholder="Goal Title" required>
      <input type="number" name="amount" placeholder="Target Amount" required>
      <input type="date" name="deadline" required>
      <button type="submit" class="btn">Add Goal</button>
      <button type="button" class="btn small-btn" onclick="toggleGoalForm()">Cancel</button>
    </form>
  </div>

  <?php include 'bottom-nav.php'; ?>
</div>
<script>
  function toggleGoalForm() {
    const form = document.getElementById("goalForm");
    form.style.display = form.style.display === "none" ? "block" : "none";
  }
</script>
</body>
</html>
