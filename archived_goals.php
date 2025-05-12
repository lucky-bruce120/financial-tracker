<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = $search ? "AND title LIKE '%" . $conn->real_escape_string($search) . "%'" : '';

// Fetch archived (read) goals
$res = $conn->query("
    SELECT * FROM goals 
    WHERE user_id = $user_id 
      AND read_status = 'read'
      $search_sql
    ORDER BY deadline DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Archived Goals — My Wallet</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">
  <header>
    <div>My Wallet</div>
    <div class="header-right">
      <div>Archived Goals</div>
      <a href="dashboard.php" class="btn small-btn">← Back</a>
    </div>
  </header>

  <div class="container">
    <h3 style="text-align:center; margin:16px 0;">Archived Goals</h3>

    <!-- Search Form -->
    <form method="GET" class="card" style="margin-bottom: 20px;">
      <input type="text" name="search" placeholder="Search goal title..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn small-btn">Search</button>
    </form>

    <?php if ($res->num_rows): ?>
      <?php while ($g = $res->fetch_assoc()):
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
            Target: <?= number_format($g['amount'], 2) ?> RWF<br>
            Saved: <?= number_format($g['amount_contributed'], 2) ?> RWF<br>
            Deadline: <?= $g['deadline'] ?>
          </p>
          <div class="actions">
            <span class="badge archived">Archived</span>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;">No archived goals found<?= $search ? " for '$search'" : "" ?>.</p>
    <?php endif; ?>
  </div>

  <?php include 'bottom-nav.php'; ?>
</div>
</body>
</html>
