<?php
require_once 'config.php';

// Se já está logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($email) && !empty($senha)) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Email ou senha inválidos!';
        }
    } else {
        $erro = 'Preencha todos os campos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <img src="imagens/logo.png" alt="Logo" style="width:200px; display:block; margin:0 auto 10px auto;">
                        <h3 class="mt-3 mb-0">Sistema de Gestão de RDO</h3>
                        <p class="mb-0 mt-2">Relatório Diário de Obras</p>
                    </div>
                    <div class="login-body">
                        <?php if ($erro): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= $erro ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-envelope"></i> Email
                                </label>
                                <input type="email" name="email" class="form-control form-control-lg" 
                                       placeholder="seu@email.com" required autofocus>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-lock"></i> Senha
                                </label>
                                <input type="password" name="senha" class="form-control form-control-lg" 
                                       placeholder="••••••••" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login btn-lg w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Entrar
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="recuperar_senha.php" class="text-decoration-none">Esqueceu sua senha?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>