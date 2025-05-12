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

    // Fetch the goal's current amount_contributed and target amount
    $stmt = $conn->prepare("SELECT amount, amount_contributed FROM goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $goal = $result->fetch_assoc();
    $stmt->close();

    if ($goal) {
        $target_amount = $goal['amount'];
        $current_contributed = $goal['amount_contributed'];

        // Check if the new amount exceeds the target
        if (($current_contributed + $amount) > $target_amount) {
            echo "<script>alert('You must enter an amount that does not exceed the target amount.');</script>";
        } else {
            // Update the goal's amount_contributed
            $stmt = $conn->prepare("UPDATE goals SET amount_contributed = amount_contributed + ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $amount, $goal_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
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

    header("Location: goals.php");
    exit;
}

// Handle Mark Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $goal_id = intval($_POST['mark_read_id']);
    $stmt = $conn->prepare("UPDATE goals SET read_status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Determine the filter based on the tab
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Fetch goals based on the filter
$query = "SELECT * FROM goals WHERE user_id = $user_id";
if ($filter === 'pending') {
    $query .= " AND amount_contributed < amount ORDER BY deadline ASC";
} elseif ($filter === 'completed') {
    $query .= " AND amount_contributed >= amount ORDER BY deadline ASC";
} else {
    $query .= " ORDER BY (amount_contributed < amount) DESC, deadline ASC"; // Pending goals first
}
$res = $conn->query($query);

// Check for completed goals
$completed_query = "SELECT COUNT(*) AS completed_count FROM goals WHERE user_id = $user_id AND amount_contributed >= amount AND read_status = 'unread'";
$completed_result = $conn->query($completed_query);
$completed_data = $completed_result->fetch_assoc();
$completed_count = $completed_data['completed_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Goals — My Wallet</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* General Button Styling */
    .btn {
      padding: 10px 1px;
      font-size: 14px;
      font-weight: bold;
      text-align: center;
      text-decoration: none;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn:hover {
      transform: scale(1.05);
    }

    .btn.small-btn {
      padding: 8px 12px;
      font-size: 12px;
    }

    .btn.success {
      background-color: #facc15; /* Green */
      color: white;
    }

    .btn.success:hover {
      background-color: #45a049;
    }

    .btn.danger {
      background-color: #facc15; /* Red */
      color: white;
    }

    .btn.danger:hover {
      background-color: #45a049;
    }

    .btn.edit {
      background-color: #facc15; /* Blue */
      color: white;
    }

    .btn.edit:hover {
      background-color: #45a049;
    }

    /* Tabs Styling */
    .tabs {
      display: flex;
      justify-content: center;
      margin: 20px 0;
      gap: 10px;
    }

    .tabs a {
      padding: 10px 30px;
      text-decoration: none;
      color: white;
      background-color: #facc15;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    .tabs a:hover {
      background-color: rgb(208, 173, 33);
    }

    .tabs a.active {
      background-color: #4caf50; /* Green for active tab */
      color: white;
    }

    .tabs a.active:hover {
      background-color: #45a049;
    }

    /* Notification Styling */
    .notification {
      background-color: #4caf50;
      color: white;
      padding: 10px;
      margin: 20px 0;
      text-align: center;
      border-radius: 5px;
    }

    /* Card Styling */
    .card {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-bottom: 20px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    .card button:hover {
      background-color: #45a049;
    }

    .card h4 {
      font-size: 18px;
      margin-bottom: 10px;
      color: #333;
    }

    .card p {
      font-size: 14px;
      color: #555;
      margin-bottom: 15px;
    }

    .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* Modal Styling */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      text-align: center;
      animation: fadeIn 0.3s ease-in-out;
    }

    .modal-content h3 {
      margin-bottom: 20px;
      font-size: 22px;
      font-weight: bold;
      color: #333;
    }

    .modal-content label {
      display: block;
      margin-bottom: 10px;
      font-size: 14px;
      color: #555;
      text-align: left;
    }

    .modal-content input {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    .modal-content button {
      display: inline-block;
      padding: 10px 16px;
      font-size: 14px;
      font-weight: bold;
      text-align: center;
      text-decoration: none;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .modal-content button:hover {
      transform: scale(1.05);
    }

    .modal-content .btn {
      margin-right: 10px;
    }

    .modal-content .close-modal {
      background-color: #ccc; /* Gray */
      color: black;
    }

    .modal-content .close-modal:hover {
      background-color: #bbb;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }
  </style>
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

    <!-- Notification for Completed Goals -->
    <?php if ($completed_count > 0): ?>
      <div class="notification">
        <p>You have <?= $completed_count ?> completed goal. Mark them as read!</p>
      </div>
    <?php endif; ?>

    

    <hr id="add-goal-form">
    <div class="card">
      <h3>Add New Goal</h3>
      <form method="POST">
        <input type="text" name="title" placeholder="Goal Title" required>
        <input type="number" name="amount" placeholder="Target Amount" required>
        <input type="date" name="deadline" required>
        <button class="btn">Add Goal</button>
        <!-- Tabs -->
    <div class="tabs">
  <a href="goals.php?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">All</a>
  <a href="goals.php?filter=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
  <a href="goals.php?filter=completed" class="<?= $filter === 'completed' ? 'active' : '' ?>">Completed</a>
</div>
      </form>
    </div>

    <!-- Display Goals -->
    <?php if ($res->num_rows): ?>
      <?php while ($g = $res->fetch_assoc()):
        $fulfilled = $g['amount_contributed'] >= $g['amount'];
        $archived  = $g['read_status'] === 'read';
      ?>
        <div class="card" style="<?= $archived ? 'opacity: 0.6;' : '' ?>">
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
              <!-- Add On Button -->
              <form method="post" style="margin-bottom:8px;">
                <input type="hidden" name="add_on_id" value="<?= $g['id'] ?>">
                <input type="number" name="add_amount" min="1" required>
                <button class="btn small-btn success">➕ Add On</button>
              </form>
              <!-- Edit Button -->
              <button class="btn small-btn edit" onclick="openEditModal(<?= $g['id'] ?>, '<?= htmlspecialchars($g['title']) ?>', <?= $g['amount'] ?>, '<?= $g['deadline'] ?>')">Edit</button>
              <!-- Delete Button -->
              <button class="btn small-btn danger" onclick="openDeleteModal(<?= $g['id'] ?>)">Delete</button>
            <?php elseif ($fulfilled && !$archived): ?>
              <!-- Mark as Read Button -->
              <form method="post" style="display:inline;">
                <input type="hidden" name="mark_read_id" value="<?= $g['id'] ?>">
                <button class="btn small-btn success">Mark as Read</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;">No goals found.</p>
    <?php endif; ?>
  </div>

  <a href="#add-goal-form" class="fab">+</a>
  <?php include 'bottom-nav.php'; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <h3>Edit Goal</h3>
    <form method="POST" action="edit_goal.php">
      <input type="hidden" name="goal_id" id="editGoalId">
      <label for="editGoalTitle">Title</label>
      <input type="text" name="title" id="editGoalTitle" required>
      <label for="editGoalAmount">Target Amount</label>
      <input type="number" name="amount" id="editGoalAmount" required>
      <label for="editGoalDeadline">Deadline</label>
      <input type="date" name="deadline" id="editGoalDeadline" required>
      <button type="submit" class="btn success">Save Changes</button>
      <button type="button" class="close-modal" onclick="closeModal('editModal')">Cancel</button>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h3>Are you sure you want to delete this goal?</h3>
    <form method="POST" action="delete_goal.php">
      <input type="hidden" name="goal_id" id="deleteGoalId">
      <button type="submit" class="btn danger">Delete</button>
      <button type="button" class="close-modal" onclick="closeModal('deleteModal')">Cancel</button>
    </form>
  </div>
</div>

<script>
  function openEditModal(id, title, amount, deadline) {
    document.getElementById('editGoalId').value = id;
    document.getElementById('editGoalTitle').value = title;
    document.getElementById('editGoalAmount').value = amount;
    document.getElementById('editGoalDeadline').value = deadline;
    document.getElementById('editModal').style.display = 'flex';
  }

  function openDeleteModal(id) {
    document.getElementById('deleteGoalId').value = id;
    document.getElementById('deleteModal').style.display = 'flex';
  }

  function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }

  // Get the current URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const filter = urlParams.get('filter') || 'all'; // Default to 'all' if no filter is set

  // Highlight the active tab
  document.querySelectorAll('.tabs a').forEach(tab => {
    if (tab.href.includes(`filter=${filter}`)) {
      tab.classList.add('active');
    } else {
      tab.classList.remove('active');
    }
  });
</script>
</body>
</html>