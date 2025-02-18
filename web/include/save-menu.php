<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/config/menu.json', json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}