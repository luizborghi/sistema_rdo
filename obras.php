<?php
require_once 'config.php';
verificarLogin();

$db = Database::getInstance()->getConnection();

// Buscar obras
$filtro_status = $_GET['status'] ?? '';

$sql = "SELECT o.*, u.nome as responsavel_nome,
        (SELECT COUNT(*) FROM rdos WHERE obra_id = o.id) as total_rdos,
        (SELECT COUNT(*) FROM rdos WHERE obra_id = o.id AND status = 'aprovado') as rdos_aprovados
        FROM obras o
        LEFT JOIN usuarios u ON o.responsavel_id = u.id
        WHERE 1=1";

$params = [];

if ($filtro_status) {
    $sql .= " AND o.status = ?";
    $params[] = $filtro_status;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$obras = $stmt->fetchAll();

// Estatísticas
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM obras")->fetchColumn(),
    'em_andamento' => $db->query("SELECT COUNT(*) FROM obras WHERE status = 'em_andamento'")->fetchColumn(),
    'concluidas' => $db->query("SELECT COUNT(*) FROM obras WHERE status = 'concluida'")->fetchColumn(),
    'pausadas' => $db->query("SELECT COUNT(*) FROM obras WHERE status = 'pausada'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obras - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        .obra-card {
            transition: all 0.3s;
            border-left: 4px solid #667eea;
            cursor: pointer;
        }
        .obra-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transform: translateY(-3px);
        }
        .progress-bar-custom {
            height: 25px;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-nova-obra {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-clipboard-check"></i> Sistema RDO
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rdos.php">
                            <i class="bi bi-file-text"></i> RDOs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="obras.php">
                            <i class="bi bi-building"></i> Obras
                        </a>
                    </li>
                    <?php if (in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro', 'fiscal'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="aprovacoes.php">
                            <i class="bi bi-check-circle"></i> Aprovações
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center text-white">
                    <span class="me-3"><?= $_SESSION['usuario_nome'] ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['mensagem']['texto'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-building"></i> Gerenciar Obras</h2>
            <?php if (in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro'])): ?>
            <a href="obra_criar.php" class="btn btn-nova-obra text-white btn-lg">
                <i class="bi bi-plus-circle"></i> Nova Obra
            </a>
            <?php endif; ?>
        </div>

        <!-- Estatísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-building"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total de Obras</h6>
                            <h3 class="mb-0"><?= $stats['total'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-play-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Em Andamento</h6>
                            <h3 class="mb-0 text-success"><?= $stats['em_andamento'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Concluídas</h6>
                            <h3 class="mb-0 text-info"><?= $stats['concluidas'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-pause-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Pausadas</h6>
                            <h3 class="mb-0 text-warning"><?= $stats['pausadas'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filtrar por Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos os status</option>
                            <option value="planejamento" <?= $filtro_status == 'planejamento' ? 'selected' : '' ?>>Planejamento</option>
                            <option value="em_andamento" <?= $filtro_status == 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="pausada" <?= $filtro_status == 'pausada' ? 'selected' : '' ?>>Pausada</option>
                            <option value="concluida" <?= $filtro_status == 'concluida' ? 'selected' : '' ?>>Concluída</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="obras.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Obras -->
        <div class="row g-4">
            <?php if (empty($obras)): ?>
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-building" style="font-size: 4rem; color: #ccc;"></i>
                            <h4 class="mt-3 text-muted">Nenhuma obra cadastrada</h4>
                            <p class="text-muted">Comece criando sua primeira obra</p>
                            <?php if (in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro'])): ?>
                            <a href="obra_criar.php" class="btn btn-primary mt-3">
                                <i class="bi bi-plus-circle"></i> Criar Primeira Obra
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach($obras as $obra): 
                    $progresso = $obra['total_rdos'] > 0 ? ($obra['rdos_aprovados'] / $obra['total_rdos']) * 100 : 0;
                    
                    $status_badges = [
                        'planejamento' => ['secondary', 'Planejamento'],
                        'em_andamento' => ['success', 'Em Andamento'],
                        'pausada' => ['warning', 'Pausada'],
                        'concluida' => ['info', 'Concluída']
                    ];
                    $badge = $status_badges[$obra['status']];
                ?>
                <div class="col-md-6">
                    <div class="card obra-card" onclick="window.location='obra_detalhes.php?id=<?= $obra['id'] ?>'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($obra['nome']) ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-tag"></i> <?= $obra['codigo'] ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?= $badge[0] ?>" style="font-size: 0.9rem; padding: 8px 15px;">
                                    <?= $badge[1] ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <p class="mb-1"><i class="bi bi-person"></i> <strong>Cliente:</strong> <?= htmlspecialchars($obra['cliente'] ?: 'Não informado') ?></p>
                                <p class="mb-1"><i class="bi bi-geo-alt"></i> <strong>Local:</strong> <?= htmlspecialchars($obra['endereco'] ?: 'Não informado') ?></p>
                                <p class="mb-0"><i class="bi bi-person-badge"></i> <strong>Responsável:</strong> <?= htmlspecialchars($obra['responsavel_nome'] ?: 'Não atribuído') ?></p>
                            </div>

                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <h4 class="mb-0 text-primary"><?= $obra['total_rdos'] ?></h4>
                                        <small class="text-muted">RDOs Total</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <h4 class="mb-0 text-success"><?= $obra['rdos_aprovados'] ?></h4>
                                        <small class="text-muted">Aprovados</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <h4 class="mb-0 text-info"><?= number_format($progresso, 0) ?>%</h4>
                                        <small class="text-muted">Progresso</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small mb-1">Progresso de RDOs Aprovados</label>
                                <div class="progress progress-bar-custom">
                                    <div class="progress-bar bg-success" style="width: <?= $progresso ?>%">
                                        <?= number_format($progresso, 0) ?>%
                                    </div>
                                </div>
                            </div>

                            <?php if ($obra['data_inicio']): ?>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="bi bi-calendar-check"></i> Início: <?= formatarDataBR($obra['data_inicio']) ?></span>
                                <?php if ($obra['data_prevista_fim']): ?>
                                <span><i class="bi bi-calendar-x"></i> Previsão: <?= formatarDataBR($obra['data_prevista_fim']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <hr>

                            <div class="d-flex gap-2" onclick="event.stopPropagation()">
                                <a href="obra_detalhes.php?id=<?= $obra['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                    <i class="bi bi-eye"></i> Ver Detalhes
                                </a>
                                <?php if (in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro'])): ?>
                                <a href="obra_criar.php?id=<?= $obra['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <?php endif; ?>
                                <a href="rdos.php?obra=<?= $obra['id'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-file-text"></i> RDOs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>