<?php
require_once 'config.php';
verificarLogin();

$rdo_id = $_GET['id'] ?? null;

if (!$rdo_id) {
    header('Location: rdos.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Verificar se o RDO existe e se é rascunho
    $stmt = $db->prepare("SELECT * FROM rdos WHERE id = ? AND status = 'rascunho'");
    $stmt->execute([$rdo_id]);
    $rdo = $stmt->fetch();
    
    if (!$rdo) {
        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'RDO não encontrado ou não pode ser excluído!'
        ];
        header('Location: rdos.php');
        exit;
    }
    
    // Excluir (cascade delete irá remover os relacionados)
    $stmt = $db->prepare("DELETE FROM rdos WHERE id = ?");
    $stmt->execute([$rdo_id]);
    
    $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => 'RDO excluído com sucesso!'
    ];
    
} catch (Exception $e) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Erro ao excluir RDO: ' . $e->getMessage()
    ];
}

header('Location: rdos.php');
exit;