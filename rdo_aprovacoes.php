<?php
require_once 'config.php';
verificarLogin();

// Verificar permissão
$pode_aprovar = in_array($_SESSION['usuario_tipo'], ['admin', 'engenheiro', 'fiscal']);

if (!$pode_aprovar) {
    $_SESSION['mensagem'] = [
        'tipo' => 'warning',
        'texto' => 'Você não tem permissão para acessar o painel de aprovações.'
    ];
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Estatísticas de aprovação
if ($_SESSION['usuario_tipo'] == 'fiscal') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos r JOIN obras o ON r.obra_id = o.id WHERE r.status = 'enviado' AND o.fiscal_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $pendentes = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos r JOIN obras o ON r.obra_id = o.id WHERE r.status = 'aprovado' AND DATE(r.data_aprovacao) = CURDATE() AND o.fiscal_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $aprovados_hoje = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos r JOIN obras o ON r.obra_id = o.id WHERE r.status = 'rejeitado' AND DATE(r.data_aprovacao) = CURDATE() AND o.fiscal_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $rejeitados_hoje = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM rdos r JOIN obras o ON r.obra_id = o.id WHERE r.status IN ('aprovado', 'rejeitado') AND MONTH(r.data_aprovacao) = MONTH(CURDATE()) AND o.fiscal_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $total_mes = $stmt->fetchColumn();
} else {
    $pendentes = $db->query("SELECT COUNT(*) FROM rdos WHERE status = 'enviado'")->fetchColumn();
    $aprovados_hoje = $db->query("SELECT COUNT(*) FROM rdos WHERE status = 'aprovado' AND DATE(data_aprovacao) = CURDATE()")->fetchColumn();
    $rejeitados_hoje = $db->query("SELECT COUNT(*) FROM rdos WHERE status = 'rejeitado' AND DATE(data_aprovacao) = CURDATE()")->fetchColumn();
    $total_mes = $db->query("SELECT COUNT(*) FROM rdos WHERE status IN ('aprovado', 'rejeitado') AND MONTH(data_aprovacao) = MONTH(CURDATE())")->fetchColumn();
}

$stats = [
    'pendentes' => $pendentes,
    'aprovados_hoje' => $aprovados_hoje,
    'rejeitados_hoje' => $rejeitados_hoje,
    'total_mes' => $total_mes
];

// RDOs pendentes de aprovação
$sql_pendentes = "
    SELECT r.*, o.nome as obra_nome, o.codigo as obra_codigo, 
           u.nome as criador_nome,
           DATEDIFF(CURDATE(), r.data_rdo) as dias_atraso
    FROM rdos r
    JOIN obras o ON r.obra_id = o.id
    JOIN usuarios u ON r.criado_por = u.id
    WHERE r.status = 'enviado'
";

$params_pendentes = [];

// CRÍTICO: Se for fiscal, mostrar apenas RDOs das obras que ele é responsável
if ($_SESSION['usuario_tipo'] == 'fiscal') {
    $sql_pendentes .= " AND o.fiscal_id = ?";
    $params_pendentes[] = $_SESSION['usuario_id'];
}

$sql_pendentes .= " ORDER BY r.data_rdo ASC, r.created_at ASC";

$stmt_pendentes = $db->prepare($sql_pendentes);
$stmt_pendentes->execute($params_pendentes);
$rdos_pendentes = $stmt_pendentes->fetchAll();

// RDOs recentemente aprovados/rejeitados
$sql_processados = "
    SELECT r.*, o.nome as obra_nome, o.codigo as obra_codigo,
           u.nome as criador_nome, ap.nome as aprovador_nome
    FROM rdos r
    JOIN obras o ON r.obra_id = o.id
    JOIN usuarios u ON r.criado_por = u.id
    LEFT JOIN usuarios ap ON r.aprovado_por = ap.id
    WHERE r.status IN ('aprovado', 'rejeitado')
";

$params_processados = [];

// CRÍTICO: Se for fiscal, mostrar apenas das suas obras
if ($_SESSION['usuario_tipo'] == 'fiscal') {
    $sql_processados .= " AND o.fiscal_id = ?";
    $params_processados[] = $_SESSION['usuario_id'];
}

$sql_processados .= " ORDER BY r.data_aprovacao DESC LIMIT 20";

$stmt_processados = $db->prepare($sql_processados);
$stmt_processados->execute($params_processados);
$rdos_processados = $stmt_processados->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Aprovações - <?= SITE_NAME ?></title>
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
        .urgente {
            border-left: 4px solid #dc3545 !important;
            background: #fff5f5 !important;
        }
        .alerta {
            border-left: 4px solid #ffc107 !important;
            background: #fffbf0 !important;
        }
        .rdo-card {
            transition: all 0.3s;
            cursor: pointer;
            border-left: 4px solid #667eea;
        }
        .rdo-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transform: translateX(5px);
        }
        .badge-dias {
            font-size: 0.9rem;
            padding: 6px 12px;
        }
        .aprovacao-rapida {
            display: none;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 2px solid #667eea;
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
                        <a class="nav-link active" href="aprovacoes.php">
                            <i class="bi bi-check-circle"></i> Aprovações
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
        <h2 class="mb-4"><i class="bi bi-check-circle"></i> Painel de Aprovações</h2>

        <?php if ($_SESSION['usuario_tipo'] == 'fiscal'): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="bi bi-shield-check"></i> 
                <strong>Modo Fiscal:</strong> Você está visualizando apenas RDOs das obras onde você é o fiscal responsável.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Pendentes</h6>
                            <h3 class="mb-0 text-warning"><?= $stats['pendentes'] ?></h3>
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
                            <h6 class="text-muted mb-1">Aprovados Hoje</h6>
                            <h3 class="mb-0 text-success"><?= $stats['aprovados_hoje'] ?></h3>
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
                            <h6 class="text-muted mb-1">Rejeitados Hoje</h6>
                            <h3 class="mb-0 text-danger"><?= $stats['rejeitados_hoje'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total no Mês</h6>
                            <h3 class="mb-0 text-primary"><?= $stats['total_mes'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RDOs Pendentes -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-hourglass-split"></i> RDOs Aguardando Aprovação 
                    <span class="badge bg-warning ms-2"><?= count($rdos_pendentes) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($rdos_pendentes)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle" style="font-size: 4rem; color: #28a745;"></i>
                        <h4 class="mt-3 text-success">Parabéns!</h4>
                        <p class="text-muted">Não há RDOs pendentes de aprovação no momento.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach($rdos_pendentes as $rdo): 
                            $classe_urgencia = '';
                            $texto_urgencia = '';
                            
                            if ($rdo['dias_atraso'] > 3) {
                                $classe_urgencia = 'urgente';
                                $texto_urgencia = 'URGENTE';
                            } elseif ($rdo['dias_atraso'] > 1) {
                                $classe_urgencia = 'alerta';
                                $texto_urgencia = 'ATENÇÃO';
                            }
                        ?>
                        <div class="col-12">
                            <div class="card rdo-card <?= $classe_urgencia ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <h5 class="mb-1">
                                                <a href="rdo_visualizar.php?id=<?= $rdo['id'] ?>" class="text-decoration-none">
                                                    RDO #<?= $rdo['id'] ?> - <?= htmlspecialchars($rdo['obra_nome']) ?>
                                                </a>
                                            </h5>
                                            <p class="text-muted mb-1">
                                                <i class="bi bi-building"></i> <?= $rdo['obra_codigo'] ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?= htmlspecialchars($rdo['criador_nome']) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <p class="mb-1">
                                                <strong>Data do RDO:</strong><br>
                                                <?= formatarDataBR($rdo['data_rdo']) ?>
                                            </p>
                                            <p class="mb-0">
                                                <strong>Enviado em:</strong><br>
                                                <?= date('d/m/Y H:i', strtotime($rdo['created_at'])) ?>
                                            </p>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <?php if ($texto_urgencia): ?>
                                                <span class="badge bg-<?= $rdo['dias_atraso'] > 3 ? 'danger' : 'warning' ?> badge-dias">
                                                    <?= $texto_urgencia ?>
                                                </span><br>
                                            <?php endif; ?>
                                            <span class="badge bg-secondary badge-dias mt-1">
                                                <?= $rdo['dias_atraso'] ?> dia(s)
                                            </span>
                                        </div>
                                        
                                        <div class="col-md-2 text-end">
                                            <button class="btn btn-outline-primary btn-sm mb-1 w-100" 
                                                    onclick="toggleAprovacaoRapida(<?= $rdo['id'] ?>)">
                                                <i class="bi bi-lightning"></i> Aprovação Rápida
                                            </button>
                                            <a href="rdo_visualizar.php?id=<?= $rdo['id'] ?>" 
                                               class="btn btn-outline-secondary btn-sm w-100">
                                                <i class="bi bi-eye"></i> Ver Completo
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Aprovação Rápida -->
                                    <div class="aprovacao-rapida" id="aprovacao-<?= $rdo['id'] ?>">
                                        <form method="POST" action="rdo_aprovar.php" class="row g-2">
                                            <input type="hidden" name="rdo_id" value="<?= $rdo['id'] ?>">
                                            <div class="col-md-8">
                                                <textarea name="comentarios" class="form-control" 
                                                          placeholder="Comentários (opcional)" rows="2"></textarea>
                                            </div>
                                            <div class="col-md-4 d-flex flex-column gap-2">
                                                <button type="submit" name="acao" value="aprovar" 
                                                        class="btn btn-success">
                                                    <i class="bi bi-check-lg"></i> Aprovar
                                                </button>
                                                <button type="submit" name="acao" value="rejeitar" 
                                                        class="btn btn-danger">
                                                    <i class="bi bi-x-lg"></i> Rejeitar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RDOs Processados Recentemente -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Processados Recentemente</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>RDO</th>
                                <th>Obra</th>
                                <th>Data RDO</th>
                                <th>Criado por</th>
                                <th>Aprovado/Rejeitado por</th>
                                <th>Data Aprovação</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rdos_processados as $rdo): ?>
                            <tr>
                                <td><strong>#<?= $rdo['id'] ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($rdo['obra_nome']) ?><br>
                                    <small class="text-muted"><?= $rdo['obra_codigo'] ?></small>
                                </td>
                                <td><?= formatarDataBR($rdo['data_rdo']) ?></td>
                                <td><?= htmlspecialchars($rdo['criador_nome']) ?></td>
                                <td><?= htmlspecialchars($rdo['aprovador_nome']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($rdo['data_aprovacao'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $rdo['status'] == 'aprovado' ? 'success' : 'danger' ?>">
                                        <?= $rdo['status'] == 'aprovado' ? 'Aprovado' : 'Rejeitado' ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAprovacaoRapida(id) {
            const elemento = document.getElementById('aprovacao-' + id);
            if (elemento.style.display === 'block') {
                elemento.style.display = 'none';
            } else {
                // Fechar outros abertos
                document.querySelectorAll('.aprovacao-rapida').forEach(el => {
                    el.style.display = 'none';
                });
                elemento.style.display = 'block';
            }
        }

        // Confirmar aprovação/rejeição rápida
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const acao = document.activeElement.value;
                const mensagem = acao === 'aprovar' 
                    ? 'Confirma a APROVAÇÃO deste RDO?' 
                    : 'Confirma a REJEIÇÃO deste RDO?';
                
                if (!confirm(mensagem)) {
                    e.preventDefault();
                }
            });
        });

        // Auto-refresh a cada 2 minutos para ver novos RDOs
        setTimeout(function() {
            location.reload();
        }, 120000);
    </script>
</body>
</html>