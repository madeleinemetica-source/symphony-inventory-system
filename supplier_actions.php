<?php
require_once 'config.php';

session_start();

header('Content-Type: application/json');

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get the action from POST data
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_supplier':
            addSupplier();
            break;
        case 'get_supplier':
            getSupplier();
            break;
        case 'edit_supplier':
        case 'update_supplier':
            // Accept both 'edit_supplier' and 'update_supplier' action names
            updateSupplier();
            break;
        case 'delete_supplier':
            deleteSupplier();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

function addSupplier() {
    global $db;
    
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = (($_POST['status'] ?? '') == 'active') ? 'active' : 'inactive';
    
    if (empty($supplier_name)) {
        echo json_encode(['success' => false, 'error' => 'Supplier name is required']);
        return;
    }

    try {
        // Check for duplicate supplier name
        $dupStmt = $db->prepare("SELECT supplier_id FROM suppliers WHERE supplier_name = ?");
        $dupStmt->execute([$supplier_name]);
        if ($dupStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Supplier name already exists']);
            return;
        }

        $stmt = $db->prepare('INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$supplier_name, $contact_person, $email, $phone, $address, $status]);
        $id = $db->lastInsertId();

        // Log activity
        try {
            if (isset($_SESSION['user_id'])) {
                $ua = $db->prepare('INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())');
                $ua->execute([$_SESSION['user_id'], 'supplier_add', "Added supplier: $supplier_name"]);
            }
        } catch (Exception $e) {
            // non-fatal
        }

        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getSupplier() {
    global $db;
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    try {
        $stmt = $db->prepare('SELECT * FROM suppliers WHERE supplier_id = ?');
        $stmt->execute([$supplier_id]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'supplier' => $supplier]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updateSupplier() {
    global $db;
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    try {
        $stmt = $db->prepare('UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ?, phone = ?, address = ?, status = ? WHERE supplier_id = ?');
        $stmt->execute([$supplier_name, $contact_person, $email, $phone, $address, $status, $supplier_id]);

        // Log activity
        try {
            if (isset($_SESSION['user_id'])) {
                $ua = $db->prepare('INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())');
                $ua->execute([$_SESSION['user_id'], 'supplier_update', "Updated supplier: $supplier_name"]);
            }
        } catch (Exception $e) {
            // non-fatal
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function deleteSupplier() {
    global $db;
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $delete_with_related = ($_POST['delete_with_related'] ?? 'false') === 'true';
    try {
        // First, check for related products or brands
        $pstmt = $db->prepare('SELECT COUNT(*) FROM products WHERE supplier_id = ?');
        $pstmt->execute([$supplier_id]);
        $productCount = $pstmt->fetchColumn();

        $bstmt = $db->prepare('SELECT COUNT(*) FROM brands WHERE supplier_id = ?');
        $bstmt->execute([$supplier_id]);
        $brandCount = $bstmt->fetchColumn();

        if (($productCount > 0 || $brandCount > 0) && !$delete_with_related) {
            // Inform caller about related records
            echo json_encode(['success' => false, 'error' => 'related_exist', 'product_count' => intval($productCount), 'brand_count' => intval($brandCount)]);
            return;
        }

        // Fetch supplier name for logging
        $stmt = $db->prepare('SELECT supplier_name FROM suppliers WHERE supplier_id = ?');
        $stmt->execute([$supplier_id]);
        $supplierName = $stmt->fetchColumn();

        // If delete_with_related, delete products and brands first
        if ($delete_with_related) {
            $db->beginTransaction();
            $db->prepare('DELETE FROM products WHERE supplier_id = ?')->execute([$supplier_id]);
            $db->prepare('DELETE FROM brands WHERE supplier_id = ?')->execute([$supplier_id]);
            $db->prepare('DELETE FROM suppliers WHERE supplier_id = ?')->execute([$supplier_id]);
            $db->commit();
        } else {
            $stmt = $db->prepare('DELETE FROM suppliers WHERE supplier_id = ?');
            $stmt->execute([$supplier_id]);
        }

        // Log activity
        try {
            if (isset($_SESSION['user_id'])) {
                $ua = $db->prepare('INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())');
                $ua->execute([$_SESSION['user_id'], 'supplier_delete', "Deleted supplier: $supplierName"]);
            }
        } catch (Exception $e) {
            // non-fatal
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // rollback if needed
        try { $db->rollBack(); } catch (Exception $ex) {}
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>