<?php
// Prevent direct access to this file
defined('ACCESSED_THROUGH_DASHBOARD') or die('This file cannot be accessed directly.');

require_once 'config.php';

// Get related data for proper display
$categories = [];
$brands = [];
$suppliers = [];

try {
    if (isset($db) && $db) {
        // Pagination setup
        $items_per_page = 10;
        $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($current_page - 1) * $items_per_page;

        // Get total number of items
        $count_query = "SELECT COUNT(*) as total FROM products";
        $count_result = $db->query($count_query);
        $total_items = $count_result->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_items / $items_per_page);

        // Get products with related names instead of IDs - SKU before product_name as requested
        $stmt = $db->query("
            SELECT 
                p.sku,
                p.product_name,
                p.product_image,
                p.unit,
                p.quantity_stock,
                p.cost_price,
                p.selling_price,
                p.expiration_date,
                p.created_at,
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
            LIMIT $offset, $items_per_page
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get related data for reference
        $cat_stmt = $db->query("
            SELECT c.*, parent.category_name as parent_name 
            FROM categories c 
            LEFT JOIN categories parent ON c.parent_id = parent.category_id 
            ORDER BY parent.category_name, c.category_name
        ");
        $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Separate parent and child categories
        $parentCategories = [];
        $childCategories = [];

        foreach ($categories as $category) {
            if (empty($category['parent_id'])) {
                // This is a parent category
                $parentCategories[] = $category;
            } else {
                // This is a child category, group by parent_id
                $childCategories[$category['parent_id']][] = $category;
            }
        }

        // Create a lookup array for category relationships
        $categoryLookup = [];
        foreach ($categories as $category) {
            if (empty($category['parent_id'])) {
                $categoryLookup[$category['category_id']] = [
                    'parent_name' => $category['category_name'],
                    'child_name' => null
                ];
            } else {
                $categoryLookup[$category['category_id']] = [
                    'parent_name' => $category['parent_name'],
                    'child_name' => $category['category_name']
                ];
            }
        }

        $brand_stmt = $db->query("SELECT brand_id, brand_name FROM brands WHERE status = 'active' ORDER BY brand_name");
        $brands = $brand_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sup_stmt = $db->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status = 'active' ORDER BY supplier_name");
        $suppliers = $sup_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $products = [];
    $total_items = 0;
    $total_pages = 1;
}
?>

<div class="bg-white p-6 rounded-2xl shadow max-h-screen flex flex-col">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-red-800">Inventory Overview</h2>
        <div class="flex space-x-4">
            <button onclick="window.location.href='print_inventory.php'" class="bg-sky-500 text-white px-4 py-2 rounded-lg hover:bg-sky-600 transition">
                Print Report
            </button>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="overflow-x-auto flex-grow">
        <table class="min-w-full bg-white border border-gray-300">
            <thead class="bg-gray-100 sticky top-0">
                <tr>
                    <th class="py-2 px-4 border-b">SKU</th>
                    <th class="py-2 px-4 border-b">Product Name</th>
                    <th class="py-2 px-4 border-b">Main Category</th>
                    <th class="py-2 px-4 border-b">Sub Category</th>
                    <th class="py-2 px-4 border-b">Brand</th>
                    <th class="py-2 px-4 border-b">Supplier</th>
                    <th class="py-2 px-4 border-b">Unit</th>
                    <th class="py-2 px-4 border-b">Stock Level</th>
                    <th class="py-2 px-4 border-b">Cost Price</th>
                    <th class="py-2 px-4 border-b">Selling Price</th>
                    <th class="py-2 px-4 border-b">Expiration Date</th>
                    <th class="py-2 px-4 border-b">Status</th>
                    <th class="py-2 px-4 border-b">Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                        <tr class="hover:bg-gray-50">
                            <!-- SKU (moved before product_name as requested) -->
                            <td class="py-2 px-4 border-b font-mono text-sm">
                                <?php echo !empty($product['sku']) ? htmlspecialchars($product['sku']) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                            
                            <!-- Product Name -->
                            <td class="py-2 px-4 border-b font-medium text-gray-900">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </td>
                            
                            <!-- Main Category -->
                            <td class="py-2 px-4 border-b">
                                <?php echo !empty($product['parent_category_name']) ? htmlspecialchars($product['parent_category_name']) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                            
                            <!-- Sub Category -->
                            <td class="py-2 px-4 border-b">
                                <?php echo !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                            
                            <!-- Brand -->
                            <td class="py-2 px-4 border-b">
                                <?php echo !empty($product['brand_name']) ? htmlspecialchars($product['brand_name']) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                            
                            <!-- Supplier -->
                            <td class="py-2 px-4 border-b">
                                <?php echo !empty($product['supplier_name']) ? htmlspecialchars($product['supplier_name']) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                            
                            <!-- Unit -->
                            <td class="py-2 px-4 border-b text-center">
                                <?php echo !empty($product['unit']) ? htmlspecialchars($product['unit']) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                            
                            <!-- Stock Level -->
                            <td class="py-2 px-4 border-b text-center">
                                <span class="<?php 
                                    if ($product['quantity_stock'] == 0) echo 'bg-red-100 text-red-800';
                                    elseif ($product['quantity_stock'] <= 25) echo 'bg-yellow-100 text-yellow-800';
                                    else echo 'bg-green-100 text-green-800';
                                ?> px-2 py-1 rounded-full text-xs font-medium">
                                    <?php echo $product['quantity_stock']; ?>
                                </span>
                            </td>
                            
                            <!-- Cost Price -->
                            <td class="py-2 px-4 border-b text-right">
                                ₱<?php echo number_format($product['cost_price'], 2); ?>
                            </td>
                            
                            <!-- Selling Price -->
                            <td class="py-2 px-4 border-b text-right">
                                ₱<?php echo number_format($product['selling_price'], 2); ?>
                            </td>
                            
                            <!-- Expiration Date -->
                            <td class="py-2 px-4 border-b">
                                <?php echo !empty($product['expiration_date']) ? date('M d, Y', strtotime($product['expiration_date'])) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                            
                            <!-- Status -->
                            <td class="py-2 px-4 border-b">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $product['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            
                            <!-- Last Updated -->
                            <td class="py-2 px-4 border-b">
                                <?php echo !empty($product['updated_at']) ? date('M d, Y', strtotime($product['updated_at'])) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="py-4 px-4 border-b text-center text-gray-500">
                            No products found in inventory.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200">
        <div class="text-sm text-gray-600">
            Showing <?php echo (($current_page - 1) * $items_per_page) + 1; ?> - 
            <?php echo min($current_page * $items_per_page, $total_items); ?> of <?php echo $total_items; ?> items
        </div>
        <div class="flex space-x-2">
            <!-- Previous Button -->
            <button 
                onclick="changePage(<?php echo $current_page - 1; ?>)" 
                <?php echo $current_page <= 1 ? 'disabled' : ''; ?>
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                Previous
            </button>
            
            <!-- Page Numbers -->
            <div class="flex space-x-1">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <button 
                        onclick="changePage(<?php echo $i; ?>)" 
                        class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 <?php echo $i == $current_page ? 'bg-sky-500 text-white border-sky-500' : ''; ?> transition">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
            </div>
            
            <!-- Next Button -->
            <button 
                onclick="changePage(<?php echo $current_page + 1; ?>)" 
                <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                Next
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Print Styles -->
<style>
@media print {
    body * {
        visibility: hidden;
    }
    .bg-white.p-6.rounded-2xl.shadow.max-h-screen,
    .bg-white.p-6.rounded-2xl.shadow.max-h-screen * {
        visibility: visible;
    }
    .bg-white.p-6.rounded-2xl.shadow.max-h-screen {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        border-radius: 0;
        max-height: none;
    }
    .bg-gray-100 {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact;
    }
    button {
        display: none !important;
    }
    .flex.justify-between.items-center.mb-6 {
        margin-bottom: 1rem;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    th, td {
        border: 1px solid #d1d5db;
        padding: 0.5rem;
    }
    .max-h-screen {
        max-height: none !important;
    }
}
</style>

<script>
function changePage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}
</script>