<?php
// filepath: c:\xampp\htdocs\financial-tracker\delete_goal.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['goal_id'])) {
    $goal_id = intval($_POST['goal_id']);
    $user_id = $_SESSION['user_id'];

    // Delete the goal from the database
    $stmt = $conn->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: goals.php");
    exit;
}
?>
