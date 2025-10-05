<?php
/**
 * Script para resetar senha do admin ou criar novo usuário
 * IMPORTANTE: Após usar, APAGUE este arquivo por segurança!
 */

require_once 'config.php';

$mensagem = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $tipo = $_POST['tipo'] ?? 'admin';
    
    if (!empty($email) && !empty($senha)) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Verificar se usuário existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario_existe = $stmt->fetch();
            
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            if ($usuario_existe) {
                // Atualizar senha existente
                $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
                $stmt->execute([$senha_hash, $email]);
                $mensagem = "Senha atualizada com sucesso para: $email";
                $sucesso = true;
            } else {
                // Criar novo usuário
                if (empty($nome)) {
                    $nome = 'Administrador';
                }
                $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo, ativo) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$nome, $email, $senha_hash, $tipo]);
                $mensagem = "Usuário criado com sucesso!<br>Email: $email<br>Senha: $senha";
                $sucesso = true;
            }
        } catch (Exception $e) {
            $mensagem = "Erro: " . $e->getMessage();
        }
    } else {
        $mensagem = "Preencha todos os campos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset de Senha - Sistema RDO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .alert-warning {
            border-left: 4px solid #ff6b6b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">⚠️ Reset de Senha - Sistema RDO</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>ATENÇÃO:</strong> Este arquivo é para resetar senhas em emergência.
                            <br>Após usar, <strong>APAGUE este arquivo</strong> por segurança!
                        </div>

                        <?php if ($mensagem): ?>
                            <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?>">
                                <?= $mensagem ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required 
                                       placeholder="admin@sistema.com">
                                <small class="text-muted">Se existir, a senha será resetada. Se não existir, será criado.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nova Senha *</label>
                                <input type="text" name="senha" class="form-control" required 
                                       placeholder="admin123" value="admin123">
                                <small class="text-muted">Use uma senha temporária e troque depois no sistema</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nome (para novos usuários)</label>
                                <input type="text" name="nome" class="form-control" 
                                       placeholder="Administrador" value="Administrador">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Usuário</label>
                                <select name="tipo" class="form-select">
                                    <option value="admin">Admin (total)</option>
                                    <option value="engenheiro">Engenheiro</option>
                                    <option value="fiscal">Fiscal</option>
                                    <option value="operacional">Operacional</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-danger w-100">
                                🔄 Resetar/Criar Usuário
                            </button>
                        </form>

                        <hr>

                        <h5>Opções Rápidas:</h5>
                        <form method="POST" class="mb-2">
                            <input type="hidden" name="email" value="admin@sistema.com">
                            <input type="hidden" name="senha" value="admin123">
                            <input type="hidden" name="nome" value="Administrador">
                            <input type="hidden" name="tipo" value="admin">
                            <button type="submit" class="btn btn-primary w-100">
                                Criar/Resetar: admin@sistema.com / admin123
                            </button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="email" value="seu@email.com">
                            <input type="hidden" name="senha" value="123456">
                            <input type="hidden" name="nome" value="Seu Nome">
                            <input type="hidden" name="tipo" value="admin">
                            <button type="submit" class="btn btn-secondary w-100">
                                Criar: seu@email.com / 123456
                            </button>
                        </form>

                        <hr>

                        <div class="text-center">
                            <a href="login.php" class="btn btn-success">
                                ✅ Ir para Login
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card mt-3 bg-dark text-white">
                    <div class="card-body">
                        <h6>🔍 Verificar Banco de Dados:</h6>
                        <p class="mb-2">Execute este SQL no phpMyAdmin para ver usuários:</p>
                        <code>SELECT id, nome, email, tipo FROM usuarios;</code>
                        
                        <hr>
                        
                        <h6>🗑️ Resetar completamente (cuidado!):</h6>
                        <p class="mb-2">Para resetar todos os usuários:</p>
                        <code>TRUNCATE TABLE usuarios;</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>