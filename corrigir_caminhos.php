<?php
/**
 * Script para corrigir caminhos de fotos no banco de dados
 * Execute UMA VEZ para corrigir caminhos absolutos para relativos
 */

require_once 'config.php';
verificarLogin();

if (!in_array($_SESSION['usuario_tipo'], ['admin'])) {
    die('Apenas administradores podem executar este script');
}

$db = Database::getInstance()->getConnection();

// Buscar todas as fotos
$stmt = $db->query("SELECT * FROM rdo_anexos WHERE tipo = 'foto'");
$fotos = $stmt->fetchAll();

$corrigidos = 0;
$erros = 0;

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Correção de Caminhos</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h2>🔧 Corrigindo Caminhos das Fotos</h2>
    <div class='alert alert-info'>Processando " . count($fotos) . " foto(s)...</div>
";

foreach($fotos as $foto) {
    $caminho_antigo = $foto['caminho_arquivo'];
    $caminho_novo = $caminho_antigo;
    
    // Se for caminho absoluto do Windows (C:\xampp\...)
    if (preg_match('/^[a-z]:\\/i', $caminho_antigo)) {
        // Extrair parte relevante (uploads/rdos/...)
        if (preg_match('/uploads[\\/]rdos[\\/](\d+)[\\/](.+)$/i', $caminho_antigo, $matches)) {
            $rdo_id = $matches[1];
            $arquivo = $matches[2];
            $caminho_novo = "uploads/rdos/{$rdo_id}/{$arquivo}";
        }
    }
    
    // Se o caminho foi alterado, atualizar no banco
    if ($caminho_novo !== $caminho_antigo) {
        try {
            $stmt = $db->prepare("UPDATE rdo_anexos SET caminho_arquivo = ? WHERE id = ?");
            $stmt->execute([$caminho_novo, $foto['id']]);
            
            echo "<div class='alert alert-success'>
                ✓ <strong>Foto #{$foto['id']}</strong><br>
                <small>Antigo: <code>{$caminho_antigo}</code></small><br>
                <small>Novo: <code>{$caminho_novo}</code></small>
            </div>";
            
            $corrigidos++;
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>
                ✗ <strong>Erro na foto #{$foto['id']}</strong><br>
                {$e->getMessage()}
            </div>";
            $erros++;
        }
    } else {
        echo "<div class='alert alert-secondary'>
            ➜ <strong>Foto #{$foto['id']}</strong> já está com caminho correto<br>
            <small><code>{$caminho_antigo}</code></small>
        </div>";
    }
}

echo "
    <hr>
    <div class='alert alert-info'>
        <h4>Resumo:</h4>
        <strong>Total de fotos:</strong> " . count($fotos) . "<br>
        <strong>Corrigidos:</strong> <span class='text-success'>{$corrigidos}</span><br>
        <strong>Erros:</strong> <span class='text-danger'>{$erros}</span><br>
        <strong>Já corretos:</strong> " . (count($fotos) - $corrigidos - $erros) . "
    </div>
    
    <a href='rdos.php' class='btn btn-primary'>← Voltar para RDOs</a>
    
    <div class='alert alert-warning mt-3'>
        <strong>⚠️ IMPORTANTE:</strong> Após executar este script e confirmar que está tudo OK, 
        você pode apagar este arquivo (corrigir_caminhos_fotos.php) por segurança.
    </div>
</div>
</body>
</html>";
?>