<?php
// Prevent direct access to this file
defined('ACCESSED_THROUGH_DASHBOARD') or die('This file cannot be accessed directly.');

// SUPPRESS ERRORS to prevent breaking JavaScript
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

// Fetch suppliers from database
$suppliers = [];
try {
    // Create database connection properly
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $stmt = $db->query("SELECT * FROM suppliers ORDER BY supplier_name");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}
?>

<!-- Overall container -->
<div class="flex p-6 gap-6 w-full max-h-screen">

<!-- Table container -->
<div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-2xl shadow flex-1 flex flex-col min-h-0">
    <div class="flex justify-between items-center mb-4 flex-shrink-0">
        <h2 class="text-xl font-bold text-white">Suppliers Management</h2>
        <div class="flex space-x-4">
            <!-- Add supplier button -->
            <button onclick="addSupplier()" class="w-12 h-12 p-2 bg-green-400 rounded-lg hover:bg-green-500 transition duration-300">
                <svg class="w-8 h-8 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.757v8.486M7.757 12h8.486M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white rounded-lg shadow flex-1 overflow-hidden flex flex-col">
        <!-- Search input -->
        <div class="p-4 bg-red-700 flex-shrink-0">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="text" id="supplier-search" class="block w-full pl-10 p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Search suppliers...">
            </div>
        </div>

        <!-- Scrollable table container -->
        <div class="flex-1 overflow-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3">Actions</th>
                        <th class="px-4 py-3">Supplier Name</th>
                        <th class="px-4 py-3">Contact Person</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Address</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($suppliers)): ?>
                        <?php foreach($suppliers as $supplier): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <button onclick="editSupplier(<?php echo $supplier['supplier_id']; ?>)" class="p-2 bg-yellow-400 rounded-lg hover:bg-yellow-500 transition">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="deleteSupplier(<?php echo $supplier['supplier_id']; ?>, '<?php echo htmlspecialchars($supplier['supplier_name']); ?>')" class="p-2 bg-red-400 rounded-lg hover:bg-red-500 transition">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($supplier['email'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($supplier['phone'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($supplier['address'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $supplier['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($supplier['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3"><?php echo date('M d, Y', strtotime($supplier['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-600">No suppliers found</p>
                                    <button onclick="addSupplier()" class="mt-4 bg-gradient-to-r from-amber-500 to-red-600 text-white px-6 py-2 rounded-lg font-bold hover:from-amber-600 hover:to-red-700 transition">
                                        Add First Supplier
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary Section -->
<div class="p-6 bg-gray-100 rounded-2xl shadow w-80 flex-shrink-0">
    <h2 class="text-xl font-semibold mb-4 text-red-700">Suppliers Summary</h2>
    <div class="flex gap-4 flex-col">
        <div class="bg-sky-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-2">Total Suppliers</h3>
            <p class="text-2xl font-bold"><?php echo count($suppliers); ?></p>
        </div>
        <div class="bg-lime-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-2">Active Suppliers</h3>
            <p class="text-2xl font-bold">
                <?php
                $activeCount = 0;
                foreach ($suppliers as $supplier) {
                    if ($supplier['status'] == 'active') $activeCount++;
                }
                echo $activeCount;
                ?>
            </p>
        </div>
        <div class="bg-amber-500 text-white p-4 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-2">Recently Added</h3>
            <p class="text-2xl font-bold">
                <?php
                $recentCount = 0;
                $oneMonthAgo = date('Y-m-d', strtotime('-1 month'));
                foreach ($suppliers as $supplier) {
                    if ($supplier['created_at'] >= $oneMonthAgo) $recentCount++;
                }
                echo $recentCount;
                ?>
            </p>
        </div>
    </div>
</div>
</div>

<!-- Add/Edit Supplier Modal -->
<div id="supplierModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <!-- Modal content will be inserted here by JavaScript -->
</div>

<script>
// Supplier management functions
function addSupplier() {
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">Add New Supplier</h3>
            </div>
            <div class="p-6">
                <form id="supplierForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Supplier Name *</label>
                            <input type="text" name="supplier_name" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Person</label>
                            <input type="text" name="contact_person" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea name="address" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="status" value="active" checked class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Active Supplier</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button type="button" onclick="submitSupplierForm()" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-red-600 text-white rounded-lg hover:from-amber-600 hover:to-red-700 transition">Add Supplier</button>
            </div>
        </div>
    `;
    
    document.getElementById('supplierModal').innerHTML = modalContent;
    document.getElementById('supplierModal').classList.remove('hidden');
}

function closeSupplierModal() {
    document.getElementById('supplierModal').classList.add('hidden');
}

function submitSupplierForm() {
    const form = document.getElementById('supplierForm');
    const formData = new FormData(form);
    
    // Determine if this is add or edit based on hidden supplier_id field
    const supplierId = formData.get('supplier_id');
    const action = supplierId ? 'edit_supplier' : 'add_supplier';
    formData.append('action', action);
    
    fetch('supplier_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(supplierId ? 'Supplier updated successfully!' : 'Supplier added successfully!');
            closeSupplierModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error ' + (supplierId ? 'updating' : 'adding') + ' supplier');
    });
}

function editSupplier(supplierId) {
    // Fetch supplier data first
    const formData = new FormData();
    formData.append('action', 'get_supplier');
    formData.append('supplier_id', supplierId);
    
    fetch('supplier_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const supplier = data.supplier;
            showEditModal(supplier);
        } else {
            alert('Error loading supplier data: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading supplier data');
    });
}

function showEditModal(supplier) {
    const modalContent = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-amber-500 to-orange-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">Edit Supplier</h3>
            </div>
            <div class="p-6">
                <form id="supplierForm" class="space-y-4">
                    <input type="hidden" name="supplier_id" value="${supplier.supplier_id}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Supplier Name *</label>
                            <input type="text" name="supplier_name" value="${supplier.supplier_name}" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Person</label>
                            <input type="text" name="contact_person" value="${supplier.contact_person || ''}" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" value="${supplier.email || ''}" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone" value="${supplier.phone || ''}" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea name="address" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">${supplier.address || ''}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="status" value="active" ${supplier.status === 'active' ? 'checked' : ''} class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Active Supplier</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button type="button" onclick="submitSupplierForm()" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-lg hover:from-amber-600 hover:to-orange-700 transition">Update Supplier</button>
            </div>
        </div>
    `;
    
    document.getElementById('supplierModal').innerHTML = modalContent;
    document.getElementById('supplierModal').classList.remove('hidden');
}

function deleteSupplier(supplierId, supplierName) {
    // First check if supplier has products or brands
    const formData = new FormData();
    formData.append('action', 'delete_supplier');
    formData.append('supplier_id', supplierId);
    formData.append('delete_with_related', 'false');
    
    fetch('supplier_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error === 'related_exist') {
            // Show warning modal for related records
            showSupplierDeleteConfirmation(supplierId, supplierName, data.product_count, data.brand_count);
        } else if (data.success) {
            // No related records, show a simple confirmation before deleting
            showSimpleDeleteConfirmation(supplierId, supplierName);
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error checking supplier data');
    });
}

function showSimpleDeleteConfirmation(supplierId, supplierName) {
    const confirmationMessage = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">⚠️ Delete Supplier</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Confirm Deletion</h4>
                        <p class="text-sm text-gray-600">This action cannot be undone</p>
                    </div>
                </div>

                <p class="text-gray-700 mb-4">You are about to delete the supplier "<strong>${supplierName}</strong>"</p>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-red-700 text-sm font-medium">This will permanently delete:</p>
                    <ul class="text-red-600 text-sm mt-2 space-y-1">
                        <li>• The supplier "${supplierName}"</li>
                    </ul>
                </div>

                <p class="text-gray-700 text-sm mb-4">Type <strong>"DELETE ${supplierName.toUpperCase()}"</strong> to confirm:</p>
                
                <input type="text" id="confirmDeleteSupplierText" placeholder="Type DELETE ${supplierName.toUpperCase()} here" 
                       class="w-full p-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeSupplierDeleteModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmSimpleSupplierDelete(${supplierId}, '${supplierName.replace(/'/g, "\\'" )}')" id="finalSupplierDeleteBtn" disabled
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Delete Supplier
                    </button>
                </div>
            </div>
        </div>
    `;
    
    let modal = document.getElementById('supplierDeleteModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'supplierDeleteModal';
        modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeSupplierDeleteModal();
        });
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = confirmationMessage;
    modal.classList.remove('hidden');
    
    const confirmInput = document.getElementById('confirmDeleteSupplierText');
    const finalBtn = document.getElementById('finalSupplierDeleteBtn');
    
    confirmInput.addEventListener('input', function() {
        finalBtn.disabled = this.value !== `DELETE ${supplierName.toUpperCase()}`;
    });
}

function confirmSimpleSupplierDelete(supplierId, supplierName) {
    const confirmInput = document.getElementById('confirmDeleteSupplierText');
    if (confirmInput.value !== 'DELETE ' + supplierName.toUpperCase()) {
        alert('Please type "DELETE ' + supplierName.toUpperCase() + '" exactly as shown to proceed.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_supplier');
    formData.append('supplier_id', supplierId);
    formData.append('delete_with_related', 'false');
    
    fetch('supplier_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Supplier "' + supplierName + '" deleted successfully!');
            closeSupplierDeleteModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
            closeSupplierDeleteModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting supplier');
        closeSupplierDeleteModal();
    });
}

function showSupplierDeleteConfirmation(supplierId, supplierName, productCount, brandCount) {
    const totalCount = (productCount || 0) + (brandCount || 0);
    const confirmationMessage = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-red-600 p-6 rounded-t-xl">
                <h3 class="text-xl font-bold text-white">⚠️ Delete Supplier</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Critical Warning</h4>
                        <p class="text-sm text-gray-600">This supplier has related records</p>
                    </div>
                </div>

                <p class="text-gray-700 mb-4">You are about to delete the supplier "<strong>${supplierName}</strong>" which has:</p>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-red-700 text-sm font-medium">This action will permanently delete:</p>
                    <ul class="text-red-600 text-sm mt-2 space-y-1">
                        <li>• The supplier "${supplierName}"</li>
                        ${productCount > 0 ? `<li>• ${productCount} product(s) linked to this supplier</li>` : ''}
                        ${brandCount > 0 ? `<li>• ${brandCount} brand(s) linked to this supplier</li>` : ''}
                    </ul>
                </div>

                <p class="text-gray-700 text-sm mb-4">Type <strong>"DELETE ${supplierName.toUpperCase()}"</strong> to confirm:</p>
                
                <input type="text" id="confirmDeleteSupplierText" placeholder="Type DELETE ${supplierName.toUpperCase()} here" 
                       class="w-full p-3 border border-red-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeSupplierDeleteModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmSupplierDelete(${supplierId}, '${supplierName.replace(/'/g, "\\'")}')" id="finalSupplierDeleteBtn" disabled
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Delete Supplier & Related Records
                    </button>
                </div>
            </div>
        </div>
    `;
    
    let modal = document.getElementById('supplierDeleteModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'supplierDeleteModal';
        modal.className = 'hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeSupplierDeleteModal();
        });
        document.body.appendChild(modal);
    }
    
    modal.innerHTML = confirmationMessage;
    modal.classList.remove('hidden');
    
    const confirmInput = document.getElementById('confirmDeleteSupplierText');
    const finalBtn = document.getElementById('finalSupplierDeleteBtn');
    
    confirmInput.addEventListener('input', function() {
        finalBtn.disabled = this.value !== `DELETE ${supplierName.toUpperCase()}`;
    });
}

function confirmSupplierDelete(supplierId, supplierName) {
    const confirmInput = document.getElementById('confirmDeleteSupplierText');
    if (confirmInput.value !== 'DELETE ' + supplierName.toUpperCase()) {
        alert('Please type "DELETE ' + supplierName.toUpperCase() + '" exactly as shown to proceed.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_supplier');
    formData.append('supplier_id', supplierId);
    formData.append('delete_with_related', 'true');
    
    fetch('supplier_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Supplier "' + supplierName + '" and all associated records deleted successfully!');
            closeSupplierDeleteModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
            closeSupplierDeleteModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting supplier');
        closeSupplierDeleteModal();
    });
}

// Search functionality
document.getElementById('supplier-search')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Close modal when clicking outside
document.getElementById('supplierModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSupplierModal();
    }
});

function confirmSupplierDelete(supplierId, supplierName) {
    const confirmInput = document.getElementById('confirmDeleteSupplierText');
    if (confirmInput.value !== 'DELETE ' + supplierName.toUpperCase()) {
        alert('Please type "DELETE ' + supplierName.toUpperCase() + '" exactly as shown to proceed.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_supplier');
    formData.append('supplier_id', supplierId);
    formData.append('delete_with_related', 'true');
    
    fetch('supplier_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Supplier "' + supplierName + '" and all associated records deleted successfully!');
            closeSupplierDeleteModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
            closeSupplierDeleteModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting supplier');
        closeSupplierDeleteModal();
    });
}

// Search functionality
document.getElementById('supplier-search')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Close modal when clicking outside
document.getElementById('supplierModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSupplierModal();
    }
});
</script>