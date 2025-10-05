<?php
require_once 'config.php';
require_once 'email_config.php';
verificarLogin();

// Verificar permissão
if (!in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro', 'fiscal'])) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para aprovar RDOs!'
    ];
    header('Location: rdos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rdos.php');
    exit;
}

$rdo_id = (int)$_POST['rdo_id'];
$acao = $_POST['acao'] ?? '';
$comentarios = $_POST['comentarios'] ?? '';

if (!in_array($acao, ['aprovar', 'rejeitar'])) {
    header('Location: rdos.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();
    
    // Verificar se RDO existe e está pendente
    $stmt = $db->prepare("
        SELECT r.*, o.fiscal_id 
        FROM rdos r
        JOIN obras o ON r.obra_id = o.id
        WHERE r.id = ? AND r.status = 'enviado'
    ");
    $stmt->execute([$rdo_id]);
    $rdo = $stmt->fetch();
    
    if (!$rdo) {
        throw new Exception('RDO não encontrado ou não está aguardando aprovação!');
    }
    
    // Verificar se o usuário é o fiscal responsável pela obra OU é admin/engenheiro
    $pode_aprovar_rdo = false;
    
    if ($_SESSION['usuario_tipo'] == 'admin' || $_SESSION['usuario_tipo'] == 'engenheiro') {
        $pode_aprovar_rdo = true; // Admin e Engenheiro podem aprovar qualquer RDO
    } elseif ($_SESSION['usuario_tipo'] == 'fiscal' && $rdo['fiscal_id'] == $_SESSION['usuario_id']) {
        $pode_aprovar_rdo = true; // Fiscal só pode aprovar RDOs das suas obras
    }
    
    if (!$pode_aprovar_rdo) {
        throw new Exception('Você não tem permissão para aprovar este RDO. Apenas o fiscal responsável pela obra pode aprovar.');
    }
    
    // Definir novo status
    $novo_status = ($acao === 'aprovar') ? 'aprovado' : 'rejeitado';
    
    // Atualizar RDO
    $stmt = $db->prepare("
        UPDATE rdos SET 
            status = ?,
            aprovado_por = ?,
            data_aprovacao = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$novo_status, $_SESSION['usuario_id'], $rdo_id]);
    
    // Registrar no histórico
    $acao_texto = ($acao === 'aprovar') 
        ? 'RDO aprovado' 
        : 'RDO rejeitado';
    
    $detalhes = !empty($comentarios) ? 'Comentários: ' . $comentarios : null;
    
    $stmt = $db->prepare("
        INSERT INTO rdo_historico (rdo_id, usuario_id, acao, detalhes) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$rdo_id, $_SESSION['usuario_id'], $acao_texto, $detalhes]);
    
    // Buscar dados completos para o email
    $stmt = $db->prepare("
        SELECT r.*, o.nome as obra_nome, 
               c.nome as criador_nome, c.email as criador_email,
               a.nome as aprovador_nome, a.email as aprovador_email
        FROM rdos r
        JOIN obras o ON r.obra_id = o.id
        JOIN usuarios c ON r.criado_por = c.id
        LEFT JOIN usuarios a ON r.aprovado_por = a.id
        WHERE r.id = ?
    ");
    $stmt->execute([$rdo_id]);
    $rdo_completo = $stmt->fetch();
    
    // Enviar email para o criador
    if ($rdo_completo && !empty($rdo_completo['criador_email'])) {
        $obra_data = ['nome' => $rdo_completo['obra_nome']];
        $criador_data = ['nome' => $rdo_completo['criador_nome']];
        $aprovador_data = ['nome' => $rdo_completo['aprovador_nome']];
        
        $assunto = "RDO #{$rdo_id} " . ($acao === 'aprovar' ? 'Aprovado' : 'Rejeitado');
        $mensagem = EmailSender::rdoAprovado(
            $rdo_completo, 
            $obra_data, 
            $criador_data, 
            $aprovador_data, 
            $novo_status, 
            $comentarios
        );
        
        EmailSender::enviar($rdo_completo['criador_email'], $assunto, $mensagem);
    }
    
    $db->commit();
    
    $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => ($acao === 'aprovar') 
            ? 'RDO aprovado com sucesso!' 
            : 'RDO rejeitado com sucesso!'
    ];
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Erro ao processar aprovação: ' . $e->getMessage()
    ];
}

header('Location: rdo_visualizar.php?id=' . $rdo_id);
exit;