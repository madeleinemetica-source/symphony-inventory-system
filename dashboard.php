<?php
// Initialize session if needed
session_start();

// Define constant to allow included files
define('ACCESSED_THROUGH_DASHBOARD', true);

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once 'config.php';
include_once 'user.php';

// Get current user info
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->user_id = $_SESSION['user_id'];

// Get user data for display
$current_user = [
    'full_name' => $_SESSION['full_name'],
    'email' => $_SESSION['email'],
    'profile_picture' => $_SESSION['profile_picture'] ?? 'default_profile.jpg'
];

// Function to get first name from full name
function getFirstName($full_name) {
    $names = explode(' ', $full_name);
    return $names[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | Inventory System</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-200 flex h-screen font-serif">

<script>
  // Add new product
            function addProduct() {
              fetch('product_actions.php', {
                  method: 'POST',
                  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                  body: 'action=get_columns&table=products'
              })
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      openAddForm(data.columns);
                  } else {
                      alert('Error loading form: ' + data.error);
                  }
              });
          }

            // add product form
            function openAddForm(columns) {
                let formHTML = '<div class="bg-white p-6 rounded-lg shadow-lg max-w-md">';
                formHTML += '<h3 class="text-xl font-bold mb-4">Add New Product</h3>';
                const formData = {};

                // Generate form fields dynamically
                columns.forEach(column => {
                    if (column !== 'product_id') { // Skip auto-increment ID
                        formHTML += `
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    ${column.replace(/_/g, ' ').toUpperCase()}
                                </label>
                                <input type="text" 
                                    id="field_${column}" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    placeholder="Enter ${column.replace(/_/g, ' ')}">
                            </div>
                        `;
                        formData[column] = '';
                    }
                });

                formHTML += `
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
                        <button type="button" onclick="submitNewProduct()" class="bg-green-500 text-white px-4 py-2 rounded">Add Product</button>
                    </div>
                </div>`;
                
                // Show modal with dynamic form
                showModal(formHTML);
                
                // Store columns for submission
                window.currentColumns = columns;
            }

            // submit the newly added product
            function submitNewProduct() {
                const productData = {};
                const columns = window.currentColumns;

                console.log("Available columns:", columns); // Debug

                // collect data from form fields
                columns.forEach(column => {
                    if (column !== 'product_id') {
                        const input = document.getElementById(`field_${column}`);
                        console.log(`Looking for column: ${column}, found input:`, input); // Debug
                        if (input) {
                            productData[column] = input.value;
                            console.log(`Column ${column} value:`, input.value); // Debug
                        }
                    }
                });
                console.log("Final productData:", productData); // Debug
                if (Object.keys(productData).length === 0) {
                    alert('No data collected! Check console for errors.');
                    return;
                }

                // send to server
                fetch('product_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=add_product&${new URLSearchParams(productData)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product added successfully!');
                        closeModal();
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }

            
            // User clicks edit button
            function editProduct(productId) {
                // Get current product data
                fetch('product_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=get_product&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        openEditForm(data.product); // Dynamic form with actual columns
                    } else {
                        alert('Error loading product: ' + data.error);
                    }
                });
            }

            // edit product form
            function openEditForm(product) {
                const columns = Object.keys(product);
                
                let formHTML = '<div class="bg-white p-6 rounded-lg shadow-lg max-w-md">';
                formHTML += '<h3 class="text-xl font-bold mb-4">Edit Product</h3>';
                
                columns.forEach(column => {
                    if (column !== 'product_id') {
                        const label = column.replace(/_/g, ' ').toUpperCase();
                        const value = product[column] || '';
                        const fieldType = detectFieldType(column, value);
                        
                        formHTML += `
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    ${label}
                                </label>
                                <input type="${fieldType}" 
                                      id="edit_${column}" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                      value="${value}"
                                      placeholder="Enter ${label}">
                            </div>
                        `;
                    }
                });
                
                formHTML += `
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
                        <button type="button" onclick="submitEditProduct(${product.product_id})" class="bg-blue-500 text-white px-4 py-2 rounded">Update Product</button>
                    </div>
                </div>`;
                
                showModal(formHTML);
            }

            // submit the edited product
            function submitEditProduct(productId) {
                const updatedData = {};
                const inputs = document.querySelectorAll('#modal input');
                
                inputs.forEach(input => {
                    const columnName = input.id.replace('edit_', '');
                    let value = input.value;
                    
                    // Auto-convert based on input type
                    if (input.type === 'number') {
                        value = parseFloat(value) || 0;
                    }
                    
                    updatedData[columnName] = value;
                });
                
                fetch('product_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=update_product&product_id=${productId}&${new URLSearchParams(updatedData)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product updated successfully!');
                        closeModal();
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }

            // detect the columns
            function detectFieldType(columnName, value) {
                // Simple type detection based on column name and value
                if (columnName.includes('price') || columnName.includes('cost')) {
                    return 'number';
                }
                if (columnName.includes('quantity') || columnName.includes('stock')) {
                    return 'number';
                }
                if (columnName.includes('email')) {
                    return 'email';
                }
                if (columnName.includes('url') || columnName.includes('image')) {
                    return 'url';
                }
                return 'text';
            }

            // Update product - saves changes (called from edit form)
            function updateProduct(productId, updatedData) {
                updatedData.product_id = productId;
                
                fetch('product_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=update_product&${new URLSearchParams(updatedData)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }

            // Delete product
            function deleteProduct(productId, productName) {
                if (confirm(`Are you sure you want to delete "${productName}"?`)) {
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

            // Modal functions
            function showModal(content) {
                const modal = document.createElement('div');
                modal.id = 'modal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                modal.innerHTML = content;
                document.body.appendChild(modal);
            }

            function closeModal() {
                const modal = document.getElementById('modal');
                if (modal) {
                    modal.remove();
                }
            }

            // Delivery management functions
            function addInboundDelivery() {
                const modalContent = `
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                        <div class="bg-gradient-to-r from-green-500 to-teal-600 p-6 rounded-t-xl">
                            <h3 class="text-xl font-bold text-white">New Inbound Delivery</h3>
                        </div>
                        <div class="p-6">
                            <form id="inboundForm" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Supplier *</label>
                                        <select name="supplier_id" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" id="supplierSelect">
                                            <option value="">Select Supplier</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Date *</label>
                                        <input type="date" name="delivery_date" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" value="${new Date().toISOString().split('T')[0]}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                        <select name="status" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                            <option value="pending">Pending</option>
                                            <option value="received">Received</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="border-t pt-4">
                                    <h4 class="text-lg font-medium text-gray-900 mb-4">Delivery Items</h4>
                                    <div id="delivery-items-container" class="space-y-3">
                                        <!-- Items will be added here -->
                                    </div>
                                    <button type="button" onclick="addDeliveryItem()" class="mt-3 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                        + Add Item
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                            <button type="button" onclick="closeDeliveryModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                            <button type="button" onclick="submitInboundForm()" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-green-600 text-white rounded-lg hover:from-blue-600 hover:to-green-700 transition">Create Delivery</button>
                        </div>
                    </div>
                `;
                
                document.getElementById('deliveryModal').innerHTML = modalContent;
                document.getElementById('deliveryModal').classList.remove('hidden');
                
                // Load suppliers and products dynamically
                loadSuppliersAndProducts();
            }

            function addOutboundDelivery() {
                const modalContent = `
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                        <div class="bg-gradient-to-r from-orange-500 to-teal-600 p-6 rounded-t-xl">
                            <h3 class="text-xl font-bold text-white">New Outbound Delivery</h3>
                        </div>
                        <div class="p-6">
                            <form id="outboundForm" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                                        <input type="text" name="customer_name" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" placeholder="Enter customer name">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Date *</label>
                                        <input type="date" name="delivery_date" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" value="${new Date().toISOString().split('T')[0]}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                        <select name="status" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                                            <option value="pending">Pending</option>
                                            <option value="received">Received</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="border-t pt-4">
                                    <h4 class="text-lg font-medium text-gray-900 mb-4">Delivery Items</h4>
                                    <div id="delivery-items-container" class="space-y-3">
                                        <!-- Items will be added here -->
                                    </div>
                                    <button type="button" onclick="addDeliveryItem()" class="mt-3 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                        + Add Item
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                            <button type="button" onclick="closeDeliveryModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                            <button type="button" onclick="submitOutboundForm()" class="px-4 py-2 bg-gradient-to-r from-orange-500 to-teal-600 text-white rounded-lg hover:from-orange-600 hover:to-teal-700 transition">Create Delivery</button>
                        </div>
                    </div>
                `;
                
                document.getElementById('deliveryModal').innerHTML = modalContent;
                document.getElementById('deliveryModal').classList.remove('hidden');
                
                // Load products dynamically
                loadSuppliersAndProducts();
            }

            function loadSuppliersAndProducts() {
                // Fetch suppliers and products from the server
                fetch('delivery_actions.php?action=get_suppliers_products')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            populateSupplierDropdown(data.suppliers);
                            // Ensure products is always an array
                            window.products = Array.isArray(data.products) ? data.products : [];
                        } else {
                            console.error('Error loading data:', data.error);
                            window.products = [];
                        }
                    })
                    .catch(error => {
                        console.error('Error loading data:', error);
                        window.products = [];
                    });
            }

            function populateSupplierDropdown(suppliers) {
                const supplierSelect = document.getElementById('supplierSelect');
                if (supplierSelect && Array.isArray(suppliers)) {
                    supplierSelect.innerHTML = '<option value="">Select Supplier</option>' +
                        suppliers.map(s => `<option value="${s.supplier_id}">${s.supplier_name}</option>`).join('');
                }
            }

            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                const modal = document.getElementById('deliveryModal');
                if (e.target === modal) {
                    closeDeliveryModal();
                }
            });


// Initialize delivery tabs when deliveries section is shown
document.addEventListener('DOMContentLoaded', function() {
    // This will be called when the deliveries section becomes active
    const deliveriesLink = document.querySelector('a[href="#deliveries"]');
    if (deliveriesLink) {
        deliveriesLink.addEventListener('click', function() {
            // Small delay to ensure the section is loaded
            setTimeout(initDeliveryTabs, 100);
        });
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('deliveryModal');
    if (e.target === modal) {
        closeDeliveryModal();
    }
});
</script>
  <!-- Sidebar -->
  <aside class="w-56 h-auto bg-red-700 shadow-lg flex flex-col transform transition-transform duration-300">
    <!-- sidebar header -->
      <header class="sidebar-header flex justify-between items-center p-4 border-b bg-white">
        <img src="inventorylogo.png" alt="" class="header-logo object-scale-down h-11 w-11 rounded-full mr-2">
        <h2 class="text-2xl font-bold text-red-700">Stock Symphony</h2>
        <button id="sidebar-btn" class="sidebar-toggle mt-3 p-2 mx-11 bg-amber-500 rounded-lg hover:bg-amber-600 transition duration-300">
          <span>
          <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/>
          </svg>
          </span>  
        </button>
      </header>
    </div>
    
    <nav class="flex-1 p-4 space-y-2">
      <a href="#home" class="menu-link inline-flex items-center px-4 py-2 rounded-lg font-medium hover:bg-amber-200 hover:text-red-700 text-white space-x-2">
        <svg class="w-6 h-6 text-white hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M13.5 2c-.178 0-.356.013-.492.022l-.074.005a1 1 0 0 0-.934.998V11a1 1 0 0 0 1 1h7.975a1 1 0 0 0 .998-.934l.005-.074A7.04 7.04 0 0 0 22 10.5 8.5 8.5 0 0 0 13.5 2Z"/><path d="M11 6.025a1 1 0 0 0-1.065-.998 8.5 8.5 0 1 0 9.038 9.039A1 1 0 0 0 17.975 13H11V6.025Z"/></svg>
        <span>Home</span>
      </a>
      <a href="#products" class="menu-link inline-flex items-center px-4 py-2 rounded-lg hover:bg-amber-200 text-white hover:text-red-700 space-x-2">
        <svg class="w-6 h-6 text-white hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M4.857 3A1.857 1.857 0 0 0 3 4.857v4.286C3 10.169 3.831 11 4.857 11h4.286A1.857 1.857 0 0 0 11 9.143V4.857A1.857 1.857 0 0 0 9.143 3H4.857Zm10 0A1.857 1.857 0 0 0 13 4.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 9.143V4.857A1.857 1.857 0 0 0 19.143 3h-4.286Zm-10 10A1.857 1.857 0 0 0 3 14.857v4.286C3 20.169 3.831 21 4.857 21h4.286A1.857 1.857 0 0 0 11 19.143v-4.286A1.857 1.857 0 0 0 9.143 13H4.857Zm10 0A1.857 1.857 0 0 0 13 14.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 19.143v-4.286A1.857 1.857 0 0 0 19.143 13h-4.286Z" clip-rule="evenodd"/>
        </svg>
        <span>Products</span>
      </a>
      <a href="#brands" class="menu-link inline-flex items-center px-4 py-2 rounded-lg hover:bg-amber-200 text-white hover:text-red-700 space-x-2">
        <svg class="w-6 h-6 text-white hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M14 7h-4v3a1 1 0 0 1-2 0V7H6a1 1 0 0 0-.997.923l-.917 11.924A2 2 0 0 0 6.08 22h11.84a2 2 0 0 0 1.994-2.153l-.917-11.924A1 1 0 0 0 18 7h-2v3a1 1 0 1 1-2 0V7Zm-2-3a2 2 0 0 0-2 2v1H8V6a4 4 0 0 1 8 0v1h-2V6a2 2 0 0 0-2-2Z" clip-rule="evenodd"/>
        </svg>
        <span>Brands</span>
      </a>
      <a href="#categories" class="menu-link inline-flex items-center px-4 py-2 rounded-lg hover:bg-amber-200 text-white hover:text-red-700 space-x-2">
        <svg class="w-6 h-6 text-white hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
        </svg>
        <span>Categories</span>
      </a>
      <a href="#suppliers" class="menu-link inline-flex items-center px-4 py-2 rounded-lg hover:bg-amber-200 text-white hover:text-red-700 space-x-2">
        <svg class="w-6 h-6 text-white hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M5.535 7.677c.313-.98.687-2.023.926-2.677H17.46c.253.63.646 1.64.977 2.61.166.487.312.953.416 1.347.11.42.148.675.148.779 0 .18-.032.355-.09.515-.06.161-.144.3-.243.412-.1.111-.21.192-.324.245a.809.809 0 0 1-.686 0 1.004 1.004 0 0 1-.324-.245c-.1-.112-.183-.25-.242-.412a1.473 1.473 0 0 1-.091-.515 1 1 0 1 0-2 0 1.4 1.4 0 0 1-.333.927.896.896 0 0 1-.667.323.896.896 0 0 1-.667-.323A1.401 1.401 0 0 1 13 9.736a1 1 0 1 0-2 0 1.4 1.4 0 0 1-.333.927.896.896 0 0 1-.667.323.896.896 0 0 1-.667-.323A1.4 1.4 0 0 1 9 9.74v-.008a1 1 0 0 0-2 .003v.008a1.504 1.504 0 0 1-.18.712 1.22 1.22 0 0 1-.146.209l-.007.007a1.01 1.01 0 0 1-.325.248.82.82 0 0 1-.316.08.973.973 0 0 1-.563-.256 1.224 1.224 0 0 1-.102-.103A1.518 1.518 0 0 1 5 9.724v-.006a2.543 2.543 0 0 1 .029-.207c.024-.132.06-.296.11-.49.098-.385.237-.85.395-1.344ZM4 12.112a3.521 3.521 0 0 1-1-2.376c0-.349.098-.8.202-1.208.112-.441.264-.95.428-1.46.327-1.024.715-2.104.958-2.767A1.985 1.985 0 0 1 6.456 3h11.01c.803 0 1.539.481 1.844 1.243.258.641.67 1.697 1.019 2.72a22.3 22.3 0 0 1 .457 1.487c.114.433.214.903.214 1.286 0 .412-.072.821-.214 1.207A3.288 3.288 0 0 1 20 12.16V19a2 2 0 0 1-2 2h-6a1 1 0 0 1-1-1v-4H8v4a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2v-6.888ZM13 15a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2Z" clip-rule="evenodd"/>
        </svg>
        <span>Suppliers</span>
      </a>
      <a href="#inventory" class="menu-link inline-flex items-center px-4 py-2 rounded-lg hover:bg-amber-200 text-white hover:text-red-700 space-x-2">
        <svg class="w-6 h-6 text-white hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v9.293l-2-2a1 1 0 0 0-1.414 1.414l.293.293h-6.586a1 1 0 1 0 0 2h6.586l-.293.293A1 1 0 0 0 18 16.707l2-2V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd"/>
        </svg>
        <span>Inventory</span>
      </a>
      <a href="#profile" class="menu-link inline-flex items-center px-4 py-2 rounded-lg hover:bg-amber-200 text-white hover:text-red-700 space-x-2">
        <svg class="w-6 h-6 text-white hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M12 20a7.966 7.966 0 0 1-5.002-1.756l.002.001v-.683c0-1.794 1.492-3.25 3.333-3.25h3.334c1.84 0 3.333 1.456 3.333 3.25v.683A7.966 7.966 0 0 1 12 20ZM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10c0 5.5-4.44 9.963-9.932 10h-.138C6.438 21.962 2 17.5 2 12Zm10-5c-1.84 0-3.333 1.455-3.333 3.25S10.159 13.5 12 13.5c1.84 0 3.333-1.455 3.333-3.25S13.841 7 12 7Z" clip-rule="evenodd"/>
        </svg>
        <span>Profile</span>
      </a>
      <a href="#settings" class="menu-link inline-flex items-center px-4 py-2 rounded-lg hover:bg-amber-200 text-white hover:text-red-700 space-x-2">
        <svg class="w-6 h-6 text-white hover:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M9.586 2.586A2 2 0 0 1 11 2h2a2 2 0 0 1 2 2v.089l.473.196.063-.063a2.002 2.002 0 0 1 2.828 0l1.414 1.414a2 2 0 0 1 0 2.827l-.063.064.196.473H20a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-.089l-.196.473.063.063a2.002 2.002 0 0 1 0 2.828l-1.414 1.414a2 2 0 0 1-2.828 0l-.063-.063-.473.196V20a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-.089l-.473-.196-.063.063a2.002 2.002 0 0 1-2.828 0l-1.414-1.414a2 2 0 0 1 0-2.827l.063-.064L4.089 15H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h.09l.195-.473-.063-.063a2 2 0 0 1 0-2.828l1.414-1.414a2 2 0 0 1 2.827 0l.064.063L9 4.089V4a2 2 0 0 1 .586-1.414ZM8 12a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/>
        </svg>
        <span>Settings</span>
      </a>
    </nav>

    <div class="p-4 border-t bg-white">
      <a href="logout.php" class="block text-center bg-red-700 text-white py-2 rounded-lg hover:bg-red-600 hover:text-red-100 transition space-x-2">Logout</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-8 overflow-y-auto">
    <header class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-red-700 text-right px-8 mt-2 ml-4" id="pageTitle">Home</h1>
      <div class="text-gray-600 text-sm">Welcome, <span class="font-medium text-amber-600">
        <?php echo htmlspecialchars(getFirstName($current_user['full_name'])); ?>
      </span></div>
    </header>

    <!-- Section: Home -->
    <section id="home" class="content-section hidden">
        <?php
        // Get counts for dashboard cards
        $total_products = 0;
        $total_categories = 0;
        $total_brands = 0;
        $out_of_stock = 0;
        $warning_stock = 0;
        $total_suppliers = 0;

        try {
            if (isset($db) && $db) {
                // Total Products
                $stmt = $db->query("SELECT * FROM products");
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total_products = count($products);

                // Total Categories
                $stmt = $db->query("SELECT * FROM categories");
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total_categories = count($categories);

                // Total Brands
                $stmt = $db->query("SELECT * FROM brands");
                $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total_brands = count($brands);

                // Out of Stock (quantity = 0)
                $stmt = $db->query("SELECT * FROM products WHERE quantity_stock <= 0");
                $out_of_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $out_of_stock = count($out_of_stock_products);

                // Warning Stock (quantity below threshold of 10)
                $stmt = $db->query("SELECT * FROM products WHERE quantity_stock > 0 AND quantity_stock <= 25");
                $warning_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $warning_stock = count($warning_stock_products);

                // Total Suppliers
                $stmt = $db->query("SELECT * FROM suppliers");
                $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total_suppliers = count($suppliers);
            }
        } catch (Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
        }
        ?>
        
        <!-- home headings -->
        <div class="bg-gradient-to-r from-amber-500 to-red-600 p-6 rounded-t-2xl shadow">
            <p class="text-gray-100">Welcome to your Inventory System Dashboard.</p>
        </div> 
        
        <div class="grid grid-cols-4 gap-4 justify-evenly bg-white p-6 rounded-b-2xl shadow mb-6">
            <!-- Dashboard summary cards -->
            <div class="flex flex-col max-w-xs h-35">
                <div class="bg-blue-500 p-4 rounded-t-lg shadow text-white">
                    <h3 class="text-2xl font-bold mb-2"><?php echo $total_products; ?></h3>
                    <p class="text-lg">Total Products</p>
                </div>
                <a href="#products" data-title="Products" class="menu-link bg-blue-400 p-4 rounded-b-lg shadow text-white hover:bg-blue-600 transition duration-300 inline-block text-center">
                    <p>More info &gt;</p>
                </a>
            </div>
            
            <div class="flex flex-col max-w-xs">
                <div class="bg-teal-500 p-4 rounded-t-lg shadow text-white">
                    <h3 class="text-2xl font-bold mb-2"><?php echo $total_categories; ?></h3>
                    <p class="text-lg">Total Categories</p>
                </div>
                <a href="#categories" data-title="Categories" class="menu-link bg-teal-400 p-4 rounded-b-lg shadow text-white hover:bg-teal-600 transition duration-300 inline-block text-center">
                    <p>More info &gt;</p>
                </a>
            </div>
            
            <div class="flex flex-col max-w-xs">
                <div class="bg-amber-500 p-4 rounded-t-lg shadow text-white">
                    <h3 class="text-2xl font-bold mb-2"><?php echo $total_brands; ?></h3>
                    <p class="text-lg">Total Brands</p>
                </div>
                <a href="#brands" data-title="Brands" class="menu-link bg-amber-400 p-4 rounded-b-lg shadow text-white hover:bg-amber-600 transition duration-300 inline-block text-center">
                    <p>More info &gt;</p>
                </a>
            </div>
            
            <div class="flex flex-col max-w-xs">
                <div class="bg-red-500 p-4 rounded-t-lg shadow text-white">
                    <h3 class="text-2xl font-bold mb-2"><?php echo $out_of_stock; ?></h3>
                    <p class="text-lg">Out of Stock</p>
                </div>
                <a href="#products" data-title="Out of Stock" class="menu-link bg-red-400 p-4 rounded-b-lg shadow text-white hover:bg-red-600 transition duration-300 inline-block text-center">
                    <p>More info &gt;</p>
                </a>
            </div>
            
            <div class="flex flex-col max-w-xs">
                <div class="bg-yellow-500 p-4 rounded-t-lg shadow text-white">
                    <h3 class="text-2xl font-bold mb-2"><?php echo $warning_stock; ?></h3>
                    <p class="text-lg">Warning Stock</p>
                </div>
                <a href="#products" data-title="Warning Stock" class="menu-link bg-yellow-400 p-4 rounded-b-lg shadow text-white hover:bg-yellow-600 transition duration-300 inline-block text-center">
                    <p>More info &gt;</p>
                </a>
            </div>
            
            <div class="flex flex-col max-w-xs">
                <div class="bg-green-500 p-4 rounded-t-lg shadow text-white">
                    <h3 class="text-2xl font-bold mb-2"><?php echo $total_suppliers; ?></h3>
                    <p class="text-lg">Total Suppliers</p>
                </div>
                <a href="#suppliers" data-title="Suppliers" class="menu-link bg-green-400 p-4 rounded-b-lg shadow text-white hover:bg-green-600 transition duration-300 inline-block text-center">
                    <p>More info &gt;</p>
                </a>
            </div>
            
            <div class="flex flex-col max-w-xs">
                <div class="bg-rose-500 p-4 rounded-t-lg shadow text-white grid grid-cols-2 items-center justify-center">
                    <!-- Profile picture and user name -->
                    <?php
                    $profile_picture = !empty($current_user['profile_picture']) ? $current_user['profile_picture'] : 'default_profile.jpg';
                    ?>
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
                        alt="Profile Picture" 
                        class="w-16 h-16 rounded-full object-cover border-2 border-white mb-2">
                    <div>
                        <h3 class="text-xl font-bold mb-1 text-white"><?php echo htmlspecialchars(getFirstName($current_user['full_name'])); ?></h3>
                        <p class="text-sm text-white">Current User</p>
                    </div>
                </div>
                <a href="#profile" data-title="Profile" class="menu-link bg-rose-400 p-4 rounded-b-lg shadow text-white hover:bg-rose-600 transition duration-300 inline-block text-center">
                    <p>More info &gt;</p>
                </a>
            </div>
        </div>
    </section>

        <!-- Section: Products -->
    <section id="products" class="content-section hidden">
      <?php 

      require_once 'manage_products.php';
      ?>
    </section>

    <!-- Section: Brands -->
    <section id="brands" class="content-section hidden">
      <?php 
 
      require_once 'manage_brands.php'; 
      ?>
    </section>

    <!-- Section: Categories -->
    <section id="categories" class="content-section hidden">
      <?php 
 
      require_once 'manage_categories.php'; 
      ?>
    </section>

    <!-- Section: Suppliers -->
    <section id="suppliers" class="content-section hidden">
      <?php 
 
      require_once 'manage_suppliers.php'; 
      ?>
    </section>

    <!-- Section: Inventory -->
    <section id="inventory" class="content-section hidden">
      <?php 

      require_once 'view_inventory.php'; 
      ?>
    </section>

    <!-- Section: Profile -->
    <section id="profile" class="content-section hidden">
      <?php
      require_once 'user_profile.php';
      ?>
    </section>

    <!-- Section: Settings -->
    <section id="settings" class="content-section hidden">
      <?php 

      require_once 'user_settings.php'; 
      ?>
    </section>
  </main>

  <script src="https://cdn.tailwindcss.com"></script>
  <!-- JavaScript for Sidebar Toggle -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {

      // add main variables used throughout document
    const sidebarBtn = document.getElementById('sidebar-btn');
    const sidebar = document.querySelector('aside');
    const links = document.querySelectorAll('.menu-link');
    const sections = document.querySelectorAll('.content-section');
    const pageTitle = document.getElementById('pageTitle');

      // sidebar toggle functionality
      sidebarBtn.addEventListener('click', (e) => {
      sidebar.classList.toggle('-translate-x-56');
      sidebarBtn.classList.toggle('rotate-180');
      })

      // helper: clear active classes from links
      function clearActiveLinks() {
        links.forEach(l => {
          l.classList.remove('bg-red-200', 'text-red-700', 'font-medium');
        });
      }

      // show only the requested section
      function showSection(id) {
        sections.forEach(sec => sec.classList.add('hidden'));
        const target = document.getElementById(id);
        if (target) target.classList.remove('hidden');
      }

      // attach click handlers

links.forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    const href = link.getAttribute('href') || '';
    const targetId = href.startsWith('#') ? href.substring(1) : href;

    if (!targetId) return;

    clearActiveLinks();
    link.classList.add('bg-red-200', 'text-red-700', 'font-medium');

    showSection(targetId);

    // Initialize profile section if needed
    if (targetId === 'profile') {
      // Small delay to ensure the profile section is fully loaded
      setTimeout(() => {
        // Check if profile functions exist and initialize them
        if (typeof initProfileSection === 'function') {
          initProfileSection();
        } else {
          // Fallback: manually initialize profile events
          initProfileEventsManually();
        }
      }, 100);
    }

    // update page title (prefer data-title when provided)
    pageTitle.textContent = (link.dataset && link.dataset.title) ? link.dataset.title.trim() : link.textContent.trim();
    // Optionally update the URL hash without jumping
    history.replaceState(null, '', '#' + targetId);
  });
});

      // Initial state: activate Home
      const initialHash = window.location.hash ? window.location.hash.substring(1) : 'home';
      const initialLink = Array.from(links).find(l => l.getAttribute('href') === '#' + initialHash);
      if (initialLink) {
        initialLink.classList.add('bg-red-700', 'text-white', 'font-medium');
      } else {
        // fallback to first link
        links[0].classList.add('bg-red-200', 'text-red-700', 'font-medium');
      }
      showSection(initialHash);
    pageTitle.textContent = (initialLink && initialLink.dataset && initialLink.dataset.title) ? initialLink.dataset.title.trim() : (initialLink ? initialLink.textContent.trim() : links[0].textContent.trim());

// End of DOMContentLoaded
    });
  </script>

</body>
</html>
