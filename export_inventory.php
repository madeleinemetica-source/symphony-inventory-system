<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Not authorized');
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Output header row
fputcsv($output, ['Product Name', 'Category', 'Brand', 'Stock', 'Cost Price', 'Selling Price']);

// Fetch data
$stmt = $db->query("
    SELECT p.product_name, c.category_name, b.brand_name, p.quantity_stock, p.cost_price, p.selling_price
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    ORDER BY p.product_name
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>