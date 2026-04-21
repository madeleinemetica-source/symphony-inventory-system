<!DOCTYPE html>
<html>
<head>
    <title>Sign Up | Inventory System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-800 flex mt-20 items-baseline justify-evenly min-h-screen bg-[url(foodbg3.jpg)] bg-cover bg-center bg-no-repeat font-serif">

<!-- company information -->
<div class="grid grid-cols-1">
   <div class="grid grid-cols-1 w-full max-w-md bg-red-700 rounded-t-2xl shadow-lg p-6">
    <h3 class="text-2xl text-white mb-4">Welcome to</h3>
    <div class="flex items-start">
      <img src="inventorylogo.png" alt="" class="header-logo object-scale-down h-10 w-10 rounded-full mr-2">
      <h1 class="text-4xl text-red-100 mb-4">STOCK SYMPHONY</h1>
    </div>
   </div>
   <div class="w-full max-w-md rounded-b-2xl shadow-lg p-6 bg-white bg-opacity-25">
    <p class="mt-4">Your ultimate inventory management solution. Streamline your stock control, track inventory levels, and optimize your supply chain with ease. Join us today and experience the harmony of efficient inventory management!</p>
   </div>
</div>

<!-- signup form -->
<div class="w-full max-w-sm bg-gray-100 bg-opacity-25 rounded-2xl shadow-lg p-6">
    <h1 class="text-2xl font-bold text-center text-red-700 mb-4">Create Account</h1>
    
    <!-- Display Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <!-- SIGNUP FORM -->
    <form action="signup_process.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block text-black mb-1">Full Name</label>
            <input type="text" name="full_name" required class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-red-200">
        </div>
        <div>
            <label class="block text-black mb-1">Email</label>
            <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-red-200">
        </div>
        <div>
            <label class="block text-black mb-1">Password</label>
            <input type="password" id="signupPassword" name="password" required class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-red-200">
            <!-- Password validation feedback -->
            <div id="passwordFeedback" class="mt-2 text-sm hidden">
                <p class="font-semibold text-gray-700 mb-2">Password must have:</p>
                <ul class="space-y-1 text-gray-600">
                    <li id="check-length" class="flex items-center">
                        <span class="inline-block w-4 h-4 mr-2 text-center leading-4">✓</span>
                        <span>at least 10 characters</span>
                    </li>
                    <li id="check-alphanumeric" class="flex items-center">
                        <span class="inline-block w-4 h-4 mr-2 text-center leading-4">✓</span>
                        <span>alphanumeric (letters and numbers)</span>
                    </li>
                    <li id="check-special" class="flex items-center">
                        <span class="inline-block w-4 h-4 mr-2 text-center leading-4">✓</span>
                        <span>at least 1 special character</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Profile Picture Upload -->
        <div class="flex items-center space-x-4 pt-2">
            <div class="w-16 h-16 bg-white rounded-full overflow-hidden flex items-center justify-center border border-gray-200">
                <img id="avatarPreview" src="default_profile.png" alt="Profile preview" class="w-full h-full object-cover">
            </div>
            <div class="flex-1">
                <label class="block text-black mb-1">Profile Photo (optional)</label>
                <div class="flex items-center space-x-2">
                    <input id="profileFile" name="profile_picture" type="file" accept="image/*" class="hidden">
                    <button id="uploadBtn" type="button" class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50">Upload Profile</button>
                    <span id="fileName" class="text-sm text-gray-600">No file chosen</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF, WEBP. Max 2MB.</p>
            </div>
        </div>

        <button type="submit" id="signupSubmitBtn" class="w-full bg-red-500 text-white py-2 rounded-lg hover:bg-green-600 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>Sign Up</button>
    </form>
    <p class="text-sm text-red-600 text-center mt-4">
        Already have an account? 
        <a href="login.php" class="text-green-600 hover:underline">Login</a>
    </p>
</div>

<script>
// Password validation function
function validatePassword(password) {
    const checks = {
        length: password.length >= 10,
        alphanumeric: /^[a-zA-Z0-9!@#$%^&*()_\-=+\[\]{};:'",./<>?\\|`~\s]*$/.test(password) && /[a-zA-Z]/.test(password) && /[0-9]/.test(password),
        special: /[!@#$%^&*()_\-=+\[\]{};:'",./<>?\\|`~]/.test(password)
    };
    return checks;
}

function updatePasswordUI() {
    const password = document.getElementById('signupPassword').value;
    const checks = validatePassword(password);
    const feedback = document.getElementById('passwordFeedback');
    const submitBtn = document.getElementById('signupSubmitBtn');
    
    // Show feedback if password has any input
    if (password.length > 0) {
        feedback.classList.remove('hidden');
    } else {
        feedback.classList.add('hidden');
    }
    
    // Update check markers
    const lengthCheck = document.getElementById('check-length');
    const alphanumericCheck = document.getElementById('check-alphanumeric');
    const specialCheck = document.getElementById('check-special');
    
    lengthCheck.classList.toggle('text-green-600', checks.length);
    lengthCheck.classList.toggle('text-gray-400', !checks.length);
    
    alphanumericCheck.classList.toggle('text-green-600', checks.alphanumeric);
    alphanumericCheck.classList.toggle('text-gray-400', !checks.alphanumeric);
    
    specialCheck.classList.toggle('text-green-600', checks.special);
    specialCheck.classList.toggle('text-gray-400', !checks.special);
    
    // Enable/disable submit button
    const allValid = checks.length && checks.alphanumeric && checks.special;
    submitBtn.disabled = !allValid;
}

// Simple file upload preview
document.addEventListener('DOMContentLoaded', function() {
    const signupPassword = document.getElementById('signupPassword');
    if (signupPassword) {
        signupPassword.addEventListener('input', updatePasswordUI);
    }
    const uploadBtn = document.getElementById('uploadBtn');
    const profileFile = document.getElementById('profileFile');
    const avatarPreview = document.getElementById('avatarPreview');
    const fileNameLabel = document.getElementById('fileName');

    if (uploadBtn && profileFile) {
        uploadBtn.addEventListener('click', function() {
            profileFile.click();
        });

        profileFile.addEventListener('change', function(ev) {
            const file = ev.target.files[0];
            
            if (!file) {
                fileNameLabel.textContent = 'No file chosen';
                avatarPreview.src = 'default_profile.jpg';
                return;
            }

            // Validate file size
            if (file.size > 2 * 1024 * 1024) {
                alert('File size too large! Please choose a file smaller than 2MB.');
                profileFile.value = '';
                fileNameLabel.textContent = 'No file chosen';
                avatarPreview.src = 'default_profile.jpg';
                return;
            }

            fileNameLabel.textContent = file.name;

            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>
</body>
</html>