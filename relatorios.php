<?php
require_once 'config.php';
verificarLogin();

// Apenas admin e engenheiro podem ver relatórios gerenciais
if (!in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro'])) {
    $_SESSION['mensagem'] = [
        'tipo' => 'warning',
        'texto' => 'Você não tem permissão para acessar relatórios gerenciais.'
    ];
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Primeiro dia do mês
$data_fim = $_GET['data_fim'] ?? date('Y-m-d'); // Hoje
$obra_id = $_GET['obra_id'] ?? '';

// Estatísticas Gerais
$stats = [];

// Total de RDOs no período
$stmt = $db->prepare("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as aprovados,
           SUM(CASE WHEN status = 'rejeitado' THEN 1 ELSE 0 END) as rejeitados,
           SUM(CASE WHEN status = 'enviado' THEN 1 ELSE 0 END) as pendentes
    FROM rdos 
    WHERE data_rdo BETWEEN ? AND ?
    " . ($obra_id ? "AND obra_id = ?" : "")
);
$params = [$data_inicio, $data_fim];
if ($obra_id) $params[] = $obra_id;
$stmt->execute($params);
$stats['rdos'] = $stmt->fetch();

// RDOs por Obra
$stmt = $db->prepare("
    SELECT o.codigo, o.nome, COUNT(r.id) as total_rdos,
           SUM(CASE WHEN r.status = 'aprovado' THEN 1 ELSE 0 END) as aprovados
    FROM obras o
    LEFT JOIN rdos r ON o.id = r.obra_id AND r.data_rdo BETWEEN ? AND ?
    " . ($obra_id ? "WHERE o.id = ?" : "") . "
    GROUP BY o.id
    HAVING total_rdos > 0
    ORDER BY total_rdos DESC
    LIMIT 10
");
$stmt->execute($params);
$stats['por_obra'] = $stmt->fetchAll();

// RDOs por Usuário
$stmt = $db->prepare("
    SELECT u.nome, COUNT(r.id) as total_rdos,
           SUM(CASE WHEN r.status = 'aprovado' THEN 1 ELSE 0 END) as aprovados
    FROM usuarios u
    JOIN rdos r ON u.id = r.criado_por
    WHERE r.data_rdo BETWEEN ? AND ?
    " . ($obra_id ? "AND r.obra_id = ?" : "") . "
    GROUP BY u.id
    ORDER BY total_rdos DESC
    LIMIT 10
");
$stmt->execute($params);
$stats['por_usuario'] = $stmt->fetchAll();

// RDOs por Dia (últimos 30 dias ou período filtrado)
$stmt = $db->prepare("
    SELECT DATE(data_rdo) as data, COUNT(*) as total
    FROM rdos
    WHERE data_rdo BETWEEN ? AND ?
    " . ($obra_id ? "AND obra_id = ?" : "") . "
    GROUP BY DATE(data_rdo)
    ORDER BY data
");
$stmt->execute($params);
$stats['por_dia'] = $stmt->fetchAll();

// RDOs por Status
$stmt = $db->prepare("
    SELECT status, COUNT(*) as total
    FROM rdos
    WHERE data_rdo BETWEEN ? AND ?
    " . ($obra_id ? "AND obra_id = ?" : "") . "
    GROUP BY status
");
$stmt->execute($params);
$stats['por_status'] = $stmt->fetchAll();

// Buscar obras para filtro
$obras = $db->query("SELECT id, codigo, nome FROM obras ORDER BY nome")->fetchAll();

// Preparar dados para gráficos (JSON)
$grafico_obras = [];
foreach($stats['por_obra'] as $item) {
    $grafico_obras['labels'][] = $item['codigo'];
    $grafico_obras['data'][] = $item['total_rdos'];
}

$grafico_status = [];
foreach($stats['por_status'] as $item) {
    $grafico_status['labels'][] = ucfirst($item['status']);
    $grafico_status['data'][] = $item['total'];
}

$grafico_dias = [];
foreach($stats['por_dia'] as $item) {
    $grafico_dias['labels'][] = date('d/m', strtotime($item['data']));
    $grafico_dias['data'][] = $item['total'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Gerenciais - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .chart-container {
            position: relative;
            height: 300px;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark navbar-expand-lg no-print">
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
                        <a class="nav-link" href="obras.php">
                            <i class="bi bi-building"></i> Obras
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="relatorios.php">
                            <i class="bi bi-graph-up"></i> Relatórios
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center text-white">
                    <span class="me-3"><?= $_SESSION['usuario_nome'] ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h2><i class="bi bi-graph-up"></i> Relatórios Gerenciais</h2>
            <div>
                <button onclick="window.print()" class="btn btn-danger me-2">
                    <i class="bi bi-file-pdf"></i> Gerar PDF
                </button>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Data Início</label>
                        <input type="date" name="data_inicio" class="form-control" 
                               value="<?= $data_inicio ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Fim</label>
                        <input type="date" name="data_fim" class="form-control" 
                               value="<?= $data_fim ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Obra (opcional)</label>
                        <select name="obra_id" class="form-select">
                            <option value="">Todas as obras</option>
                            <?php foreach($obras as $obra): ?>
                            <option value="<?= $obra['id'] ?>" <?= $obra_id == $obra['id'] ? 'selected' : '' ?>>
                                <?= $obra['codigo'] ?> - <?= htmlspecialchars($obra['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cabeçalho do Relatório -->
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <h3>Relatório Gerencial de RDOs</h3>
                <p class="text-muted mb-0">
                    Período: <?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?>
                    <?php if($obra_id): ?>
                        <br>Obra: <?php
                        $obra_sel = array_filter($obras, fn($o) => $o['id'] == $obra_id);
                        $obra_sel = reset($obra_sel);
                        echo $obra_sel['codigo'] . ' - ' . htmlspecialchars($obra_sel['nome']);
                        ?>
                    <?php endif; ?>
                </p>
                <small class="text-muted">Gerado em <?= date('d/m/Y H:i') ?></small>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total de RDOs</h6>
                            <h3 class="mb-0"><?= $stats['rdos']['total'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Aprovados</h6>
                            <h3 class="mb-0 text-success"><?= $stats['rdos']['aprovados'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Rejeitados</h6>
                            <h3 class="mb-0 text-danger"><?= $stats['rdos']['rejeitados'] ?></h3>
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
                            <h3 class="mb-0 text-warning"><?= $stats['rdos']['pendentes'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Gráfico RDOs por Dia -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-calendar3"></i> RDOs por Dia</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartDias"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico por Status -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Por Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartStatus"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico por Obra -->
        <?php if(!empty($stats['por_obra'])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-building"></i> RDOs por Obra (Top 10)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 400px;">
                    <canvas id="chartObras"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabelas -->
        <div class="row g-4">
            <!-- RDOs por Usuário -->
            <?php if(!empty($stats['por_usuario'])): ?>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-person"></i> RDOs por Usuário</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Aprovados</th>
                                        <th class="text-center">Taxa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($stats['por_usuario'] as $item): 
                                        $taxa = $item['total_rdos'] > 0 ? ($item['aprovados'] / $item['total_rdos']) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['nome']) ?></td>
                                        <td class="text-center"><strong><?= $item['total_rdos'] ?></strong></td>
                                        <td class="text-center"><?= $item['aprovados'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $taxa >= 80 ? 'success' : ($taxa >= 50 ? 'warning' : 'danger') ?>">
                                                <?= number_format($taxa, 0) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- RDOs por Obra (Tabela) -->
            <?php if(!empty($stats['por_obra'])): ?>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-building"></i> RDOs por Obra</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Obra</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Aprovados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($stats['por_obra'] as $item): ?>
                                    <tr>
                                        <td><strong><?= $item['codigo'] ?></strong></td>
                                        <td><?= htmlspecialchars($item['nome']) ?></td>
                                        <td class="text-center"><strong><?= $item['total_rdos'] ?></strong></td>
                                        <td class="text-center"><?= $item['aprovados'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de RDOs por Dia
        <?php if(!empty($grafico_dias['labels'])): ?>
        new Chart(document.getElementById('chartDias'), {
            type: 'line',
            data: {
                labels: <?= json_encode($grafico_dias['labels']) ?>,
                datasets: [{
                    label: 'RDOs Criados',
                    data: <?= json_encode($grafico_dias['data']) ?>,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        <?php endif; ?>

        // Gráfico por Status
        <?php if(!empty($grafico_status['labels'])): ?>
        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($grafico_status['labels']) ?>,
                datasets: [{
                    data: <?= json_encode($grafico_status['data']) ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        <?php endif; ?>

        // Gráfico por Obra
        <?php if(!empty($grafico_obras['labels'])): ?>
        new Chart(document.getElementById('chartObras'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($grafico_obras['labels']) ?>,
                datasets: [{
                    label: 'Total de RDOs',
                    data: <?= json_encode($grafico_obras['data']) ?>,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgb(102, 126, 234)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>