<?php
require_once 'config.php';
require_once 'email_config.php';
verificarLogin();

// Apenas admin pode testar emails
if ($_SESSION['usuario_tipo'] != 'admin') {
    die('Apenas administradores podem acessar esta página');
}

$resultado = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_destino = $_POST['email_destino'] ?? '';
    $tipo_teste = $_POST['tipo_teste'] ?? 'simples';
    
    if (empty($email_destino) || !filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um email válido!';
    } else {
        try {
            if ($tipo_teste === 'simples') {
                // Email simples
                $assunto = "Teste de Email - Sistema RDO";
                $conteudo = '
                <p>Olá! 👋</p>
                <p>Este é um <strong>email de teste</strong> do Sistema de Gestão de RDO.</p>
                <p>Se você recebeu este email, significa que as configurações estão corretas! ✅</p>
                <p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>
                ';
                $mensagem = EmailSender::templateBase('Teste de Email', $conteudo);
                
            } else {
                // Email simulando RDO aprovado
                $rdo_fake = [
                    'id' => 999,
                    'data_rdo' => date('Y-m-d'),
                    'periodo' => 'dia_todo',
                    'status' => 'aprovado',
                    'data_aprovacao' => date('Y-m-d H:i:s')
                ];
                $obra_fake = ['nome' => 'Obra Exemplo de Teste'];
                $criador_fake = ['nome' => $_SESSION['usuario_nome']];
                $aprovador_fake = ['nome' => 'Administrador'];
                
                $assunto = "Teste: RDO Aprovado - Sistema RDO";
                $mensagem = EmailSender::rdoAprovado(
                    $rdo_fake, 
                    $obra_fake, 
                    $criador_fake, 
                    $aprovador_fake, 
                    'aprovado',
                    'Este é um comentário de teste do sistema de emails.'
                );
            }
            
            $enviado = EmailSender::enviar($email_destino, $assunto, $mensagem);
            
            if ($enviado) {
                $resultado = "✅ Email enviado com sucesso para: <strong>{$email_destino}</strong>";
            } else {
                $erro = "❌ Erro ao enviar email. Verifique as configurações.";
            }
            
        } catch (Exception $e) {
            $erro = "❌ Erro: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testar Email - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .config-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
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
            <h2><i class="bi bi-envelope-check"></i> Configurações e Teste de Email</h2>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($resultado): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $resultado ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $erro ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Configurações Atuais -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Configurações Atuais</h5>
                    </div>
                    <div class="card-body">
                        <div class="config-box">
                            <strong>Status do Sistema de Email:</strong><br>
                            <?php if (EMAIL_ENABLED): ?>
                                <span class="status-badge bg-success text-white">✓ Ativado</span>
                            <?php else: ?>
                                <span class="status-badge bg-danger text-white">✗ Desativado</span>
                            <?php endif; ?>
                        </div>

                        <div class="config-box">
                            <strong>Método de Envio:</strong><br>
                            <span class="badge bg-primary"><?= strtoupper(EMAIL_METHOD) ?></span>
                        </div>

                        <?php if (EMAIL_METHOD === 'smtp'): ?>
                        <div class="config-box">
                            <strong>Servidor SMTP:</strong><br>
                            <?= SMTP_HOST ?>:<?= SMTP_PORT ?> (<?= strtoupper(SMTP_SECURE) ?>)
                        </div>

                        <div class="config-box">
                            <strong>Remetente:</strong><br>
                            <?= SMTP_FROM_NAME ?> &lt;<?= SMTP_FROM_EMAIL ?>&gt;
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-warning mt-3">
                            <strong><i class="bi bi-exclamation-triangle"></i> Para editar:</strong><br>
                            Abra o arquivo <code>email_config.php</code> e altere as constantes no topo.
                        </div>
                    </div>
                </div>

                <!-- Guia de Configuração -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-book"></i> Guia de Configuração</h5>
                    </div>
                    <div class="card-body">
                        <h6>📧 Gmail (Recomendado):</h6>
                        <ol class="small">
                            <li>Acesse: <a href="https://myaccount.google.com/apppasswords" target="_blank">Senhas de App</a></li>
                            <li>Crie uma senha de aplicativo</li>
                            <li>Use a senha gerada em <code>SMTP_PASS</code></li>
                        </ol>

                        <h6 class="mt-3">📧 Outlook/Hotmail:</h6>
                        <pre class="small bg-dark text-white p-2">
SMTP_HOST: smtp-mail.outlook.com
SMTP_PORT: 587
SMTP_SECURE: tls</pre>

                        <h6 class="mt-3">📧 Servidor Próprio:</h6>
                        <p class="small">Consulte seu provedor de hospedagem para obter os dados SMTP.</p>

                        <h6 class="mt-3">📧 Desabilitar Emails:</h6>
                        <p class="small">No <code>email_config.php</code>, mude:<br>
                        <code>define('EMAIL_ENABLED', false);</code></p>
                    </div>
                </div>
            </div>

            <!-- Testar Envio -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-send"></i> Testar Envio de Email</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email de Destino *</label>
                                <input type="email" name="email_destino" class="form-control" 
                                       required placeholder="seuemail@exemplo.com"
                                       value="<?= $_SESSION['usuario_email'] ?>">
                                <small class="text-muted">Enviaremos um email de teste para este endereço</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Teste</label>
                                <select name="tipo_teste" class="form-select">
                                    <option value="simples">Email Simples</option>
                                    <option value="rdo">Simulação de RDO Aprovado</option>
                                </select>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                <strong>Importante:</strong> Verifique sua caixa de spam se não receber o email.
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-send"></i> Enviar Email de Teste
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Notificações Automáticas -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-bell"></i> Notificações Automáticas</h5>
                    </div>
                    <div class="card-body">
                        <p>O sistema envia emails automaticamente nos seguintes casos:</p>

                        <h6><i class="bi bi-1-circle text-primary"></i> RDO Enviado para Aprovação</h6>
                        <ul class="small mb-3">
                            <li><strong>Quem recebe:</strong> Todos os aprovadores (Admin, Engenheiro, Fiscal)</li>
                            <li><strong>Quando:</strong> Ao clicar em "Enviar RDO"</li>
                            <li><strong>Conteúdo:</strong> Dados do RDO + Link para aprovação</li>
                        </ul>

                        <h6><i class="bi bi-2-circle text-success"></i> RDO Aprovado</h6>
                        <ul class="small mb-3">
                            <li><strong>Quem recebe:</strong> Criador do RDO</li>
                            <li><strong>Quando:</strong> Ao aprovar o RDO</li>
                            <li><strong>Conteúdo:</strong> Confirmação + Comentários + Link</li>
                        </ul>

                        <h6><i class="bi bi-3-circle text-danger"></i> RDO Rejeitado</h6>
                        <ul class="small mb-0">
                            <li><strong>Quem recebe:</strong> Criador do RDO</li>
                            <li><strong>Quando:</strong> Ao rejeitar o RDO</li>
                            <li><strong>Conteúdo:</strong> Motivo da rejeição + Link</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview dos Templates -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-eye"></i> Preview dos Templates</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='300'%3E%3Crect fill='%23667eea' width='200' height='80'/%3E%3Ctext x='50%25' y='40' text-anchor='middle' fill='white' font-size='16' font-weight='bold'%3ERDO Enviado%3C/text%3E%3Crect fill='%23f8f9fa' y='80' width='200' height='180'/%3E%3Ctext x='50%25' y='120' text-anchor='middle' font-size='12'%3EInformações do RDO%3C/text%3E%3Crect fill='%23ffc107' x='50' y='150' width='100' height='30' rx='5'/%3E%3Ctext x='50%25' y='170' text-anchor='middle' fill='white' font-weight='bold'%3EVer RDO%3C/text%3E%3Crect fill='%23e9ecef' y='260' width='200' height='40'/%3E%3Ctext x='50%25' y='285' text-anchor='middle' font-size='10'%3ESistema RDO%3C/text%3E%3C/svg%3E" 
                             class="img-fluid border rounded">
                        <p class="mt-2"><strong>RDO Enviado</strong></p>
                        <span class="badge bg-warning">Para Aprovadores</span>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='300'%3E%3Crect fill='%2328a745' width='200' height='80'/%3E%3Ctext x='50%25' y='40' text-anchor='middle' fill='white' font-size='16' font-weight='bold'%3ERDO Aprovado%3C/text%3E%3Crect fill='%23f8f9fa' y='80' width='200' height='180'/%3E%3Ctext x='50%25' y='120' text-anchor='middle' font-size='12'%3EParabéns!%3C/text%3E%3Crect fill='%2328a745' x='50' y='150' width='100' height='30' rx='5'/%3E%3Ctext x='50%25' y='170' text-anchor='middle' fill='white' font-weight='bold'%3EVer RDO%3C/text%3E%3Crect fill='%23e9ecef' y='260' width='200' height='40'/%3E%3Ctext x='50%25' y='285' text-anchor='middle' font-size='10'%3ESistema RDO%3C/text%3E%3C/svg%3E" 
                             class="img-fluid border rounded">
                        <p class="mt-2"><strong>RDO Aprovado</strong></p>
                        <span class="badge bg-success">Para Criador</span>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='300'%3E%3Crect fill='%23dc3545' width='200' height='80'/%3E%3Ctext x='50%25' y='40' text-anchor='middle' fill='white' font-size='16' font-weight='bold'%3ERDO Rejeitado%3C/text%3E%3Crect fill='%23f8f9fa' y='80' width='200' height='180'/%3E%3Ctext x='50%25' y='120' text-anchor='middle' font-size='12'%3EMotivo da Rejeição%3C/text%3E%3Crect fill='%23dc3545' x='50' y='150' width='100' height='30' rx='5'/%3E%3Ctext x='50%25' y='170' text-anchor='middle' fill='white' font-weight='bold'%3EVer RDO%3C/text%3E%3Crect fill='%23e9ecef' y='260' width='200' height='40'/%3E%3Ctext x='50%25' y='285' text-anchor='middle' font-size='10'%3ESistema RDO%3C/text%3E%3C/svg%3E" 
                             class="img-fluid border rounded">
                        <p class="mt-2"><strong>RDO Rejeitado</strong></p>
                        <span class="badge bg-danger">Para Criador</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>