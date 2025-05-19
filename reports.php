<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

include 'config/db.php';

$user_id = $_SESSION['user_id']; // Ensure the user is logged in

// Default filter
$report_filter = isset($_GET['filter']) ? $_GET['filter'] : 'daily';

// Set date range based on the filter
$today = date('Y-m-d');
if ($report_filter === 'daily') {
    $start_date = $today;
    $end_date = $today;
} elseif ($report_filter === 'weekly') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = $today;
} elseif ($report_filter === 'monthly') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = $today;
}

// Fetch income data
$income_query = $conn->prepare("
    SELECT description, amount, date 
    FROM income 
    WHERE user_id = ? AND date BETWEEN ? AND ?
    ORDER BY date DESC
");
$income_query->bind_param("iss", $user_id, $start_date, $end_date);
$income_query->execute();
$income_result = $income_query->get_result();

// Fetch expense data
$expense_query = $conn->prepare("
    SELECT description, amount, date 
    FROM expenses 
    WHERE user_id = ? AND date BETWEEN ? AND ?
    ORDER BY date DESC
");
$expense_query->bind_param("iss", $user_id, $start_date, $end_date);
$expense_query->execute();
$expense_result = $expense_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .filters {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filters a {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #4caf50;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .filters a.active {
            background-color: #45a049;
        }

        .filters a:hover {
            background-color: #367c39;
        }

        .export-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }

        .export-buttons a {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #007bff;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .export-buttons a:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f4f4f4;
        }

        .no-data {
            text-align: center;
            color: #888;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reports</h2>

        <!-- Filter Options -->
        <div class="filters">
            <a href="reports.php?filter=daily" class="<?= $report_filter === 'daily' ? 'active' : '' ?>">Daily</a>
            <a href="reports.php?filter=weekly" class="<?= $report_filter === 'weekly' ? 'active' : '' ?>">Weekly</a>
            <a href="reports.php?filter=monthly" class="<?= $report_filter === 'monthly' ? 'active' : '' ?>">Monthly</a>
        </div>

        <!-- Export Buttons -->
       <!-- <div class="export-buttons">
            <a href="export_pdf.php?filter=<?= $report_filter ?>" target="_blank">Export to PDF</a>
            <a href="export_excel.php?filter=<?= $report_filter ?>">Export to Excel</a>
        </div>-->

        <!-- Income Report -->
        <h3>Income</h3>
        <?php if ($income_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($income = $income_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($income['description']) ?></td>
                            <td>+ <?= number_format($income['amount'], 2) ?> RWF</td>
                            <td><?= $income['date'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No income data available for this period.</p>
        <?php endif; ?>

        <!-- Expense Report -->
        <h3>Expenses</h3>
        <?php if ($expense_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($expense = $expense_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($expense['description']) ?></td>
                            <td>- <?= number_format($expense['amount'], 2) ?> RWF</td>
                            <td><?= $expense['date'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No expense data available for this period.</p>
        <?php endif; ?>
    </div>
     <!-- Bottom Navigation -->
  <?php include 'bottom-nav.php'; ?>
</body>
</html>