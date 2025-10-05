<?php
require_once 'config.php';
verificarLogin();

// Apenas admin pode gerenciar usuários
if ($_SESSION['usuario_tipo'] != 'admin') {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Apenas administradores podem gerenciar usuários!'
    ];
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM rdos WHERE criado_por = u.id) as total_rdos,
        (SELECT MAX(created_at) FROM rdos WHERE criado_por = u.id) as ultimo_rdo
        FROM usuarios u
        WHERE 1=1";

$params = [];

if ($filtro_tipo) {
    $sql .= " AND u.tipo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_status !== '') {
    $sql .= " AND u.ativo = ?";
    $params[] = $filtro_status;
}

$sql .= " ORDER BY u.nome";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Estatísticas
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'ativos' => $db->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1")->fetchColumn(),
    'inativos' => $db->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 0")->fetchColumn(),
    'admins' => $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin' AND ativo = 1")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - <?= SITE_NAME ?></title>
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
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .tipo-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .btn-novo-usuario {
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
                        <a class="nav-link" href="obras.php">
                            <i class="bi bi-building"></i> Obras
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="usuarios.php">
                            <i class="bi bi-people"></i> Usuários
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
            <h2><i class="bi bi-people"></i> Gerenciar Usuários</h2>
            <a href="usuario_criar.php" class="btn btn-novo-usuario text-white btn-lg">
                <i class="bi bi-person-plus"></i> Novo Usuário
            </a>
        </div>

        <!-- Estatísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total</h6>
                            <h3 class="mb-0"><?= $stats['total'] ?></h3>
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
                            <h6 class="text-muted mb-1">Ativos</h6>
                            <h3 class="mb-0 text-success"><?= $stats['ativos'] ?></h3>
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
                            <h6 class="text-muted mb-1">Inativos</h6>
                            <h3 class="mb-0 text-danger"><?= $stats['inativos'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Administradores</h6>
                            <h3 class="mb-0 text-warning"><?= $stats['admins'] ?></h3>
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
                        <label class="form-label">Filtrar por Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos os tipos</option>
                            <option value="admin" <?= $filtro_tipo == 'admin' ? 'selected' : '' ?>>Administrador</option>
                            <option value="engenheiro" <?= $filtro_tipo == 'engenheiro' ? 'selected' : '' ?>>Engenheiro</option>
                            <option value="fiscal" <?= $filtro_tipo == 'fiscal' ? 'selected' : '' ?>>Fiscal</option>
                            <option value="operacional" <?= $filtro_tipo == 'operacional' ? 'selected' : '' ?>>Operacional</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?= $filtro_status === '1' ? 'selected' : '' ?>>Ativos</option>
                            <option value="0" <?= $filtro_status === '0' ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="usuarios.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Usuários -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="80">Avatar</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>RDOs</th>
                                <th>Último Acesso</th>
                                <th>Status</th>
                                <th width="200">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3">Nenhum usuário encontrado</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($usuarios as $usuario): 
                                    $iniciais = strtoupper(substr($usuario['nome'], 0, 2));
                                    
                                    $tipo_badges = [
                                        'admin' => ['danger', 'Admin'],
                                        'engenheiro' => ['primary', 'Engenheiro'],
                                        'fiscal' => ['info', 'Fiscal'],
                                        'operacional' => ['secondary', 'Operacional']
                                    ];
                                    $badge = $tipo_badges[$usuario['tipo']];
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-avatar"><?= $iniciais ?></div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($usuario['nome']) ?></strong>
                                        <?php if ($usuario['id'] == $_SESSION['usuario_id']): ?>
                                        <span class="badge bg-success ms-2">Você</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                    <td>
                                        <span class="tipo-badge bg-<?= $badge[0] ?> text-white">
                                            <?= $badge[1] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['total_rdos'] > 0): ?>
                                            <span class="badge bg-primary"><?= $usuario['total_rdos'] ?> RDOs</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['ultimo_rdo']): ?>
                                            <?= date('d/m/Y', strtotime($usuario['ultimo_rdo'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="usuario_editar.php?id=<?= $usuario['id'] ?>" 
                                               class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="usuario_senha.php?id=<?= $usuario['id'] ?>" 
                                               class="btn btn-outline-warning" title="Trocar Senha">
                                                <i class="bi bi-key"></i>
                                            </a>
                                            <?php if ($usuario['ativo']): ?>
                                                <button onclick="toggleStatus(<?= $usuario['id'] ?>, 0)" 
                                                        class="btn btn-outline-danger" title="Desativar">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <button onclick="toggleStatus(<?= $usuario['id'] ?>, 1)" 
                                                        class="btn btn-outline-success" title="Ativar">
                                                    <i class="bi bi-check-circle"></i>
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
        function toggleStatus(id, status) {
            const acao = status == 1 ? 'ativar' : 'desativar';
            if (confirm(`Tem certeza que deseja ${acao} este usuário?`)) {
                window.location.href = `usuario_toggle.php?id=${id}&status=${status}`;
            }
        }
    </script>
</body>
</html>