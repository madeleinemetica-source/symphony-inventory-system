<?php
// Prevent direct access to this file
defined('ACCESSED_THROUGH_DASHBOARD') or die('This file cannot be accessed directly.');

$require_once_line = false;
require_once 'config.php';

// Ensure session available and determine current user id
if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1; // fallback to 1 for testing
$user = [];
$profile_picture = 'default_profile.jpg';

try {
    if (isset($db) && $db) {
        $stmt = $db->prepare("SELECT user_id, full_name, email, profile_picture, last_login FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['profile_picture'])) {
            $profile_picture = $user['profile_picture'];
        }
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Make a copy for older JS that expects $current_user
$current_user = $user;
?>

<div class="bg-white p-6 rounded-2xl shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-sky-600">User Profile</h2>
        <button onclick="openEditProfileModal()" class="bg-sky-500 text-white px-4 py-2 rounded-lg hover:bg-sky-600 transition">
            Edit Profile
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Image Section -->
        <div class="text-center">
            <div class="w-32 h-32 mx-auto rounded-full bg-gray-200 flex items-center justify-center mb-4 overflow-hidden">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="w-full h-full object-cover" id="current-profile-picture">
            </div>
            <button onclick="openEditProfileModal()" class="text-sky-600 hover:text-sky-700 text-sm">Change Photo</button>
        </div>

        <!-- Profile Details -->
        <div class="md:col-span-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <p class="text-gray-900 font-medium">Administrator</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Login</label>
                    <p class="text-gray-900 font-medium"><?php echo !empty($user['last_login']) ? date('M d, Y g:i A', strtotime($user['last_login'])) : 'N/A'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="mt-8">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Recent Activity</h3>
        <div id="recent-activities" class="space-y-3">
            <!-- Recent activities will be loaded here via AJAX -->
            <div class="text-sm text-gray-500">Loading recent activities...</div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="bg-gradient-to-r from-sky-500 to-blue-600 p-6 rounded-t-xl">
            <h3 class="text-xl font-bold text-white">Edit Profile</h3>
        </div>
        <div class="p-6">
            <form id="editProfileForm" class="space-y-4">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                
                <!-- Current Profile Picture Preview -->
                <div class="text-center mb-4">
                    <div class="w-24 h-24 mx-auto rounded-full bg-gray-200 flex items-center justify-center mb-2 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Current Profile" class="w-full h-full object-cover" id="current-profile-modal">
                    </div>
                    <p class="text-sm text-gray-600">Current Profile Picture</p>
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>

                <!-- Profile Picture Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Profile Picture</label>
                    <div class="mt-1 flex items-center">
                        <input type="file" name="profile_picture" id="profile_picture" 
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                            class="hidden">
                        <label for="profile_picture" class="cursor-pointer w-full">
                            <div class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 transition">
                                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-sm text-gray-600">Click to upload new photo</span>
                                <span class="text-xs text-gray-500">JPG, PNG, GIF, WebP (Max 5MB)</span>
                            </div>
                        </label>
                    </div>
                    <!-- New Image Preview -->
                    <div id="image-preview-profile" class="mt-4 hidden text-center">
                        <p class="text-sm font-medium text-gray-700 mb-2">New Photo Preview:</p>
                        <img id="preview-image-profile" class="h-24 w-24 rounded-full mx-auto shadow-md object-cover">
                        <p class="text-xs text-green-600 mt-1">This will replace your current profile picture</p>
                    </div>
                </div>
            </form>
        </div>
        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
            <button type="button" onclick="closeEditProfileModal()" 
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Cancel
            </button>
            <button type="button" onclick="updateProfile()" 
                    class="px-4 py-2 bg-gradient-to-r from-sky-500 to-blue-600 text-white rounded-lg hover:from-sky-600 hover:to-blue-700 transition">
                Update Profile
            </button>
        </div>
    </div>
</div>

<script>
// Profile Management Functions
            function openEditProfileModal() {
                // Use the PHP session data directly since we're in dashboard.php
                const currentUserData = {
                    full_name: '<?php echo addslashes($current_user["full_name"]); ?>',
                    email: '<?php echo addslashes($current_user["email"]); ?>',
                    profile_picture: '<?php echo addslashes($current_user["profile_picture"]); ?>',
                    user_id: '<?php echo $_SESSION["user_id"]; ?>'
                };

                const modalContent = `
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                        <div class="bg-gradient-to-r from-sky-500 to-blue-600 p-6 rounded-t-xl">
                            <h3 class="text-xl font-bold text-white">Edit Profile</h3>
                        </div>
                        <div class="p-6">
                            <form id="editProfileForm" class="space-y-4">
                                <input type="hidden" name="user_id" value="${currentUserData.user_id}">
                                
                                <!-- Current Profile Picture Preview -->
                                <div class="text-center mb-4">
                                    <div class="w-24 h-24 mx-auto rounded-full bg-gray-200 flex items-center justify-center mb-2 overflow-hidden">
                                        <img src="${currentUserData.profile_picture}" alt="Current Profile" class="w-full h-full object-cover" id="current-profile-modal">
                                    </div>
                                    <p class="text-sm text-gray-600">Current Profile Picture</p>
                                </div>

                                <!-- Full Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                    <input type="text" name="full_name" value="${currentUserData.full_name}" required 
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <input type="email" name="email" value="${currentUserData.email}" required 
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                                </div>

                                <!-- Profile Picture Upload -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">New Profile Picture</label>
                                    <div class="mt-1 flex items-center">
                                        <input type="file" name="profile_picture" id="profile_picture" 
                                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                            class="hidden">
                                        <label for="profile_picture" class="cursor-pointer w-full">
                                            <div class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg hover:border-gray-400 transition">
                                                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <span class="text-sm text-gray-600">Click to upload new photo</span>
                                                <span class="text-xs text-gray-500">JPG, PNG, GIF, WebP (Max 5MB)</span>
                                            </div>
                                        </label>
                                    </div>
                                    <!-- New Image Preview -->
                                    <div id="image-preview-profile" class="mt-4 hidden text-center">
                                        <p class="text-sm font-medium text-gray-700 mb-2">New Photo Preview:</p>
                                        <img id="preview-image-profile" class="h-24 w-24 rounded-full mx-auto shadow-md object-cover">
                                        <p class="text-xs text-green-600 mt-1">This will replace your current profile picture</p>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
                            <button type="button" onclick="closeEditProfileModal()" 
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="button" onclick="updateProfile()" 
                                    class="px-4 py-2 bg-gradient-to-r from-sky-500 to-blue-600 text-white rounded-lg hover:from-sky-600 hover:to-blue-700 transition">
                                Update Profile
                            </button>
                        </div>
                    </div>
                `;
                
                document.getElementById('editProfileModal').innerHTML = modalContent;
                document.getElementById('editProfileModal').classList.remove('hidden');
                setupProfileImagePreview();
            }

            function closeEditProfileModal() {
                document.getElementById('editProfileModal').classList.add('hidden');
            }

            function setupProfileImagePreview() {
                const imageInput = document.getElementById('profile_picture');
                if (imageInput) {
                        // Prevent attaching multiple handlers if this is called repeatedly
                        if (imageInput._previewHandlerAttached) return;
                        imageInput._previewHandlerAttached = true;

                        imageInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            // Validate file size (5MB max)
                            if (file.size > 5 * 1024 * 1024) {
                                alert('File size must be less than 5MB');
                                this.value = '';
                                return;
                            }

                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const preview = document.getElementById('image-preview-profile');
                                const previewImage = document.getElementById('preview-image-profile');
                                previewImage.src = e.target.result;
                                preview.classList.remove('hidden');
                            }
                            reader.readAsDataURL(file);
                        }
                    });
                }
            }

            function updateProfile() {
                const form = document.getElementById('editProfileForm');
                const formData = new FormData(form);
                formData.append('action', 'update_profile');
                
                // Use the test file instead of user_actions.php
                fetch('user_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Profile updated successfully!');
                        closeEditProfileModal();
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error: ' + error.message);
                });
            }

            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                const modal = document.getElementById('editProfileModal');
                if (modal && e.target === modal) {
                    closeEditProfileModal();
                }
            });

            // Activity functions: fetch and render recent activities, and a helper to log new ones.
            const CURRENT_PROFILE_USER_ID = <?php echo json_encode($user_id); ?>;

            function renderActivity(activity) {
                // activity: { activity_id, user_id, activity_type, activity_description, activity_date, nice_date }
                const container = document.getElementById('recent-activities');
                if (!container) return;

                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center p-3 bg-gray-50 rounded-lg';

                const inner = document.createElement('div');
                inner.className = 'flex-1';

                const desc = document.createElement('p');
                desc.className = 'text-gray-800';
                desc.textContent = activity.activity_description;

                const dateP = document.createElement('p');
                dateP.className = 'text-sm text-gray-500';
                dateP.textContent = activity.nice_date || activity.activity_date;

                inner.appendChild(desc);
                inner.appendChild(dateP);
                wrapper.appendChild(inner);

                // Prepend newest activities
                if (container.firstChild) {
                    container.insertBefore(wrapper, container.firstChild);
                } else {
                    container.appendChild(wrapper);
                }
            }

            function fetchRecentActivities(limit = 10) {
                const id = CURRENT_PROFILE_USER_ID;
                fetch(`activity_actions.php?action=list&user_id=${encodeURIComponent(id)}&limit=${limit}`)
                    .then(res => res.json())
                    .then(data => {
                        const container = document.getElementById('recent-activities');
                        if (!container) return;
                        container.innerHTML = '';
                        if (!data.success) {
                            container.innerHTML = '<div class="text-sm text-red-500">Unable to load activities</div>';
                            return;
                        }
                        if (!data.activities || data.activities.length === 0) {
                            container.innerHTML = '<div class="text-sm text-gray-500">No recent activity</div>';
                            return;
                        }
                        data.activities.forEach(act => renderActivity(act));
                    })
                    .catch(err => {
                        const container = document.getElementById('recent-activities');
                        if (container) container.innerHTML = '<div class="text-sm text-red-500">Error loading activities</div>';
                        console.error('Fetch activities error', err);
                    });
            }

            /**
             * Log an activity for the current user.
             * Other pages should call: window.logActivity('product_add', 'Added product: NAME', userId)
             * If userId omitted, CURRENT_PROFILE_USER_ID will be used.
             */
            function logActivity(activity_type, activity_description, userId = CURRENT_PROFILE_USER_ID) {
                const form = new FormData();
                form.append('action', 'add');
                form.append('user_id', userId);
                form.append('activity_type', activity_type);
                form.append('activity_description', activity_description);

                return fetch('activity_actions.php', {
                    method: 'POST',
                    body: form
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.activity) {
                        // Prepend new activity to the list
                        renderActivity(data.activity);
                        // Dispatch an event for other scripts to listen to
                        document.dispatchEvent(new CustomEvent('activity:added', { detail: data.activity }));
                    }
                    return data;
                })
                .catch(err => {
                    console.error('Error logging activity', err);
                    throw err;
                });
            }

            // Load recent activities on page load
            document.addEventListener('DOMContentLoaded', function() {
                fetchRecentActivities(10);
            });

            // Initialize profile-related event bindings. Called by dashboard router when opening profile.
            function initProfileSection() {
                // Attach Edit Profile button in case inline onclick isn't present
                const editBtn = document.querySelector('button[onclick="openEditProfileModal()"]');
                if (editBtn && !editBtn._initBound) {
                    editBtn.addEventListener('click', openEditProfileModal);
                    editBtn._initBound = true;
                }

                // Ensure file input preview handler is attached
                setupProfileImagePreview();
            }

            // Backwards-compatible name expected by dashboard
            function initProfileEventsManually() {
                initProfileSection();
            }
</script>
