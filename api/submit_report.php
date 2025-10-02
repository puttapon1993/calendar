<?php
header('Content-Type: application/json');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อความ']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO problem_reports (message) VALUES (?)");
        $stmt->execute([$message]);
        echo json_encode(['success' => true, 'message' => 'ส่งข้อความเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลครับ']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
