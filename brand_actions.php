<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

error_reporting(0);
ini_set('display_errors', 0);

// Function to validate and sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateRequired($fields) {
    foreach ($fields as $field => $value) {
        if (empty($value)) {
            return ['success' => false, 'error' => "Field '$field' is required"];
        }
    }
    return ['success' => true];
}

// Function to handle brand logo upload
function handleImageUpload($file, $type = 'brands') {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, GIF, and WebP images are allowed'];
    }
    
    // Validate file size
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'error' => 'Image size must be less than 5MB'];
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = "uploads/$type/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = $type . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $filePath];
    } else {
        return ['success' => false, 'error' => 'Failed to upload image'];
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add_brand':
            $brand_name = sanitizeInput($_POST['brand_name'] ?? '');
            $supplier_id = $_POST['supplier_id'] ?? null;
            $description = sanitizeInput($_POST['description'] ?? '');
            $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
            
            // Validate required fields
            $validation = validateRequired(['brand_name' => $brand_name]);
            if (!$validation['success']) {
                echo json_encode($validation);
                break;
            }

            // Handle logo upload
            $brand_logo = null;
            if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['brand_logo'], 'brands');
                if (!$uploadResult['success']) {
                    echo json_encode(['success' => false, 'error' => $uploadResult['error']]);
                    exit;
                }
                $brand_logo = $uploadResult['file_path'];
            }
            
            // Check if brand name already exists
            $check_stmt = $db->prepare("SELECT brand_id FROM brands WHERE brand_name = ?");
            $check_stmt->execute([$brand_name]);
            if ($check_stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Brand name already exists']);
                break;
            }
            
            $stmt = $db->prepare("INSERT INTO brands (brand_name, supplier_id, description, brand_logo, status) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$brand_name, $supplier_id, $description, $brand_logo, $status]);
            
            // Log activity
            if ($result) {
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $act->execute([$_SESSION['user_id'], 'brand_add', 'Added brand: ' . $brand_name]);
                } catch (Exception $e) {
                    // non-fatal
                }
            }
            
            echo json_encode([
                'success' => $result, 
                'id' => $db->lastInsertId(),
                'message' => 'Brand added successfully'
            ]);
            break;
            
        case 'update_brand':
            $brand_id = $_POST['brand_id'] ?? 0;
            $brand_name = sanitizeInput($_POST['brand_name'] ?? '');
            $supplier_id = $_POST['supplier_id'] ?? null;
            $description = sanitizeInput($_POST['description'] ?? '');
            $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
            
            if ($brand_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid brand ID']);
                break;
            }
            
            // Validate required fields
            $validation = validateRequired(['brand_name' => $brand_name]);
            if (!$validation['success']) {
                echo json_encode($validation);
                break;
            }

            // Handle logo upload and removal
            $brand_logo = null;
            $remove_logo = ($_POST['remove_logo'] ?? '') === '1';
            
            if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['brand_logo'], 'brands');
                if (!$uploadResult['success']) {
                    echo json_encode(['success' => false, 'error' => $uploadResult['error']]);
                    exit;
                }
                $brand_logo = $uploadResult['file_path'];
                
                // Delete old image if exists
                $oldLogoStmt = $db->prepare("SELECT brand_logo FROM brands WHERE brand_id = ?");
                $oldLogoStmt->execute([$brand_id]);
                $oldLogo = $oldLogoStmt->fetchColumn();
                if ($oldLogo && file_exists($oldLogo)) {
                    unlink($oldLogo);
                }
            }
            
            // Check if brand name already exists (excluding current brand)
            $check_stmt = $db->prepare("SELECT brand_id FROM brands WHERE brand_name = ? AND brand_id != ?");
            $check_stmt->execute([$brand_name, $brand_id]);
            if ($check_stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Brand name already exists']);
                break;
            }
            
            // Build update query based on what's being changed
            if ($brand_logo !== null) {
                // New logo uploaded
                $stmt = $db->prepare("UPDATE brands SET brand_name = ?, supplier_id = ?, description = ?, brand_logo = ?, status = ? WHERE brand_id = ?");
                $result = $stmt->execute([$brand_name, $supplier_id, $description, $brand_logo, $status, $brand_id]);
            } elseif ($remove_logo) {
                // Logo removal requested
                $oldLogoStmt = $db->prepare("SELECT brand_logo FROM brands WHERE brand_id = ?");
                $oldLogoStmt->execute([$brand_id]);
                $oldLogo = $oldLogoStmt->fetchColumn();
                if ($oldLogo && file_exists($oldLogo)) {
                    unlink($oldLogo);
                }
                $stmt = $db->prepare("UPDATE brands SET brand_name = ?, supplier_id = ?, description = ?, brand_logo = NULL, status = ? WHERE brand_id = ?");
                $result = $stmt->execute([$brand_name, $supplier_id, $description, $status, $brand_id]);
            } else {
                // No logo changes
                $stmt = $db->prepare("UPDATE brands SET brand_name = ?, supplier_id = ?, description = ?, status = ? WHERE brand_id = ?");
                $result = $stmt->execute([$brand_name, $supplier_id, $description, $status, $brand_id]);
            }
            
            // Log activity
            if ($result) {
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $act->execute([$_SESSION['user_id'], 'brand_update', 'Updated brand: ' . $brand_name . ' (ID ' . $brand_id . ')']);
                } catch (Exception $e) {
                    // non-fatal
                }
            }
            
            echo json_encode([
                'success' => $result, 
                'affected_rows' => $stmt->rowCount(),
                'message' => 'Brand updated successfully'
            ]);
            break;
            
        case 'delete_brand':
            $brand_id = $_POST['brand_id'] ?? 0;
            $delete_products = ($_POST['delete_products'] ?? 'false') === 'true';
            
            if ($brand_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid brand ID']);
                break;
            }
            
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Check if brand has products
                $check_stmt = $db->prepare("SELECT COUNT(*) as product_count FROM products WHERE brand_id = ?");
                $check_stmt->execute([$brand_id]);
                $product_count = $check_stmt->fetchColumn();
                
                if ($product_count > 0 && !$delete_products) {
                    // If products exist but user didn't confirm deletion, return warning
                    echo json_encode([
                        'success' => false, 
                        'error' => 'brand_has_products',
                        'product_count' => $product_count,
                        'message' => "This brand has $product_count product(s) associated with it."
                    ]);
                    $db->rollBack();
                    break;
                }
                
                // Delete products if confirmed
                if ($product_count > 0 && $delete_products) {
                    $delete_products_stmt = $db->prepare("DELETE FROM products WHERE brand_id = ?");
                    $delete_products_stmt->execute([$brand_id]);
                    $deleted_products_count = $delete_products_stmt->rowCount();
                }
                
                // Fetch brand name BEFORE deletion for logging
                $brandNameStmt = $db->prepare("SELECT brand_name FROM brands WHERE brand_id = ?");
                $brandNameStmt->execute([$brand_id]);
                $brandName = $brandNameStmt->fetchColumn() ?: 'Unknown';

                // Delete the brand
                $stmt = $db->prepare("DELETE FROM brands WHERE brand_id = ?");
                $result = $stmt->execute([$brand_id]);
                $deleted_brand = $stmt->rowCount();
                
                // Commit transaction
                $db->commit();
                
                // Log activity (use $brandName fetched earlier)
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $act->execute([$_SESSION['user_id'], 'brand_delete', 'Deleted brand: ' . $brandName . ($product_count > 0 ? ' (with ' . $product_count . ' product(s))' : '')]);
                } catch (Exception $e) {
                    // non-fatal
                }
                
                echo json_encode([
                    'success' => $result, 
                    'deleted_brand' => $deleted_brand,
                    'deleted_products' => $deleted_products_count ?? 0,
                    'total_products' => $product_count,
                    'message' => $product_count > 0 ? 
                        "Brand and $product_count product(s) deleted successfully" : 
                        "Brand deleted successfully"
                ]);
                
            } catch (Exception $e) {
                // Rollback on error
                $db->rollBack();
                throw new Exception('Delete operation failed: ' . $e->getMessage());
            }
            break;
            
        case 'get_brand':
            $brand_id = $_POST['brand_id'] ?? 0;
            
            if ($brand_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid brand ID']);
                break;
            }
            
            $stmt = $db->prepare("
                SELECT b.*, s.supplier_name, s.contact_person, s.email, s.phone 
                FROM brands b 
                LEFT JOIN suppliers s ON b.supplier_id = s.supplier_id 
                WHERE b.brand_id = ?
            ");
            $stmt->execute([$brand_id]);
            $brand = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($brand) {
                echo json_encode(['success' => true, 'brand' => $brand]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Brand not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Brand actions error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>