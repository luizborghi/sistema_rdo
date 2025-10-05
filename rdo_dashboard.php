<?php
require_once 'config.php';
verificarLogin();

$db = Database::getInstance()->getConnection();

// Estatísticas
if ($_SESSION['usuario_tipo'] == 'fiscal') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM obras WHERE status = 'em_andamento' AND fiscal_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $total_obras = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos r JOIN obras o ON r.obra_id = o.id WHERE MONTH(r.data_rdo) = MONTH(CURRENT_DATE) AND o.fiscal_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $total_rdos = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos r JOIN obras o ON r.obra_id = o.id WHERE r.status = 'enviado' AND o.fiscal_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $rdos_pendentes = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos r JOIN obras o ON r.obra_id = o.id WHERE r.data_rdo = CURDATE() AND o.fiscal_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $rdos_hoje = $stmt->fetchColumn();
} else {
    $total_obras = $db->query("SELECT COUNT(*) FROM obras WHERE status = 'em_andamento'")->fetchColumn();
    $total_rdos = $db->query("SELECT COUNT(*) FROM rdos WHERE MONTH(data_rdo) = MONTH(CURRENT_DATE)")->fetchColumn();
    $rdos_pendentes = $db->query("SELECT COUNT(*) FROM rdos WHERE status = 'enviado'")->fetchColumn();
    $rdos_hoje = $db->query("SELECT COUNT(*) FROM rdos WHERE data_rdo = CURDATE()")->fetchColumn();
}

$stats = [
    'total_obras' => $total_obras,
    'total_rdos' => $total_rdos,
    'rdos_pendentes' => $rdos_pendentes,
    'rdos_hoje' => $rdos_hoje
];

// Obras ativas
if ($_SESSION['usuario_tipo'] == 'fiscal') {
    $stmt = $db->prepare("SELECT * FROM obras WHERE status = 'em_andamento' AND fiscal_id = ? ORDER BY nome LIMIT 5");
    $stmt->execute([$_SESSION['usuario_id']]);
    $obras = $stmt->fetchAll();
} else {
    $obras = $db->query("SELECT * FROM obras WHERE status = 'em_andamento' ORDER BY nome LIMIT 5")->fetchAll();
}

// Últimos RDOs
if ($_SESSION['usuario_tipo'] == 'fiscal') {
    $stmt = $db->prepare("
        SELECT r.*, o.nome as obra_nome, u.nome as criador_nome 
        FROM rdos r
        JOIN obras o ON r.obra_id = o.id
        JOIN usuarios u ON r.criado_por = u.id
        WHERE o.fiscal_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $ultimos_rdos = $stmt->fetchAll();
} else {
    $ultimos_rdos = $db->query("
        SELECT r.*, o.nome as obra_nome, u.nome as criador_nome 
        FROM rdos r
        JOIN obras o ON r.obra_id = o.id
        JOIN usuarios u ON r.criado_por = u.id
        ORDER BY r.created_at DESC
        LIMIT 10
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rdos.php">
                            <i class="bi bi-file-text"></i> RDOs
                        </a>
                    </li>
                    <?php if (in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro', 'fiscal'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="aprovacoes.php">
                            <i class="bi bi-check-circle"></i> Aprovações
                            <?php if ($stats['rdos_pendentes'] > 0): ?>
                            <span class="badge bg-warning"><?= $stats['rdos_pendentes'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="obras.php">
                            <i class="bi bi-building"></i> Obras
                        </a>
                    </li>
                    <?php if (in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="relatorios.php">
                            <i class="bi bi-graph-up"></i> Relatórios
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['usuario_tipo'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="bi bi-people"></i> Usuários
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="relatorios.php">
                            <i class="bi bi-graph-up"></i> Relatórios
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center text-white">
                    <i class="bi bi-person-circle me-2"></i>
                    <span class="me-3"><?= $_SESSION['usuario_nome'] ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Estatísticas -->
        <?php if ($_SESSION['usuario_tipo'] == 'fiscal'): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="bi bi-shield-check"></i> 
                <strong>Modo Fiscal:</strong> Dashboard mostrando apenas dados das suas obras.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-building"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Obras Ativas</h6>
                            <h3 class="mb-0"><?= $stats['total_obras'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">RDOs este Mês</h6>
                            <h3 class="mb-0"><?= $stats['total_rdos'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Pendentes</h6>
                            <h3 class="mb-0"><?= $stats['rdos_pendentes'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">RDOs Hoje</h6>
                            <h3 class="mb-0"><?= $stats['rdos_hoje'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Gráfico -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> RDOs nos Últimos 7 Dias</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="rdoChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Obras Ativas -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-building"></i> Obras em Andamento</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach($obras as $obra): ?>
                            <a href="obra_detalhes.php?id=<?= $obra['id'] ?>" 
                               class="list-group-item list-group-item-action border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($obra['nome']) ?></h6>
                                        <small class="text-muted"><?= $obra['codigo'] ?></small>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="obras.php" class="btn btn-outline-primary btn-sm">Ver Todas</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos RDOs -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimos RDOs</h5>
                        <a href="rdos.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Novo RDO
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Obra</th>
                                        <th>Criado por</th>
                                        <th>Período</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ultimos_rdos as $rdo): ?>
                                    <tr>
                                        <td><?= formatarDataBR($rdo['data_rdo']) ?></td>
                                        <td><?= htmlspecialchars($rdo['obra_nome']) ?></td>
                                        <td><?= htmlspecialchars($rdo['criador_nome']) ?></td>
                                        <td><span class="badge bg-secondary"><?= ucfirst($rdo['periodo']) ?></span></td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'rascunho' => 'secondary',
                                                'enviado' => 'warning',
                                                'aprovado' => 'success',
                                                'rejeitado' => 'danger'
                                            ];
                                            ?>
                                            <span class="status-badge bg-<?= $badges[$rdo['status']] ?>">
                                                <?= ucfirst($rdo['status']) ?>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de RDOs
        const ctx = document.getElementById('rdoChart').getContext('2d');
        
        // Buscar dados via AJAX
        fetch('api_rdos_chart.php')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'RDOs Criados',
                            data: data.values,
                            backgroundColor: 'rgba(102, 126, 234, 0.8)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            });
    </script>
</body>
</html>