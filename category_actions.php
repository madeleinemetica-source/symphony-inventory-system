<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add_category':
            $category_name = sanitizeInput($_POST['category_name'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $description = sanitizeInput($_POST['description'] ?? '');
            
            // Validate required fields
            $validation = validateRequired(['category_name' => $category_name]);
            if (!$validation['success']) {
                echo json_encode($validation);
                break;
            }
            
            // Check if category name already exists
            $check_stmt = $db->prepare("SELECT category_id FROM categories WHERE category_name = ?");
            $check_stmt->execute([$category_name]);
            if ($check_stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Category name already exists']);
                break;
            }
            
            // If parent_id is provided, verify it exists
            if ($parent_id) {
                $parent_check = $db->prepare("SELECT category_id FROM categories WHERE category_id = ?");
                $parent_check->execute([$parent_id]);
                if (!$parent_check->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Invalid parent category']);
                    break;
                }
            }
            
            $stmt = $db->prepare("INSERT INTO categories (category_name, parent_id, description) VALUES (?, ?, ?)");
            $result = $stmt->execute([$category_name, $parent_id, $description]);
            
            // Debug: log the insert result
            error_log("Category INSERT - Result: " . ($result ? 'true' : 'false') . ", Error: " . json_encode($stmt->errorInfo()));
            
            if ($result) {
                $lastId = $db->lastInsertId();
                error_log("Category INSERT Success - New ID: $lastId");
                
                // Log activity
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $act->execute([$_SESSION['user_id'], 'category_add', 'Added category: ' . $category_name]);
                } catch (Exception $e) {
                    error_log("Activity logging failed: " . $e->getMessage());
                }
            } else {
                error_log("Category INSERT Failed - Errors: " . json_encode($stmt->errorInfo()));
            }
            
            echo json_encode([
                'success' => $result, 
                'id' => $db->lastInsertId(),
                'message' => 'Category added successfully'
            ]);
            break;
            
        case 'update_category':
            $category_id = $_POST['category_id'] ?? 0;
            $category_name = sanitizeInput($_POST['category_name'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if ($category_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
                break;
            }
            
            // Validate required fields
            $validation = validateRequired(['category_name' => $category_name]);
            if (!$validation['success']) {
                echo json_encode($validation);
                break;
            }
            
            // Check if category name already exists (excluding current category)
            $check_stmt = $db->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?");
            $check_stmt->execute([$category_name, $category_id]);
            if ($check_stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Category name already exists']);
                break;
            }
            
            // Prevent circular reference (category cannot be its own parent)
            if ($parent_id == $category_id) {
                echo json_encode(['success' => false, 'error' => 'Category cannot be its own parent']);
                break;
            }
            
            // If parent_id is provided, verify it exists
            if ($parent_id) {
                $parent_check = $db->prepare("SELECT category_id FROM categories WHERE category_id = ?");
                $parent_check->execute([$parent_id]);
                if (!$parent_check->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Invalid parent category']);
                    break;
                }
            }
            
            $stmt = $db->prepare("UPDATE categories SET category_name = ?, parent_id = ?, description = ? WHERE category_id = ?");
            $result = $stmt->execute([$category_name, $parent_id, $description, $category_id]);
            
            if ($result) {
                // Log activity
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $act->execute([$_SESSION['user_id'], 'category_update', 'Updated category: ' . $category_name . ' (ID ' . $category_id . ')']);
                } catch (Exception $e) {
                    // non-fatal
                }
            }
            
            echo json_encode([
                'success' => $result, 
                'affected_rows' => $stmt->rowCount(),
                'message' => 'Category updated successfully'
            ]);
            break;
            
        case 'delete_category':
            $category_id = $_POST['category_id'] ?? 0;
            $delete_with_children = ($_POST['delete_with_children'] ?? 'false') === 'true';
            
            if ($category_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
                break;
            }
            
            // Get the category to check if it's main or sub
            $cat_stmt = $db->prepare("SELECT parent_id FROM categories WHERE category_id = ?");
            $cat_stmt->execute([$category_id]);
            $category = $cat_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                echo json_encode(['success' => false, 'error' => 'Category not found']);
                break;
            }
            
            $is_main_category = $category['parent_id'] === null;
            
            // Check if category has subcategories (only relevant for main categories)
            $check_subcategories = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
            $check_subcategories->execute([$category_id]);
            $subcategory_result = $check_subcategories->fetch(PDO::FETCH_ASSOC);
            $subcategory_count = $subcategory_result['count'];
            
            // Check total products (including subcategories if main)
            if ($is_main_category) {
                // Main category: check products in main + all subcategories
                $check_products = $db->prepare("
                    SELECT COUNT(*) as count FROM products WHERE 
                    category_id = ? OR category_id IN (
                        SELECT category_id FROM categories WHERE parent_id = ?
                    )
                ");
                $check_products->execute([$category_id, $category_id]);
            } else {
                // Sub category: check only direct products
                $check_products = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                $check_products->execute([$category_id]);
            }
            $product_result = $check_products->fetch(PDO::FETCH_ASSOC);
            $product_count = $product_result['count'];
            
            // If main category with subcategories, warn about cascade
            if ($is_main_category && $subcategory_count > 0 && !$delete_with_children) {
                echo json_encode([
                    'success' => false,
                    'error' => 'main_has_subcategories',
                    'subcategory_count' => $subcategory_count,
                    'product_count' => $product_count,
                    'message' => "This main category has $subcategory_count subcategory(ies) and $product_count product(s)"
                ]);
                break;
            }
            
            // Start transaction for safe deletion
            $db->beginTransaction();
            
            try {
                if ($is_main_category && $delete_with_children) {
                    // Delete all products from this main category and its subcategories
                    $delete_products = $db->prepare("
                        DELETE FROM products WHERE 
                        category_id = ? OR category_id IN (
                            SELECT category_id FROM categories WHERE parent_id = ?
                        )
                    ");
                    $delete_products->execute([$category_id, $category_id]);
                    $deleted_products = $delete_products->rowCount();
                    
                    // Delete all subcategories
                    $delete_subcats = $db->prepare("DELETE FROM categories WHERE parent_id = ?");
                    $delete_subcats->execute([$category_id]);
                    $deleted_subcats = $delete_subcats->rowCount();
                } else {
                    // Just delete products in this category
                    $delete_products = $db->prepare("DELETE FROM products WHERE category_id = ?");
                    $delete_products->execute([$category_id]);
                    $deleted_products = $delete_products->rowCount();
                    $deleted_subcats = 0;
                }
                
                // Fetch category name BEFORE deletion for logging
                $catNameStmt = $db->prepare("SELECT category_name FROM categories WHERE category_id = ?");
                $catNameStmt->execute([$category_id]);
                $catName = $catNameStmt->fetchColumn() ?: 'Unknown';

                // Delete the category itself
                $stmt = $db->prepare("DELETE FROM categories WHERE category_id = ?");
                $stmt->execute([$category_id]);
                
                $db->commit();
                
                // Log activity (use $catName fetched earlier)
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $typeLabel = $is_main_category ? 'main category' : 'sub-category';
                    $deletedInfo = ($deleted_products > 0 || $deleted_subcats > 0) ? ' (with ' . ($deleted_products > 0 ? $deleted_products . ' product(s)' : '') . ($deleted_subcats > 0 ? ($deleted_products > 0 ? ' and ' : '') . $deleted_subcats . ' sub-category(ies)' : '') . ')' : '';
                    $act->execute([$_SESSION['user_id'], 'category_delete', 'Deleted ' . $typeLabel . ': ' . $catName . $deletedInfo]);
                } catch (Exception $e) {
                    // non-fatal
                }
                
                echo json_encode([
                    'success' => true,
                    'deleted_products' => $deleted_products,
                    'deleted_subcategories' => $deleted_subcats,
                    'message' => 'Category deleted successfully'
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'error' => 'Delete operation failed: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_category':
            $category_id = $_POST['category_id'] ?? 0;
            
            if ($category_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
                break;
            }
            
            $stmt = $db->prepare("SELECT * FROM categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($category) {
                echo json_encode(['success' => true, 'category' => $category]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Category not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Category actions error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>