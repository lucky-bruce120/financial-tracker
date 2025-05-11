<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$today   = date('Y-m-d');

// Handle Mark as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $goal_id = intval($_POST['mark_read_id']);
    $stmt = $conn->prepare("UPDATE goals SET read_status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Calculate totals
$income  = $conn->query("SELECT SUM(amount) AS total FROM income WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$expense = $conn->query("SELECT SUM(amount) AS total FROM expenses WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$savings = $income - $expense;

// Fetch overdue goals
$overdue_res = $conn->query("
    SELECT * FROM goals 
    WHERE user_id = $user_id 
      AND deadline < '$today' 
    ORDER BY deadline ASC
");

// Fetch upcoming goals
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
    <div>My Wallet</div>
    <div class="header-right">
      <div>Dashboard</div>
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

    <?php if ($overdue_res->num_rows): ?>
      <?php while ($g = $overdue_res->fetch_assoc()):
        $fulfilled = $g['amount_contributed'] >= $g['amount'];
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
            <?php if ($fulfilled && !$archived): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="mark_read_id" value="<?= $g['id'] ?>">
                <button class="btn small-btn">Mark Read</button>
              </form>
            <?php elseif ($archived): ?>
              <span class="badge archived">Archived</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;">No overdue goals.</p>
    <?php endif; ?>

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

  <!-- Goal Form (Hidden by default) -->
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
