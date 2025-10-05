<?php
require_once 'config.php';
verificarLogin();

$obra_id = $_GET['id'] ?? null;

if (!$obra_id) {
    header('Location: obras.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Buscar obra
$stmt = $db->prepare("
    SELECT o.*, u.nome as responsavel_nome, u.email as responsavel_email,
           f.nome as fiscal_nome, f.email as fiscal_email
    FROM obras o
    LEFT JOIN usuarios u ON o.responsavel_id = u.id
    LEFT JOIN usuarios f ON o.fiscal_id = f.id
    WHERE o.id = ?
");
$stmt->execute([$obra_id]);
$obra = $stmt->fetch();

if (!$obra) {
    header('Location: obras.php');
    exit;
}

// NOVO: Verificar se fiscal pode ver esta obra
if ($_SESSION['usuario_tipo'] == 'fiscal' && $obra['fiscal_id'] != $_SESSION['usuario_id']) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para visualizar esta obra! Você só pode acessar obras onde você é o fiscal responsável.'
    ];
    header('Location: obras.php');
    exit;
}

// Estatísticas da obra
$stats = [];
$stats['total_rdos'] = $db->prepare("SELECT COUNT(*) FROM rdos WHERE obra_id = ?");
$stats['total_rdos']->execute([$obra_id]);
$stats['total_rdos'] = $stats['total_rdos']->fetchColumn();

$stats['rdos_aprovados'] = $db->prepare("SELECT COUNT(*) FROM rdos WHERE obra_id = ? AND status = 'aprovado'");
$stats['rdos_aprovados']->execute([$obra_id]);
$stats['rdos_aprovados'] = $stats['rdos_aprovados']->fetchColumn();

$stats['rdos_pendentes'] = $db->prepare("SELECT COUNT(*) FROM rdos WHERE obra_id = ? AND status = 'enviado'");
$stats['rdos_pendentes']->execute([$obra_id]);
$stats['rdos_pendentes'] = $stats['rdos_pendentes']->fetchColumn();

$stats['rdos_rascunho'] = $db->prepare("SELECT COUNT(*) FROM rdos WHERE obra_id = ? AND status = 'rascunho'");
$stats['rdos_rascunho']->execute([$obra_id]);
$stats['rdos_rascunho'] = $stats['rdos_rascunho']->fetchColumn();

// Últimos RDOs da obra
$ultimos_rdos = $db->prepare("
    SELECT r.*, u.nome as criador_nome
    FROM rdos r
    JOIN usuarios u ON r.criado_por = u.id
    WHERE r.obra_id = ?
    ORDER BY r.data_rdo DESC, r.created_at DESC
    LIMIT 10
");
$ultimos_rdos->execute([$obra_id]);
$ultimos_rdos = $ultimos_rdos->fetchAll();

// Progresso
$progresso = $stats['total_rdos'] > 0 ? ($stats['rdos_aprovados'] / $stats['total_rdos']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($obra['nome']) ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .obra-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .progress-bar-custom {
            height: 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
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
            <h2><i class="bi bi-building"></i> Detalhes da Obra</h2>
            <a href="obras.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Header da Obra -->
        <div class="card shadow-lg mb-4">
            <div class="obra-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-2"><?= htmlspecialchars($obra['nome']) ?></h3>
                        <p class="mb-1"><i class="bi bi-tag"></i> Código: <?= $obra['codigo'] ?></p>
                        <?php if ($obra['cliente']): ?>
                        <p class="mb-0"><i class="bi bi-person"></i> Cliente: <?= htmlspecialchars($obra['cliente']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php
                        $status_badges = [
                            'planejamento' => ['secondary', 'Planejamento'],
                            'em_andamento' => ['success', 'Em Andamento'],
                            'pausada' => ['warning', 'Pausada'],
                            'concluida' => ['info', 'Concluída']
                        ];
                        $badge = $status_badges[$obra['status']];
                        ?>
                        <span class="badge bg-<?= $badge[0] ?>" style="font-size: 1.2rem; padding: 12px 20px;">
                            <?= $badge[1] ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted">Endereço</h6>
                        <p class="mb-0"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($obra['endereco'] ?: 'Não informado') ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted">Responsável pela Obra</h6>
                        <p class="mb-0">
                            <i class="bi bi-person-badge"></i> 
                            <?= $obra['responsavel_nome'] ? htmlspecialchars($obra['responsavel_nome']) . ' (' . $obra['responsavel_email'] . ')' : 'Não atribuído' ?>
                        </p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h6 class="text-muted">Fiscal Responsável (Aprova RDOs)</h6>
                        <p class="mb-0">
                            <i class="bi bi-shield-check text-success"></i> 
                            <?php if ($obra['fiscal_nome']): ?>
                                <strong><?= htmlspecialchars($obra['fiscal_nome']) ?></strong> (<?= $obra['fiscal_email'] ?>)
                                <br><small class="text-muted">Apenas este fiscal pode aprovar os RDOs desta obra</small>
                            <?php else: ?>
                                <span class="text-danger">Nenhum fiscal atribuído</span>
                                <br><small class="text-muted">Configure um fiscal para poder aprovar RDOs</small>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if ($obra['data_inicio'] || $obra['data_prevista_fim']): ?>
                <hr>
                <div class="row">
                    <?php if ($obra['data_inicio']): ?>
                    <div class="col-md-6">
                        <h6 class="text-muted">Data de Início</h6>
                        <p class="mb-0"><i class="bi bi-calendar-check"></i> <?= formatarDataBR($obra['data_inicio']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($obra['data_prevista_fim']): ?>
                    <div class="col-md-6">
                        <h6 class="text-muted">Previsão de Término</h6>
                        <p class="mb-0"><i class="bi bi-calendar-x"></i> <?= formatarDataBR($obra['data_prevista_fim']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-file-text text-primary" style="font-size: 2.5rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $stats['total_rdos'] ?></h3>
                        <p class="text-muted mb-0">Total de RDOs</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $stats['rdos_aprovados'] ?></h3>
                        <p class="text-muted mb-0">RDOs Aprovados</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-clock-history text-warning" style="font-size: 2.5rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $stats['rdos_pendentes'] ?></h3>
                        <p class="text-muted mb-0">Pendentes</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark text-secondary" style="font-size: 2.5rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $stats['rdos_rascunho'] ?></h3>
                        <p class="text-muted mb-0">Rascunhos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progresso -->
        <div class="info-card">
            <h5 class="mb-3"><i class="bi bi-graph-up"></i> Progresso da Obra</h5>
            <div class="progress progress-bar-custom">
                <div class="progress-bar bg-success" style="width: <?= $progresso ?>%">
                    <?= number_format($progresso, 1) ?>% Completo
                </div>
            </div>
            <p class="text-muted mt-2 mb-0">
                <?= $stats['rdos_aprovados'] ?> de <?= $stats['total_rdos'] ?> RDOs aprovados
            </p>
        </div>

        <div class="row">
            <!-- Últimos RDOs -->
            <div class="col-md-8">
                <div class="info-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimos RDOs</h5>
                        <a href="rdos.php?obra=<?= $obra_id ?>" class="btn btn-primary btn-sm">
                            Ver Todos os RDOs
                        </a>
                    </div>

                    <?php if (empty($ultimos_rdos)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-2">Nenhum RDO cadastrado para esta obra</p>
                            <a href="rdo_criar.php?obra_id=<?= $obra_id ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Criar Primeiro RDO
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Período</th>
                                        <th>Criado por</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ultimos_rdos as $rdo): ?>
                                    <tr>
                                        <td><?= formatarDataBR($rdo['data_rdo']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= ucfirst(str_replace('_', ' ', $rdo['periodo'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($rdo['criador_nome']) ?></td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'rascunho' => ['secondary', 'Rascunho'],
                                                'enviado' => ['warning', 'Enviado'],
                                                'aprovado' => ['success', 'Aprovado'],
                                                'rejeitado' => ['danger', 'Rejeitado']
                                            ];
                                            $badge = $badges[$rdo['status']];
                                            ?>
                                            <span class="badge bg-<?= $badge[0] ?>">
                                                <?= $badge[1] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="rdo_visualizar.php?id=<?= $rdo['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ações -->
            <div class="col-md-4">
                <div class="info-card">
                    <h5 class="mb-3"><i class="bi bi-lightning"></i> Ações Rápidas</h5>
                    <div class="d-grid gap-2">
                        <a href="rdo_criar.php?obra_id=<?= $obra_id ?>" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Novo RDO para esta Obra
                        </a>
                        
                        <a href="rdos.php?obra=<?= $obra_id ?>" class="btn btn-primary">
                            <i class="bi bi-file-text"></i> Ver Todos os RDOs
                        </a>
                        
                        <?php if (in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro'])): ?>
                        <a href="obra_criar.php?id=<?= $obra_id ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Editar Obra
                        </a>
                        
                        <button onclick="confirmarExclusao()" class="btn btn-outline-danger">
                            <i class="bi bi-trash"></i> Excluir Obra
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informações Adicionais -->
                <div class="info-card">
                    <h5 class="mb-3"><i class="bi bi-info-circle"></i> Informações</h5>
                    <p class="mb-2">
                        <strong>Criado em:</strong><br>
                        <?= date('d/m/Y H:i', strtotime($obra['created_at'])) ?>
                    </p>
                    <?php if ($obra['updated_at'] != $obra['created_at']): ?>
                    <p class="mb-0">
                        <strong>Última atualização:</strong><br>
                        <?= date('d/m/Y H:i', strtotime($obra['updated_at'])) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarExclusao() {
            if (confirm('ATENÇÃO: Tem certeza que deseja excluir esta obra?\n\nTodos os RDOs relacionados também serão excluídos!\n\nEsta ação não pode ser desfeita!')) {
                if (confirm('Confirme novamente: Deseja realmente EXCLUIR esta obra e todos os seus RDOs?')) {
                    window.location.href = 'obra_excluir.php?id=<?= $obra_id ?>';
                }
            }
        }
    </script>
</body>
</html>