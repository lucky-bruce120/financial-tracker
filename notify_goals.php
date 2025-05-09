<?php
require 'config/db.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\Exception;


use PHPMailer\PHPMailer\PHPMailer;

$today = date('Y-m-d');
$query = $conn->query("SELECT users.email, goals.title, goals.deadline FROM goals 
    JOIN users ON goals.user_id = users.id
    WHERE goals.deadline <= '$today' AND goals.status != 'completed'");

while ($row = $query->fetch_assoc()) {
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com'; // Replace with your SMTP host
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@example.com';
    $mail->Password = 'your-password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('your-email@example.com', 'Financial Tracker');
    $mail->addAddress($row['email']);
    $mail->Subject = "Goal Deadline Alert";
    $mail->Body = "Reminder: Your goal '{$row['title']}' was due on {$row['deadline']}.";

    $mail->send();
}
?>
