<?php
// Prevent direct access to this file
defined('ACCESSED_THROUGH_DASHBOARD') or die('This file cannot be accessed directly.');

// SUPPRESS ERRORS to prevent breaking JavaScript
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

// Safe database fetching for brands with supplier info
$brands = [];
try {
    if (isset($db) && $db) {
        $stmt = $db->query("
            SELECT b.*, s.supplier_name, s.contact_person, s.email, s.phone 
            FROM brands b 
            LEFT JOIN suppliers s ON b.supplier_id = s.supplier_id
            ORDER BY b.brand_name
        ");
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Get suppliers for dropdown
$suppliers = [];
try {
    if (isset($db) && $db) {
        $stmt = $db->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status = 'active' ORDER BY supplier_name");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}
?>
<!-- overall div -->
<div class="flex p-6 gap-10 items-start bg-white bg-opacity-0">

<!-- Main Content -->
<div class="flex-1">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-2xl shadow">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-white">Brand Management</h1>
                <p class="text-amber-200">Manage your product brands and suppliers</p>
            </div>
            <button onclick="openAddBrandModal()" class="bg-yellow-300 text-[#CE1126] px-6 py-3 rounded-lg font-bold hover:bg-yellow-400 transition duration-300 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Add New Brand</span>
            </button>
        </div>
    </div>

    <!-- Brands Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 bg-gray-100 p-6 rounded-b-2xl shadow">
        <?php if (!empty($brands)): ?>
            <?php foreach($brands as $brand): ?>
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-yellow-500 hover:shadow-xl transition-all duration-300 group">
                    <div class="p-6">
                        <!-- Brand Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <?php if (!empty($brand['brand_logo'])): ?>
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center overflow-hidden bg-gray-100">
                                        <img src="<?php echo htmlspecialchars($brand['brand_logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($brand['brand_name']); ?> Logo" 
                                             class="w-full h-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-red-500 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                        <?php echo strtoupper(substr($brand['brand_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($brand['brand_name']); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php 
                                        // Get product count for this brand
                                        $product_count = 0;
                                        try {
                                            $count_stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
                                            $count_stmt->execute([$brand['brand_id']]);
                                            $product_count = $count_stmt->fetchColumn();
                                        } catch (Exception $e) {
                                            $product_count = 0;
                                        }
                                        echo $product_count . ' Product' . ($product_count != 1 ? 's' : '');
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <span class="px-3 py-1 <?php echo ($brand['status'] == 'active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded-full text-xs font-medium">
                                <?php echo ucfirst($brand['status']); ?>
                            </span>
                        </div>

                        <!-- Supplier Info -->
                        <div class="space-y-2 text-sm mb-4">
                            <?php if (!empty($brand['supplier_name'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Supplier:</span>
                                    <span class="font-medium text-[#0038A8]"><?php echo htmlspecialchars($brand['supplier_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($brand['contact_person'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Contact:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($brand['contact_person']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($brand['email'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-medium text-[#CE1126]"><?php echo htmlspecialchars($brand['email']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Brand Description -->
                        <?php if (!empty($brand['description'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($brand['description']); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="flex space-x-2">
                            <button onclick="editBrand(<?php echo $brand['brand_id']; ?>)" 
                                    class="flex-1 bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit</span>
                            </button>
                            <button onclick="deleteBrand(<?php echo $brand['brand_id']; ?>, '<?php echo htmlspecialchars($brand['brand_name']); ?>')" 
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
                <h3 class="text-xl font-bold text-gray-800 mb-2">No Brands Found</h3>
                <p class="text-gray-600 mb-4">Get started by adding your first brand to the system.</p>
                <button onclick="openAddBrandModal()" class="bg-gradient-to-r from-amber-500 to-red-600 text-white px-6 py-3 rounded-lg font-bold hover:from-amber-600 hover:to-red-600 transition">
                    Add Your First Brand
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Section -->
<div class="w-80">
    <div class="bg-gradient-to-b from-[#FCD116] to-[#CE1126] p-6 rounded-2xl shadow">
        <h2 class="text-xl font-bold text-white mb-4">Brands Summary</h2>
        <div class="space-y-4">
            <div class="bg-white bg-opacity-20 p-4 rounded-lg backdrop-blur-sm">
                <h3 class="text-white text-lg font-medium mb-2">Total Brands</h3>
                <p class="text-3xl font-bold text-white"><?php echo count($brands); ?></p>
            </div>
            <div class="bg-white bg-opacity-20 p-4 rounded-lg backdrop-blur-sm">
                <h3 class="text-white text-lg font-medium mb-2">Active Brands</h3>
                <p class="text-3xl font-bold text-white">
                    <?php
                    $active_count = 0;
                    foreach ($brands as $brand) {
                        if ($brand['status'] == 'active') $active_count++;
                    }
                    echo $active_count;
                    ?>
                </p>
            </div>
            <div class="bg-white bg-opacity-20 p-4 rounded-lg backdrop-blur-sm">
                <h3 class="text-white text-lg font-medium mb-2">Unique Suppliers</h3>
                <p class="text-3xl font-bold text-white">
                    <?php
                    $unique_suppliers = [];
                    foreach ($brands as $brand) {
                        if ($brand['supplier_id']) {
                            $unique_suppliers[$brand['supplier_id']] = true;
                        }
                    }
                    echo count($unique_suppliers);
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>
</div>
<!-- end of overall div -->

<!-- Add/Edit Brand Modal (will be shown via JavaScript) -->
<div id="brandModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <!-- Modal content will be inserted here by JavaScript -->
</div>

<script>
// Brand Management Functions
function openAddBrandModal() {
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
            <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">Add New Brand</h3>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh] flex-1">
                <form id="addBrandForm" class="space-y-4" enctype="multipart/form-data">
                    <!-- Brand Logo Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Brand Logo</label>
                        <div class="flex items-center justify-center w-full">
                            <label for="brand_logo" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF (Max. 2MB)</p>
                                </div>
                                <input id="brand_logo" name="brand_logo" type="file" class="hidden" accept="image/*" />
                            </label>
                        </div>
                        <div id="logoPreview" class="mt-3 hidden">
                            <img id="previewImage" class="h-20 w-20 rounded-lg object-cover mx-auto border border-gray-200">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Brand Name *</label>
                        <input type="text" name="brand_name" required 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#CE1126] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                        <select name="supplier_id" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0038A8] focus:border-transparent">
                            <option value="">Select Supplier</option>
                            <?php foreach($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FCD116] focus:border-transparent"
                                  placeholder="Brief description of the brand..."></textarea>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="status" value="active" checked class="rounded border-gray-300 text-[#CE1126] focus:ring-[#CE1126]">
                            <span class="ml-2 text-sm text-gray-700">Active Brand</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeBrandModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="submitBrandForm()" 
                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-red-600 text-white rounded-lg hover:from-amber-600 hover:to-red-700 transition">
                    Add Brand
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('brandModal').innerHTML = modalContent;
    document.getElementById('brandModal').classList.remove('hidden');
    
    // Initialize logo preview functionality
    initializeLogoPreview();
}

function initializeLogoPreview() {
    const logoInput = document.getElementById('brand_logo');
    const previewContainer = document.getElementById('logoPreview');
    const previewImage = document.getElementById('previewImage');
    
    if (logoInput && previewContainer && previewImage) {
        logoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('hidden');
            }
        });
    }
}

function closeBrandModal() {
    document.getElementById('brandModal').classList.add('hidden');
}

function submitBrandForm() {
    const form = document.getElementById('addBrandForm');
    const formData = new FormData(form);
    formData.append('action', 'add_brand');
    
    fetch('brand_actions.php', {
        method: 'POST',
        body: formData 
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Brand added successfully!');
            closeBrandModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding brand');
    });
}

// edit brand functionalities
function editBrand(brandId) {
    // Fetch brand data and open edit modal
    fetch('brand_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_brand&brand_id=${brandId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openEditBrandModal(data.brand);
        } else {
            alert('Error loading brand: ' + data.error);
        }
    });
}

function openEditBrandModal(brand) {
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
            <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">Edit Brand</h3>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh] flex-1">
                <form id="editBrandForm" class="space-y-4" enctype="multipart/form-data">
                    <input type="hidden" name="brand_id" value="${brand.brand_id}">
                    
                    <!-- Brand Logo Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Brand Logo</label>
                        
                        <!-- Current Logo Preview -->
                        ${brand.brand_logo ? `
                        <div class="mb-3 text-center">
                            <p class="text-sm text-gray-600 mb-2">Current Logo:</p>
                            <img id="currentLogoPreview" src="${brand.brand_logo}" alt="${brand.brand_name} Logo" class="h-20 w-20 rounded-lg object-cover mx-auto border border-gray-200">
                        </div>
                        ` : ''}
                        
                        <div class="flex items-center justify-center w-full">
                            <label for="brand_logo_edit" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF (Max. 2MB)</p>
                                </div>
                                <input id="brand_logo_edit" name="brand_logo" type="file" class="hidden" accept="image/*" />
                            </label>
                        </div>
                        <div id="newLogoPreview" class="mt-3 hidden text-center">
                            <p class="text-sm text-gray-600 mb-2">New Logo:</p>
                            <img id="previewImageEdit" class="h-20 w-20 rounded-lg object-cover mx-auto border border-gray-200">
                        </div>
                        ${brand.brand_logo ? `
                        <div class="mt-2 text-center">
                            <label class="flex items-center justify-center text-sm text-gray-600">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-600 mr-2">
                                <span>Remove current logo</span>
                            </label>
                        </div>
                        ` : ''}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Brand Name *</label>
                        <input type="text" name="brand_name" value="${brand.brand_name}" required 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#CE1126] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                        <select name="supplier_id" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0038A8] focus:border-transparent">
                            <option value="">Select Supplier</option>
                            <?php foreach($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>" ${brand.supplier_id == <?php echo $supplier['supplier_id']; ?> ? 'selected' : ''}>
                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FCD116] focus:border-transparent"
                                  placeholder="Brief description of the brand...">${brand.description || ''}</textarea>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="status" value="active" ${brand.status == 'active' ? 'checked' : ''} 
                                   class="rounded border-gray-300 text-[#CE1126] focus:ring-[#CE1126]">
                            <span class="ml-2 text-sm text-gray-700">Active Brand</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeBrandModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="updateBrand()" 
                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-red-600 text-white rounded-lg hover:from-amber-600 hover:to-red-700 transition">
                    Update Brand
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('brandModal').innerHTML = modalContent;
    document.getElementById('brandModal').classList.remove('hidden');
    
    // Initialize logo preview functionality for edit modal with unique IDs
    initializeEditLogoPreview();
}

function initializeEditLogoPreview() {
    const logoInput = document.getElementById('brand_logo_edit');
    const newPreviewContainer = document.getElementById('newLogoPreview');
    const previewImage = document.getElementById('previewImageEdit');
    
    if (logoInput && newPreviewContainer && previewImage) {
        logoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    newPreviewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                newPreviewContainer.classList.add('hidden');
            }
        });
    }
}

function updateBrand() {
    const form = document.getElementById('editBrandForm');
    const formData = new FormData(form);
    formData.append('action', 'update_brand');
    
    fetch('brand_actions.php', {
        method: 'POST',
        body: formData  
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Brand updated successfully!');
            closeBrandModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

// delete brand functionalities (unchanged)
function deleteBrand(brandId, brandName) {
    // First, check if brand has products
    fetch('brand_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete_brand&brand_id=${brandId}&delete_products=false`
    })
    .then(response => response.json())
    .then(data => {
        if (data.error === 'brand_has_products') {
            // Brand has products - show advanced confirmation
            showDeleteConfirmation(brandId, brandName, data.product_count);
        } else if (data.success) {
            // No products - proceed with simple deletion
            alert('Brand deleted successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error checking brand');
    });
}

function showDeleteConfirmation(brandId, brandName, productCount) {
    const confirmationMessage = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">⚠️ Delete Brand with Products</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Critical Warning</h4>
                        <p class="text-sm text-gray-600">This brand has ${productCount} product(s)</p>
                    </div>
                </div>

                <p class="text-gray-700 mb-4">You are about to delete "<strong>${brandName}</strong>" which has <strong>${productCount} product(s)</strong> associated with it.</p>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-red-700 text-sm font-medium">This action will permanently delete:</p>
                    <ul class="text-red-600 text-sm mt-2 space-y-1">
                        <li>• The brand "${brandName}"</li>
                        <li>• All ${productCount} product(s) associated with this brand</li>
                        <li>• All inventory records for these products</li>
                    </ul>
                </div>

                <p class="text-gray-700 text-sm mb-4">Type <strong>"DELETE ${brandName.toUpperCase()}"</strong> to confirm:</p>
                
                <input type="text" id="confirmDeleteText" placeholder="Type DELETE ${brandName.toUpperCase()} here" 
                       class="w-full p-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmBrandWithProductsDelete(${brandId}, '${brandName.replace(/'/g, "\\'")}')" id="finalDeleteBtn" disabled
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Delete Brand & Products
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Create or update modal
    let modal = document.getElementById('deleteConfirmationModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'deleteConfirmationModal';
        modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = confirmationMessage;
    modal.classList.remove('hidden');
    
    // Setup confirmation input
    const confirmInput = document.getElementById('confirmDeleteText');
    const finalBtn = document.getElementById('finalDeleteBtn');
    
    confirmInput.addEventListener('input', function() {
        finalBtn.disabled = this.value !== `DELETE ${brandName.toUpperCase()}`;
    });
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteConfirmationModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function confirmBrandWithProductsDelete(brandId, brandName) {
    const confirmInput = document.getElementById('confirmDeleteText');
    if (confirmInput.value !== 'DELETE ' + brandName.toUpperCase()) {
        alert('Please type "DELETE ' + brandName.toUpperCase() + '" exactly as shown to proceed.');
        return;
    }

    if (confirm('🚨 FINAL WARNING: This will permanently delete the brand and ALL associated products. This action cannot be undone!')) {
        // Perform deletion with products
        fetch('brand_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=delete_brand&brand_id=' + brandId + '&delete_products=true'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Brand "' + brandName + '" and ' + data.deleted_products + ' product(s) deleted successfully!');
                closeDeleteModal();
                location.reload();
            } else {
                alert('Error: ' + data.error);
                closeDeleteModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting brand and products');
            closeDeleteModal();
        });
    }
}

// Close modal when clicking outside
document.getElementById('brandModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeBrandModal();
    }
});
</script>