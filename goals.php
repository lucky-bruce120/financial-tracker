<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$today   = date('Y-m-d');

// Handle Add On
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_on_id'])) {
    $goal_id = intval($_POST['add_on_id']);
    $amount  = floatval($_POST['add_amount']);
    $stmt = $conn->prepare("UPDATE goals SET amount_contributed = amount_contributed + ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("dii", $amount, $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Handle Mark Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $goal_id = intval($_POST['mark_read_id']);
    $stmt = $conn->prepare("UPDATE goals SET read_status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all goals
$res = $conn->query("SELECT * FROM goals WHERE user_id = $user_id ORDER BY deadline ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Goals — My Wallet</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
  <header>
    <div>My Wallet</div>
    <div class="header-right">
      <div>Goals</div>
      <a href="auth/logout.php" class="btn small-btn">Logout</a>
    </div>
  </header>

  <div class="container">
    <h2 style="text-align:center;">My Goals</h2>
    <?php if ($res->num_rows): ?>
      <?php while ($g = $res->fetch_assoc()):
        $fulfilled = $g['amount_contributed'] >= $g['amount'];
        $overdue   = $g['deadline'] < $today;
        $archived  = $g['read_status'] === 'read';
      ?>
        <div class="card">
          <h4>
            <?= htmlspecialchars($g['title']) ?>
            <span class="<?= $fulfilled ? 'goal-status success' : 'goal-status failed' ?>">
              <?= $fulfilled ? '✔' : '❌' ?>
            </span>
          </h4>
          <p>
            Target: <?= number_format($g['amount'], 2) ?> RWF<br>
            Saved: <?= number_format($g['amount_contributed'], 2) ?> RWF<br>
            Deadline: <?= $g['deadline'] ?>
          </p>
          <div class="actions">
            <?php if (!$fulfilled && !$archived): ?>
              <form method="post" style="margin-bottom:8px;">
                <input type="hidden" name="add_on_id" value="<?= $g['id'] ?>">
                <input type="number" name="add_amount" min="1" required>
                <button class="btn small-btn">➕ Add On</button>
              </form>
            <?php endif; ?>

            <?php if ($fulfilled && $overdue && !$archived): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="mark_read_id" value="<?= $g['id'] ?>">
                <button class="btn small-btn">Mark Read</button>
              </form>
            <?php elseif ($archived): ?>
              <span class="badge archived">Archived</span>
            <?php endif; ?>

            <!-- Edit & Delete Buttons -->
            <div style="margin-top:10px;">
              <a href="edit_goal.php?id=<?= $g['id'] ?>" class="btn small-btn">Edit</a>
              <a href="delete_goal.php?id=<?= $g['id'] ?>" class="btn small-btn danger" onclick="return confirm('Delete this goal?')">Delete</a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;">No goals added yet.</p>
    <?php endif; ?>

    <hr id="add-goal-form">
    <div class="card">
      <h3>Add New Goal</h3>
      <form action="add_goal.php" method="POST">
        <input type="text" name="title" placeholder="Goal Title" required>
        <input type="number" name="amount" placeholder="Target Amount" required>
        <input type="date" name="deadline" required>
        <button class="btn">Add Goal</button>
      </form>
    </div>
  </div>

  <a href="#add-goal-form" class="fab">+</a>
  <?php include 'bottom-nav.php'; ?>
</div>

<script>
  document.querySelector('.fab').addEventListener('click', function(e){
    e.preventDefault();
    document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
  });
</script>
</body>
</html>
