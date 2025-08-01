<?php
require_once 'db_connect.php';

echo "<h2>Debug Expenses Table</h2>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'expenses'");
if ($table_check->num_rows > 0) {
    echo "<p>✅ Expenses table exists</p>";
} else {
    echo "<p>❌ Expenses table does not exist</p>";
    exit;
}

// Check table structure
$structure = $conn->query("DESCRIBE expenses");
echo "<h3>Table Structure:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $structure->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check current data
$result = $conn->query("SELECT * FROM expenses ORDER BY id");
echo "<h3>Current Expenses Data:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['date'] . "</td>";
        echo "<td>" . $row['category'] . "</td>";
        echo "<td>" . $row['description'] . "</td>";
        echo "<td>" . $row['amount'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No expenses found in database</p>";
}

// Test inserting a sample expense
echo "<h3>Testing Insert:</h3>";
$test_stmt = $conn->prepare("INSERT INTO expenses (date, category, description, amount, status) VALUES (?, ?, ?, ?, ?)");
$date = '2024-01-30';
$category = 'Test';
$description = 'Test expense for debugging';
$amount = 100.00;
$status = 'Pending';

$test_stmt->bind_param("sssds", $date, $category, $description, $amount, $status);
if ($test_stmt->execute()) {
    echo "<p>✅ Test expense inserted with ID: " . $test_stmt->insert_id . "</p>";
} else {
    echo "<p>❌ Error inserting test expense: " . $test_stmt->error . "</p>";
}
$test_stmt->close();

$conn->close();
?> 