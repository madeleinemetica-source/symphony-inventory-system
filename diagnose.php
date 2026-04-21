<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die('Not authorized');
}

echo "<h1>Database Diagnostic Report</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>✓ Database Connection OK</h2>";
    
    // Check categories table
    echo "<h3>Categories Table Status:</h3>";
    $cat_info = $db->query("SHOW TABLE STATUS WHERE Name='categories'")->fetch(PDO::FETCH_ASSOC);
    if ($cat_info) {
        echo "<pre>";
        print_r($cat_info);
        echo "</pre>";
    }
    
    // Check products table
    echo "<h3>Products Table Status:</h3>";
    $prod_info = $db->query("SHOW TABLE STATUS WHERE Name='products'")->fetch(PDO::FETCH_ASSOC);
    if ($prod_info) {
        echo "<pre>";
        print_r($prod_info);
        echo "</pre>";
    }
    
    // Check categories count
    echo "<h3>Categories Count:</h3>";
    $cat_count = $db->query("SELECT COUNT(*) as count FROM categories")->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total categories: <strong>" . $cat_count['count'] . "</strong></p>";
    
    // List all categories
    echo "<h3>All Categories:</h3>";
    $cats = $db->query("SELECT * FROM categories ORDER BY category_id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Parent ID</th><th>Created</th></tr>";
    foreach ($cats as $cat) {
        echo "<tr>";
        echo "<td>{$cat['category_id']}</td>";
        echo "<td>{$cat['category_name']}</td>";
        echo "<td>" . ($cat['parent_id'] ?? 'NULL') . "</td>";
        echo "<td>{$cat['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check products count
    echo "<h3>Products Count:</h3>";
    $prod_count = $db->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total products: <strong>" . $prod_count['count'] . "</strong></p>";
    
    // Check user_activities table
    echo "<h3>Recent Activities (Last 10):</h3>";
    $acts = $db->query("SELECT * FROM user_activities ORDER BY activity_id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if ($acts) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Description</th><th>Date</th></tr>";
        foreach ($acts as $act) {
            echo "<tr>";
            echo "<td>{$act['activity_id']}</td>";
            echo "<td>{$act['user_id']}</td>";
            echo "<td>{$act['activity_type']}</td>";
            echo "<td>{$act['activity_description']}</td>";
            echo "<td>{$act['activity_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No activities found</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Database Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
