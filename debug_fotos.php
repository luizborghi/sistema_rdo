<?php
require_once 'config.php';
verificarLogin();

$rdo_id = $_GET['id'] ?? null;

if (!$rdo_id) {
    die('Informe o ID do RDO: debug_fotos.php?id=1');
}

$db = Database::getInstance()->getConnection();

// Buscar fotos
$stmt = $db->prepare("SELECT * FROM rdo_anexos WHERE rdo_id = ? AND tipo = 'foto'");
$stmt->execute([$rdo_id]);
$fotos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Fotos RDO #<?= $rdo_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        .error { color: red; }
        .success { color: green; }
        .warning { color: orange; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h2>🔍 Debug - Fotos do RDO #<?= $rdo_id ?></h2>
        
        <div class="alert alert-info">
            <strong>Informações do Sistema:</strong><br>
            <strong>UPLOAD_DIR:</strong> <?= UPLOAD_DIR ?><br>
            <strong>DOCUMENT_ROOT:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?><br>
            <strong>Script Path:</strong> <?= __DIR__ ?>
        </div>

        <?php if (empty($fotos)): ?>
            <div class="alert alert-warning">
                ⚠️ Nenhuma foto encontrada no banco de dados para este RDO.
            </div>
        <?php else: ?>
            <h4>Fotos Encontradas: <?= count($fotos) ?></h4>
            
            <?php foreach($fotos as $index => $foto): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Foto #<?= $index + 1 ?>: <?= htmlspecialchars($foto['nome_arquivo']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <strong>ID:</strong> <?= $foto['id'] ?><br>
                            <strong>Nome do Arquivo:</strong> <?= htmlspecialchars($foto['nome_arquivo']) ?><br>
                            <strong>Caminho no BD:</strong> <code><?= htmlspecialchars($foto['caminho_arquivo']) ?></code><br>
                            <strong>Upload em:</strong> <?= $foto['uploaded_at'] ?>
                        </div>

                        <?php
                        $caminho_bd = $foto['caminho_arquivo'];
                        $arquivo_existe = file_exists($caminho_bd);
                        $arquivo_legivel = is_readable($caminho_bd);
                        
                        // Tentar diferentes caminhos
                        $caminhos_testar = [
                            'Caminho do BD' => $caminho_bd,
                            'Caminho relativo' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $caminho_bd),
                            'Uploads direto' => 'uploads/rdos/' . $rdo_id . '/' . basename($caminho_bd),
                            'Com barra' => '/' . ltrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', $caminho_bd), '/')
                        ];
                        ?>

                        <h6>Testes de Caminho:</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Caminho</th>
                                    <th>Existe?</th>
                                    <th>Legível?</th>
                                    <th>Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($caminhos_testar as $tipo => $caminho): 
                                    $existe = file_exists($caminho);
                                    $legivel = $existe && is_readable($caminho);
                                ?>
                                <tr>
                                    <td><?= $tipo ?></td>
                                    <td><code><?= htmlspecialchars($caminho) ?></code></td>
                                    <td class="<?= $existe ? 'success' : 'error' ?>">
                                        <?= $existe ? '✓ Sim' : '✗ Não' ?>
                                    </td>
                                    <td class="<?= $legivel ? 'success' : 'error' ?>">
                                        <?= $legivel ? '✓ Sim' : '✗ Não' ?>
                                    </td>
                                    <td>
                                        <?php if ($legivel): ?>
                                            <img src="<?= htmlspecialchars($caminho) ?>" 
                                                 style="max-width: 100px; max-height: 100px;" 
                                                 onerror="this.parentElement.innerHTML='<span class=error>Erro ao carregar</span>'">
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="alert alert-secondary mt-3">
                            <strong>Tamanho do arquivo:</strong> 
                            <?php if ($arquivo_existe): ?>
                                <?= number_format(filesize($caminho_bd) / 1024, 2) ?> KB
                            <?php else: ?>
                                <span class="error">Arquivo não encontrado</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($arquivo_legivel): ?>
                            <div class="alert alert-success">
                                ✅ <strong>Foto está acessível! Caminho correto:</strong><br>
                                <code><?= htmlspecialchars($caminho_bd) ?></code>
                            </div>
                            
                            <h6>Preview da Imagem:</h6>
                            <img src="<?= htmlspecialchars($caminho_bd) ?>" 
                                 class="img-fluid" 
                                 style="max-width: 400px; border: 2px solid #ddd; padding: 5px;">
                        <?php else: ?>
                            <div class="alert alert-danger">
                                ❌ <strong>Problema encontrado:</strong><br>
                                <?php if (!$arquivo_existe): ?>
                                    O arquivo não existe no servidor. Verifique se o upload foi feito corretamente.
                                <?php else: ?>
                                    O arquivo existe mas não pode ser lido. Verifique as permissões.
                                <?php endif; ?>
                            </div>

                            <h6>💡 Soluções:</h6>
                            <ol>
                                <li>Verifique se a pasta <code>uploads/rdos/<?= $rdo_id ?>/</code> existe</li>
                                <li>Verifique as permissões da pasta: <code>chmod 755 uploads/ -R</code></li>
                                <li>Verifique se o arquivo realmente foi feito upload</li>
                                <li>Tente fazer upload de uma nova foto para testar</li>
                            </ol>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr>
        
        <h5>🔧 Verificar Pasta de Uploads</h5>
        <div class="info-box">
            <?php
            $pasta_uploads = UPLOAD_DIR . 'rdos/' . $rdo_id . '/';
            $pasta_existe = is_dir($pasta_uploads);
            $pasta_gravavel = is_writable($pasta_uploads);
            ?>
            <strong>Pasta:</strong> <code><?= $pasta_uploads ?></code><br>
            <strong>Existe:</strong> <span class="<?= $pasta_existe ? 'success' : 'error' ?>">
                <?= $pasta_existe ? '✓ Sim' : '✗ Não' ?>
            </span><br>
            <strong>Gravável:</strong> <span class="<?= $pasta_gravavel ? 'success' : 'error' ?>">
                <?= $pasta_gravavel ? '✓ Sim' : '✗ Não' ?>
            </span>

            <?php if ($pasta_existe): ?>
                <br><br><strong>Arquivos na pasta:</strong>
                <ul>
                    <?php
                    $arquivos = scandir($pasta_uploads);
                    foreach($arquivos as $arquivo) {
                        if ($arquivo != '.' && $arquivo != '..') {
                            echo '<li>' . htmlspecialchars($arquivo) . '</li>';
                        }
                    }
                    ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <a href="rdo_visualizar.php?id=<?= $rdo_id ?>" class="btn btn-primary">← Voltar para RDO</a>
            <a href="rdo_pdf.php?id=<?= $rdo_id ?>" class="btn btn-info" target="_blank">Ver PDF</a>
        </div>
    </div>
</body>
</html>