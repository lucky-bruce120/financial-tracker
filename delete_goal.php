<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$goal_id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $goal_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: goals.php");
exit;
