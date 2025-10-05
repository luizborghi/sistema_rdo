<?php
require_once 'config.php';
verificarLogin();

$db = Database::getInstance()->getConnection();

// Filtros
$filtro_obra = $_GET['obra'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

// Construir query com filtros
$sql = "SELECT r.*, o.nome as obra_nome, o.codigo as obra_codigo, u.nome as criador_nome 
        FROM rdos r
        JOIN obras o ON r.obra_id = o.id
        JOIN usuarios u ON r.criado_por = u.id
        WHERE 1=1";

$params = [];

if ($filtro_obra) {
    $sql .= " AND r.obra_id = ?";
    $params[] = $filtro_obra;
}

if ($filtro_status) {
    $sql .= " AND r.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_data_inicio) {
    $sql .= " AND r.data_rdo >= ?";
    $params[] = $filtro_data_inicio;
}

if ($filtro_data_fim) {
    $sql .= " AND r.data_rdo <= ?";
    $params[] = $filtro_data_fim;
}

$sql .= " ORDER BY r.data_rdo DESC, r.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rdos = $stmt->fetchAll();

// Buscar obras para filtro
$obras = $db->query("SELECT id, codigo, nome FROM obras ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RDOs - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .btn-novo-rdo {
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
                        <a class="nav-link active" href="rdos.php">
                            <i class="bi bi-file-text"></i> RDOs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="obras.php">
                            <i class="bi bi-building"></i> Obras
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
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['mensagem']['texto'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-text"></i> Gerenciar RDOs</h2>
            <a href="rdo_criar.php" class="btn btn-novo-rdo text-white btn-lg">
                <i class="bi bi-plus-circle"></i> Novo RDO
            </a>
        </div>

        <!-- Filtros -->
        <div class="filter-card p-4 mb-4">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Obra</label>
                        <select name="obra" class="form-select">
                            <option value="">Todas as obras</option>
                            <?php foreach($obras as $obra): ?>
                            <option value="<?= $obra['id'] ?>" <?= $filtro_obra == $obra['id'] ? 'selected' : '' ?>>
                                <?= $obra['codigo'] ?> - <?= htmlspecialchars($obra['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="rascunho" <?= $filtro_status == 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                            <option value="enviado" <?= $filtro_status == 'enviado' ? 'selected' : '' ?>>Enviado</option>
                            <option value="aprovado" <?= $filtro_status == 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                            <option value="rejeitado" <?= $filtro_status == 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Data Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?= $filtro_data_inicio ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Data Fim</label>
                        <input type="date" name="data_fim" class="form-control" value="<?= $filtro_data_fim ?>">
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="rdos.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabela de RDOs -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Obra</th>
                                <th>Período</th>
                                <th>Clima</th>
                                <th>Criado por</th>
                                <th>Status</th>
                                <th width="150">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rdos)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3">Nenhum RDO encontrado</p>
                                    <a href="rdo_criar.php" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Criar Primeiro RDO
                                    </a>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($rdos as $rdo): ?>
                                <tr onclick="window.location='rdo_visualizar.php?id=<?= $rdo['id'] ?>'">
                                    <td>
                                        <strong><?= formatarDataBR($rdo['data_rdo']) ?></strong><br>
                                        <small class="text-muted"><?= date('H:i', strtotime($rdo['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($rdo['obra_nome']) ?></strong><br>
                                        <small class="text-muted"><?= $rdo['obra_codigo'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst(str_replace('_', ' ', $rdo['periodo'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $climas = [
                                            'sol' => '☀️ Sol',
                                            'parcialmente_nublado' => '⛅ Parc. Nublado',
                                            'nublado' => '☁️ Nublado',
                                            'chuva' => '🌧️ Chuva'
                                        ];
                                        echo $climas[$rdo['clima']] ?? $rdo['clima'];
                                        ?>
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
                                        <span class="status-badge bg-<?= $badge[0] ?>">
                                            <?= $badge[1] ?>
                                        </span>
                                    </td>
                                    <td onclick="event.stopPropagation()">
                                        <div class="btn-group btn-group-sm">
                                            <a href="rdo_visualizar.php?id=<?= $rdo['id'] ?>" 
                                               class="btn btn-outline-primary" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($rdo['status'] == 'rascunho' || $rdo['criado_por'] == $_SESSION['usuario_id']): ?>
                                            <a href="rdo_criar.php?id=<?= $rdo['id'] ?>" 
                                               class="btn btn-outline-secondary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="rdo_pdf.php?id=<?= $rdo['id'] ?>" 
                                               class="btn btn-outline-info" title="PDF" target="_blank">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                            <?php if ($rdo['status'] == 'rascunho'): ?>
                                            <button onclick="confirmarExclusao(<?= $rdo['id'] ?>)" 
                                                    class="btn btn-outline-danger" title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir este RDO? Esta ação não pode ser desfeita.')) {
                window.location.href = 'rdo_excluir.php?id=' + id;
            }
        }
    </script>
</body>
</html>