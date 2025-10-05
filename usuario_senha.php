<?php
require_once 'config.php';
verificarLogin();

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

// Verificar permissão: admin pode trocar senha de qualquer um, usuário só pode trocar a própria
$pode_trocar = ($_SESSION['usuario_tipo'] == 'admin' || $_SESSION['usuario_id'] == $usuario_id);

if (!$pode_trocar) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para trocar a senha deste usuário!'
    ];
    header('Location: dashboard.php');
    exit;
}

$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'];
    $senha_confirm = $_POST['senha_confirm'];
    
    // Se não for admin, precisa validar senha atual
    if ($_SESSION['usuario_tipo'] != 'admin' || $usuario_id == $_SESSION['usuario_id']) {
        if (empty($senha_atual)) {
            $erro = 'Informe a senha atual!';
        } elseif (!password_verify($senha_atual, $usuario['senha'])) {
            $erro = 'Senha atual incorreta!';
        }
    }
    
    if (!$erro) {
        if (strlen($senha_nova) < 6) {
            $erro = 'A nova senha deve ter no mínimo 6 caracteres!';
        } elseif ($senha_nova !== $senha_confirm) {
            $erro = 'As senhas não conferem!';
        } else {
            try {
                $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt->execute([$senha_hash, $usuario_id]);
                
                $sucesso = true;
                
                // Se alterou a própria senha, mostrar mensagem e redirecionar
                if ($usuario_id == $_SESSION['usuario_id']) {
                    $_SESSION['mensagem'] = [
                        'tipo' => 'success',
                        'texto' => 'Sua senha foi alterada com sucesso!'
                    ];
                    header('Location: dashboard.php');
                    exit;
                }
            } catch (Exception $e) {
                $erro = 'Erro ao alterar senha: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 38px;
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
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-key"></i> Trocar Senha</h2>
                    <a href="<?= $_SESSION['usuario_tipo'] == 'admin' ? 'usuarios.php' : 'dashboard.php' ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                </div>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> 
                        Senha de <strong><?= htmlspecialchars($usuario['nome']) ?></strong> alterada com sucesso!
                        <div class="mt-3">
                            <a href="usuarios.php" class="btn btn-success">← Voltar para Usuários</a>
                        </div>
                    </div>
                <?php else: ?>

                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?= $erro ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-lock"></i> 
                            Alteração de Senha: <?= htmlspecialchars($usuario['nome']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formSenha">
                            <?php if ($_SESSION['usuario_tipo'] != 'admin' || $usuario_id == $_SESSION['usuario_id']): ?>
                            <div class="mb-3 position-relative">
                                <label class="form-label">Senha Atual *</label>
                                <input type="password" name="senha_atual" class="form-control" required
                                       id="senha_atual" placeholder="Digite sua senha atual">
                                <i class="bi bi-eye password-toggle" onclick="togglePassword('senha_atual')"></i>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                Como administrador, você pode definir uma nova senha sem informar a atual.
                            </div>
                            <?php endif; ?>

                            <div class="mb-3 position-relative">
                                <label class="form-label">Nova Senha *</label>
                                <input type="password" name="senha_nova" class="form-control" required
                                       id="senha_nova" minlength="6" placeholder="Mínimo 6 caracteres">
                                <i class="bi bi-eye password-toggle" onclick="togglePassword('senha_nova')"></i>
                            </div>

                            <div class="mb-3 position-relative">
                                <label class="form-label">Confirmar Nova Senha *</label>
                                <input type="password" name="senha_confirm" class="form-control" required
                                       id="senha_confirm" minlength="6" placeholder="Digite novamente">
                                <i class="bi bi-eye password-toggle" onclick="togglePassword('senha_confirm')"></i>
                                <small class="text-muted" id="matchText"></small>
                            </div>

                            <div class="alert alert-warning">
                                <strong><i class="bi bi-exclamation-triangle"></i> Importante:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Use uma senha forte e única</li>
                                    <li>Não compartilhe sua senha</li>
                                    <li>Mínimo de 6 caracteres</li>
                                </ul>
                            </div>

                            <hr>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-key"></i> Alterar Senha
                                </button>
                                <a href="<?= $_SESSION['usuario_tipo'] == 'admin' ? 'usuarios.php' : 'dashboard.php' ?>" 
                                   class="btn btn-outline-secondary">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Verificar se as senhas coincidem
        const senhaConfirm = document.getElementById('senha_confirm');
        const senhaNova = document.getElementById('senha_nova');
        const matchText = document.getElementById('matchText');

        senhaConfirm.addEventListener('input', function() {
            if (this.value === senhaNova.value && this.value !== '') {
                matchText.textContent = '✓ Senhas conferem';
                matchText.className = 'text-success';
            } else if (this.value !== '') {
                matchText.textContent = '✗ Senhas não conferem';
                matchText.className = 'text-danger';
            } else {
                matchText.textContent = '';
            }
        });

        // Validação antes de enviar
        document.getElementById('formSenha').addEventListener('submit', function(e) {
            if (senhaNova.value !== senhaConfirm.value) {
                e.preventDefault();
                alert('As senhas não conferem!');
                senhaConfirm.focus();
            }
        });
    </script>
</body>
</html>