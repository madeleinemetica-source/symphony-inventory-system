<?php
// Prevent direct access to this file
defined('ACCESSED_THROUGH_DASHBOARD') or die('This file cannot be accessed directly.');

// SUPPRESS ERRORS to prevent breaking JavaScript
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

// Get all categories with hierarchy and product counts
$categories = [];
$total_products = 0;

try {
    // Create database connection properly
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Get total product count
        $total_stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
        $total_products = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get categories with product counts and hierarchy
        // For main categories, count products in the main category AND all its subcategories
        // For subcategories, count products directly assigned to that subcategory
        $stmt = $db->query("
            SELECT 
                c.*,
                COUNT(DISTINCT p.product_id) as product_count,
                parent.category_name as parent_name
            FROM categories c
            LEFT JOIN products p ON (
                c.category_id = p.category_id 
                OR (c.parent_id IS NULL AND p.category_id IN (
                    SELECT category_id FROM categories WHERE parent_id = c.category_id
                ))
            ) AND p.status = 'active'
            LEFT JOIN categories parent ON c.parent_id = parent.category_id
            GROUP BY c.category_id
            ORDER BY c.parent_id IS NULL DESC, c.parent_id, c.category_name
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Database error in manage_categories.php: " . $e->getMessage());
}

// Separate parent and child categories
$parent_categories = array_filter($categories, function($cat) {
    return $cat['parent_id'] === null;
});

$child_categories = array_filter($categories, function($cat) {
    return $cat['parent_id'] !== null;
});

// DEBUG: Output to JavaScript console
echo "<script>";
echo "console.log('=== PHP DEBUG: manage_categories.php ===');";
echo "console.log('Total categories from DB:', " . json_encode(count($categories)) . ");";
echo "console.log('Parent categories count:', " . json_encode(count($parent_categories)) . ");";
echo "console.log('Child categories count:', " . json_encode(count($child_categories)) . ");";
echo "console.log('Parent categories:', " . json_encode($parent_categories) . ");";
echo "console.log('Child categories:', " . json_encode($child_categories) . ");";
echo "</script>";
?>

<!-- overall div -->
<div class="flex p-6 gap-10 items-start bg-white bg-opacity-0">

<!-- Main Content -->
<div class="flex-1">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-2xl shadow">
        <div class="flex justify-between items-center space-x-4">
            <div>
                <h1 class="text-2xl font-bold text-white">Category Management</h1>
                <p class="text-amber-200">Organize your products with categories and subcategories</p>
            </div>
            <button onclick="openAddMainCategoryModal()" class="bg-yellow-300 text-[#CE1126] px-6 py-3 rounded-lg font-bold hover:bg-yellow-400 transition duration-300 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Add New Main Category</span>
            </button>
            <button onclick="openAddSubCategoryModal()" class="bg-yellow-300 text-[#CE1126] px-6 py-3 rounded-lg font-bold hover:bg-yellow-400 transition duration-300 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Add New Sub Category</span>
            </button>
        </div>
    </div>

    <!-- Category Mode Filter -->
    <div class="bg-white p-4 shadow-sm border-b">
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <span class="text-gray-700 font-medium">Category Mode:</span>
                <select id="categoryMode" onchange="toggleCategoryMode()" 
                        class="bg-gray-50 border border-gray-300 text-gray-800 px-4 py-2 rounded-lg focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all duration-300">
                    <option value="main">Main Categories</option>
                    <option value="sub">Sub Categories</option>
                </select>
            </div>
            
            <!-- Main Category Filter (hidden by default) -->
            <div id="mainCategoryFilter" class="hidden transition-all duration-300">
                <div class="flex items-center space-x-2">
                    <span class="text-gray-700 font-medium">Filter by Main Category:</span>
                    <select id="mainCategorySelect" onchange="filterSubCategories()" 
                            class="bg-gray-50 border border-gray-300 text-gray-800 px-4 py-2 rounded-lg focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                        <option value="all">All Main Categories</option>
                        <?php foreach($parent_categories as $parent): ?>
                            <option value="<?php echo $parent['category_id']; ?>">
                                <?php echo htmlspecialchars($parent['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-b-2xl shadow">
    <!-- Main Categories Grid -->
    <div id="mainCategoriesSection" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php if (!empty($categories)): ?>
            <?php foreach($parent_categories as $parent): ?>
                <!-- Parent Category Card -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-yellow-500 hover:shadow-xl transition-all duration-300">
                    <div class="p-6">
                        <!-- Category Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-red-500 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                    <?php echo strtoupper(substr($parent['category_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($parent['category_name']); ?></h3>
                                    <p class="text-sm text-gray-600">Main Category</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                <?php echo $parent['product_count']; ?> Products
                            </span>
                        </div>

                        <!-- Category Description -->
                        <?php if (!empty($parent['description'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($parent['description']); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Subcategories -->
                        <?php 
                        $subcategories = array_filter($child_categories, function($child) use ($parent) {
                            return $child['parent_id'] == $parent['category_id'];
                        });
                        ?>
                        
                        <?php if (!empty($subcategories)): ?>
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Subcategories:</h4>
                                <div class="space-y-2">
                                    <?php foreach($subcategories as $subcat): ?>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600"><?php echo htmlspecialchars($subcat['category_name']); ?></span>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                                                <?php echo $subcat['product_count']; ?> products
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="flex space-x-2">
                            <button onclick="editCategory(<?php echo $parent['category_id']; ?>)" 
                                    class="flex-1 bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit</span>
                            </button>
                            <button onclick="deleteCategory(<?php echo $parent['category_id']; ?>, '<?php echo htmlspecialchars($parent['category_name']); ?>')" 
                                    class="flex-1 bg-red-500 text-white py-2 rounded-lg hover:bg-red-700 transition flex items-center justify-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-12">
                <div class="w-24 h-24 bg-gradient-to-r from-red-600 to-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No Categories Found</h3>
                <p class="text-gray-600 mb-4">Start organizing your products by creating categories.</p>
                <button onclick="openAddCategoryModal()" class="bg-gradient-to-r from-red-600 to-amber-500 text-white px-6 py-3 rounded-lg font-bold hover:from-red-700 hover:to-amber-600 transition">
                    Create First Category
                </button>
            </div>
        <?php endif; ?>
    </div> <!-- Close mainCategoriesSection -->

    <!-- Sub Categories Grid (Hidden by default, shown when "Sub Categories" mode selected) -->
    <div id="subCategoriesSection" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 hidden">
        <?php if (!empty($child_categories)): ?>
            <?php foreach($child_categories as $subcat): ?>
                <!-- Sub Category Card -->
                <div class="sub-category-card bg-white rounded-xl shadow-lg border-l-4 border-yellow-500 hover:shadow-xl transition-all duration-300" 
                     data-parent-id="<?php echo $subcat['parent_id']; ?>">
                    <div class="p-6">
                        <!-- Sub Category Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-red-500 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                    <?php echo strtoupper(substr($subcat['category_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($subcat['category_name']); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php 
                                        $parent_name = !empty($subcat['parent_name']) ? $subcat['parent_name'] : 'Unknown Parent';
                                        echo "Under " . htmlspecialchars($parent_name);
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                <?php echo $subcat['product_count']; ?> Products
                            </span>
                        </div>

                        <!-- Sub Category Description -->
                        <?php if (!empty($subcat['description'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($subcat['description']); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="flex space-x-2">
                            <button onclick="editSubCategory(<?php echo $subcat['category_id']; ?>)" 
                                    class="flex-1 bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit</span>
                            </button>
                            <button onclick="deleteSubCategory(<?php echo $subcat['category_id']; ?>, '<?php echo htmlspecialchars($subcat['category_name']); ?>')" 
                                    class="flex-1 bg-red-500 text-white py-2 rounded-lg hover:bg-red-700 transition flex items-center justify-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-12">
                <div class="w-24 h-24 bg-gradient-to-r from-amber-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No Sub Categories Found</h3>
                <p class="text-gray-600 mb-4">Create subcategories under main categories.</p>
                <button onclick="openAddSubCategoryModal()" class="bg-gradient-to-r from-amber-500 to-red-600 text-white px-6 py-3 rounded-lg font-bold hover:from-amber-600 hover:to-red-700 transition">
                    Create First Sub Category
                </button>
            </div>
        <?php endif; ?>
    </div> <!-- Close subCategoriesSection -->
    </div> <!-- close bg-white p-6 shadow container -->
    </div> <!-- close MainContent flex-1 -->

    <!-- Right Sidebar - Category Insights -->
    <div class="w-80">
    <div class="bg-gradient-to-b from-[#FCD116] to-[#CE1126] p-6 rounded-2xl shadow">
        <h2 class="text-xl font-bold text-white mb-4">Category Insights</h2>
        
        <!-- Total Products -->
        <div class="bg-white bg-opacity-20 p-4 rounded-lg backdrop-blur-sm mb-4">
            <h3 class="text-white text-lg font-medium mb-2">Total Products</h3>
            <p class="text-3xl font-bold text-white"><?php echo $total_products; ?></p>
        </div>

        <!-- Category Distribution -->
        <div class="bg-white bg-opacity-20 p-4 rounded-lg backdrop-blur-sm">
            <h3 class="text-white text-lg font-medium mb-4">Product Distribution</h3>
            <div class="space-y-3">
                <?php if ($total_products > 0): ?>
                    <?php foreach($parent_categories as $parent): ?>
                        <?php 
                        $percentage = $parent['product_count'] > 0 ? ($parent['product_count'] / $total_products) * 100 : 0;
                        ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm text-white"><?php echo htmlspecialchars($parent['category_name']); ?></span>
                                <span class="text-sm font-medium text-white"><?php echo round($percentage, 1); ?>%</span>
                            </div>
                            <div class="w-full bg-white bg-opacity-30 rounded-full h-2">
                                <div class="bg-cyan-100 h-2 rounded-full transition-all duration-500" 
                                     style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-white text-sm text-center">No products yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white bg-opacity-20 p-4 rounded-lg backdrop-blur-sm mt-4">
            <h3 class="text-white text-lg font-medium mb-3">Quick Stats</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-white">Total Categories:</span>
                    <span class="font-medium text-white"><?php echo count($categories); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-white">Main Categories:</span>
                    <span class="font-medium text-white"><?php echo count($parent_categories); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-white">Subcategories:</span>
                    <span class="font-medium text-white"><?php echo count($child_categories); ?></span>
                </div>
            </div>
        </div>
    </div>
    </div> <!-- close Right Sidebar -->
</div> <!-- end of overall flex container -->

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <!-- Modal content will be inserted here by JavaScript -->
</div>

<script>

// Toggle between Main and Sub categories
function toggleCategoryMode() {
    const mode = document.getElementById('categoryMode').value;
    const mainSection = document.getElementById('mainCategoriesSection');
    const subSection = document.getElementById('subCategoriesSection');
    const filterContainer = document.getElementById('mainCategoryFilter');
    
    console.log('=== DEBUG: toggleCategoryMode ===');
    console.log('Selected mode:', mode);
    console.log('mainCategoriesSection:', mainSection);
    console.log('subCategoriesSection:', subSection);
    console.log('subCategoriesSection HTML:', subSection ? subSection.innerHTML : 'NULL');
    console.log('Number of .sub-category-card elements:', document.querySelectorAll('.sub-category-card').length);
    
    if (mode === 'main') {
        // Show main categories
        mainSection.classList.remove('hidden');
        // Hide sub categories
        subSection.classList.add('hidden');
        // Hide filter
        filterContainer.classList.add('hidden');
    } else {
        // Hide main categories
        mainSection.classList.add('hidden');
        // Show sub categories
        subSection.classList.remove('hidden');
        // Show filter
        filterContainer.classList.remove('hidden');
        // Trigger filter to show initial state
        filterSubCategories();
    }
}

// Filter subcategories by main category
function filterSubCategories() {
    const selectedParentId = document.getElementById('mainCategorySelect').value;
    const subCategoryCards = document.querySelectorAll('.sub-category-card');
    
    console.log('=== DEBUG: filterSubCategories ===');
    console.log('Selected Parent ID:', selectedParentId);
    console.log('Total sub-category cards found:', subCategoryCards.length);
    console.log('Sub-category cards:', subCategoryCards);
    
    subCategoryCards.forEach((card, index) => {
        const parentId = card.getAttribute('data-parent-id');
        console.log(`Card ${index}: data-parent-id="${parentId}"`);
        if (selectedParentId === 'all' || parentId === selectedParentId) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Wrapper to open Add Main Category modal (header button uses this name)
function openAddMainCategoryModal() {
    openAddCategoryModal(true); // true = disable parent dropdown
}

// Wrapper to open Add Sub Category modal (header button uses this name)
function openAddSubCategoryModal() {
    openAddCategoryModal(false); // false = enable parent dropdown, preselect first parent
}

// Category Management Functions - handles both main and sub category modals
function openAddCategoryModal(disableParent = false) {
    const parentCategories = <?php echo json_encode($parent_categories); ?>;
    
    const disabledAttr = disableParent ? 'disabled' : '';
    const bgClass = disableParent ? 'bg-gray-100 cursor-not-allowed' : '';
    const modalTitle = disableParent ? 'Add New Main Category' : 'Add New Sub Category';
    const buttonText = disableParent ? 'Add Main Category' : 'Add Sub Category';
    
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">${modalTitle}</h3>
            </div>
            <div class="p-6">
                <form id="addCategoryForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                        <input type="text" name="category_name" required 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0038A8] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
                        <select name="parent_id" ${disabledAttr} class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#CE1126] focus:border-transparent ${bgClass}">
                            <option value="">Main Category (No Parent)</option>
                            ${parentCategories.map(cat => 
                                `<option value="${cat.category_id}">${cat.category_name}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FCD116] focus:border-transparent"
                                  placeholder="Describe this category..."></textarea>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeCategoryModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="submitCategoryForm()" 
                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-red-600 text-white rounded-lg hover:from-amber-600 hover:to-red-700 transition">
                    ${buttonText}
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('categoryModal').innerHTML = modalContent;
    document.getElementById('categoryModal').classList.remove('hidden');
    
    // If opening sub-category modal, preselect first parent
    if (!disableParent) {
        setTimeout(() => {
            const sel = document.querySelector('#categoryModal select[name="parent_id"]');
            if (sel) {
                const opt = Array.from(sel.options).find(o => o.value !== '');
                if (opt) sel.value = opt.value;
            }
        }, 50);
    }
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

function submitCategoryForm() {
    const form = document.getElementById('addCategoryForm');
    const formData = new FormData(form);
    formData.append('action', 'add_category');
    
    fetch('category_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Category added successfully!');
            closeCategoryModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding category');
    });
}

function editCategory(categoryId) {
    fetch('category_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_category&category_id=${categoryId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openEditCategoryModal(data.category);
        } else {
            alert('Error loading category: ' + data.error);
        }
    });
}

function openEditCategoryModal(category) {
    const parentCategories = <?php echo json_encode($parent_categories); ?>;
    
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">Edit Category</h3>
            </div>
            <div class="p-6">
                <form id="editCategoryForm" class="space-y-4">
                    <input type="hidden" name="category_id" value="${category.category_id}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                        <input type="text" name="category_name" value="${category.category_name}" required 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0038A8] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
                        <select name="parent_id" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#CE1126] focus:border-transparent">
                            <option value="">Main Category (No Parent)</option>
                            ${parentCategories.map(cat => 
                                `<option value="${cat.category_id}" ${category.parent_id == cat.category_id ? 'selected' : ''}>
                                    ${cat.category_name}
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FCD116] focus:border-transparent"
                                  placeholder="Describe this category...">${category.description || ''}</textarea>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeCategoryModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="updateCategory()" 
                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-red-600 text-white rounded-lg hover:from-amber-600 hover:to-red-700 transition">
                    Update Category
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('categoryModal').innerHTML = modalContent;
    document.getElementById('categoryModal').classList.remove('hidden');
}

// Replace placeholder functions for subcategories with real CRUD
function editSubCategory(categoryId) {
    fetch('category_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_category&category_id=${categoryId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openEditSubCategoryModal(data.category);
        } else {
            alert('Error loading category: ' + data.error);
        }
    });
}

function openEditSubCategoryModal(category) {
    const parentCategories = <?php echo json_encode($parent_categories); ?>;
    
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">Edit Sub Category</h3>
            </div>
            <div class="p-6">
                <form id="editSubCategoryForm" class="space-y-4">
                    <input type="hidden" name="category_id" value="${category.category_id}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                        <input type="text" name="category_name" value="${category.category_name}" required 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0038A8] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category *</label>
                        <select name="parent_id" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#CE1126] focus:border-transparent">
                            <option value="">Select Parent Category</option>
                            ${parentCategories.map(cat => 
                                `<option value="${cat.category_id}" ${category.parent_id == cat.category_id ? 'selected' : ''}>
                                    ${cat.category_name}
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FCD116] focus:border-transparent"
                                  placeholder="Describe this category...">${category.description || ''}</textarea>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeCategoryModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="updateSubCategory()" 
                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-red-600 text-white rounded-lg hover:from-amber-600 hover:to-red-700 transition">
                    Update Sub Category
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('categoryModal').innerHTML = modalContent;
    document.getElementById('categoryModal').classList.remove('hidden');
}

function updateSubCategory() {
    const form = document.getElementById('editSubCategoryForm');
    const formData = new FormData(form);
    formData.append('action', 'update_category');
    
    fetch('category_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sub category updated successfully!');
            closeCategoryModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function deleteSubCategory(categoryId, categoryName) {
    showSubCategoryDeleteConfirmation(categoryId, categoryName);
}

function showSubCategoryDeleteConfirmation(categoryId, categoryName) {
    const confirmationMessage = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">⚠️ Delete Sub Category</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Confirm Deletion</h4>
                        <p class="text-sm text-gray-600">This action will delete associated products</p>
                    </div>
                </div>

                <p class="text-gray-700 mb-4">You are about to delete the sub category "<strong>${categoryName}</strong>"</p>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-red-700 text-sm font-medium">This action will permanently delete:</p>
                    <ul class="text-red-600 text-sm mt-2 space-y-1">
                        <li>• The sub category "${categoryName}"</li>
                        <li>• All products assigned to this sub category</li>
                    </ul>
                </div>

                <p class="text-gray-700 text-sm mb-4">Type <strong>"DELETE ${categoryName.toUpperCase()}"</strong> to confirm:</p>
                
                <input type="text" id="confirmDeleteSubText" placeholder="Type DELETE ${categoryName.toUpperCase()} here" 
                       class="w-full p-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeSubCategoryDeleteModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmSubCategoryDelete(${categoryId}, '${categoryName.replace(/'/g, "\\'")}')" id="finalSubDeleteBtn" disabled
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Delete Sub Category
                    </button>
                </div>
            </div>
        </div>
    `;
    
    let modal = document.getElementById('subCategoryDeleteModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'subCategoryDeleteModal';
        modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeSubCategoryDeleteModal();
        });
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = confirmationMessage;
    modal.classList.remove('hidden');
    
    const confirmInput = document.getElementById('confirmDeleteSubText');
    const finalBtn = document.getElementById('finalSubDeleteBtn');
    
    confirmInput.addEventListener('input', function() {
        finalBtn.disabled = this.value !== `DELETE ${categoryName.toUpperCase()}`;
    });
}

function closeSubCategoryDeleteModal() {
    const modal = document.getElementById('subCategoryDeleteModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function confirmSubCategoryDelete(categoryId, categoryName) {
    const confirmInput = document.getElementById('confirmDeleteSubText');
    if (confirmInput.value !== 'DELETE ' + categoryName.toUpperCase()) {
        alert('Please type "DELETE ' + categoryName.toUpperCase() + '" exactly as shown to proceed.');
        return;
    }

    fetch('category_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete_category&category_id=${categoryId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sub category "' + categoryName + '" and all associated products deleted successfully!');
            closeSubCategoryDeleteModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
            closeSubCategoryDeleteModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting sub category');
        closeSubCategoryDeleteModal();
    });
}

function updateCategory() {
    const form = document.getElementById('editCategoryForm');
    const formData = new FormData(form);
    formData.append('action', 'update_category');
    
    fetch('category_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Category updated successfully!');
            closeCategoryModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function deleteCategory(categoryId, categoryName) {
    // First, check if main category has subcategories or products
    fetch('category_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete_category&category_id=${categoryId}&delete_with_children=false`
    })
    .then(response => response.json())
    .then(data => {
        if (data.error === 'main_has_subcategories') {
            // Main category has subcategories - show warning modal
            showMainCategoryDeleteConfirmation(categoryId, categoryName, data.subcategory_count, data.product_count);
        } else if (data.success) {
            // No subcategories, proceed with simple deletion
            alert('Category deleted successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error checking category');
    });
}

function showMainCategoryDeleteConfirmation(categoryId, categoryName, subcategoryCount, productCount) {
    const confirmationMessage = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">⚠️ Delete Main Category</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Critical Warning</h4>
                        <p class="text-sm text-gray-600">This category has subcategories and products</p>
                    </div>
                </div>

                <p class="text-gray-700 mb-4">You are about to delete "<strong>${categoryName}</strong>" which has:</p>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-red-700 text-sm font-medium">This action will permanently delete:</p>
                    <ul class="text-red-600 text-sm mt-2 space-y-1">
                        <li>• The main category "${categoryName}"</li>
                        <li>• ${subcategoryCount} subcategory(ies)</li>
                        <li>• ${productCount} product(s)</li>
                    </ul>
                </div>

                <p class="text-gray-700 text-sm mb-4">Type <strong>"DELETE ${categoryName.toUpperCase()}"</strong> to confirm:</p>
                
                <input type="text" id="confirmDeleteText" placeholder="Type DELETE ${categoryName.toUpperCase()} here" 
                       class="w-full p-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCategoryDeleteModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmMainCategoryDelete(${categoryId}, '${categoryName.replace(/'/g, "\\'")}')" id="finalDeleteBtn" disabled
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Delete Category & Subcategories
                    </button>
                </div>
            </div>
        </div>
    `;
    
    let modal = document.getElementById('deleteConfirmationModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'deleteConfirmationModal';
        modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeCategoryDeleteModal();
        });
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = confirmationMessage;
    modal.classList.remove('hidden');
    
    const confirmInput = document.getElementById('confirmDeleteText');
    const finalBtn = document.getElementById('finalDeleteBtn');
    
    confirmInput.addEventListener('input', function() {
        finalBtn.disabled = this.value !== `DELETE ${categoryName.toUpperCase()}`;
    });
}

function closeCategoryDeleteModal() {
    const modal = document.getElementById('deleteConfirmationModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function confirmMainCategoryDelete(categoryId, categoryName) {
    const confirmInput = document.getElementById('confirmDeleteText');
    if (confirmInput.value !== 'DELETE ' + categoryName.toUpperCase()) {
        alert('Please type "DELETE ' + categoryName.toUpperCase() + '" exactly as shown to proceed.');
        return;
    }

    fetch('category_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete_category&category_id=${categoryId}&delete_with_children=true`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Category "' + categoryName + '", ' + data.deleted_subcategories + ' subcategory(ies), and ' + data.deleted_products + ' product(s) deleted successfully!');
            closeCategoryDeleteModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
            closeCategoryDeleteModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting category');
        closeCategoryDeleteModal();
    });
}

// Close modal when clicking outside
document.getElementById('categoryModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCategoryModal();
    }
});
</script>