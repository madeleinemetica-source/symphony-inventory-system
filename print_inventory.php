<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Not authorized');
}

// Log the print action
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
        $act->execute([$_SESSION['user_id'], 'inventory_print', 'Generated inventory report']);
    }
} catch (Exception $e) {
    // non-fatal, don't block the print
}

$products = [];
$categories = [];

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Fetch ALL products for print (no pagination)
        $stmt = $db->query("
            SELECT 
                p.sku,
                p.product_name,
                p.unit,
                p.quantity_stock,
                p.cost_price,
                p.selling_price,
                p.expiration_date,
                p.updated_at,
                p.status,
                c.category_name,
                b.brand_name, 
                s.supplier_name,
                parent.category_name as parent_category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN categories parent ON c.parent_id = parent.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
            ORDER BY p.product_name
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $products = [];
}

$total_cost = 0;
$total_value = 0;

foreach ($products as $product) {
    $total_cost += $product['cost_price'] * $product['quantity_stock'];
    $total_value += $product['selling_price'] * $product['quantity_stock'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 40px;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .report-date {
            text-align: right;
            margin-bottom: 20px;
            font-size: 12px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 12px;
        }

        thead {
            background-color: #f0f0f0;
            border-bottom: 2px solid #333;
        }

        th {
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-right: 1px solid #ddd;
        }

        th:last-child {
            border-right: none;
        }

        td {
            padding: 8px 10px;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #eee;
        }

        td:last-child {
            border-right: none;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f0f0f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .status-active {
            color: #10b981;
            font-weight: 600;
        }

        .status-inactive {
            color: #ef4444;
            font-weight: 600;
        }

        .low-stock {
            background-color: #fef3c7;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .out-of-stock {
            background-color: #fee2e2;
            padding: 2px 4px;
            border-radius: 3px;
            color: #dc2626;
            font-weight: 600;
        }

        .summary {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 15px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            font-size: 13px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-label {
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 11px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        /* Print-specific styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                max-width: 100%;
                box-shadow: none;
                padding: 20px;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            table {
                page-break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid;
            }

            /* Landscape for better fit */
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            .header {
                page-break-after: avoid;
            }
        }

        /* Screen-only controls */
        .screen-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-print {
            background-color: #3b82f6;
            color: white;
        }

        .btn-print:hover {
            background-color: #2563eb;
        }

        .btn-back {
            background-color: #6b7280;
            color: white;
        }

        .btn-back:hover {
            background-color: #4b5563;
        }

        .btn-pdf {
            background-color: #ef4444;
            color: white;
        }

        .btn-pdf:hover {
            background-color: #dc2626;
        }

        /* Mobile responsiveness for screen view */
        @media screen and (max-width: 1024px) {
            .container {
                padding: 20px;
            }

            table {
                font-size: 11px;
            }

            th, td {
                padding: 6px 4px;
            }

            .summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Screen-only controls -->
        <div class="screen-controls no-print">
            <button class="btn-back" onclick="window.history.back()">← Back</button>
            <button class="btn-pdf" onclick="downloadPDF()">Download PDF</button>
            <button class="btn-print" onclick="window.print()">🖨️ Print</button>
        </div>

        <!-- Report Header -->
        <div class="header">
            <h1>📦 Inventory Report</h1>
            <p>Complete Product Inventory Overview</p>
        </div>

        <div class="report-date">
            Generated: <?php echo date('F d, Y • g:i A'); ?>
        </div>

        <!-- Inventory Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">SKU</th>
                    <th style="width: 15%;">Product Name</th>
                    <th style="width: 10%;">Main Category</th>
                    <th style="width: 10%;">Sub Category</th>
                    <th style="width: 8%;">Brand</th>
                    <th style="width: 8%;">Supplier</th>
                    <th style="width: 6%;">Unit</th>
                    <th style="width: 8%;">Stock</th>
                    <th style="width: 10%;">Cost Price</th>
                    <th style="width: 10%;">Selling Price</th>
                    <th style="width: 12%;">Expiration</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 12%;">Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                        <tr>
                            <td class="text-center"><?php echo !empty($product['sku']) ? htmlspecialchars($product['sku']) : '—'; ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo !empty($product['parent_category_name']) ? htmlspecialchars($product['parent_category_name']) : '—'; ?></td>
                            <td><?php echo !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : '—'; ?></td>
                            <td><?php echo !empty($product['brand_name']) ? htmlspecialchars($product['brand_name']) : '—'; ?></td>
                            <td><?php echo !empty($product['supplier_name']) ? htmlspecialchars($product['supplier_name']) : '—'; ?></td>
                            <td class="text-center"><?php echo !empty($product['unit']) ? htmlspecialchars($product['unit']) : '—'; ?></td>
                            <td class="text-right">
                                <?php if ($product['quantity_stock'] == 0): ?>
                                    <span class="out-of-stock">0</span>
                                <?php elseif ($product['quantity_stock'] <= 25): ?>
                                    <span class="low-stock"><?php echo $product['quantity_stock']; ?></span>
                                <?php else: ?>
                                    <?php echo $product['quantity_stock']; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">₱<?php echo number_format($product['cost_price'], 2); ?></td>
                            <td class="text-right">₱<?php echo number_format($product['selling_price'], 2); ?></td>
                            <td><?php echo !empty($product['expiration_date']) ? date('M d, Y', strtotime($product['expiration_date'])) : '—'; ?></td>
                            <td class="text-center">
                                <?php if ($product['status'] === 'active'): ?>
                                    <span class="status-active">✓ Active</span>
                                <?php else: ?>
                                    <span class="status-inactive">✗ Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($product['updated_at']) ? date('M d, Y', strtotime($product['updated_at'])) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="text-center">No products found in inventory.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Summary Section -->
        <?php if (!empty($products)): ?>
        <div class="summary">
            <div class="summary-item">
                <div class="summary-label">Total Products</div>
                <div class="summary-value"><?php echo count($products); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Units in Stock</div>
                <div class="summary-value"><?php echo array_sum(array_column($products, 'quantity_stock')); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Cost Value</div>
                <div class="summary-value">₱<?php echo number_format($total_cost, 2); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Retail Value</div>
                <div class="summary-value">₱<?php echo number_format($total_value, 2); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>This report was generated from the Inventory Management System on <?php echo date('F d, Y \a\t g:i A'); ?></p>
        </div>
    </div>

    <script>
        function downloadPDF() {
            // Simple approach: open print dialog with PDF printer
            // For advanced PDF generation, consider using a library like TCPDF or Dompdf
            // For now, let user print to PDF via browser's print dialog
            alert('Use "Print" button and select "Save as PDF" option in your browser\'s print dialog.');
            window.print();
        }
    </script>
</body>
</html>
