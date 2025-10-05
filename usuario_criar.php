<?php
require_once 'config.php';
verificarLogin();

// Apenas admin pode criar usuários
if ($_SESSION['usuario_tipo'] != 'admin') {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Apenas administradores podem criar usuários!'
    ];
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $email = sanitize($_POST['email']);
    $senha = $_POST['senha'];
    $senha_confirm = $_POST['senha_confirm'];
    $tipo = $_POST['tipo'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos obrigatórios!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido!';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres!';
    } elseif ($senha !== $senha_confirm) {
        $erro = 'As senhas não conferem!';
    } else {
        try {
            // Verificar se email já existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $erro = 'Este email já está cadastrado!';
            } else {
                // Criar usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO usuarios (nome, email, senha, tipo, ativo)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $email, $senha_hash, $tipo, $ativo]);
                
                $_SESSION['mensagem'] = [
                    'tipo' => 'success',
                    'texto' => "Usuário '{$nome}' criado com sucesso!"
                ];
                header('Location: usuarios.php');
                exit;
            }
        } catch (Exception $e) {
            $erro = 'Erro ao criar usuário: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Usuário - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            transition: all 0.3s;
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
            <h2><i class="bi bi-person-plus"></i> Novo Usuário</h2>
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

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informações do Usuário</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formUsuario">
                            <div class="mb-3">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" name="nome" class="form-control" required
                                       value="<?= $_POST['nome'] ?? '' ?>" 
                                       placeholder="Ex: João da Silva">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?= $_POST['email'] ?? '' ?>" 
                                       placeholder="usuario@email.com">
                                <small class="text-muted">Será usado para login no sistema</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Senha *</label>
                                    <input type="password" name="senha" class="form-control" required
                                           id="senha" minlength="6"
                                           placeholder="Mínimo 6 caracteres">
                                    <div class="password-strength mt-2" id="strength"></div>
                                    <small class="text-muted" id="strengthText"></small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirmar Senha *</label>
                                    <input type="password" name="senha_confirm" class="form-control" required
                                           id="senha_confirm" minlength="6"
                                           placeholder="Digite novamente">
                                    <small class="text-muted" id="matchText"></small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Usuário *</label>
                                <select name="tipo" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="admin">Administrador (acesso total)</option>
                                    <option value="engenheiro">Engenheiro (aprovar RDOs + criar)</option>
                                    <option value="fiscal">Fiscal (aprovar RDOs + visualizar)</option>
                                    <option value="operacional">Operacional (criar RDOs)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ativo" 
                                           id="ativo" checked>
                                    <label class="form-check-label" for="ativo">
                                        <strong>Usuário Ativo</strong>
                                        <small class="d-block text-muted">
                                            Desmarque para criar o usuário mas não permitir login ainda
                                        </small>
                                    </label>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <a href="usuarios.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> Criar Usuário
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> Tipos de Usuário</h6>
                    </div>
                    <div class="card-body">
                        <h6><span class="badge bg-danger">Administrador</span></h6>
                        <ul class="small mb-3">
                            <li>Acesso total ao sistema</li>
                            <li>Gerenciar usuários</li>
                            <li>Criar/editar obras</li>
                            <li>Aprovar RDOs</li>
                        </ul>

                        <h6><span class="badge bg-primary">Engenheiro</span></h6>
                        <ul class="small mb-3">
                            <li>Criar e editar RDOs</li>
                            <li>Aprovar/rejeitar RDOs</li>
                            <li>Criar/editar obras</li>
                            <li>Ver relatórios</li>
                        </ul>

                        <h6><span class="badge bg-info">Fiscal</span></h6>
                        <ul class="small mb-3">
                            <li>Visualizar RDOs</li>
                            <li>Aprovar/rejeitar RDOs</li>
                            <li>Ver relatórios</li>
                        </ul>

                        <h6><span class="badge bg-secondary">Operacional</span></h6>
                        <ul class="small mb-0">
                            <li>Criar RDOs</li>
                            <li>Editar seus RDOs</li>
                            <li>Ver RDOs</li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bi bi-shield-check"></i> Segurança</h6>
                    </div>
                    <div class="card-body">
                        <p class="small mb-2"><strong>Senha forte:</strong></p>
                        <ul class="small">
                            <li>Mínimo 6 caracteres</li>
                            <li>Misture letras e números</li>
                            <li>Use maiúsculas e minúsculas</li>
                            <li>Adicione caracteres especiais</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Verificador de força da senha
        const senha = document.getElementById('senha');
        const senhaConfirm = document.getElementById('senha_confirm');
        const strength = document.getElementById('strength');
        const strengthText = document.getElementById('strengthText');
        const matchText = document.getElementById('matchText');

        senha.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;
            
            strength.style.width = (score * 20) + '%';
            
            if (score <= 1) {
                strength.className = 'password-strength bg-danger';
                strengthText.textContent = 'Senha fraca';
                strengthText.className = 'text-danger';
            } else if (score <= 3) {
                strength.className = 'password-strength bg-warning';
                strengthText.textContent = 'Senha média';
                strengthText.className = 'text-warning';
            } else {
                strength.className = 'password-strength bg-success';
                strengthText.textContent = 'Senha forte';
                strengthText.className = 'text-success';
            }
        });

        // Verificar se as senhas coincidem
        senhaConfirm.addEventListener('input', function() {
            if (this.value === senha.value && this.value !== '') {
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
        document.getElementById('formUsuario').addEventListener('submit', function(e) {
            if (senha.value !== senhaConfirm.value) {
                e.preventDefault();
                alert('As senhas não conferem!');
                senhaConfirm.focus();
            }
        });
    </script>
</body>
</html>