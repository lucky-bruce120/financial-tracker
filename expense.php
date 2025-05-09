<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Handle new expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category'], $_POST['amount'])) {
    $category = trim($_POST['category']);
    $amount   = floatval($_POST['amount']);
    if ($category !== '' && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("isd", $user_id, $category, $amount);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch expenses
$res = $conn->query("SELECT * FROM expenses WHERE user_id = $user_id ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Expenses — My Wallet</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="wrapper">
    <header>
      <div>My Wallet</div>
      <div class="header-right">
        <div>Expenses</div>
        <a href="auth/logout.php" class="btn small-btn">Logout</a>
      </div>
    </header>

    <div class="container">
      <form id="add-expense-form" method="post" class="card">
        <h3>Add Expense</h3>
        <label>Category</label>
        <input type="text" name="category" required>
        <label>Amount (RWF)</label>
        <input type="number" name="amount" step="0.01" min="0" required>
        <button class="btn" type="submit">Add Expense</button>
      </form>

      <h3 style="margin:16px 0; text-align:center;">Recent Expenses</h3>
      <?php if ($res->num_rows): ?>
        <?php while ($row = $res->fetch_assoc()): ?>
          <div class="card">
            <h4><?= htmlspecialchars($row['category']) ?></h4>
            <p>- <?= number_format($row['amount'],2) ?> RWF<br>
               <small><?= date('Y-m-d', strtotime($row['date'])) ?></small>
            </p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align:center;">No expenses yet. Tap “+” to add one.</p>
      <?php endif; ?>
    </div>

    <!-- Floating Add Expense Button -->
    <a href="#add-expense-form" class="fab">+</a>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
      <a href="dashboard.php">🏠 Home</a>
      <a href="income.php">➕ Income</a>
      <a href="expense.php" class="active">➖ Expenses</a>
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
