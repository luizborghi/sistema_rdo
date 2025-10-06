<?php
require_once 'config.php';
verificarLogin();

$rdo_id = $_GET['id'] ?? null;

if (!$rdo_id) {
    header('Location: rdos.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Buscar RDO completo
$stmt = $db->prepare("
    SELECT r.*, o.nome as obra_nome, o.codigo as obra_codigo, o.endereco as obra_endereco,
           u.nome as criador_nome, u.email as criador_email,
           ap.nome as aprovador_nome, ap.email as aprovador_email
    FROM rdos r
    JOIN obras o ON r.obra_id = o.id
    JOIN usuarios u ON r.criado_por = u.id
    LEFT JOIN usuarios ap ON r.aprovado_por = ap.id
    WHERE r.id = ?
");
$stmt->execute([$rdo_id]);
$rdo = $stmt->fetch();

if (!$rdo) {
    header('Location: rdos.php');
    exit;
}

// Buscar mão de obra
$mao_obra = $db->prepare("SELECT * FROM rdo_mao_obra WHERE rdo_id = ? ORDER BY id");
$mao_obra->execute([$rdo_id]);
$mao_obra_items = $mao_obra->fetchAll();

// Buscar equipamentos
$equipamentos = $db->prepare("SELECT * FROM rdo_equipamentos WHERE rdo_id = ? ORDER BY id");
$equipamentos->execute([$rdo_id]);
$equipamentos_items = $equipamentos->fetchAll();

// Buscar materiais
$materiais = $db->prepare("SELECT * FROM rdo_materiais WHERE rdo_id = ? ORDER BY id");
$materiais->execute([$rdo_id]);
$materiais_items = $materiais->fetchAll();

// Buscar fotos
$fotos = $db->prepare("SELECT * FROM rdo_anexos WHERE rdo_id = ? AND tipo = 'foto' ORDER BY id");
$fotos->execute([$rdo_id]);
$fotos_items = $fotos->fetchAll();

// Buscar histórico
$historico = $db->prepare("
    SELECT h.*, u.nome as usuario_nome 
    FROM rdo_historico h
    JOIN usuarios u ON h.usuario_id = u.id
    WHERE h.rdo_id = ?
    ORDER BY h.created_at DESC
");
$historico->execute([$rdo_id]);
$historico_items = $historico->fetchAll();

// Verificar se usuário pode aprovar ESTE RDO específico
$pode_aprovar = false;

if ($_SESSION['usuario_tipo'] == 'admin' || $_SESSION['usuario_tipo'] == 'engenheiro') {
    $pode_aprovar = true; // Admin e Engenheiro podem aprovar qualquer RDO
} elseif ($_SESSION['usuario_tipo'] == 'fiscal' && $rdo['obra_fiscal_id'] == $_SESSION['usuario_id']) {
    $pode_aprovar = true; // Fiscal só pode aprovar RDOs das suas obras
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RDO #<?= $rdo['id'] ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .rdo-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
        }
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .section-title {
            border-left: 4px solid #667eea;
            padding-left: 15px;
            margin-bottom: 20px;
            font-weight: 600;
            color: #667eea;
        }
        .table-items {
            background: #f8f9fa;
            border-radius: 8px;
        }
        .foto-thumb {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .foto-thumb:hover {
            transform: scale(1.05);
        }
        .status-badge-large {
            font-size: 1.1rem;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
        }
        .aprovacao-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -18px;
            top: 17px;
            width: 2px;
            height: calc(100% - 12px);
            background: #e0e0e0;
        }
        .timeline-item:last-child::after {
            display: none;
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
            <h2><i class="bi bi-file-earmark-text"></i> Detalhes do RDO</h2>
            <a href="rdos.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Header do RDO -->
        <div class="card shadow-lg mb-4">
            <div class="rdo-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-2">RDO #<?= $rdo['id'] ?> - <?= htmlspecialchars($rdo['obra_nome']) ?></h3>
                        <p class="mb-1"><i class="bi bi-building"></i> <?= $rdo['obra_codigo'] ?></p>
                        <p class="mb-0"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($rdo['obra_endereco']) ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php
                        $badges = [
                            'rascunho' => ['secondary', 'Rascunho'],
                            'enviado' => ['warning', 'Aguardando Aprovação'],
                            'aprovado' => ['success', 'Aprovado'],
                            'rejeitado' => ['danger', 'Rejeitado']
                        ];
                        $badge = $badges[$rdo['status']];
                        ?>
                        <span class="status-badge-large bg-<?= $badge[0] ?> d-inline-block">
                            <?= $badge[1] ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Informações Básicas -->
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <h6 class="text-muted">Data do RDO</h6>
                        <p class="fs-5 mb-0"><i class="bi bi-calendar3"></i> <?= formatarDataBR($rdo['data_rdo']) ?></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <h6 class="text-muted">Período</h6>
                        <p class="fs-5 mb-0"><i class="bi bi-clock"></i> <?= ucfirst(str_replace('_', ' ', $rdo['periodo'])) ?></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <h6 class="text-muted">Clima</h6>
                        <p class="fs-5 mb-0">
                            <?php
                            $climas = [
                                'sol' => '☀️ Sol',
                                'parcialmente_nublado' => '⛅ Parcialmente Nublado',
                                'nublado' => '☁️ Nublado',
                                'chuva' => '🌧️ Chuva'
                            ];
                            echo $climas[$rdo['clima']] ?? $rdo['clima'];
                            ?>
                        </p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <h6 class="text-muted">Temperatura</h6>
                        <p class="fs-5 mb-0">
                            <i class="bi bi-thermometer-half"></i> 
                            <?= $rdo['temperatura'] ? $rdo['temperatura'] . '°C' : 'Não informada' ?>
                        </p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Criado por</h6>
                        <p><i class="bi bi-person"></i> <?= htmlspecialchars($rdo['criador_nome']) ?> (<?= $rdo['criador_email'] ?>)</p>
                        <p class="text-muted small">Em <?= date('d/m/Y H:i', strtotime($rdo['created_at'])) ?></p>
                    </div>
                    <?php if ($rdo['status'] == 'aprovado' || $rdo['status'] == 'rejeitado'): ?>
                    <div class="col-md-6">
                        <h6 class="text-muted"><?= $rdo['status'] == 'aprovado' ? 'Aprovado' : 'Rejeitado' ?> por</h6>
                        <p><i class="bi bi-person-check"></i> <?= htmlspecialchars($rdo['aprovador_nome']) ?> (<?= $rdo['aprovador_email'] ?>)</p>
                        <p class="text-muted small">Em <?= date('d/m/Y H:i', strtotime($rdo['data_aprovacao'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sistema de Aprovação -->
        <?php if ($rdo['status'] == 'enviado' && $pode_aprovar): ?>
        <div class="aprovacao-box">
            <h4 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Este RDO aguarda aprovação</h4>
            <p class="mb-3">Revise todas as informações antes de aprovar ou rejeitar este RDO.</p>
            
            <form method="POST" action="rdo_aprovar.php" id="formAprovacao">
                <input type="hidden" name="rdo_id" value="<?= $rdo_id ?>">
                
                <div class="mb-3">
                    <label class="form-label">Comentários (opcional)</label>
                    <textarea name="comentarios" class="form-control" rows="3" 
                              placeholder="Adicione comentários sobre a aprovação ou rejeição..."></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="acao" value="aprovar" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle"></i> Aprovar RDO
                    </button>
                    <button type="submit" name="acao" value="rejeitar" class="btn btn-danger btn-lg">
                        <i class="bi bi-x-circle"></i> Rejeitar RDO
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Coluna Principal -->
            <div class="col-md-8">
                <!-- Serviços Executados -->
                <div class="info-card">
                    <h5 class="section-title"><i class="bi bi-list-check"></i> Serviços Executados</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($rdo['servicos_executados'] ?: 'Não informado')) ?>
                    </div>
                </div>

                <!-- Observações -->
                <?php if ($rdo['observacoes']): ?>
                <div class="info-card">
                    <h5 class="section-title"><i class="bi bi-chat-left-text"></i> Observações</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($rdo['observacoes'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Mão de Obra -->
                <?php if (!empty($mao_obra_items)): ?>
                <div class="info-card">
                    <h5 class="section-title"><i class="bi bi-people"></i> Mão de Obra</h5>
                    <div class="table-responsive">
                        <table class="table table-items">
                            <thead>
                                <tr>
                                    <th>Função</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-center">Horas</th>
                                    <th class="text-end">Total Horas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_pessoas = 0;
                                $total_horas = 0;
                                foreach($mao_obra_items as $item): 
                                    $total_pessoas += $item['quantidade'];
                                    $subtotal = $item['quantidade'] * ($item['horas_trabalhadas'] ?? 0);
                                    $total_horas += $subtotal;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['funcao']) ?></td>
                                    <td class="text-center"><?= $item['quantidade'] ?></td>
                                    <td class="text-center"><?= $item['horas_trabalhadas'] ?? '-' ?></td>
                                    <td class="text-end"><strong><?= number_format($subtotal, 1) ?>h</strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-center"><?= $total_pessoas ?> pessoas</td>
                                    <td></td>
                                    <td class="text-end"><?= number_format($total_horas, 1) ?>h</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Equipamentos -->
                <?php if (!empty($equipamentos_items)): ?>
                <div class="info-card">
                    <h5 class="section-title"><i class="bi bi-tools"></i> Equipamentos Utilizados</h5>
                    <div class="table-responsive">
                        <table class="table table-items">
                            <thead>
                                <tr>
                                    <th>Equipamento</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-end">Horas Utilizadas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($equipamentos_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['equipamento']) ?></td>
                                    <td class="text-center"><?= $item['quantidade'] ?></td>
                                    <td class="text-end"><?= $item['horas_utilizadas'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Materiais -->
                <?php if (!empty($materiais_items)): ?>
                <div class="info-card">
                    <h5 class="section-title"><i class="bi bi-box-seam"></i> Materiais Utilizados</h5>
                    <div class="table-responsive">
                        <table class="table table-items">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-center">Quantidade</th>
                                    <th>Unidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($materiais_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['material']) ?></td>
                                    <td class="text-center"><?= number_format($item['quantidade'], 2, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($item['unidade']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Fotos -->
                <?php if (!empty($fotos_items)): ?>
                <div class="info-card">
                    <h5 class="section-title"><i class="bi bi-camera"></i> Fotos do Dia</h5>
                    <div class="row g-3">
                        <?php foreach($fotos_items as $foto): ?>
                        <div class="col-md-4">
                            <img src="<?= htmlspecialchars($foto['caminho_arquivo']) ?>" 
                                 class="foto-thumb img-fluid" 
                                 alt="<?= htmlspecialchars($foto['nome_arquivo']) ?>"
                                 onclick="abrirFoto('<?= htmlspecialchars($foto['caminho_arquivo']) ?>')">
                            <p class="small text-muted mt-1"><?= htmlspecialchars($foto['nome_arquivo']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Coluna Lateral -->
            <div class="col-md-4">
                <!-- Ações Rápidas -->
                <div class="info-card">
                    <h5 class="section-title"><i class="bi bi-lightning"></i> Ações Rápidas</h5>
                    <div class="d-grid gap-2">
                        <a href="rdo_pdf.php?id=<?= $rdo_id ?>" class="btn btn-outline-danger" target="_blank">
                            <i class="bi bi-file-pdf"></i> Gerar PDF
                        </a>
                        <?php if ($rdo['status'] == 'rascunho'): ?>
                        <a href="rdo_criar.php?id=<?= $rdo_id ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i> Editar RDO
                        </a>
                        <?php endif; ?>
                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                    </div>
                </div>

                <!-- Histórico -->
                <?php if (!empty($historico_items)): ?>
                <div class="info-card">
                    <h5 class="section-title"><i class="bi bi-clock-history"></i> Histórico</h5>
                    <div class="timeline">
                        <?php foreach($historico_items as $hist): ?>
                        <div class="timeline-item">
                            <p class="mb-1"><strong><?= htmlspecialchars($hist['acao']) ?></strong></p>
                            <p class="text-muted small mb-1">
                                <?= htmlspecialchars($hist['usuario_nome']) ?>
                            </p>
                            <p class="text-muted small">
                                <?= date('d/m/Y H:i', strtotime($hist['created_at'])) ?>
                            </p>
                            <?php if ($hist['detalhes']): ?>
                            <p class="small text-secondary"><?= htmlspecialchars($hist['detalhes']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para visualizar fotos -->
    <div class="modal fade" id="modalFoto" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <img src="" id="fotoModal" class="img-fluid w-100">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function abrirFoto(url) {
            document.getElementById('fotoModal').src = url;
            new bootstrap.Modal(document.getElementById('modalFoto')).show();
        }

        // Confirmar aprovação/rejeição
        document.getElementById('formAprovacao')?.addEventListener('submit', function(e) {
            const acao = document.activeElement.value;
            const mensagem = acao === 'aprovar' 
                ? 'Tem certeza que deseja APROVAR este RDO?' 
                : 'Tem certeza que deseja REJEITAR este RDO?';
            
            if (!confirm(mensagem)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
