<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Handle new income
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['source'], $_POST['amount'])) {
    $source = trim($_POST['source']);
    $amount = floatval($_POST['amount']);
    if ($source !== '' && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO income (user_id, source, amount, date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("isd", $user_id, $source, $amount);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch incomes
$res = $conn->query("SELECT * FROM income WHERE user_id = $user_id ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Income — My Wallet</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="wrapper">
    <header>
      <div>My Wallet</div>
      <div class="header-right">
        <div>Income</div>
        <a href="auth/logout.php" class="btn small-btn">Logout</a>
      </div>
    </header>

    <div class="container">
      <form id="add-income-form" method="post" class="card">
        <h3>Add Income</h3>
        <label>Source</label>
        <input type="text" name="source" required>
        <label>Amount (RWF)</label>
        <input type="number" name="amount" step="0.01" min="0" required>
        <button class="btn" type="submit">Add Income</button>
      </form>

      <h3 style="margin:16px 0; text-align:center;">Recent Income</h3>
      <?php if ($res->num_rows): ?>
        <?php while ($row = $res->fetch_assoc()): ?>
          <div class="card">
            <h4><?= htmlspecialchars($row['source']) ?></h4>
            <p>+ <?= number_format($row['amount'],2) ?> RWF<br>
               <small><?= date('Y-m-d', strtotime($row['date'])) ?></small>
            </p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align:center;">No income yet. Tap “+” to add one.</p>
      <?php endif; ?>
    </div>

    <!-- Floating Add Income Button -->
    <a href="#add-income-form" class="fab">+</a>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
      <a href="dashboard.php">🏠 Home</a>
      <a href="income.php" class="active">➕ Income</a>
      <a href="expense.php">➖ Expenses</a>
      <a href="goals.php">🎯 Goals</a>
    </div>
  </div>

  <script>
    document.querySelector('.fab').addEventListener('click', function(e){
      e.preventDefault();
      document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior:'smooth' });
    });
  </script>
</body>
</html>
