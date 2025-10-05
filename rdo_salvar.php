<?php
require_once 'config.php';
require_once 'email_config.php';
verificarLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rdos.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();
    
    // Dados do RDO
    $obra_id = (int)$_POST['obra_id'];
    $data_rdo = $_POST['data_rdo'];
    $periodo = sanitize($_POST['periodo']);
    $clima = sanitize($_POST['clima'] ?? 'sol');
    $temperatura = !empty($_POST['temperatura']) ? (float)$_POST['temperatura'] : null;
    $servicos = $_POST['servicos_executados'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    $acao = $_POST['acao'] ?? 'rascunho';
    $status = ($acao === 'enviar') ? 'enviado' : 'rascunho';
    $rdo_id = $_POST['rdo_id'] ?? null;
    
    if ($rdo_id) {
        // Atualizar RDO existente
        $sql = "UPDATE rdos SET 
                obra_id = ?, data_rdo = ?, periodo = ?, clima = ?, 
                temperatura = ?, servicos_executados = ?, observacoes = ?, status = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $obra_id, $data_rdo, $periodo, $clima, 
            $temperatura, $servicos, $observacoes, $status, $rdo_id
        ]);
        
        // Deletar itens antigos para inserir novos
        $db->prepare("DELETE FROM rdo_mao_obra WHERE rdo_id = ?")->execute([$rdo_id]);
        $db->prepare("DELETE FROM rdo_equipamentos WHERE rdo_id = ?")->execute([$rdo_id]);
        $db->prepare("DELETE FROM rdo_materiais WHERE rdo_id = ?")->execute([$rdo_id]);
        
    } else {
        // Criar novo RDO
        $sql = "INSERT INTO rdos (obra_id, data_rdo, periodo, clima, temperatura, 
                servicos_executados, observacoes, status, criado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $obra_id, $data_rdo, $periodo, $clima, $temperatura, 
            $servicos, $observacoes, $status, $_SESSION['usuario_id']
        ]);
        $rdo_id = $db->lastInsertId();
    }
    
    // Inserir Mão de Obra
    if (!empty($_POST['mao_obra'])) {
        $stmt = $db->prepare("INSERT INTO rdo_mao_obra (rdo_id, funcao, quantidade, horas_trabalhadas) VALUES (?, ?, ?, ?)");
        foreach ($_POST['mao_obra'] as $item) {
            if (!empty($item['funcao'])) {
                $stmt->execute([
                    $rdo_id,
                    sanitize($item['funcao']),
                    (int)($item['quantidade'] ?? 1),
                    !empty($item['horas']) ? (float)$item['horas'] : null
                ]);
            }
        }
    }
    
    // Inserir Equipamentos
    if (!empty($_POST['equipamentos'])) {
        $stmt = $db->prepare("INSERT INTO rdo_equipamentos (rdo_id, equipamento, quantidade, horas_utilizadas) VALUES (?, ?, ?, ?)");
        foreach ($_POST['equipamentos'] as $item) {
            if (!empty($item['nome'])) {
                $stmt->execute([
                    $rdo_id,
                    sanitize($item['nome']),
                    (int)($item['quantidade'] ?? 1),
                    !empty($item['horas']) ? (float)$item['horas'] : null
                ]);
            }
        }
    }
    
    // Inserir Materiais
    if (!empty($_POST['materiais'])) {
        $stmt = $db->prepare("INSERT INTO rdo_materiais (rdo_id, material, quantidade, unidade) VALUES (?, ?, ?, ?)");
        foreach ($_POST['materiais'] as $item) {
            if (!empty($item['nome'])) {
                $stmt->execute([
                    $rdo_id,
                    sanitize($item['nome']),
                    !empty($item['quantidade']) ? (float)$item['quantidade'] : 0,
                    sanitize($item['unidade'] ?? '')
                ]);
            }
        }
    }
    
    // Upload de Fotos
    if (!empty($_FILES['fotos']['name'][0])) {
        $upload_dir = UPLOAD_DIR . 'rdos/' . $rdo_id . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $stmt = $db->prepare("INSERT INTO rdo_anexos (rdo_id, tipo, nome_arquivo, caminho_arquivo) VALUES (?, ?, ?, ?)");
        
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = basename($_FILES['fotos']['name'][$key]);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed) && $_FILES['fotos']['size'][$key] <= MAX_FILE_SIZE) {
                    $new_name = uniqid() . '_' . $file_name;
                    $file_path = $upload_dir . $new_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        // Salvar caminho relativo no banco (sem C:\xampp\htdocs\)
                        $caminho_relativo = 'uploads/rdos/' . $rdo_id . '/' . $new_name;
                        $stmt->execute([$rdo_id, 'foto', $file_name, $caminho_relativo]);
                    }
                }
            }
        }
    }
    
    // Registrar no histórico
    $acao_texto = ($acao === 'enviar') ? 'RDO enviado para aprovação' : 'RDO salvo como rascunho';
    $stmt = $db->prepare("INSERT INTO rdo_historico (rdo_id, usuario_id, acao) VALUES (?, ?, ?)");
    $stmt->execute([$rdo_id, $_SESSION['usuario_id'], $acao_texto]);
    
    // Se foi enviado para aprovação, enviar email para fiscal responsável
    if ($acao === 'enviar') {
        // Buscar dados completos incluindo fiscal responsável
        $stmt = $db->prepare("
            SELECT r.*, o.nome as obra_nome, o.fiscal_id, u.nome as criador_nome,
                   f.nome as fiscal_nome, f.email as fiscal_email
            FROM rdos r
            JOIN obras o ON r.obra_id = o.id
            JOIN usuarios u ON r.criado_por = u.id
            LEFT JOIN usuarios f ON o.fiscal_id = f.id
            WHERE r.id = ?
        ");
        $stmt->execute([$rdo_id]);
        $rdo_completo = $stmt->fetch();
        
        // Enviar email para o fiscal responsável pela obra
        if ($rdo_completo && !empty($rdo_completo['fiscal_email'])) {
            $obra_data = ['nome' => $rdo_completo['obra_nome']];
            $criador_data = ['nome' => $rdo_completo['criador_nome']];
            
            $assunto = "Novo RDO aguardando sua aprovação - RDO #{$rdo_id}";
            $mensagem = EmailSender::rdoCriado($rdo_completo, $obra_data, $criador_data, 'enviado');
            
            EmailSender::enviar($rdo_completo['fiscal_email'], $assunto, $mensagem);
        }
        
        // OPCIONAL: Também notificar admin e engenheiros
        $outros_aprovadores = $db->query("
            SELECT email, nome 
            FROM usuarios 
            WHERE tipo IN ('admin', 'engenheiro') 
            AND ativo = 1
        ")->fetchAll();
        
        foreach ($outros_aprovadores as $aprovador) {
            if (!empty($aprovador['email'])) {
                $obra_data = ['nome' => $rdo_completo['obra_nome']];
                $criador_data = ['nome' => $rdo_completo['criador_nome']];
                
                $assunto = "Novo RDO criado - RDO #{$rdo_id}";
                $mensagem = EmailSender::rdoCriado($rdo_completo, $obra_data, $criador_data, 'enviado');
                
                EmailSender::enviar($aprovador['email'], $assunto, $mensagem);
            }
        }
    }
    
    $db->commit();
    
    $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => ($acao === 'enviar') ? 'RDO enviado com sucesso!' : 'RDO salvo como rascunho!'
    ];
    
    header('Location: rdos.php');
    exit;
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Erro ao salvar RDO: ' . $e->getMessage()
    ];
    header('Location: rdo_criar.php' . ($rdo_id ? '?id=' . $rdo_id : ''));
    exit;
}