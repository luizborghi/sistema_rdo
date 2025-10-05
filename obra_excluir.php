<?php
require_once 'config.php';
verificarLogin();

// Verificar permissão
if (!in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro'])) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para excluir obras! Apenas Admin e Engenheiro.'
    ];
    header('Location: obras.php');
    exit;
}

$obra_id = $_GET['id'] ?? null;

if (!$obra_id) {
    header('Location: obras.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Verificar se obra existe
    $stmt = $db->prepare("SELECT nome FROM obras WHERE id = ?");
    $stmt->execute([$obra_id]);
    $obra = $stmt->fetch();
    
    if (!$obra) {
        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'Obra não encontrada!'
        ];
        header('Location: obras.php');
        exit;
    }
    
    // Verificar quantos RDOs tem
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos WHERE obra_id = ?");
    $stmt->execute([$obra_id]);
    $total_rdos = $stmt->fetchColumn();
    
    // Excluir obra (cascade irá excluir RDOs também)
    $stmt = $db->prepare("DELETE FROM obras WHERE id = ?");
    $stmt->execute([$obra_id]);
    
    $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => "Obra '{$obra['nome']}' excluída com sucesso! ($total_rdos RDO(s) também foram excluídos)"
    ];
    
} catch (Exception $e) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Erro ao excluir obra: ' . $e->getMessage()
    ];
}

header('Location: obras.php');
exit;