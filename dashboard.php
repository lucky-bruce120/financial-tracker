<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$today   = date('Y-m-d');

// Calculate totals
$income  = $conn->query("SELECT SUM(amount) AS total FROM income WHERE user_id = $user_id")
            ->fetch_assoc()['total'] ?? 0;
$expense = $conn->query("SELECT SUM(amount) AS total FROM expenses WHERE user_id = $user_id")
            ->fetch_assoc()['total'] ?? 0;
$savings = $income - $expense;

// Fetch overdue goals
$overdue_res = $conn->query(
    "SELECT * FROM goals 
     WHERE user_id = $user_id 
       AND deadline < '$today' 
       AND read_status = 'unread'
     ORDER BY deadline ASC"
);

// Fetch upcoming goals
$upcoming_res = $conn->query(
    "SELECT * FROM goals 
     WHERE user_id = $user_id 
       AND deadline >= '$today'
     ORDER BY deadline ASC 
     LIMIT 5"
);
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
      <!-- Summary Card -->
      <div class="card">
        <h3>Summary</h3>
        <table>
          <tr><th>Total Income</th><td>+ <?= number_format($income,2) ?> RWF</td></tr>
          <tr><th>Total Expenses</th><td>- <?= number_format($expense,2) ?> RWF</td></tr>
          <tr><th>Savings</th><td><?= number_format($savings,2) ?> RWF</td></tr>
        </table>
      </div>

      <!-- Overdue Goals -->
      <h3 style="text-align:center; margin:16px 0;">
        Overdue Goals 
        <?php if($overdue_res->num_rows): ?>
          <span class="badge"><?= $overdue_res->num_rows ?></span>
        <?php endif; ?>
      </h3>
      <?php if ($overdue_res->num_rows): ?>
        <?php while($g = $overdue_res->fetch_assoc()): 
            $fulfilled = $g['amount_contributed'] >= $g['amount'];
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
              <form method="post" action="goals.php" style="display:inline;">
                <input type="hidden" name="mark_read_id" value="<?= $g['id'] ?>">
                <button class="btn small-btn">Mark Read</button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align:center;">No overdue goals.</p>
      <?php endif; ?>

      <!-- Upcoming Goals -->
      <h3 style="text-align:center; margin:24px 0;">Upcoming Goals</h3>
      <ul>
        <?php if ($upcoming_res->num_rows): ?>
          <?php while($g = $upcoming_res->fetch_assoc()): ?>
            <li>
              <?= htmlspecialchars($g['title']) ?>  
              - <?= $g['deadline'] ?>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <li>No upcoming goals.</li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Floating Button to Add a Goal -->
    <a href="goals.php#add-goal-form" class="fab">+</a>

    <!-- Bottom Navigation -->
   <?php 
    include 'bottom-nav.php'; ?>
      
  </div>

  <script>
    // FAB smooth scroll
    document.querySelector('.fab').addEventListener('click', e => {
      e.preventDefault();
      document.querySelector(this.getAttribute('href'))
              .scrollIntoView({ behavior:'smooth' });
    });
  </script>
</body>
</html>
