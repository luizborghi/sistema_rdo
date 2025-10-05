<?php
require_once 'config.php';
verificarLogin();

// Apenas admin pode editar usuários
if ($_SESSION['usuario_tipo'] != 'admin') {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Apenas administradores podem editar usuários!'
    ];
    header('Location: dashboard.php');
    exit;
}

$usuario_id = $_GET['id'] ?? null;

if (!$usuario_id) {
    header('Location: usuarios.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Buscar usuário
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Usuário não encontrado!'
    ];
    header('Location: usuarios.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $email = sanitize($_POST['email']);
    $tipo = $_POST['tipo'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validações
    if (empty($nome) || empty($email)) {
        $erro = 'Preencha todos os campos obrigatórios!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido!';
    } else {
        try {
            // Verificar se email já existe para outro usuário
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $usuario_id]);
            
            if ($stmt->fetch()) {
                $erro = 'Este email já está cadastrado para outro usuário!';
            } else {
                // Atualizar usuário
                $stmt = $db->prepare("
                    UPDATE usuarios SET nome = ?, email = ?, tipo = ?, ativo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $email, $tipo, $ativo, $usuario_id]);
                
                $_SESSION['mensagem'] = [
                    'tipo' => 'success',
                    'texto' => "Usuário '{$nome}' atualizado com sucesso!"
                ];
                header('Location: usuarios.php');
                exit;
            }
        } catch (Exception $e) {
            $erro = 'Erro ao atualizar usuário: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - <?= SITE_NAME ?></title>
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
            <h2><i class="bi bi-pencil"></i> Editar Usuário</h2>
            <a href="usuarios.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= $erro ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informações do Usuário</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" required
                                   value="<?= htmlspecialchars($usuario['nome']) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= htmlspecialchars($usuario['email']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Usuário *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="admin" <?= $usuario['tipo'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <option value="engenheiro" <?= $usuario['tipo'] == 'engenheiro' ? 'selected' : '' ?>>Engenheiro</option>
                                <option value="fiscal" <?= $usuario['tipo'] == 'fiscal' ? 'selected' : '' ?>>Fiscal</option>
                                <option value="operacional" <?= $usuario['tipo'] == 'operacional' ? 'selected' : '' ?>>Operacional</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="ativo" 
                                       id="ativo" <?= $usuario['ativo'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ativo">
                                    <strong>Usuário Ativo</strong>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        Para alterar a senha, use o botão <strong>"Trocar Senha"</strong> na listagem de usuários.
                    </div>

                    <div class="alert alert-secondary">
                        <strong>Cadastrado em:</strong> <?= date('d/m/Y H:i', strtotime($usuario['created_at'])) ?>
                        <?php if ($usuario['updated_at'] != $usuario['created_at']): ?>
                        <br><strong>Última atualização:</strong> <?= date('d/m/Y H:i', strtotime($usuario['updated_at'])) ?>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="usuarios.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>