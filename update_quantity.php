<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    
    if ($productId && $quantity !== null) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $stmt->execute([$quantity, $productId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    }
}