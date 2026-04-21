<?php
// Prevent direct access to this file
defined('ACCESSED_THROUGH_DASHBOARD') or die('This file cannot be accessed directly.');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Fetch current user data
$user_id = $_SESSION['user_id'];
$user = [];

if ($user_id && $db) {
    try {
        // First, let's check what tables exist in the database
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Available tables: " . implode(', ', $tables));
        
        // Check if users table exists and what columns it has
        if (in_array('users', $tables)) {
            $stmt = $db->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Users table columns: " . implode(', ', $columns));
            
            // Try to get user data - use different possible column names
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                error_log("Raw user data: " . print_r($user, true));
            } else {
                error_log("No user found with ID: " . $user_id);
                
                // Let's see what users exist
                $stmt = $db->query("SELECT user_id, full_name FROM users LIMIT 5");
                $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("First 5 users: " . print_r($all_users, true));
            }
        } else {
            error_log("Users table does not exist!");
        }
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }
}
?>

<div class="bg-white p-6 rounded-2xl shadow">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-sky-600">System Settings</h2>
        <p class="text-gray-500">Manage your system preferences and data</p>
    </div>

    <!-- Settings Sections -->
    <div class="space-y-6">
        <!-- Account Information -->
        <div class="border-b pb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Account Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Created</label>
                    <p class="text-gray-900 font-medium"><?php echo !empty($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User ID</label>
                    <p class="text-gray-900 font-medium">#<?php echo $user_id; ?></p>
                </div>
            </div>
        </div>

        <!-- Data Management -->
        <div class="border-b pb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Data Management</h3>
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-800 mb-2">Export Data</h4>
                    <p class="text-blue-700 text-sm mb-3">Download your inventory data as CSV file for backup purposes.</p>
                    <button onclick="exportInventoryData()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition text-sm">
                        Export Inventory Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="border border-red-200 rounded-lg p-6 bg-red-50">
            <h3 class="text-lg font-semibold text-red-700 mb-2">Danger Zone</h3>
            <p class="text-red-600 text-sm mb-4">Irreversible actions that will permanently delete your data</p>
            
            <div class="space-y-4">
                <!-- Factory Reset (Combined with Account Deletion) -->
                <div class="flex justify-between items-center p-4 bg-white rounded-lg border border-red-200">
                    <div>
                        <h4 class="font-medium text-red-700">Factory Reset & Delete Account</h4>
                        <p class="text-red-600 text-sm">Permanently erase ALL system data including your account</p>
                    </div>
                    <button onclick="openFactoryResetModal()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                        Factory Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Factory Reset Confirmation Modal -->
<div id="factoryResetModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="bg-red-500 p-6 rounded-t-xl">
            <h3 class="text-xl font-bold text-white">⚠️ Factory Reset</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center mb-4">
                <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h4 class="text-lg font-semibold text-gray-900">Critical Warning</h4>
                    <p class="text-sm text-gray-600">This action cannot be undone</p>
                </div>
            </div>

            <p class="text-gray-700 mb-4">This will permanently erase ALL system data including:</p>
            
            <ul class="text-sm text-gray-600 mb-6 space-y-2">
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Your user account and profile
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    All products and inventory data
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Categories, brands, and suppliers
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Delivery records and transaction history
                </li>
            </ul>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <p class="text-red-700 text-sm font-medium">⚠️ No backup will be created. All data will be permanently lost.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeFactoryResetModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="confirmFactoryReset()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Proceed to Final Confirmation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Final Factory Reset Confirmation Modal -->
<div id="finalFactoryResetModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="bg-red-600 p-6 rounded-t-xl">
            <h3 class="text-xl font-bold text-white">🚨 Final Confirmation</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center mb-4">
                <svg class="w-8 h-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h4 class="text-lg font-semibold text-gray-900">Last Warning</h4>
                    <p class="text-sm text-gray-600">This is your final opportunity to cancel</p>
                </div>
            </div>

            <div class="bg-red-100 border border-red-300 rounded-lg p-4 mb-4">
                <p class="text-red-800 text-sm font-medium mb-2">By confirming this action:</p>
                <ul class="text-red-700 text-sm space-y-1">
                    <li>• You acknowledge that ALL data will be permanently erased</li>
                    <li>• You understand that no backup exists and recovery is impossible</li>
                    <li>• You accept full responsibility for this irreversible action</li>
                    <li>• The system will redirect you to the registration page</li>
                </ul>
            </div>

            <p class="text-gray-700 text-sm mb-4">Type <strong>"CONFIRM RESET"</strong> below to proceed:</p>
            
            <input type="text" id="confirmResetText" placeholder="Type CONFIRM RESET here" 
                   class="w-full p-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-red-500 focus:border-transparent">
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeFinalFactoryResetModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" onclick="executeFactoryReset()" id="finalResetBtn" disabled
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Confirm Factory Reset
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Factory Reset Modals
function openFactoryResetModal() {
    document.getElementById('factoryResetModal').classList.remove('hidden');
}

function closeFactoryResetModal() {
    document.getElementById('factoryResetModal').classList.add('hidden');
}

function confirmFactoryReset() {
    closeFactoryResetModal();
    document.getElementById('finalFactoryResetModal').classList.remove('hidden');
    setupFinalConfirmation();
}

function closeFinalFactoryResetModal() {
    document.getElementById('finalFactoryResetModal').classList.add('hidden');
    document.getElementById('confirmResetText').value = '';
    document.getElementById('finalResetBtn').disabled = true;
}

function setupFinalConfirmation() {
    const confirmInput = document.getElementById('confirmResetText');
    const finalBtn = document.getElementById('finalResetBtn');
    
    confirmInput.addEventListener('input', function() {
        finalBtn.disabled = this.value !== 'CONFIRM RESET';
    });
}

function executeFactoryReset() {
    if (document.getElementById('confirmResetText').value !== 'CONFIRM RESET') {
        alert('Please type "CONFIRM RESET" exactly as shown to proceed.');
        return;
    }

    if (confirm('🚨 FINAL WARNING: This will immediately erase ALL data. Click OK to proceed or Cancel to abort.')) {
        // Perform factory reset
        fetch('user_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=factory_reset'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Factory reset completed. You will be redirected to the registration page.');
                window.location.href = 'signup.php';
            } else {
                alert('Error: ' + data.error);
                closeFinalFactoryResetModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error performing factory reset');
            closeFinalFactoryResetModal();
        });
    }
}

// Export Inventory Data
function exportInventoryData() {
    // Simple CSV export - can be enhanced later
    fetch('export_inventory.php')
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = 'inventory_export_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        alert('Inventory data exported successfully!');
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error exporting data');
    });
}

// Close modals when clicking outside
document.getElementById('factoryResetModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeFactoryResetModal();
});

document.getElementById('finalFactoryResetModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeFinalFactoryResetModal();
});
</script>