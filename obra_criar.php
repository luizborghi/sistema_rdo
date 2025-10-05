<?php
require_once 'config.php';
verificarLogin();

// Verificar permissão
if (!in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro'])) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para criar/editar obras! Apenas Admin e Engenheiro podem gerenciar obras.'
    ];
    header('Location: obras.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Buscar usuários para select de responsável
$usuarios = $db->query("SELECT id, nome, email FROM usuarios WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar apenas fiscais para select de fiscal responsável
$fiscais = $db->query("SELECT id, nome, email FROM usuarios WHERE tipo = 'fiscal' AND ativo = 1 ORDER BY nome")->fetchAll();

$obra_id = $_GET['id'] ?? null;
$obra = null;

// Se está editando, buscar dados da obra
if ($obra_id) {
    $stmt = $db->prepare("SELECT * FROM obras WHERE id = ?");
    $stmt->execute([$obra_id]);
    $obra = $stmt->fetch();
    
    if (!$obra) {
        header('Location: obras.php');
        exit;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $codigo = sanitize($_POST['codigo']);
        $nome = sanitize($_POST['nome']);
        $cliente = sanitize($_POST['cliente'] ?? '');
        $endereco = $_POST['endereco'] ?? '';
        $data_inicio = $_POST['data_inicio'] ?: null;
        $data_prevista_fim = $_POST['data_prevista_fim'] ?: null;
        $status = $_POST['status'];
        $responsavel_id = $_POST['responsavel_id'] ?: null;
        $fiscal_id = $_POST['fiscal_id'] ?: null; // NOVO
        
        if ($obra_id) {
            // Atualizar obra existente
            $stmt = $db->prepare("
                UPDATE obras SET 
                    codigo = ?, nome = ?, cliente = ?, endereco = ?,
                    data_inicio = ?, data_prevista_fim = ?, status = ?, responsavel_id = ?, fiscal_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $codigo, $nome, $cliente, $endereco,
                $data_inicio, $data_prevista_fim, $status, $responsavel_id, $fiscal_id, $obra_id
            ]);
            
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Obra atualizada com sucesso!'
            ];
        } else {
            // Criar nova obra
            $stmt = $db->prepare("
                INSERT INTO obras (codigo, nome, cliente, endereco, data_inicio, data_prevista_fim, status, responsavel_id, fiscal_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $codigo, $nome, $cliente, $endereco,
                $data_inicio, $data_prevista_fim, $status, $responsavel_id, $fiscal_id
            ]);
            
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Obra criada com sucesso!'
            ];
        }
        
        header('Location: obras.php');
        exit;
        
    } catch (PDOException $e) {
        $erro = 'Erro ao salvar obra';
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $erro = 'Código da obra já existe!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $obra ? 'Editar' : 'Nova' ?> Obra - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-clipboard-check"></i> Sistema RDO
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3"><?= $_SESSION['usuario_nome'] ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-building"></i> <?= $obra ? 'Editar' : 'Nova' ?> Obra</h2>
            <a href="obras.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $erro ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informações da Obra</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Código da Obra *</label>
                            <input type="text" name="codigo" class="form-control" required
                                   value="<?= $obra['codigo'] ?? '' ?>" 
                                   placeholder="Ex: OBR-001">
                            <small class="text-muted">Código único de identificação</small>
                        </div>

                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nome da Obra *</label>
                            <input type="text" name="nome" class="form-control" required
                                   value="<?= htmlspecialchars($obra['nome'] ?? '') ?>" 
                                   placeholder="Ex: Construção Edifício Central">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" name="cliente" class="form-control"
                                   value="<?= htmlspecialchars($obra['cliente'] ?? '') ?>" 
                                   placeholder="Nome do cliente">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Responsável pela Obra</label>
                            <select name="responsavel_id" class="form-select">
                                <option value="">Selecione o responsável...</option>
                                <?php foreach($usuarios as $usuario): ?>
                                <option value="<?= $usuario['id'] ?>" 
                                    <?= ($obra && $obra['responsavel_id'] == $usuario['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($usuario['nome']) ?> (<?= $usuario['email'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Gerente/Engenheiro da obra</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fiscal Responsável (Aprova RDOs) *</label>
                        <select name="fiscal_id" class="form-select" required>
                            <option value="">Selecione o fiscal...</option>
                            <?php foreach($fiscais as $fiscal): ?>
                            <option value="<?= $fiscal['id'] ?>" 
                                <?= ($obra && $obra['fiscal_id'] == $fiscal['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fiscal['nome']) ?> (<?= $fiscal['email'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Apenas este fiscal poderá aprovar/rejeitar os RDOs desta obra
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Endereço</label>
                        <textarea name="endereco" class="form-control" rows="2" 
                                  placeholder="Endereço completo da obra"><?= htmlspecialchars($obra['endereco'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data de Início</label>
                            <input type="date" name="data_inicio" class="form-control"
                                   value="<?= $obra['data_inicio'] ?? '' ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Previsão de Término</label>
                            <input type="date" name="data_prevista_fim" class="form-control"
                                   value="<?= $obra['data_prevista_fim'] ?? '' ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="planejamento" <?= ($obra && $obra['status'] == 'planejamento') ? 'selected' : '' ?>>Planejamento</option>
                                <option value="em_andamento" <?= ($obra && $obra['status'] == 'em_andamento') ? 'selected' : '' ?>>Em Andamento</option>
                                <option value="pausada" <?= ($obra && $obra['status'] == 'pausada') ? 'selected' : '' ?>>Pausada</option>
                                <option value="concluida" <?= ($obra && $obra['status'] == 'concluida') ? 'selected' : '' ?>>Concluída</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="obras.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> <?= $obra ? 'Atualizar' : 'Criar' ?> Obra
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>