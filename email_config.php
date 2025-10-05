<?php
/**
 * Configurações de Email do Sistema RDO
 */

// ============================================
// CONFIGURAÇÕES DE EMAIL
// ============================================

// Método de envio: 'smtp' ou 'php_mail'
define('EMAIL_METHOD', 'smtp'); // Mude para 'php_mail' se não tiver SMTP

// Configurações SMTP (Gmail, Outlook, etc)
define('SMTP_HOST', 'smtp.office365.com');          // Servidor SMTP
define('SMTP_PORT', 587);                        // Porta (587 para TLS, 465 para SSL)
define('SMTP_SECURE', 'tls');                    // 'tls' ou 'ssl'
define('SMTP_USER', 'luiz.borghi@cts-services.com.br');     // Seu email
define('SMTP_PASS', 'Borghi252725');            // Senha de app do Gmail
define('SMTP_FROM_EMAIL', 'luiz.borghi@cts-services.com.br');
define('SMTP_FROM_NAME', 'Sistema RDO');

// ============================================
// CONFIGURAÇÕES DE NOTIFICAÇÕES
// ============================================

// Ativar/Desativar envio de emails
define('EMAIL_ENABLED', true); // Mude para false para desativar emails

// Emails que sempre recebem cópia (BCC)
define('EMAIL_BCC', 'luiz.borghi@live.com'); // Ex: 'admin@empresa.com'

// ============================================
// TEMPLATES DE EMAIL
// ============================================

/**
 * Classe para gerenciar envio de emails
 */
class EmailSender {
    
    /**
     * Envia email usando SMTP ou PHP mail()
     */
    public static function enviar($para, $assunto, $mensagem_html, $anexos = []) {
        
        // Se emails estão desabilitados, apenas loga
        if (!EMAIL_ENABLED) {
            error_log("Email desabilitado - Para: {$para} - Assunto: {$assunto}");
            return true;
        }
        
        if (EMAIL_METHOD === 'smtp') {
            return self::enviarSMTP($para, $assunto, $mensagem_html, $anexos);
        } else {
            return self::enviarPHPMail($para, $assunto, $mensagem_html);
        }
    }
    
    /**
     * Enviar usando SMTP (PHPMailer)
     */
    private static function enviarSMTP($para, $assunto, $mensagem_html, $anexos = []) {
        
        // Verificar se PHPMailer está disponível
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Fallback para PHP mail()
            return self::enviarPHPMail($para, $assunto, $mensagem_html);
        }
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configurações SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Remetente
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
            // Destinatário
            $mail->addAddress($para);
            
            // BCC (cópia oculta)
            if (!empty(EMAIL_BCC)) {
                $mail->addBCC(EMAIL_BCC);
            }
            
            // Anexos
            foreach ($anexos as $anexo) {
                if (file_exists($anexo['path'])) {
                    $mail->addAttachment($anexo['path'], $anexo['name']);
                }
            }
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $mensagem_html;
            $mail->AltBody = strip_tags($mensagem_html);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email SMTP: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Enviar usando PHP mail()
     */
    private static function enviarPHPMail($para, $assunto, $mensagem_html) {
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        
        if (!empty(EMAIL_BCC)) {
            $headers .= "Bcc: " . EMAIL_BCC . "\r\n";
        }
        
        return mail($para, $assunto, $mensagem_html, $headers);
    }
    
    /**
     * Template base de email
     */
    public static function templateBase($titulo, $conteudo, $cor = '#667eea') {
        
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background: #f4f4f4;
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, ' . $cor . ' 0%, #764ba2 100%);
                    color: white; 
                    padding: 30px; 
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content { 
                    padding: 30px;
                }
                .button { 
                    display: inline-block; 
                    padding: 12px 30px; 
                    background: ' . $cor . ';
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 5px;
                    font-weight: bold;
                    margin: 15px 0;
                }
                .button:hover {
                    opacity: 0.9;
                }
                .footer { 
                    text-align: center; 
                    padding: 20px; 
                    background: #f8f9fa;
                    color: #666; 
                    font-size: 12px;
                    border-top: 1px solid #ddd;
                }
                .info-box {
                    background: #f8f9fa;
                    border-left: 4px solid ' . $cor . ';
                    padding: 15px;
                    margin: 15px 0;
                }
                .info-box strong {
                    color: ' . $cor . ';
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                table td {
                    padding: 8px;
                    border-bottom: 1px solid #eee;
                }
                table td:first-child {
                    font-weight: bold;
                    width: 40%;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🏗️ ' . htmlspecialchars($titulo) . '</h1>
                </div>
                <div class="content">
                    ' . $conteudo . '
                </div>
                <div class="footer">
                    <p><strong>Sistema de Gestão de RDO</strong></p>
                    <p>Este é um email automático, não responda.</p>
                    <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Email de RDO criado/enviado
     */
    public static function rdoCriado($rdo, $obra, $criador, $tipo = 'criado') {
        
        $acao = ($tipo === 'enviado') ? 'enviado para aprovação' : 'criado';
        $cor = ($tipo === 'enviado') ? '#ffc107' : '#667eea';
        
        $conteudo = '
        <p>Olá! 👋</p>
        <p>Um novo RDO foi <strong>' . $acao . '</strong> no sistema.</p>
        
        <div class="info-box">
            <strong>📋 Informações do RDO:</strong>
            <table>
                <tr>
                    <td>RDO Nº:</td>
                    <td>#' . str_pad($rdo['id'], 6, '0', STR_PAD_LEFT) . '</td>
                </tr>
                <tr>
                    <td>Obra:</td>
                    <td>' . htmlspecialchars($obra['nome']) . '</td>
                </tr>
                <tr>
                    <td>Data:</td>
                    <td>' . date('d/m/Y', strtotime($rdo['data_rdo'])) . '</td>
                </tr>
                <tr>
                    <td>Período:</td>
                    <td>' . ucfirst(str_replace('_', ' ', $rdo['periodo'])) . '</td>
                </tr>
                <tr>
                    <td>Criado por:</td>
                    <td>' . htmlspecialchars($criador['nome']) . '</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td><strong>' . ucfirst($rdo['status']) . '</strong></td>
                </tr>
            </table>
        </div>
        
        <p style="text-align: center;">
            <a href="' . SITE_URL . '/rdo_visualizar.php?id=' . $rdo['id'] . '" class="button">
                Ver RDO Completo
            </a>
        </p>
        ';
        
        $html = self::templateBase('RDO ' . ucfirst($acao), $conteudo, $cor);
        return $html;
    }
    
    /**
     * Email de RDO aprovado/rejeitado
     */
    public static function rdoAprovado($rdo, $obra, $criador, $aprovador, $status, $comentarios = '') {
        
        $cor = ($status === 'aprovado') ? '#28a745' : '#dc3545';
        $icone = ($status === 'aprovado') ? '✅' : '❌';
        $titulo = ($status === 'aprovado') ? 'RDO Aprovado' : 'RDO Rejeitado';
        
        $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($criador['nome']) . '</strong>! 👋</p>
        <p>Seu RDO foi <strong>' . $status . '</strong>.</p>
        
        <div class="info-box">
            <strong>' . $icone . ' Status: ' . strtoupper($status) . '</strong>
            <table>
                <tr>
                    <td>RDO Nº:</td>
                    <td>#' . str_pad($rdo['id'], 6, '0', STR_PAD_LEFT) . '</td>
                </tr>
                <tr>
                    <td>Obra:</td>
                    <td>' . htmlspecialchars($obra['nome']) . '</td>
                </tr>
                <tr>
                    <td>Data:</td>
                    <td>' . date('d/m/Y', strtotime($rdo['data_rdo'])) . '</td>
                </tr>
                <tr>
                    <td>' . ($status === 'aprovado' ? 'Aprovado' : 'Rejeitado') . ' por:</td>
                    <td>' . htmlspecialchars($aprovador['nome']) . '</td>
                </tr>
                <tr>
                    <td>Data:</td>
                    <td>' . date('d/m/Y H:i', strtotime($rdo['data_aprovacao'])) . '</td>
                </tr>
            </table>
        </div>
        ';
        
        if (!empty($comentarios)) {
            $conteudo .= '
            <div class="info-box">
                <strong>💬 Comentários:</strong>
                <p>' . nl2br(htmlspecialchars($comentarios)) . '</p>
            </div>';
        }
        
        $conteudo .= '
        <p style="text-align: center;">
            <a href="' . SITE_URL . '/rdo_visualizar.php?id=' . $rdo['id'] . '" class="button">
                Ver RDO Completo
            </a>
        </p>
        ';
        
        $html = self::templateBase($titulo, $conteudo, $cor);
        return $html;
    }
}
?>