<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Calculate available balance
$income  = $conn->query("SELECT SUM(amount) AS total FROM income WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$expense = $conn->query("SELECT SUM(amount) AS total FROM expenses WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$available = $income - $expense;

$notification_message = "";

// Handle new expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category'], $_POST['amount'])) {
    $category = trim($_POST['category']);
    $amount   = floatval($_POST['amount']);
    if ($category !== '' && $amount > 0) {
        if ($amount > $available) {
            $notification_message = "You cannot add an expense greater than your available balance (" . number_format($available,2) . " RWF).";
        } else {
            $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("isd", $user_id, $category, $amount);
            $stmt->execute();
            $stmt->close();
            // Update available after adding
            $available -= $amount;
        }
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
  <style>
    .modal {
      display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;
    }
    .modal-content {
      background: #fff; padding: 20px; border-radius: 10px; max-width: 350px; margin: auto; text-align: center;
    }
    .modal-content button { margin-top: 15px; }
  </style>
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
      <form id="add-expense-form" method="post" class="card" onsubmit="return checkExpenseLimit()">
        <h3>Add Expense</h3>
        <label>Category</label>
        <input type="text" name="category" required>
        <label>Amount (RWF)</label>
        <input type="number" name="amount" id="expenseAmount" step="0.01" min="0" required>
        <div style="margin:8px 0; color:#888;">Available: <?= number_format($available,2) ?> RWF</div>
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
    <?php include 'bottom-nav.php'; ?>
  </div>

  <!-- Notification Modal -->
  <div id="notificationModal" class="modal">
    <div class="modal-content">
      <h3 id="notificationMessage"></h3>
      <button onclick="closeModal('notificationModal')">OK</button>
    </div>
  </div>

  <script>
    // Floating button scroll
    document.querySelector('.fab').addEventListener('click', function(e){
      e.preventDefault();
      document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior:'smooth' });
    });

    // Frontend validation for available balance
    function checkExpenseLimit() {
      var available = <?= json_encode($available) ?>;
      var amount = parseFloat(document.getElementById('expenseAmount').value);
      if (amount > available) {
        showModal('notificationModal', 'You cannot add an expense greater than your available balance (<?= number_format($available,2) ?> RWF).');
        return false;
      }
      return true;
    }

    function showModal(modalId, message) {
      var modal = document.getElementById(modalId);
      document.getElementById('notificationMessage').textContent = message;
      modal.style.display = 'flex';
    }
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }

    // Show backend notification if exists
    <?php if (!empty($notification_message)): ?>
      showModal('notificationModal', <?= json_encode($notification_message) ?>);
    <?php endif; ?>
  </script>
</body>
</html>
