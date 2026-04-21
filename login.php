<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Inventory System</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="grid bg-red-800 items-start justify-center min-h-screen bg-[url(foodbg3.jpg)] bg-cover bg-center bg-no-repeat font-serif p-6">

<!-- login headings -->
  <div class="w-full max-w-sm bg-red-700 rounded-t-2xl shadow-lg p-6">
    <h3 class="text-2xl text-white mb-4 text-center">Welcome to</h3>
    <div class="flex items-start">
      <img src="inventorylogo.png" alt="" class="header-logo object-scale-down h-20 w-20 rounded-full mr-4">
      <h1 class="text-4xl text-red-100 mb-4">STOCK SYMPHONY</h1>
    </div>
    <p class="mt-4 text-white mb-4">Your ultimate inventory management solution!</p>
  </div>

  <!-- login form -->
  <div class="w-full max-w-sm bg-white bg-opacity-75 rounded-b-2xl shadow-lg p-6 mb-20">
    <h1 class="text-2xl font-bold text-center text-red-700 mb-4">Login</h1>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-r-lg flex items-start gap-3">
            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 mb-1">Login Failed</h3>
                <p class="text-sm text-red-700">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <form action="login_backend.php" method="POST" class="space-y-4">
      <div>
        <label class="block text-red-600 mb-1">Email</label>
        <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-blue-200" />
      </div>
      <div>
        <label class="block text-red-600 mb-1">Password</label>
        <input type="password" id="loginPassword" name="password" required class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-blue-200" />
        <!-- Password validation feedback -->
        <div id="loginPasswordFeedback" class="mt-2 text-sm hidden">
          <p class="font-semibold text-gray-700 mb-2">Password must have:</p>
          <ul class="space-y-1 text-gray-600">
              <li id="login-check-length" class="flex items-center">
                  <span class="inline-block w-4 h-4 mr-2 text-center leading-4">✓</span>
                  <span>at least 10 characters</span>
              </li>
              <li id="login-check-alphanumeric" class="flex items-center">
                  <span class="inline-block w-4 h-4 mr-2 text-center leading-4">✓</span>
                  <span>alphanumeric (letters and numbers)</span>
              </li>
              <li id="login-check-special" class="flex items-center">
                  <span class="inline-block w-4 h-4 mr-2 text-center leading-4">✓</span>
                  <span>at least 1 special character</span>
              </li>
          </ul>
        </div>
      </div>
      <button type="submit" id="loginSubmitBtn" class="w-full bg-red-700 text-white py-2 rounded-lg hover:bg-red-500 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>Login</button>
    </form>
    <p class="text-sm text-red-800 text-center mt-4">
      Don't have an account? 
      <a href="signup.php" class="text-red-400 hover:underline">Sign up</a>
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

    function updateLoginPasswordUI() {
        const password = document.getElementById('loginPassword').value;
        const checks = validatePassword(password);
        const feedback = document.getElementById('loginPasswordFeedback');
        const submitBtn = document.getElementById('loginSubmitBtn');
        
        // Show feedback if password has any input
        if (password.length > 0) {
            feedback.classList.remove('hidden');
        } else {
            feedback.classList.add('hidden');
        }
        
        // Update check markers
        const lengthCheck = document.getElementById('login-check-length');
        const alphanumericCheck = document.getElementById('login-check-alphanumeric');
        const specialCheck = document.getElementById('login-check-special');
        
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

    document.addEventListener('DOMContentLoaded', function() {
        const loginPassword = document.getElementById('loginPassword');
        if (loginPassword) {
            loginPassword.addEventListener('input', updateLoginPasswordUI);
        }
    });
  </script>
