<?php
// Prevent direct access to this file
defined('ACCESSED_THROUGH_DASHBOARD') or die('This file cannot be accessed directly.');

// SUPPRESS ERRORS to prevent breaking JavaScript
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

// Safe database fetching with related data
$products = [];
$categories = [];
$brands = [];
$suppliers = [];

try {
    if (isset($db) && $db) {
        // Get products with related names instead of IDs
        $stmt = $db->query("
            SELECT 
                p.*,
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
        
        // Get related data for dropdowns
        $cat_stmt = $db->query("
            SELECT c.*, parent.category_name as parent_name 
            FROM categories c 
            LEFT JOIN categories parent ON c.parent_id = parent.category_id 
            ORDER BY parent.category_name, c.category_name
        ");
        $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Separate parent and child categories for the dropdowns (NEW)
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

            // Create a lookup array for category relationships (for table display)
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
}
?>
<!-- overall div with flexible height -->
<div class="flex p-6 gap-6 w-96 max-h-screen">

<!-- Table container div - scrollable -->
<div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-2xl shadow flex-1 flex flex-col min-h-0">
<!-- toggle buttons -->
        <div class="flex justify-between items-center mb-4 flex-shrink-0">
            <h2 class="text-xl font-bold text-white">Products Management</h2>
            <div class="flex space-x-4">
                <!-- add product button -->
                <button onclick="addProduct()" class="w-12 h-12 p-2 bg-green-400 rounded-lg hover:bg-green-500 transition duration-300">
                    <svg class="w-8 h-8 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.757v8.486M7.757 12h8.486M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </button>
                <!-- change table mode toggle button -->
                <button type="button" id="changetbl-btn" class="table-toggle w-12 h-12 p-2 bg-amber-400 rounded-lg hover:bg-amber-500 transition duration-300">
                    <svg class="w-8 h-8 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v14M9 5v14M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                    </svg>
                </button>
            </div>
        </div>
        
<!-- descriptive table - scrollable container -->
<div id="descriptive-table" class="bg-white rounded-lg shadow flex-1 overflow-hidden flex flex-col">
    <!-- search input -->
    <div class="pb-4 bg-red-700 p-4 flex-shrink-0">
        <label for="table-search-horizontal" class="sr-only">Search</label>
        <div class="relative mt-1">
            <div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
            </div>
            <input type="text" id="table-search-horizontal" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Search for items">
        </div>

        <!-- Sort by dropdowns -->
        <div class="flex items-center space-x-4 mt-2 flex-shrink-0">
            <label class="text-sm font-medium text-white">Sort by:</label>
            
            <!-- Main Category Dropdown -->
            <select id="sort-main-category" class="p-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Main Categories</option>
                <?php foreach($parentCategories as $parent): ?>
                    <option value="<?php echo $parent['category_id']; ?>">
                        <?php echo htmlspecialchars($parent['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- Sub Category Dropdown -->
            <select id="sort-sub-category" class="p-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Sub Categories</option>
                <?php foreach($categories as $category): ?>
                    <?php if(!empty($category['parent_id'])): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            
            <!-- Brand Dropdown -->
            <select id="sort-brand" class="p-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Brands</option>
                <?php foreach($brands as $brand): ?>
                    <option value="<?php echo $brand['brand_id']; ?>">
                        <?php echo htmlspecialchars($brand['brand_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- Clear Filters Button -->
            <button id="clear-filters" class="p-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Clear Filters
            </button>
        </div>
    </div>
    <!-- end of search input -->
    
    <!-- No products message for filtered view -->
        <div id="no-products-message" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4 flex-shrink-0">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <p class="text-yellow-800">
                    There are no products yet on this category / brand. 
                    Please click on add product form to start.
                </p>
            </div>
        </div>
    
    <!-- Scrollable table container -->
    <div class="flex-1 overflow-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0 z-10">
                <tr>
                    <th scope="col" class="px-4 py-3 sticky left-0 bg-gray-100 z-20 border-r min-w-[100px]">Actions</th>
                    <th scope="col" class="px-4 py-3 min-w-[180px]">Product Name</th>
                    <th scope="col" class="px-4 py-3 min-w-[120px]">Main Category</th>
                    <th scope="col" class="px-4 py-3 min-w-[120px]">Sub Category</th>
                    <th scope="col" class="px-4 py-3 min-w-[100px]">Brand</th>
                    <th scope="col" class="px-4 py-3 min-w-[100px]">Supplier</th>
                    <th scope="col" class="px-4 py-3 min-w-[90px]">SKU</th>
                    <th scope="col" class="px-4 py-3 min-w-[70px]">Unit</th>
                    <th scope="col" class="px-4 py-3 min-w-[80px]">Stock</th>
                    <th scope="col" class="px-4 py-3 min-w-[80px]">Cost Price</th>
                    <th scope="col" class="px-4 py-3 min-w-[80px]">Selling Price</th>
                    <th scope="col" class="px-4 py-3 min-w-[100px]">Expiration</th>
                    <th scope="col" class="px-4 py-3 min-w-[80px]">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                        <tr class="hover:bg-gray-50 border-b border-gray-200">
                            <!-- Actions Column - Sticky -->
                            <td class="p-3 sticky left-0 bg-white z-10 border-r border-gray-200">
                                <div class="flex items-center space-x-2">
                                    <button onclick="editProduct(<?php echo $product['product_id']; ?>)" class="p-2 bg-yellow-400 rounded-lg hover:bg-yellow-500 transition duration-300">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="deleteProduct(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')" class="p-2 bg-red-400 rounded-lg hover:bg-red-500 transition duration-300">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                
                            <!-- Product Data Columns -->
                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap"><?php echo htmlspecialchars($product['product_name']); ?></td>
                            
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php 
                                $categoryInfo = $categoryLookup[$product['category_id']] ?? ['parent_name' => '', 'child_name' => ''];
                                echo !empty($categoryInfo['parent_name']) ? htmlspecialchars($categoryInfo['parent_name']) : '<span class="text-gray-400">N/A</span>';
                                ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php 
                                $categoryInfo = $categoryLookup[$product['category_id']] ?? ['parent_name' => '', 'child_name' => ''];
                                echo !empty($categoryInfo['child_name']) ? htmlspecialchars($categoryInfo['child_name']) : '<span class="text-gray-400">N/A</span>';
                                ?>
                            </td>
                
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo !empty($product['brand_name']) ? htmlspecialchars($product['brand_name']) : '<span class="text-gray-400">N/A</span>'; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo !empty($product['supplier_name']) ? htmlspecialchars($product['supplier_name']) : '<span class="text-gray-400">N/A</span>'; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo !empty($product['sku']) ? htmlspecialchars($product['sku']) : '<span class="text-gray-400">N/A</span>'; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo !empty($product['unit']) ? htmlspecialchars($product['unit']) : '<span class="text-gray-400">N/A</span>'; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="<?php 
                                    if ($product['quantity_stock'] == 0) echo 'bg-red-100 text-red-800';
                                    elseif ($product['quantity_stock'] <= 50) echo 'bg-yellow-100 text-yellow-800';
                                    else echo 'bg-green-100 text-green-800';
                                ?> px-2 py-1 rounded-full text-xs font-medium">
                                    <?php echo $product['quantity_stock']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">₱<?php echo number_format($product['cost_price'], 2); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">₱<?php echo number_format($product['selling_price'], 2); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo !empty($product['expiration_date']) ? date('M d, Y', strtotime($product['expiration_date'])) : '<span class="text-gray-400">N/A</span>'; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $product['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m8-8V4a1 1 0 00-1-1h-2a1 1 0 00-1 1v1M9 7h6"></path>
                                </svg>
                                <p class="text-lg font-medium text-gray-600">No products found</p>
                                <button onclick="addProduct()" class="mt-4 bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-2 rounded-lg font-bold hover:from-red-600 hover:to-rose-700 transition">
                                    Add First Product
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- end of descriptive table -->

<!-- Vertical visual table -->
<div id="vertical-table" class="bg-white rounded-lg shadow flex-1 overflow-hidden flex flex-col hidden">
    <!-- search input -->
    <div class="pb-4 rounded-t-lg p-4 bg-red-700 flex-shrink-0">
        <label for="table-search-vertical" class="sr-only">Search</label>
        <div class="relative mt-1">
            <div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
            </div>
            <input type="text" id="table-search-vertical" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Search for items">
        </div>

        <!-- sort by dropdown -->
        <div class="flex items-center space-x-4 mt-2 flex-shrink-0">
            <label class="text-sm font-medium text-white">Sort by:</label>
            
            <!-- Main Category Dropdown -->
            <select id="sort-main-category-vertical" class="p-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Main Categories</option>
                <?php foreach($parentCategories as $parent): ?>
                    <option value="<?php echo $parent['category_id']; ?>">
                        <?php echo htmlspecialchars($parent['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- Sub Category Dropdown -->
            <select id="sort-sub-category-vertical" class="p-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Sub Categories</option>
                <?php foreach($categories as $category): ?>
                    <?php if(!empty($category['parent_id'])): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            
            <!-- Brand Dropdown -->
            <select id="sort-brand-vertical" class="p-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Brands</option>
                <?php foreach($brands as $brand): ?>
                    <option value="<?php echo $brand['brand_id']; ?>">
                        <?php echo htmlspecialchars($brand['brand_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- Clear Filters Button -->
            <button id="clear-filters-vertical" class="p-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Clear Filters
            </button>
        </div>
    </div>
    <!-- end of search input -->

    <!-- No products message for vertical table -->
        <div id="no-products-message-vertical" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4 flex-shrink-0">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <p class="text-yellow-800">
                    There are no products yet on this category / brand. 
                    Please click on add product form to start.
                </p>
            </div>
        </div>

    <!-- Scrollable table container -->
    <div class="flex-1 overflow-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-800 uppercase bg-amber-100 bg-opacity-50 sticky top-0 z-10">
                <tr>
                    <th scope="col" class="px-6 py-3 bg-gray-100 sticky left-0 z-20">
                        <span>Edit</span>
                    </th>
                    <th scope="col" class="px-16 py-3 bg-gray-100">
                        <span>Image</span>
                    </th>
                    <th scope="col" class="px-6 py-3 bg-gray-100">
                        Product Name
                    </th>
                    <th scope="col" class="px-6 py-3 bg-gray-100">
                        Main Category
                    </th>
                    <th scope="col" class="px-6 py-3 bg-gray-100">
                        Sub Category
                    </th>
                    <th scope="col" class="px-6 py-3 bg-gray-100">
                        Brand
                    </th>
                    <th scope="col" class="px-6 py-3 bg-gray-100">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 bg-gray-100 sticky right-0 z-20">
                        <span>Delete</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach($products as $index => $product): ?>
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                            <!-- Edit Button -->
                            <td class="p-4 sticky left-0 bg-white z-10">
                                <div class="flex items-center">
                                    <button onclick="editProduct(<?php echo $product['product_id']; ?>)" class="edit-button p-2 bg-yellow-400 rounded-lg hover:bg-yellow-500 transition duration-300">
                                        <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.779 17.779 4.36 19.918 6.5 13.5m4.279 4.279 8.364-8.643a3.027 3.027 0 0 0-2.14-5.165 3.03 3.03 0 0 0-2.14.886L6.5 13.5m4.279 4.279L6.499 13.5m2.14 2.14 6.213-6.504M12.75 7.04 17 11.28"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                
                            <!-- Product Image -->
                            <td class="p-4">
                                <?php if (isset($product['product_image']) && !empty($product['product_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['product_image']) ?>" class="w-16 md:w-32 max-w-full max-h-full object-cover" alt="<?php echo htmlspecialchars($product['product_name'] ?? 'Product') ?>">
                                <?php else: ?>
                                    <div class="w-16 md:w-32 h-16 bg-gray-200 flex items-center justify-center rounded">
                                        <span class="text-gray-500 text-xs">No Image</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Product Name -->
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </td>
                
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $categoryInfo = $categoryLookup[$product['category_id']] ?? ['parent_name' => '', 'child_name' => ''];
                                echo !empty($categoryInfo['parent_name']) ? htmlspecialchars($categoryInfo['parent_name']) : '<span class="text-gray-400">N/A</span>';
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $categoryInfo = $categoryLookup[$product['category_id']] ?? ['parent_name' => '', 'child_name' => ''];
                                echo !empty($categoryInfo['child_name']) ? htmlspecialchars($categoryInfo['child_name']) : '<span class="text-gray-400">N/A</span>';
                                ?>
                            </td>
                            
                            <!-- Brand -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo !empty($product['brand_name']) ? htmlspecialchars($product['brand_name']) : '<span class="text-gray-400">N/A</span>'; ?>
                            </td>
                
                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $product['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            
                            <!-- Delete Button -->
                            <td class="p-4 sticky right-0 bg-white z-10">
                                <div class="flex items-center">
                                    <button onclick="deleteProduct(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')" class="delete-button p-2 bg-red-400 rounded-lg hover:bg-red-500 transition duration-300">
                                        <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>    
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m8-8V4a1 1 0 00-1-1h-2a1 1 0 00-1 1v1M9 7h6"></path>
                                </svg>
                                <p class="text-lg font-medium text-gray-600">No products found</p>
                                <button onclick="addProduct()" class="mt-4 bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-2 rounded-lg font-bold hover:from-red-600 hover:to-rose-700 transition">
                                    Add First Product
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- End of vertical table -->

</div>
<!-- end of table container div -->

<!-- Summary Section -->
<div class="p-6 bg-gray-100 rounded-2xl shadow w-80 flex-shrink-0">
    <h2 class="text-xl font-semibold mb-4 text-red-900">Inventory Summary</h2>
    <div class="flex gap-4 flex-col">
        <!-- NEW: Total Stock Quantity -->
        <div class="bg-blue-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-2">Total Stock Quantity</h3>
            <p class="text-2xl font-bold">
                <?php
                $totalStockQuantity = 0;
                if (!empty($products)) {
                    foreach ($products as $product) {
                        $totalStockQuantity += $product['quantity_stock'];
                    }
                }
                echo number_format($totalStockQuantity);
                ?>
            </p>
        </div>
        
        <div class="bg-green-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-2">Total Products</h3>
            <p class="text-2xl font-bold"><?php echo count($products) ?></p>
        </div>
        
        <!-- NEW: In-Stock Items -->
        <div class="bg-emerald-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-2">In-Stock Items</h3>
            <p class="text-2xl font-bold">
                <?php
                $inStockCount = 0;
                if (!empty($products)) {
                    foreach ($products as $product) {
                        if ($product['quantity_stock'] > 50) {
                            $inStockCount++;
                        }
                    }
                }
                echo $inStockCount;
                ?>
            </p>
        </div>
        
        <div class="bg-yellow-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-2">Low Stock Items</h3>
            <p class="text-2xl font-bold">
                <?php
                $lowStockCount = 0;
                if (!empty($products)) {
                    foreach ($products as $product) {
                        if ($product['quantity_stock'] > 0 && $product['quantity_stock'] <= 50) {
                            $lowStockCount++;
                        }
                    }
                }
                echo $lowStockCount;
                ?>
            </p>
        </div>
        
        <div class="bg-red-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-2">Out of Stock Items</h3>
            <p class="text-2xl font-bold">
                <?php
                $outOfStockCount = 0;
                if (!empty($products)) {
                    foreach ($products as $product) {
                        if ($product['quantity_stock'] <= 0) {
                            $outOfStockCount++;
                        }
                    }
                }
                echo $outOfStockCount;
                ?>
            </p>
        </div>
    </div>
</div>
<!-- end of summary section -->
</div>
<!-- end of overall div -->

<!-- Add/Edit Product Modal -->
<div id="productModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <!-- Modal content will be inserted here by JavaScript -->
</div>

<script>
// Store related data for forms - SEPARATE PARENT AND CHILD CATEGORIES
const parentCategories = <?php echo json_encode($parentCategories); ?>;
const childCategories = <?php echo json_encode($childCategories); ?>;
const allCategories = <?php echo json_encode($categories); ?>;
const categoryLookup = <?php echo json_encode($categoryLookup); ?>;
const brands = <?php echo json_encode($brands ?? []); ?>;
const suppliers = <?php echo json_encode($suppliers ?? []); ?>;

// Fix: Safe mapping function to prevent errors
function safeMap(array, callback) {
    if (!Array.isArray(array)) {
        console.warn('Expected array but got:', typeof array, array);
        return [];
    }
    return array.map(callback);
}

// Search functionality for both tables
function initializeSearch() {
    const searchInputHorizontal = document.getElementById('table-search-horizontal');
    const searchInputVertical = document.getElementById('table-search-vertical');
    
    function filterTable(searchTerm, tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        let hasVisibleRows = false;
        
        rows.forEach(row => {
            const textContent = row.textContent.toLowerCase();
            if (textContent.includes(searchTerm.toLowerCase())) {
                row.style.display = '';
                hasVisibleRows = true;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        const noResultsRow = table.querySelector('tbody tr td[colspan]');
        if (noResultsRow && noResultsRow.closest('tr')) {
            const noResultsTr = noResultsRow.closest('tr');
            if (!hasVisibleRows && searchTerm.length > 0) {
                noResultsTr.style.display = '';
                noResultsTr.innerHTML = `
                    <td colspan="${noResultsRow.getAttribute('colspan')}" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <p class="text-lg font-medium text-gray-600">No products found matching "${searchTerm}"</p>
                        </div>
                    </td>
                `;
            } else if (searchTerm.length === 0) {
                // Show original empty state if no search and no products
                const originalEmptyState = table.querySelector('tbody tr:first-child td[colspan]');
                if (originalEmptyState && originalEmptyState.querySelector('button[onclick*="addProduct"]')) {
                    noResultsTr.style.display = '';
                    noResultsTr.innerHTML = originalEmptyState.closest('tr').innerHTML;
                } else {
                    noResultsTr.style.display = 'none';
                }
            } else {
                noResultsTr.style.display = 'none';
            }
        }
    }
    
    if (searchInputHorizontal) {
        searchInputHorizontal.addEventListener('input', (e) => {
            filterTable(e.target.value, 'descriptive-table');
        });
    }
    
    if (searchInputVertical) {
        searchInputVertical.addEventListener('input', (e) => {
            filterTable(e.target.value, 'vertical-table');
        });
    }
}

// Image preview functionality
function initializeImagePreview() {
    // For add form
    const imageInput = document.getElementById('product_image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview');
                    const previewImage = document.getElementById('preview-image');
                    previewImage.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // For edit form
    const imageInputEdit = document.getElementById('product_image_edit');
    if (imageInputEdit) {
        imageInputEdit.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview-edit');
                    const previewImage = document.getElementById('preview-image-edit');
                    previewImage.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    }
}

// Enhanced Sorting functionality
function initializeSorting() {
    // Initialize both table filters
    initializeTableFilters('descriptive');
    initializeTableFilters('vertical');
}

function initializeTableFilters(tableType) {
    const isVertical = tableType === 'vertical';
    const prefix = isVertical ? '-vertical' : '';
    
    const mainCategorySelect = document.getElementById(`sort-main-category${prefix}`);
    const subCategorySelect = document.getElementById(`sort-sub-category${prefix}`);
    const brandSelect = document.getElementById(`sort-brand${prefix}`);
    const clearFiltersBtn = document.getElementById(`clear-filters${prefix}`);
    const noProductsMessage = document.getElementById(`no-products-message${prefix}`);
    
    if (!mainCategorySelect || !subCategorySelect || !brandSelect) return;
    
    // Store current filter state
    let currentFilters = {
        mainCategory: '',
        subCategory: '', 
        brand: ''
    };
    
    // Add event listeners to all dropdowns
    [mainCategorySelect, subCategorySelect, brandSelect].forEach(select => {
        select.addEventListener('change', function() {
            // Update current filters
            if (this.id.includes('main-category')) {
                currentFilters.mainCategory = this.value;
                // When main category changes, reset sub category if it doesn't belong to this main category
                if (this.value && currentFilters.subCategory) {
                    const subCategory = allCategories.find(cat => cat.category_id == currentFilters.subCategory);
                    if (subCategory && subCategory.parent_id != this.value) {
                        currentFilters.subCategory = '';
                        subCategorySelect.value = '';
                    }
                }
            } else if (this.id.includes('sub-category')) {
                currentFilters.subCategory = this.value;
                // When sub category is selected, auto-select its parent main category
                if (this.value) {
                    const subCategory = allCategories.find(cat => cat.category_id == this.value);
                    if (subCategory && subCategory.parent_id) {
                        currentFilters.mainCategory = subCategory.parent_id;
                        mainCategorySelect.value = subCategory.parent_id;
                    }
                }
            } else if (this.id.includes('brand')) {
                currentFilters.brand = this.value;
            }
            
            filterTableByMultipleCriteria(currentFilters, tableType);
        });
    });
    
    // Clear filters button
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            mainCategorySelect.value = '';
            subCategorySelect.value = '';
            brandSelect.value = '';
            currentFilters = { mainCategory: '', subCategory: '', brand: '' };
            filterTableByMultipleCriteria(currentFilters, tableType);
        });
    }
    
    function filterTableByMultipleCriteria(filters, tableType) {
        const isVertical = tableType === 'vertical';
        const tableId = isVertical ? 'vertical-table' : 'descriptive-table';
        const table = document.getElementById(tableId);
        
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        let hasVisibleRows = false;
        let visibleCount = 0;
        
        rows.forEach(row => {
            // Skip the no results row
            if (row.querySelector('td[colspan]')) return;
            
            let shouldShow = true;
            
            // Get product data from the row
            const rowData = getRowData(row, isVertical);
            
            // Apply main category filter
            if (filters.mainCategory && rowData.mainCategoryId != filters.mainCategory) {
                shouldShow = false;
            }
            
            // Apply sub category filter
            if (filters.subCategory && rowData.categoryId != filters.subCategory) {
                shouldShow = false;
            }
            
            // Apply brand filter
            if (filters.brand && rowData.brandId != filters.brand) {
                shouldShow = false;
            }
            
            if (shouldShow) {
                row.style.display = '';
                visibleCount++;
                hasVisibleRows = true;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide no products message
        const hasActiveFilters = filters.mainCategory || filters.subCategory || filters.brand;
        if (hasActiveFilters && !hasVisibleRows) {
            if (noProductsMessage) noProductsMessage.classList.remove('hidden');
        } else {
            if (noProductsMessage) noProductsMessage.classList.add('hidden');
        }
        
        // Update search to work with current filters
        const searchInput = isVertical ? 
            document.getElementById('table-search-vertical') : 
            document.getElementById('table-search-horizontal');
        
        if (searchInput && searchInput.value) {
            const event = new Event('input');
            searchInput.dispatchEvent(event);
        }
    }
    
    function getRowData(row, isVertical) {
        if (isVertical) {
            // For vertical table
            const cells = row.querySelectorAll('td');
            const productNameCell = cells[2]; // Product Name is 3rd cell
            const mainCategoryCell = cells[3]; // Main Category is 4th cell
            const subCategoryCell = cells[4]; // Sub Category is 5th cell
            const brandCell = cells[5]; // Brand is 6th cell
            
            const productName = productNameCell?.textContent?.trim() || '';
            const mainCategoryName = mainCategoryCell?.textContent?.trim() || '';
            const subCategoryName = subCategoryCell?.textContent?.trim() || '';
            const brandName = brandCell?.textContent?.trim() || '';
            
            // Find IDs from names
            const mainCategory = parentCategories.find(cat => 
                cat.category_name === mainCategoryName && mainCategoryName !== 'N/A'
            );
            const subCategory = allCategories.find(cat => 
                cat.category_name === subCategoryName && subCategoryName !== 'N/A'
            );
            const brand = brands.find(b => 
                b.brand_name === brandName && brandName !== 'N/A'
            );
            
            // Get product ID from edit button
            const editButton = row.querySelector('button[onclick*="editProduct"]');
            let productId = null;
            if (editButton) {
                const match = editButton.getAttribute('onclick').match(/editProduct\((\d+)/);
                if (match) productId = parseInt(match[1]);
            }
            
            return {
                productId: productId,
                productName: productName,
                mainCategoryId: mainCategory?.category_id || '',
                categoryId: subCategory?.category_id || '',
                brandId: brand?.brand_id || ''
            };
        } else {
            // For descriptive table
            const cells = row.querySelectorAll('td');
            const productNameCell = cells[1]; // Product Name is 2nd cell
            const mainCategoryCell = cells[2]; // Main Category is 3rd cell
            const subCategoryCell = cells[3]; // Sub Category is 4th cell
            const brandCell = cells[4]; // Brand is 5th cell
            
            const productName = productNameCell?.textContent?.trim() || '';
            const mainCategoryName = mainCategoryCell?.textContent?.trim() || '';
            const subCategoryName = subCategoryCell?.textContent?.trim() || '';
            const brandName = brandCell?.textContent?.trim() || '';
            
            // Find IDs from names
            const mainCategory = parentCategories.find(cat => 
                cat.category_name === mainCategoryName && mainCategoryName !== 'N/A'
            );
            const subCategory = allCategories.find(cat => 
                cat.category_name === subCategoryName && subCategoryName !== 'N/A'
            );
            const brand = brands.find(b => 
                b.brand_name === brandName && brandName !== 'N/A'
            );
            
            // Get product ID from edit button
            const editButton = row.querySelector('button[onclick*="editProduct"]');
            let productId = null;
            if (editButton) {
                const match = editButton.getAttribute('onclick').match(/editProduct\((\d+)/);
                if (match) productId = parseInt(match[1]);
            }
            
            return {
                productId: productId,
                productName: productName,
                mainCategoryId: mainCategory?.category_id || '',
                categoryId: subCategory?.category_id || '',
                brandId: brand?.brand_id || ''
            };
        }
    }
}

// Helper function to get parent category ID for a given category ID
function getParentCategoryId(categoryId) {
    const category = allCategories.find(cat => cat.category_id == categoryId);
    return category ? (category.parent_id || category.category_id) : '';
}

// Table toggle functionality
document.addEventListener('DOMContentLoaded', function () {
    initializeSearch();
    initializeSorting();
    const tableToggleBtn = document.getElementById('changetbl-btn');
    const descriptiveTable = document.getElementById('descriptive-table');
    const verticalTable = document.getElementById('vertical-table');

    if (!tableToggleBtn || !descriptiveTable || !verticalTable) return;

    // Default: show descriptive only
    descriptiveTable.classList.remove('hidden');
    verticalTable.classList.add('hidden');

    tableToggleBtn.addEventListener('click', function () {
        const showingDescriptive = !descriptiveTable.classList.contains('hidden');

        if (showingDescriptive) {
            descriptiveTable.classList.add('hidden');
            verticalTable.classList.remove('hidden');
        } else {
            descriptiveTable.classList.remove('hidden');
            verticalTable.classList.add('hidden');
        }

        tableToggleBtn.classList.toggle('rotate-90');
    });
});

// Helper function to get parent category ID for a given category ID
function getParentCategoryId(categoryId) {
    const category = allCategories.find(cat => cat.category_id == categoryId);
    return category ? (category.parent_id || category.category_id) : '';
}

// Helper function to get child categories for edit form
function getChildCategoriesForEdit(categoryId) {
    const category = allCategories.find(cat => cat.category_id == categoryId);
    if (!category) return [];
    
    const parentId = category.parent_id || category.category_id;
    return childCategories[parentId] || [];
}

// Function to populate child categories based on parent selection
function populateChildCategories(parentSelectId, childSelectId, preselectedChildId = null) {
    const parentSelect = document.getElementById(parentSelectId);
    const childSelect = document.getElementById(childSelectId);
    
    if (!parentSelect || !childSelect) return;
    
    // Clear existing options
    childSelect.innerHTML = '<option value="">Select Sub Category</option>';
    
    const parentId = parentSelect.value;
    
    if (parentId) {
        // Enable and populate child dropdown
        childSelect.disabled = false;
        const children = childCategories[parentId] || [];
        
        children.forEach(child => {
            const option = document.createElement('option');
            option.value = child.category_id;
            option.textContent = child.category_name;
            if (preselectedChildId && child.category_id == preselectedChildId) {
                option.selected = true;
            }
            childSelect.appendChild(option);
        });
    } else {
        // Disable child dropdown if no parent selected
        childSelect.disabled = true;
        childSelect.innerHTML = '<option value="">Select Main Category First</option>';
    }
}

// Setup category dropdowns for add form
function setupAddFormCategoryDropdowns() {
    const parentSelect = document.getElementById('parent_category_id');
    const childSelect = document.getElementById('category_id');
    
    if (parentSelect && childSelect) {
        parentSelect.addEventListener('change', function() {
            populateChildCategories('parent_category_id', 'category_id');
        });
    }
}

// Setup category dropdowns for edit form
function setupEditFormCategoryDropdowns(product) {
    const parentSelect = document.getElementById('parent_category_id_edit');
    const childSelect = document.getElementById('category_id_edit');
    
    if (parentSelect && childSelect) {
        parentSelect.addEventListener('change', function() {
            populateChildCategories('parent_category_id_edit', 'category_id_edit');
        });
        
        // If parent is already selected, populate children
        if (parentSelect.value) {
            populateChildCategories('parent_category_id_edit', 'category_id_edit', product.category_id);
        }
    }
}

// Image preview setup for add form
function setupAddFormImagePreview() {
    const imageInput = document.getElementById('product_image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview');
                    const previewImage = document.getElementById('preview-image');
                    previewImage.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    }
}

// Image preview setup for edit form
function setupEditFormImagePreview() {
    const imageInputEdit = document.getElementById('product_image_edit');
    if (imageInputEdit) {
        imageInputEdit.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview-edit');
                    const previewImage = document.getElementById('preview-image-edit');
                    previewImage.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    }
}

// Product Management Functions: Add function
function addProduct() {
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-red-500 to-rose-600 p-6 rounded-t-xl sticky top-0 z-10">
                <h3 class="text-xl font-bold text-white">Add New Product</h3>
            </div>
            <div class="p-6">
                <form id="addProductForm" class="space-y-4">
                    <!-- Product Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                            <input type="text" name="product_name" required 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                            <input type="text" name="sku" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Category and Brand Dropdowns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <!-- parent category dropdown -->
                            <label class="block text-sm font-medium text-gray-700 mb-2">Main Category *</label>
                                <select name="parent_category_id" id="parent_category_id" required 
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                    <option value="">Select Main Category</option>
                                    ${safeMap(parentCategories, parent => 
                                        `<option value="${parent.category_id}">${parent.category_name}</option>`
                                    ).join('')}
                                </select>
                        </div>
                        <div>
                                <!-- child category dropdown -->
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sub Category *</label>
                                <select name="category_id" id="category_id" required 
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent" disabled>
                                    <option value="">Select Main Category First</option>
                                </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                            <select name="brand_id" 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                                <option value="">Select Brand</option>
                                ${safeMap(brands, brand => 
                                    `<option value="${brand.brand_id}">${brand.brand_name}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>

                    <!-- Supplier and Unit -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                            <select name="supplier_id" 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">Select Supplier</option>
                                ${safeMap(suppliers, supplier => 
                                    `<option value="${supplier.supplier_id}">${supplier.supplier_name}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Unit</label>
                            <input type="text" name="unit" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent"
                                   placeholder="e.g., bottle, pack, piece">
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Cost Price Input -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cost Price (₱) *</label>
                            <input type="number" name="cost_price" step="0.01" min="0" required 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                onkeypress="return preventInvalidChars(event)">
                            <div class="text-red-500 text-sm mt-1 hidden" id="cost_price_error">Cost price must be a positive number</div>
                        </div>
                        <!-- Selling Price Input -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price (₱) *</label>
                            <input type="number" name="selling_price" step="0.01" min="0" required 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent"
                                onkeypress="return preventInvalidChars(event)">
                            <div class="text-red-500 text-sm mt-1 hidden" id="selling_price_error">Selling price must be a positive number</div>
                        </div>
                    </div>

                    <!-- Stock and Expiration -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Quantity Stock Input -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity Stock *</label>
                            <input type="number" name="quantity_stock" min="0" required 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                onkeypress="return preventInvalidChars(event)">
                            <div class="text-red-500 text-sm mt-1 hidden" id="quantity_stock_error">Quantity must be zero or a positive number</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiration Date</label>
                            <input type="date" name="expiration_date" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="status" value="active" checked class="rounded border-gray-300 text-red-500 focus:ring-red-500">
                            <span class="ml-2 text-sm text-gray-700">Active Product</span>
                        </label>
                    </div>

                    <!-- Product Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                        <div class="mt-1 flex items-center">
                            <input type="file" name="product_image" id="product_image" 
                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                class="hidden">
                            <label for="product_image" class="cursor-pointer">
                                <div class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 transition">
                                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">Click to upload product image</span>
                                    <span class="text-xs text-gray-500">JPG, PNG, GIF, WebP (Max 5MB)</span>
                                </div>
                            </label>
                        </div>
                        <div id="image-preview" class="mt-2 hidden">
                            <img id="preview-image" class="h-32 rounded-lg shadow-md">
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 sticky bottom-0 bg-white">
                <button type="button" onclick="closeProductModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="submitProductForm()" 
                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-lg hover:from-red-600 hover:to-rose-700 transition">
                    Add Product
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('productModal').innerHTML = modalContent;
    document.getElementById('productModal').classList.remove('hidden');
    setupAddFormImagePreview();
    setupAddFormCategoryDropdowns();
    setupSmartValidation(); 
}

function closeProductModal() {
    document.getElementById('productModal').classList.add('hidden');
}

function submitProductForm() {

    if (!validateProductForm('addProductForm')) {
        alert('Please fix the validation errors before submitting.');
        return;
    }

    const form = document.getElementById('addProductForm');
    const formData = new FormData(form);
    formData.append('action', 'add_product');
    
    fetch('product_actions.php', {
        method: 'POST',
        body: formData // No Content-Type header for FormData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added successfully!');
            closeProductModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product');
    });
}

// Product Management Functions: Edit function
function updateProduct() {

    if (!validateProductForm('editProductForm')) {
        alert('Please fix the validation errors before submitting.');
        return;
    }

    const form = document.getElementById('editProductForm');
    const formData = new FormData(form);
    formData.append('action', 'update_product');
    
    fetch('product_actions.php', {
        method: 'POST',
        body: formData // No Content-Type header for FormData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product updated successfully!');
            closeProductModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function editProduct(productId) {
    fetch('product_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_product&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openEditProductModal(data.product);
        } else {
            alert('Error loading product: ' + data.error);
        }
    });
}

function openEditProductModal(product) {

    // helper function to get child categories for edit form
    function getChildCategoriesForEdit(categoryId) {
        const category = allCategories.find(cat => cat.category_id == categoryId); 
        if (!category) return [];

        const parentId = category.parent_id || category.category_id;
        return childCategories[parentId] || [];
    }
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-rose-600 to-red-500 p-6 rounded-t-xl sticky top-0 z-10">
                <h3 class="text-xl font-bold text-white">Edit Product</h3>
            </div>
            <div class="p-6">
                <form id="editProductForm" class="space-y-4">
                    <input type="hidden" name="product_id" value="${product.product_id}">
                    
                    <!-- Product Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                            <input type="text" name="product_name" value="${product.product_name}" required 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                            <input type="text" name="sku" value="${product.sku || ''}"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Category and Brand Dropdowns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Main Category *</label>
                                <select name="parent_category_id" id="parent_category_id_edit" required 
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                    <option value="">Select Main Category</option>
                                    ${safeMap(parentCategories, parent => 
                                        `<option value="${parent.category_id}" ${getParentCategoryId(product.category_id) == parent.category_id ? 'selected' : ''}>
                                            ${parent.category_name}
                                        </option>`
                                    ).join('')}
                                </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sub Category *</label>
                            <select name="category_id" id="category_id_edit" required 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                                <option value="">Select Sub Category</option>
                                ${safeMap(getChildCategoriesForEdit(product.category_id), child => 
                                    `<option value="${child.category_id}" ${product.category_id == child.category_id ? 'selected' : ''}>
                                        ${child.category_name}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                            <select name="brand_id" 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                                <option value="">Select Brand</option>
                                ${safeMap(brands, brand => 
                                    `<option value="${brand.brand_id}" ${product.brand_id == brand.brand_id ? 'selected' : ''}>
                                        ${brand.brand_name}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>

                    <!-- Supplier and Unit -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                            <select name="supplier_id" 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">Select Supplier</option>
                                ${safeMap(suppliers, supplier => 
                                    `<option value="${supplier.supplier_id}" ${product.supplier_id == supplier.supplier_id ? 'selected' : ''}>
                                        ${supplier.supplier_name}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Unit</label>
                            <input type="text" name="unit" value="${product.unit || ''}"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent"
                                   placeholder="e.g., bottle, pack, piece">
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Cost Price Input -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cost Price (₱) *</label>
                            <input type="number" name="cost_price" step="0.01" min="0.01" required 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                oninput="validatePositiveNumber(this)"
                                onkeypress="return preventInvalidChars(event)"
                                value="${product.cost_price !== undefined && product.cost_price !== null ? Number(product.cost_price).toFixed(2) : ''}">
                            <div class="text-red-500 text-sm mt-1 hidden" id="cost_price_error">Cost price must be a positive number</div>
                        </div>
                        <!-- Selling Price Input -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price (₱) *</label>
                            <input type="number" name="selling_price" step="0.01" min="0.01" required 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent"
                                oninput="validatePositiveNumber(this)"
                                onkeypress="return preventInvalidChars(event)"
                                value="${product.selling_price !== undefined && product.selling_price !== null ? Number(product.selling_price).toFixed(2) : ''}">
                            <div class="text-red-500 text-sm mt-1 hidden" id="selling_price_error">Selling price must be a positive number</div>
                        </div>
                    </div>

                    <!-- Stock and Expiration -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Quantity Stock Input -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity Stock *</label>
                            <input type="number" name="quantity_stock" min="0" required 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                oninput="validatePositiveNumber(this)"
                                onkeypress="return preventInvalidChars(event)"
                                value="${product.quantity_stock !== undefined && product.quantity_stock !== null ? product.quantity_stock : ''}">
                            <div class="text-red-500 text-sm mt-1 hidden" id="quantity_stock_error">Quantity must be zero or a positive number</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiration Date</label>
                            <input type="date" name="expiration_date" value="${product.expiration_date || ''}"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="status" value="active" ${product.status == 'active' ? 'checked' : ''} 
                                   class="rounded border-gray-300 text-red-500 focus:ring-red-500">
                            <span class="ml-2 text-sm text-gray-700">Active Product</span>
                        </label>
                    </div>

                    <!-- Product Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                        <div class="mb-4">
                            ${product.product_image ? 
                                `<img src="${product.product_image}" class="h-32 rounded-lg shadow-md mb-2" alt="Current product image">
                                <p class="text-sm text-gray-600">Current image</p>` :
                                `<div class="h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-500">No image uploaded</span>
                                </div>`
                            }
                        </div>
                        <div class="mt-1 flex items-center">
                            <input type="file" name="product_image" id="product_image_edit" 
                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                class="hidden">
                            <label for="product_image_edit" class="cursor-pointer">
                                <div class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 transition">
                                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">Click to change product image</span>
                                    <span class="text-xs text-gray-500">JPG, PNG, GIF, WebP (Max 5MB)</span>
                                </div>
                            </label>
                        </div>
                        <div id="image-preview-edit" class="mt-2 hidden">
                            <img id="preview-image-edit" class="h-32 rounded-lg shadow-md">
                            <p class="text-sm text-green-600 mt-1">New image preview</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 sticky bottom-0 bg-white">
                <button type="button" onclick="closeProductModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="updateProduct()" 
                        class="px-4 py-2 bg-gradient-to-r from-rose-600 to-red-500 text-white rounded-lg hover:from-rose-700 hover:to-red-600 transition">
                    Update Product
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('productModal').innerHTML = modalContent;
    document.getElementById('productModal').classList.remove('hidden');
    setupEditFormImagePreview();
    setupEditFormCategoryDropdowns(product);
    setupSmartValidation(); // Add this line
}

// Product Management Functions: Delete function
function deleteProduct(productId, productName) {
    if (confirm(`Are you sure you want to delete "${productName}"? This cannot be undone.`)) {
        fetch('product_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=delete_product&product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}

// Prevent typing of +, -, e, E in number inputs
function preventInvalidChars(event) {
    const invalidChars = ['-', '+', 'e', 'E'];
    if (invalidChars.includes(event.key)) {
        event.preventDefault();
        showFieldError(event.target, `"${event.key}" is not allowed`);
        return false;
    }
    return true;
}

// Smart validation for prices (cannot be 0 or start with multiple zeros)
function validatePrice(input) {
    const rawValue = input.value;
    const numericValue = parseFloat(rawValue);
    const errorId = input.name + '_error';
    
    // Allow empty for better UX while typing
    if (rawValue === '') {
        hideFieldError(input);
        return true;
    }
    
    // Check for multiple zeros like "000", "0000" etc.
    if (/^0+$/.test(rawValue) || /^0+0/.test(rawValue)) {
        showFieldError(input, 'Price must be greater than 0');
        return false;
    }
    
    // Check if it's a valid number and greater than 0
    if (isNaN(numericValue) || numericValue <= 0) {
        showFieldError(input, 'Price must be greater than 0');
        return false;
    }
    
    // Check for values like "0.000" which are effectively 0
    if (numericValue === 0) {
        showFieldError(input, 'Price must be greater than 0');
        return false;
    }
    
    hideFieldError(input);
    return true;
}

// Smart validation for quantity (can be 0 but not multiple zeros)
function validatePositiveNumber(input) {
    const rawValue = input.value;
    const numericValue = parseFloat(rawValue);
    const errorId = input.name + '_error';
    
    if (rawValue === '') {
        hideFieldError(input);
        return true;
    }
    
    // Check for multiple zeros like "000", "0000" etc.
    if (/^0+$/.test(rawValue) && rawValue !== '0') {
        showFieldError(input, 'Please enter a valid number (0 or positive)');
        return false;
    }
    
    if (isNaN(numericValue) || numericValue < 0) {
        showFieldError(input, 'Please enter a positive number or zero');
        return false;
    }
    
    hideFieldError(input);
    return true;
}

// Real-time validation that also handles paste events
function setupSmartValidation() {
    const priceInputs = document.querySelectorAll('input[name="cost_price"], input[name="selling_price"]');
    const quantityInputs = document.querySelectorAll('input[name="quantity_stock"]');
    
    // Add input event for real-time validation
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            validatePrice(this);
        });
        input.addEventListener('blur', function() {
            // Format the value on blur to remove leading zeros
            if (this.value && parseFloat(this.value) > 0) {
                this.value = parseFloat(this.value).toString();
            }
        });
    });
    
    quantityInputs.forEach(input => {
        input.addEventListener('input', function() {
            validatePositiveNumber(this);
        });
        input.addEventListener('blur', function() {
            // Format the value on blur to remove leading zeros
            if (this.value && !isNaN(parseFloat(this.value))) {
                this.value = parseFloat(this.value).toString();
            }
        });
    });
}

// Show field error with smooth animation
function showFieldError(input, message) {
    const errorId = input.name + '_error';
    const errorElement = document.getElementById(errorId);
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
        input.classList.add('border-red-500', 'bg-red-50');
        input.classList.remove('border-gray-300');
    }
}

// Hide field error
function hideFieldError(input) {
    const errorId = input.name + '_error';
    const errorElement = document.getElementById(errorId);
    
    if (errorElement) {
        errorElement.classList.add('hidden');
        input.classList.remove('border-red-500', 'bg-red-50');
        input.classList.add('border-gray-300');
    }
}

// Enhanced form validation before submission
function validateProductForm(formId) {
    const form = document.getElementById(formId);
    const priceInputs = form.querySelectorAll('input[name="cost_price"], input[name="selling_price"]');
    const quantityInputs = form.querySelectorAll('input[name="quantity_stock"]');
    let isValid = true;
    
    // Validate prices (cannot be 0)
    priceInputs.forEach(input => {
        if (!validatePrice(input)) {
            isValid = false;
        }
    });
    
    // Validate quantity (can be 0)
    quantityInputs.forEach(input => {
        if (!validatePositiveNumber(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Close modal when clicking outside
document.getElementById('productModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeProductModal();
    }
});
</script>