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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_on_amount'], $_POST['goal_id'])) {
    $goal_id = intval($_POST['goal_id']);
    $add_on  = floatval($_POST['add_on_amount']);

    // Update contributed
    $stmt = $conn->prepare("UPDATE goals SET amount_contributed = amount_contributed + ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("dii", $add_on, $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Record expense
    $desc  = "Goal Contribution #$goal_id";
    $stmt2 = $conn->prepare(
        "INSERT INTO expenses (user_id, category, amount, description, date) 
         VALUES (?, 'Goal Contribution', ?, ?, NOW())"
    );
    $stmt2->bind_param("ids", $user_id, $add_on, $desc);
    $stmt2->execute();
    $stmt2->close();
}

// Handle Mark as Read
if (isset($_POST['mark_read_id'])) {
    $goal_id = intval($_POST['mark_read_id']);
    $stmt = $conn->prepare("UPDATE goals SET read_status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Handle New Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST'
 && isset($_POST['title'], $_POST['amount'], $_POST['deadline'])
 && !isset($_POST['add_on_amount'], $_POST['mark_read_id'])) {
    $title    = trim($_POST['title']);
    $amount   = floatval($_POST['amount']);
    $deadline = $_POST['deadline'];

    if ($title !== '' && $amount > 0 && $deadline !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO goals (user_id, title, amount, deadline) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("isss", $user_id, $title, $amount, $deadline);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch goals
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
      <form id="add-goal-form" method="post" class="card">
        <h3>Add New Goal</h3>
        <label>Title</label>
        <input type="text" name="title" required>
        <label>Target Amount (RWF)</label>
        <input type="number" name="amount" step="0.01" min="0" required>
        <label>Deadline</label>
        <input type="date" name="deadline" required>
        <button class="btn" type="submit">Add Goal</button>
      </form>

      <h3 style="margin:16px 0; text-align:center;">Your Goals</h3>

      <?php if ($res->num_rows): ?>
        <?php while ($g = $res->fetch_assoc()): 
          $fulfilled = $g['amount_contributed'] >= $g['amount'];
          $overdue   = $g['deadline'] < $today;
        ?>
        <div class="card">
          <h4>
            <?= htmlspecialchars($g['title']) ?>
            <span class="<?= $fulfilled ? 'goal-status success' : 'goal-status failed' ?>">
              <?= $fulfilled ? '✔' : '❌' ?>
            </span>
          </h4>
          <p>
            Target: <?= number_format($g['amount'],2) ?> RWF<br>
            Saved:  <?= number_format($g['amount_contributed'],2) ?> RWF<br>
            Deadline: <?= $g['deadline'] ?>
          </p>

          <div class="actions">
            <?php if ($overdue && $g['read_status']!=='read'): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="mark_read_id" value="<?= $g['id'] ?>">
                <button class="btn small-btn" type="submit">Mark Read</button>
              </form>
            <?php endif; ?>

            <?php if (!$overdue): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="goal_id" value="<?= $g['id'] ?>">
                <input type="number" name="add_on_amount" placeholder="Add RWF" step="0.01" min="0" required>
                <button class="btn small-btn" type="submit">➕ Add On</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align:center;">No goals yet. Tap “+” to add one.</p>
      <?php endif; ?>
    </div>

    <!-- Floating Add Goal Button -->
    <a href="#add-goal-form" class="fab">+</a>

    <!-- Bottom Navigation -->
    <?php 
    include 'bottom-nav.php'; ?>
  </div>

  <script>
    document.querySelector('.fab').addEventListener('click', function(e){
      e.preventDefault();
      document.querySelector(this.getAttribute('href'))
        .scrollIntoView({ behavior:'smooth' });
    });
  </script>
</body>
</html>
