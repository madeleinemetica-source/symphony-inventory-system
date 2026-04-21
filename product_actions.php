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

// Validate numeric fields for products
function validateProductNumbers($data) {
    if (isset($data['cost_price']) && (!is_numeric($data['cost_price']) || $data['cost_price'] <= 0)) {
        return "Cost price must be greater than 0";
    }
    
    if (isset($data['selling_price']) && (!is_numeric($data['selling_price']) || $data['selling_price'] <= 0)) {
        return "Selling price must be greater than 0";
    }
    
    if (isset($data['quantity_stock']) && (!is_numeric($data['quantity_stock']) || $data['quantity_stock'] < 0)) {
        return "Quantity stock must be zero or a positive number";
    }
    
    return null; // No errors
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = $_POST['action'] ?? '';
    
    // function to handle image upload
        function handleImageUpload($file) {
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
        $uploadDir = 'uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'product_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => true, 'file_path' => $filePath];
        } else {
            return ['success' => false, 'error' => 'Failed to upload image'];
        }
    }

    switch($action) {
        case 'update_quantity':
            $product_id = $_POST['product_id'] ?? 0;
            $quantity = intval($_POST['quantity'] ?? 0);

            if ($quantity < 0) {
                echo json_encode(['success' => false, 'error' => 'Quantity cannot be negative']);
                break;
            }
            
            $stmt = $db->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
            $result = $stmt->execute([$quantity, $product_id]);
            
            echo json_encode(['success' => $result, 'affected_rows' => $stmt->rowCount()]);
            break;
            
        case 'delete_product':
            $product_id = $_POST['product_id'] ?? 0;

            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
                break;
            }
            // Fetch product name for activity log
            $nameStmt = $db->prepare("SELECT product_name FROM products WHERE product_id = ?");
            $nameStmt->execute([$product_id]);
            $prodName = $nameStmt->fetchColumn();

            $stmt = $db->prepare("DELETE FROM products WHERE product_id = ?");
            $result = $stmt->execute([$product_id]);

            // Log activity if deletion succeeded
            if ($result) {
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $act->execute([$_SESSION['user_id'], 'product_delete', 'Deleted product: ' . ($prodName ?: $product_id)]);
                } catch (Exception $e) {
                    // non-fatal
                }
            }

            echo json_encode(['success' => $result, 'affected_rows' => $stmt->rowCount()]);
            break;
            
        case 'add_product':
            // Validate required fields
            $validation = validateRequired([
                'product_name' => $_POST['product_name'] ?? '',
                'category_id' => $_POST['category_id'] ?? ''
            ]);
            
            if (!$validation['success']) {
                echo json_encode($validation);
                break;
            }

            // Validate numeric fields
            $numberValidation = validateProductNumbers($_POST);
            if ($numberValidation) {
                echo json_encode(['success' => false, 'error' => $numberValidation]);
                break;
            }

            // Handle image upload
            $productImage = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['product_image']);
                if ($uploadResult['success']) {
                    $productImage = $uploadResult['file_path'];
                } else {
                    echo json_encode($uploadResult);
                    break;
                }
            }

            // Collect product data
            $productData = [
                'product_name' => sanitizeInput($_POST['product_name']),
                'category_id' => intval($_POST['category_id']),
                'brand_id' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
                'supplier_id' => !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null,
                'sku' => sanitizeInput($_POST['sku'] ?? ''),
                'unit' => sanitizeInput($_POST['unit'] ?? ''),
                'cost_price' => floatval($_POST['cost_price']),
                'selling_price' => floatval($_POST['selling_price']),
                'quantity_stock' => intval($_POST['quantity_stock']),
                'expiration_date' => !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null,
                'status' => ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive'
            ];

            // Add product image if uploaded
            if ($productImage) {
                $productData['product_image'] = $productImage;
            }

            // Build and execute INSERT query
            $columns = implode(', ', array_keys($productData));
            $placeholders = implode(', ', array_fill(0, count($productData), '?'));
            
            $stmt = $db->prepare("INSERT INTO products ($columns) VALUES ($placeholders)");
            $result = $stmt->execute(array_values($productData));
            $newId = $db->lastInsertId();

            // Debug: log the insert result
            error_log("Product INSERT - Result: " . ($result ? 'true' : 'false') . ", New ID: $newId, Error: " . json_encode($stmt->errorInfo()));

            // Log activity for add
            if ($result) {
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $act->execute([$_SESSION['user_id'], 'product_add', 'Added product: ' . $productData['product_name'] . ' (ID ' . $newId . ')']);
                } catch (Exception $e) {
                    error_log("Activity logging failed: " . $e->getMessage());
                }
            }

            echo json_encode([
                'success' => $result, 
                'id' => $newId,
                'message' => 'Product added successfully'
            ]);
            break;

        case 'update_product':
            $product_id = $_POST['product_id'] ?? 0;
            
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
                break;
            }

            // Validate required fields
            $validation = validateRequired([
                'product_name' => $_POST['product_name'] ?? '',
                'category_id' => $_POST['category_id'] ?? ''
            ]);
            
            if (!$validation['success']) {
                echo json_encode($validation);
                break;
            }

            // Validate numeric fields
            $numberValidation = validateProductNumbers($_POST);
            if ($numberValidation) {
                echo json_encode(['success' => false, 'error' => $numberValidation]);
                break;
            }

            // Handle image upload
            $productImage = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['product_image']);
                if ($uploadResult['success']) {
                    $productImage = $uploadResult['file_path'];
                    
                    // Delete old image if exists
                    $oldImageStmt = $db->prepare("SELECT product_image FROM products WHERE product_id = ?");
                    $oldImageStmt->execute([$product_id]);
                    $oldImage = $oldImageStmt->fetchColumn();
                    if ($oldImage && file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                } else {
                    echo json_encode($uploadResult);
                    break;
                }
            }

            // Collect update data
            $updateData = [
                'product_name' => sanitizeInput($_POST['product_name']),
                'category_id' => intval($_POST['category_id']),
                'brand_id' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
                'supplier_id' => !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null,
                'sku' => sanitizeInput($_POST['sku'] ?? ''),
                'unit' => sanitizeInput($_POST['unit'] ?? ''),
                'cost_price' => floatval($_POST['cost_price']),
                'selling_price' => floatval($_POST['selling_price']),
                'quantity_stock' => intval($_POST['quantity_stock']),
                'expiration_date' => !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null,
                'status' => ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive'
            ];

            // Add product image if uploaded
            if ($productImage) {
                $updateData['product_image'] = $productImage;
            }

            // Build and execute UPDATE query
            $setClause = implode(' = ?, ', array_keys($updateData)) . ' = ?';
            $values = array_values($updateData);
            $values[] = $product_id;
            
            $stmt = $db->prepare("UPDATE products SET $setClause WHERE product_id = ?");
            $result = $stmt->execute($values);

            // Log activity for update
            if ($result) {
                try {
                    $act = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
                    $act->execute([$_SESSION['user_id'], 'product_update', 'Updated product: ' . $updateData['product_name'] . ' (ID ' . $product_id . ')']);
                } catch (Exception $e) {
                    // non-fatal
                }
            }

            echo json_encode([
                'success' => $result, 
                'affected_rows' => $stmt->rowCount(),
                'message' => 'Product updated successfully'
            ]);
            break;

        case 'get_columns':
            $table = $_POST['table'] ?? '';
            
            if (empty($table)) {
                echo json_encode(['success' => false, 'error' => 'Table name required']);
                break;
            }
            
            // Get column names from the table
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode(['success' => true, 'columns' => $columns]);
            break;

        case 'get_product':
            $product_id = $_POST['product_id'] ?? 0;
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
                break;
            }

            $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                echo json_encode(['success' => true, 'product' => $product]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Product not found']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Product actions error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred: ' . $e->getMessage()]);
}
?>