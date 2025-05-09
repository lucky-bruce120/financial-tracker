<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

require 'vendor/autoload.php';
include 'config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$user_id = $_SESSION['user_id'];
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Financial Report");

// Insert App Logo
$logo = new Drawing();
$logo->setName('Logo');
$logo->setDescription('App Logo');
$logo->setPath(__DIR__ . '/assets/logo.png'); // Use full path
$logo->setHeight(60);
$logo->setCoordinates('A1');
$logo->setWorksheet($sheet);

// Title
$sheet->mergeCells('A5:D5');
$sheet->setCellValue('A5', '📄 Financial Report');
$sheet->getStyle('A5')->getFont()->setBold(true)->setSize(16);
$sheet->getRowDimension('5')->setRowHeight(30);

// INCOMES
$row = 7;
$sheet->setCellValue("A{$row}", "💰 Incomes");
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
$row += 1;
$sheet->fromArray(['Date', 'Source', 'Amount (RWF)'], null, "A{$row}");
$sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
$sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
$sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$row++;

$income_result = $conn->query("SELECT date, source, amount FROM incomes WHERE user_id = $user_id");
if ($income_result->num_rows > 0) {
    while ($data = $income_result->fetch_assoc()) {
        $sheet->fromArray([$data['date'], $data['source'], $data['amount']], null, "A{$row}");
        $row++;
    }
} else {
    $sheet->setCellValue("A{$row}", "No income records found.");
    $row++;
}

// EXPENSES
$row += 2;
$sheet->setCellValue("A{$row}", "📉 Expenses");
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
$row += 1;
$sheet->fromArray(['Date', 'Category', 'Amount (RWF)'], null, "A{$row}");
$sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
$sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
$sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$row++;

$expense_result = $conn->query("SELECT date, category, amount FROM expenses WHERE user_id = $user_id");
if ($expense_result->num_rows > 0) {
    while ($data = $expense_result->fetch_assoc()) {
        $sheet->fromArray([$data['date'], $data['category'], $data['amount']], null, "A{$row}");
        $row++;
    }
} else {
    $sheet->setCellValue("A{$row}", "No expense records found.");
    $row++;
}

// GOALS
$row += 2;
$sheet->setCellValue("A{$row}", "🎯 Financial Goals");
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
$row += 1;
$sheet->fromArray(['Title', 'Target (RWF)', 'Deadline', 'Status'], null, "A{$row}");
$sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
$sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
$sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$row++;

$goal_result = $conn->query("SELECT title, target_amount, deadline, status FROM goals WHERE user_id = $user_id");
if ($goal_result->num_rows > 0) {
    while ($data = $goal_result->fetch_assoc()) {
        $sheet->fromArray([
            $data['title'],
            $data['target_amount'],
            $data['deadline'],
            ucfirst($data['status'])
        ], null, "A{$row}");
        $row++;
    }
} else {
    $sheet->setCellValue("A{$row}", "No goals set.");
    $row++;
}

// Auto-size columns
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output the file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="financial_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
