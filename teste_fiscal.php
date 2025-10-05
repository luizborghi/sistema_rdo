<?php
require_once 'config.php';
verificarLogin();

// Apenas admin pode executar testes
if ($_SESSION['usuario_tipo'] != 'admin') {
    die('Apenas administradores podem executar testes');
}

$db = Database::getInstance()->getConnection();

// Buscar todos os fiscais
$fiscais = $db->query("SELECT id, nome, email FROM usuarios WHERE tipo = 'fiscal' AND ativo = 1")->fetchAll();

// Buscar todas as obras
$obras = $db->query("SELECT id, codigo, nome, fiscal_id FROM obras ORDER BY nome")->fetchAll();

// Buscar todos os RDOs
$rdos = $db->query("SELECT r.id, r.data_rdo, o.nome as obra_nome, o.fiscal_id 
                    FROM rdos r 
                    JOIN obras o ON r.obra_id = o.id 
                    ORDER BY r.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Restrições do Fiscal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .test-box {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .ok { color: #28a745; font-weight: bold; }
        .erro { color: #dc3545; font-weight: bold; }
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
            <h2><i class="bi bi-bug"></i> Teste de Restrições do Fiscal</h2>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            <strong>Atenção:</strong> Este é um teste de diagnóstico para verificar se as restrições estão funcionando corretamente.
        </div>

        <!-- Resumo -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?= count($fiscais) ?></h3>
                        <p class="mb-0">Fiscais Ativos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?= count($obras) ?></h3>
                        <p class="mb-0">Obras Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3><?= count($rdos) ?></h3>
                        <p class="mb-0">RDOs Total</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalhamento por Fiscal -->
        <?php foreach($fiscais as $fiscal): ?>
        <div class="test-box">
            <h4><i class="bi bi-person-badge"></i> <?= htmlspecialchars($fiscal['nome']) ?></h4>
            <p class="text-muted"><?= $fiscal['email'] ?></p>

            <hr>

            <h5>📋 Obras Atribuídas:</h5>
            <?php
            $obras_fiscal = array_filter($obras, function($o) use ($fiscal) {
                return $o['fiscal_id'] == $fiscal['id'];
            });
            ?>

            <?php if (empty($obras_fiscal)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong>ATENÇÃO:</strong> Este fiscal não tem nenhuma obra atribuída!
                </div>
            <?php else: ?>
                <ul class="list-group mb-3">
                    <?php foreach($obras_fiscal as $obra): ?>
                    <li class="list-group-item">
                        <strong><?= $obra['codigo'] ?></strong> - <?= htmlspecialchars($obra['nome']) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <h5>📄 RDOs que DEVE VER:</h5>
                <?php
                $rdos_fiscal = array_filter($rdos, function($r) use ($fiscal) {
                    return $r['fiscal_id'] == $fiscal['id'];
                });
                ?>

                <?php if (empty($rdos_fiscal)): ?>
                    <p class="text-muted">Nenhum RDO nas obras deste fiscal.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>RDO #</th>
                                    <th>Data</th>
                                    <th>Obra</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($rdos_fiscal as $rdo): ?>
                                <tr>
                                    <td><strong><?= $rdo['id'] ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($rdo['data_rdo'])) ?></td>
                                    <td><?= htmlspecialchars($rdo['obra_nome']) ?></td>
                                    <td><span class="ok">✓ Deve ver</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <h5>🚫 RDOs que NÃO DEVE VER:</h5>
                <?php
                $rdos_outros = array_filter($rdos, function($r) use ($fiscal) {
                    return $r['fiscal_id'] != $fiscal['id'];
                });
                ?>

                <?php if (empty($rdos_outros)): ?>
                    <p class="text-muted">Não há RDOs de outras obras.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>RDO #</th>
                                    <th>Data</th>
                                    <th>Obra</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                foreach($rdos_outros as $rdo): 
                                    if ($count >= 5) break; // Mostrar apenas 5
                                    $count++;
                                ?>
                                <tr>
                                    <td><strong><?= $rdo['id'] ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($rdo['data_rdo'])) ?></td>
                                    <td><?= htmlspecialchars($rdo['obra_nome']) ?></td>
                                    <td><span class="erro">✗ NÃO deve ver</span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($rdos_outros) > 5): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        ... e mais <?= count($rdos_outros) - 5 ?> RDO(s)
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- SQL de Teste -->
        <div class="card mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-code-square"></i> SQL de Teste</h5>
            </div>
            <div class="card-body">
                <p>Para testar manualmente, use estas queries no phpMyAdmin:</p>

                <h6>Ver obras de um fiscal específico:</h6>
                <pre class="bg-light p-3"><code>SELECT * FROM obras WHERE fiscal_id = [ID_DO_FISCAL];</code></pre>

                <h6>Ver RDOs que um fiscal DEVE ver:</h6>
                <pre class="bg-light p-3"><code>SELECT r.*, o.nome as obra_nome 
FROM rdos r
JOIN obras o ON r.obra_id = o.id
WHERE o.fiscal_id = [ID_DO_FISCAL];</code></pre>

                <h6>Contar RDOs pendentes de um fiscal:</h6>
                <pre class="bg-light p-3"><code>SELECT COUNT(*) 
FROM rdos r
JOIN obras o ON r.obra_id = o.id
WHERE r.status = 'enviado' 
AND o.fiscal_id = [ID_DO_FISCAL];</code></pre>
            </div>
        </div>

        <!-- Instruções de Teste -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Como Testar</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li>Faça <strong>logout</strong></li>
                    <li>Faça <strong>login como um dos fiscais</strong> acima</li>
                    <li>Vá em <strong>"Obras"</strong> → Deve ver APENAS as obras listadas acima</li>
                    <li>Vá em <strong>"RDOs"</strong> → Deve ver APENAS os RDOs listados como "✓ Deve ver"</li>
                    <li>Vá em <strong>"Aprovações"</strong> → Deve ver APENAS pendentes das suas obras</li>
                    <li>Tente acessar um RDO de outra obra → Deve ser <strong>BLOQUEADO</strong></li>
                </ol>

                <div class="alert alert-success mt-3">
                    <strong>✓ Teste passou</strong> se o fiscal vir APENAS os RDOs marcados em verde acima.
                </div>

                <div class="alert alert-danger">
                    <strong>✗ Teste falhou</strong> se o fiscal conseguir ver RDOs marcados em vermelho acima.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>