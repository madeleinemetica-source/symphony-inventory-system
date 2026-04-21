<?php
// user_actions.php - COMPLETE FIXED VERSION
session_start();
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

// Turn off error display to users
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    if (!isset($_POST['action'])) {
        throw new Exception('No action specified');
    }

    $action = $_POST['action'];

    if ($action === 'update_profile') {
        // Check if database connection exists
        if (!isset($db)) {
            throw new Exception('Database connection not available');
        }

        $user_id = $_POST['user_id'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        
        // Basic validation
        if (empty($full_name) || empty($email)) {
            throw new Exception('Full name and email are required');
        }
        
        // Handle file upload (optional)
        $profile_picture = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/profiles/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $user_id . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                throw new Exception('Invalid file type');
            }
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                $profile_picture = $filePath;
            }
        }
        
        // Update database - SIMPLIFIED
        if ($profile_picture) {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE user_id = ?");
            $result = $stmt->execute([$full_name, $email, $profile_picture, $user_id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
            $result = $stmt->execute([$full_name, $email, $user_id]);
        }
        
        if ($result) {
            // Update session
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            if ($profile_picture) {
                $_SESSION['profile_picture'] = $profile_picture;
            }
            
            // Log activity with specific actions
            try {
                $activityDesc = '';
                if ($profile_picture) {
                    $activityDesc = 'Updated profile (changed photo and details)';
                } else {
                    $activityDesc = 'Updated profile details (name and/or email)';
                }
                
                $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                $act->execute([$user_id, 'user_profile_update', $activityDesc]);
            } catch (Exception $e) {
                // non-fatal
            }
            
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            throw new Exception('Database update failed - check if user exists');
        }
    }
    elseif ($action === 'factory_reset') {
        // Check if database connection exists
        if (!isset($db)) {
            throw new Exception('Database connection not available');
        }

        $user_id = $_SESSION['user_id'];
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Perform full reset. Many tables do not have a `user_id` column,
            // so delete dependent data in the correct order without a WHERE clause,
            // then delete the user record specifically.

            // Order matters because of foreign key constraints:
            // 1) products (depends on brands/categories/suppliers)
            // 2) brands
            // 3) categories
            // 4) suppliers
            // 5) user_activities
            // 6) users (delete the account of the current user)

            $deleteOrder = [
                'products',
                'brands',
                'categories',
                'suppliers',
                'user_activities'
            ];

            // Temporarily disable foreign key checks so we can delete rows
            // in a single operation without running into ordering issues
            // on self-referential constraints (e.g. categories.parent_id).
            $db->exec("SET FOREIGN_KEY_CHECKS=0");

            try {
                foreach ($deleteOrder as $tableName) {
                    $stmt = $db->prepare("DELETE FROM $tableName");
                    $stmt->execute();
                }

                // Finally delete the current user's account
                $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);

                // Commit transaction
                $db->commit();

                // Re-enable foreign key checks
                $db->exec("SET FOREIGN_KEY_CHECKS=1");

                // Destroy session
                session_destroy();

                echo json_encode(['success' => true, 'message' => 'Factory reset completed']);
            } catch (Exception $e) {
                // Ensure we re-enable foreign key checks before propagating error
                try { $db->exec("SET FOREIGN_KEY_CHECKS=1"); } catch (Exception $inner) {}
                // Re-throw to be caught by outer catch
                throw $e;
            }
            
        } catch (Exception $e) {
            // Rollback on error
            $db->rollBack();
            throw new Exception('Factory reset failed: ' . $e->getMessage());
        }
    }
    else {
        throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    // Always return valid JSON
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>