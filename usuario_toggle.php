<?php
require_once 'config.php';
verificarLogin();

// Apenas admin pode ativar/desativar usuários
if ($_SESSION['usuario_tipo'] != 'admin') {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Apenas administradores podem ativar/desativar usuários!'
    ];
    header('Location: dashboard.php');
    exit;
}

$usuario_id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

if (!$usuario_id || $status === null) {
    header('Location: usuarios.php');
    exit;
}

// Não permitir desativar a si mesmo
if ($usuario_id == $_SESSION['usuario_id']) {
    $_SESSION['mensagem'] = [
        'tipo' => 'warning',
        'texto' => 'Você não pode desativar sua própria conta!'
    ];
    header('Location: usuarios.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Buscar nome do usuário
    $stmt = $db->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuário não encontrado!');
    }
    
    // Atualizar status
    $stmt = $db->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
    $stmt->execute([$status, $usuario_id]);
    
    $acao = $status == 1 ? 'ativado' : 'desativado';
    
    $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => "Usuário '{$usuario['nome']}' {$acao} com sucesso!"
    ];
    
} catch (Exception $e) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Erro: ' . $e->getMessage()
    ];
}

header('Location: usuarios.php');
exit;