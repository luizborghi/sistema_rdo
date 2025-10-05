<?php
require_once 'config.php';
verificarLogin();

$db = Database::getInstance()->getConnection();

// Últimos 7 dias
$labels = [];
$values = [];

for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($data));
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos WHERE data_rdo = ?");
    $stmt->execute([$data]);
    $values[] = (int)$stmt->fetchColumn();
}

jsonResponse([
    'labels' => $labels,
    'values' => $values
]);