<?php
header('Content-Type: application/json');
require 'db_connect.php';

$range = $_GET['range'] ?? 'weekly';
$labels = [];
$income = [];
$expenses = [];

// Decide queries based on range
switch ($range) {
    case 'weekly':
        // Current month, grouped by week number
        $sql_income = "
            SELECT CONCAT('Week ', WEEK(date) - WEEK(DATE_SUB(date, INTERVAL DAY(date)-1 DAY)) + 1) AS label,
                   SUM(amount) AS total
            FROM income
            WHERE MONTH(date) = MONTH(CURDATE()) 
              AND YEAR(date) = YEAR(CURDATE())
            GROUP BY label
            ORDER BY label
        ";

        $sql_expenses = "
            SELECT CONCAT('Week ', WEEK(date) - WEEK(DATE_SUB(date, INTERVAL DAY(date)-1 DAY)) + 1) AS label,
                   SUM(amount) AS total
            FROM expenses
            WHERE MONTH(date) = MONTH(CURDATE()) 
              AND YEAR(date) = YEAR(CURDATE())
            GROUP BY label
            ORDER BY label
        ";
        break;

    case 'monthly':
        // This year, grouped by month
        $sql_income = "
            SELECT DATE_FORMAT(date, '%b') AS label, 
                   SUM(amount) AS total
            FROM income
            WHERE YEAR(date) = YEAR(CURDATE())
            GROUP BY MONTH(date)
            ORDER BY MONTH(date)
        ";

        $sql_expenses = "
            SELECT DATE_FORMAT(date, '%b') AS label, 
                   SUM(amount) AS total
            FROM expenses
            WHERE YEAR(date) = YEAR(CURDATE())
            GROUP BY MONTH(date)
            ORDER BY MONTH(date)
        ";
        break;

    case 'quarterly':
        // This year, grouped by quarter
        $sql_income = "
            SELECT CONCAT('Q', QUARTER(date)) AS label, 
                   SUM(amount) AS total
            FROM income
            WHERE YEAR(date) = YEAR(CURDATE())
            GROUP BY QUARTER(date)
            ORDER BY QUARTER(date)
        ";

        $sql_expenses = "
            SELECT CONCAT('Q', QUARTER(date)) AS label, 
                   SUM(amount) AS total
            FROM expenses
            WHERE YEAR(date) = YEAR(CURDATE())
            GROUP BY QUARTER(date)
            ORDER BY QUARTER(date)
        ";
        break;

    default:
        echo json_encode(["error" => "Invalid range"]);
        exit;
}

// Fetch income data
$income_data = [];
if ($result_income = $conn->query($sql_income)) {
    while ($row = $result_income->fetch_assoc()) {
        $income_data[$row['label']] = (float)$row['total'];
    }
}

// Fetch expense data
$expense_data = [];
if ($result_expenses = $conn->query($sql_expenses)) {
    while ($row = $result_expenses->fetch_assoc()) {
        $expense_data[$row['label']] = (float)$row['total'];
    }
}

// Merge labels and sort
$labels = array_unique(array_merge(array_keys($income_data), array_keys($expense_data)));
sort($labels);

// Fill missing values with 0
foreach ($labels as $label) {
    $income[] = $income_data[$label] ?? 0;
    $expenses[] = $expense_data[$label] ?? 0;
}

// Output JSON
echo json_encode([
    "labels" => array_values($labels),
    "income" => $income,
    "expenses" => $expenses
]);
