<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once('vendor/tecnickcom/tcpdf/tcpdf.php');
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Create PDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTitle("Financial Report");

// Add Logo (adjust path & size as needed)
$logoPath = 'assets/logo.jpg'; // <-- Replace with your actual logo path
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 30); // x, y, width
}
$pdf->Ln(20);

// Add Title
$pdf->SetFont('', 'B', 16);
$pdf->Cell(0, 10, "📄 Financial Report", 0, 1, 'C');
$pdf->Ln(5);

// 1. Income Section
$pdf->SetFont('', 'B', 13);
$pdf->Cell(0, 10, "💰 Incomes", 0, 1);
$pdf->SetFont('', '', 11);

$income_result = $conn->query("SELECT date, source, amount FROM incomes WHERE user_id = $user_id");
if ($income_result->num_rows > 0) {
    $pdf->SetFillColor(230, 230, 255);
    $pdf->Cell(50, 8, 'Date', 1, 0, 'C', 1);
    $pdf->Cell(80, 8, 'Source', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, 'Amount (RWF)', 1, 1, 'C', 1);
    while ($row = $income_result->fetch_assoc()) {
        $pdf->Cell(50, 8, $row['date'], 1);
        $pdf->Cell(80, 8, $row['source'], 1);
        $pdf->Cell(40, 8, number_format($row['amount'], 2), 1, 1);
    }
} else {
    $pdf->Cell(0, 8, "No income records found.", 0, 1);
}

// 2. Expense Section
$pdf->Ln(5);
$pdf->SetFont('', 'B', 13);
$pdf->Cell(0, 10, "📉 Expenses", 0, 1);
$pdf->SetFont('', '', 11);

$expense_result = $conn->query("SELECT date, category, amount FROM expenses WHERE user_id = $user_id");
if ($expense_result->num_rows > 0) {
    $pdf->SetFillColor(255, 230, 230);
    $pdf->Cell(50, 8, 'Date', 1, 0, 'C', 1);
    $pdf->Cell(80, 8, 'Category', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, 'Amount (RWF)', 1, 1, 'C', 1);
    while ($row = $expense_result->fetch_assoc()) {
        $pdf->Cell(50, 8, $row['date'], 1);
        $pdf->Cell(80, 8, $row['category'], 1);
        $pdf->Cell(40, 8, number_format($row['amount'], 2), 1, 1);
    }
} else {
    $pdf->Cell(0, 8, "No expense records found.", 0, 1);
}

// 3. Goals Section
$pdf->Ln(5);
$pdf->SetFont('', 'B', 13);
$pdf->Cell(0, 10, "🎯 Financial Goals", 0, 1);
$pdf->SetFont('', '', 11);

$goal_result = $conn->query("SELECT title, deadline, target_amount, status FROM goals WHERE user_id = $user_id");
if ($goal_result->num_rows > 0) {
    $pdf->SetFillColor(230, 255, 230);
    $pdf->Cell(60, 8, 'Title', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, 'Target (RWF)', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, 'Deadline', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Status', 1, 1, 'C', 1);
    while ($row = $goal_result->fetch_assoc()) {
        $pdf->Cell(60, 8, $row['title'], 1);
        $pdf->Cell(40, 8, number_format($row['target_amount'], 2), 1);
        $pdf->Cell(40, 8, $row['deadline'], 1);
        $pdf->Cell(30, 8, ucfirst($row['status']), 1, 1);
    }
} else {
    $pdf->Cell(0, 8, "No goals set.", 0, 1);
}

// Output PDF
$pdf->Output("financial_report.pdf", "I");
?>
