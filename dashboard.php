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

// Active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'summary';

// Transactions filter
$transaction_filter = isset($_GET['filter']) ? $_GET['filter'] : 'daily';
if ($transaction_filter === 'daily') {
    $start_date = $today;
    $end_date = $today;
} elseif ($transaction_filter === 'weekly') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = $today;
} elseif ($transaction_filter === 'monthly') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = $today;
}

// Fetch income transactions
$income_transactions = $conn->query("
    SELECT * FROM income 
    WHERE user_id = $user_id 
      AND date BETWEEN '$start_date' AND '$end_date'
    ORDER BY date DESC
");

// Fetch expense transactions
$expense_transactions = $conn->query("
    SELECT * FROM expenses 
    WHERE user_id = $user_id 
      AND date BETWEEN '$start_date' AND '$end_date'
    ORDER BY date DESC
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
    <div class="tabs">
      <a href="dashboard.php?tab=summary" class="<?= isset($_GET['tab']) && $_GET['tab'] === 'summary' ? 'active' : '' ?>">Summary</a>
      <a href="dashboard.php?tab=overdue" class="<?= isset($_GET['tab']) && $_GET['tab'] === 'overdue' ? 'active' : '' ?>">Overdue Goals</a>
      <a href="dashboard.php?tab=upcoming" class="<?= isset($_GET['tab']) && $_GET['tab'] === 'upcoming' ? 'active' : '' ?>">Upcoming Goals</a>
    </div>

    <?php if ($active_tab === 'summary'): ?>
      <div class="card">
        <h3>Summary:</h3>
        <table>
          <tr><th>Total Income</th><td>+ <?= number_format($income, 2) ?> RWF</td></tr>
          <tr><th>Total Expenses</th><td>- <?= number_format($expense, 2) ?> RWF</td></tr>
          <tr><th>Savings</th><td><?= number_format($savings, 2) ?> RWF</td></tr>
        </table>
      </div>

      <!-- Recent Transactions with Filter -->
      <div class="card">
        <h3>Transactions</h3>

        <!-- Filter Options -->
        <div class="transaction-filters">
          <a href="dashboard.php?tab=summary&filter=daily" class="<?= $transaction_filter === 'daily' ? 'active' : '' ?>">Daily</a>
          <a href="dashboard.php?tab=summary&filter=weekly" class="<?= $transaction_filter === 'weekly' ? 'active' : '' ?>">Weekly</a>
          <a href="dashboard.php?tab=summary&filter=monthly" class="<?= $transaction_filter === 'monthly' ? 'active' : '' ?>">Monthly</a>
        </div>

        <!-- Income Transactions -->
        <div class="transaction-list">
          <h4>Income</h4>
          <?php if ($income_transactions->num_rows > 0): ?>
            <ul>
              <?php
              $count = 0; // Limit to 5 recent transactions
              while ($income = $income_transactions->fetch_assoc()): 
                if ($count >= 5) break;
                $count++;
              ?>
                <li>
                  <span><?= htmlspecialchars($income['description'] ?? 'No description') ?></span>
                  <span>+ <?= number_format($income['amount'], 2) ?> RWF</span>
                  <span><?= $income['date'] ?></span>
                </li>
              <?php endwhile; ?>
            </ul>
          <?php else: ?>
            <p>No recent income transactions found.</p>
          <?php endif; ?>
        </div>

        <!-- Expense Transactions -->
        <div class="transaction-list">
          <h4>Expenses</h4>
          <?php if ($expense_transactions->num_rows > 0): ?>
            <ul>
              <?php
              $count = 0; // Limit to 5 recent transactions
              while ($expense = $expense_transactions->fetch_assoc()): 
                if ($count >= 5) break;
                $count++;
              ?>
                <li>
                  <span><?= htmlspecialchars($expense['description'] ?? 'No description') ?></span>
                  <span>- <?= number_format($expense['amount'], 2) ?> RWF</span>
                  <span><?= $expense['date'] ?></span>
                </li>
              <?php endwhile; ?>
            </ul>
          <?php else: ?>
            <p>No recent expense transactions found.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php elseif ($active_tab === 'overdue'): ?>
      <h3 style="text-align:center; margin:16px 0;">
        Overdue Goals 
        <?php if ($overdue_res->num_rows): ?>
          <span class="badge"><?= $overdue_res->num_rows ?></span>
        <?php endif; ?>
      </h3>
      <div style="text-align:center; margin-top:20px;">
        <a href="goals.php?filter=pending" class="btn small-btn">📋 View Overdue Goals</a>
      </div>
    <?php elseif ($active_tab === 'upcoming'): ?>
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
    <?php endif; ?>
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

  <!-- Bottom Navigation -->
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
