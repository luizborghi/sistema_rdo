<?php
require_once 'config.php';
verificarLogin();

$db = Database::getInstance()->getConnection();

// Buscar obras para select
$obras = $db->query("SELECT id, codigo, nome FROM obras WHERE status = 'em_andamento' ORDER BY nome")->fetchAll();

$rdo_id = $_GET['id'] ?? null;
$obra_pre_selecionada = $_GET['obra_id'] ?? null; // Nova linha
$rdo = null;

// Se está editando, buscar dados do RDO
if ($rdo_id) {
    $stmt = $db->prepare("SELECT * FROM rdos WHERE id = ?");
    $stmt->execute([$rdo_id]);
    $rdo = $stmt->fetch();
    
    if (!$rdo) {
        header('Location: rdos.php');
        exit;
    }
    
    // Buscar dados relacionados
    $mao_obra = $db->prepare("SELECT * FROM rdo_mao_obra WHERE rdo_id = ?");
    $mao_obra->execute([$rdo_id]);
    $mao_obra_items = $mao_obra->fetchAll();
    
    $equipamentos = $db->prepare("SELECT * FROM rdo_equipamentos WHERE rdo_id = ?");
    $equipamentos->execute([$rdo_id]);
    $equipamentos_items = $equipamentos->fetchAll();
    
    $materiais = $db->prepare("SELECT * FROM rdo_materiais WHERE rdo_id = ?");
    $materiais->execute([$rdo_id]);
    $materiais_items = $materiais->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $rdo ? 'Editar' : 'Novo' ?> RDO - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin-top: 20px;
        }
        .section-content {
            border: 2px solid #667eea;
            border-top: none;
            border-radius: 0 0 10px 10px;
            padding: 20px;
            background: white;
        }
        .item-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        .btn-add-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-remove {
            background: #dc3545;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
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

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-plus"></i> <?= $rdo ? 'Editar' : 'Novo' ?> RDO</h2>
            <a href="rdos.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <form id="formRDO" method="POST" action="rdo_salvar.php" enctype="multipart/form-data">
            <?php if ($rdo): ?>
            <input type="hidden" name="rdo_id" value="<?= $rdo['id'] ?>">
            <?php endif; ?>
            <!-- Informações Básicas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informações Básicas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Obra *</label>
                            <select name="obra_id" class="form-select" required>
                                <option value="">Selecione a obra...</option>
                                <?php foreach($obras as $obra): ?>
                                <option value="<?= $obra['id'] ?>" 
                                    <?= ($rdo && $rdo['obra_id'] == $obra['id']) ? 'selected' : '' ?>>
                                    <?= $obra['codigo'] ?> - <?= htmlspecialchars($obra['nome']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Data *</label>
                            <input type="date" name="data_rdo" class="form-control" 
                                   value="<?= $rdo ? $rdo['data_rdo'] : date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Período *</label>
                            <select name="periodo" class="form-select" required>
                                <option value="dia_todo" <?= ($rdo && $rdo['periodo'] == 'dia_todo') ? 'selected' : '' ?>>Dia Todo</option>
                                <option value="manha" <?= ($rdo && $rdo['periodo'] == 'manha') ? 'selected' : '' ?>>Manhã</option>
                                <option value="tarde" <?= ($rdo && $rdo['periodo'] == 'tarde') ? 'selected' : '' ?>>Tarde</option>
                                <option value="noite" <?= ($rdo && $rdo['periodo'] == 'noite') ? 'selected' : '' ?>>Noite</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Clima</label>
                            <select name="clima" class="form-select">
                                <option value="sol" <?= ($rdo && $rdo['clima'] == 'sol') ? 'selected' : '' ?>>☀️ Sol</option>
                                <option value="parcialmente_nublado" <?= ($rdo && $rdo['clima'] == 'parcialmente_nublado') ? 'selected' : '' ?>>⛅ Parcialmente Nublado</option>
                                <option value="nublado" <?= ($rdo && $rdo['clima'] == 'nublado') ? 'selected' : '' ?>>☁️ Nublado</option>
                                <option value="chuva" <?= ($rdo && $rdo['clima'] == 'chuva') ? 'selected' : '' ?>>🌧️ Chuva</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Temperatura (°C)</label>
                            <input type="number" name="temperatura" class="form-control" 
                                   step="0.1" value="<?= $rdo['temperatura'] ?? '' ?>" placeholder="25.5">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Serviços Executados</label>
                        <textarea name="servicos_executados" class="form-control" rows="4" 
                                  placeholder="Descreva os serviços realizados no dia..."><?= $rdo['servicos_executados'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="3" 
                                  placeholder="Observações gerais..."><?= $rdo['observacoes'] ?? '' ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Mão de Obra -->
            <div class="section-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Mão de Obra</h5>
            </div>
            <div class="section-content">
                <div id="mao-obra-container">
                    <?php if($rdo && !empty($mao_obra_items)): ?>
                        <?php foreach($mao_obra_items as $index => $item): ?>
                        <div class="item-row">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Função</label>
                                    <input type="text" name="mao_obra[<?= $index ?>][funcao]" 
                                           class="form-control" value="<?= htmlspecialchars($item['funcao']) ?>" 
                                           placeholder="Ex: Pedreiro">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantidade</label>
                                    <input type="number" name="mao_obra[<?= $index ?>][quantidade]" 
                                           class="form-control" value="<?= $item['quantidade'] ?>" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Horas Trabalhadas</label>
                                    <input type="number" name="mao_obra[<?= $index ?>][horas]" 
                                           class="form-control" value="<?= $item['horas_trabalhadas'] ?>" 
                                           step="0.5" placeholder="8.0">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-remove btn-sm w-100" 
                                            onclick="removeItem(this)">
                                        <i class="bi bi-trash"></i> Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-add-item text-white mt-2" onclick="addMaoObra()">
                    <i class="bi bi-plus-circle"></i> Adicionar Mão de Obra
                </button>
            </div>

            <!-- Equipamentos -->
            <div class="section-header">
                <h5 class="mb-0"><i class="bi bi-tools"></i> Equipamentos</h5>
            </div>
            <div class="section-content">
                <div id="equipamentos-container">
                    <?php if($rdo && !empty($equipamentos_items)): ?>
                        <?php foreach($equipamentos_items as $index => $item): ?>
                        <div class="item-row">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Equipamento</label>
                                    <input type="text" name="equipamentos[<?= $index ?>][nome]" 
                                           class="form-control" value="<?= htmlspecialchars($item['equipamento']) ?>" 
                                           placeholder="Ex: Betoneira">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantidade</label>
                                    <input type="number" name="equipamentos[<?= $index ?>][quantidade]" 
                                           class="form-control" value="<?= $item['quantidade'] ?>" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Horas Utilizadas</label>
                                    <input type="number" name="equipamentos[<?= $index ?>][horas]" 
                                           class="form-control" value="<?= $item['horas_utilizadas'] ?>" 
                                           step="0.5" placeholder="4.0">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-remove btn-sm w-100" 
                                            onclick="removeItem(this)">
                                        <i class="bi bi-trash"></i> Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-add-item text-white mt-2" onclick="addEquipamento()">
                    <i class="bi bi-plus-circle"></i> Adicionar Equipamento
                </button>
            </div>

            <!-- Materiais -->
            <div class="section-header">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Materiais</h5>
            </div>
            <div class="section-content">
                <div id="materiais-container">
                    <?php if($rdo && !empty($materiais_items)): ?>
                        <?php foreach($materiais_items as $index => $item): ?>
                        <div class="item-row">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Material</label>
                                    <input type="text" name="materiais[<?= $index ?>][nome]" 
                                           class="form-control" value="<?= htmlspecialchars($item['material']) ?>" 
                                           placeholder="Ex: Cimento">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantidade</label>
                                    <input type="number" name="materiais[<?= $index ?>][quantidade]" 
                                           class="form-control" value="<?= $item['quantidade'] ?>" 
                                           step="0.01" placeholder="50">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Unidade</label>
                                    <input type="text" name="materiais[<?= $index ?>][unidade]" 
                                           class="form-control" value="<?= htmlspecialchars($item['unidade']) ?>" 
                                           placeholder="Ex: Sacos, m³, kg">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-remove btn-sm w-100" 
                                            onclick="removeItem(this)">
                                        <i class="bi bi-trash"></i> Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-add-item text-white mt-2" onclick="addMaterial()">
                    <i class="bi bi-plus-circle"></i> Adicionar Material
                </button>
            </div>

            <!-- Fotos -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-camera"></i> Fotos do Dia</h5>
                </div>
                <div class="card-body">
                    <input type="file" name="fotos[]" class="form-control" multiple accept="image/*">
                    <small class="text-muted">Você pode selecionar múltiplas fotos (máx. 5MB cada)</small>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-flex justify-content-between mt-4 mb-5">
                <a href="rdos.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <div>
                    <button type="submit" name="acao" value="rascunho" class="btn btn-outline-primary btn-lg me-2">
                        <i class="bi bi-save"></i> Salvar como Rascunho
                    </button>
                    <button type="submit" name="acao" value="enviar" class="btn btn-success btn-lg">
                        <i class="bi bi-send"></i> Enviar RDO
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let maoObraIndex = <?= $rdo && !empty($mao_obra_items) ? count($mao_obra_items) : 0 ?>;
        let equipamentoIndex = <?= $rdo && !empty($equipamentos_items) ? count($equipamentos_items) : 0 ?>;
        let materialIndex = <?= $rdo && !empty($materiais_items) ? count($materiais_items) : 0 ?>;

        function addMaoObra() {
            const container = document.getElementById('mao-obra-container');
            const html = `
                <div class="item-row">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Função</label>
                            <input type="text" name="mao_obra[${maoObraIndex}][funcao]" 
                                   class="form-control" placeholder="Ex: Pedreiro">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantidade</label>
                            <input type="number" name="mao_obra[${maoObraIndex}][quantidade]" 
                                   class="form-control" min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Horas Trabalhadas</label>
                            <input type="number" name="mao_obra[${maoObraIndex}][horas]" 
                                   class="form-control" step="0.5" placeholder="8.0">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-remove btn-sm w-100" 
                                    onclick="removeItem(this)">
                                <i class="bi bi-trash"></i> Remover
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            maoObraIndex++;
        }

        function addEquipamento() {
            const container = document.getElementById('equipamentos-container');
            const html = `
                <div class="item-row">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Equipamento</label>
                            <input type="text" name="equipamentos[${equipamentoIndex}][nome]" 
                                   class="form-control" placeholder="Ex: Betoneira">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantidade</label>
                            <input type="number" name="equipamentos[${equipamentoIndex}][quantidade]" 
                                   class="form-control" min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Horas Utilizadas</label>
                            <input type="number" name="equipamentos[${equipamentoIndex}][horas]" 
                                   class="form-control" step="0.5" placeholder="4.0">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-remove btn-sm w-100" 
                                    onclick="removeItem(this)">
                                <i class="bi bi-trash"></i> Remover
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            equipamentoIndex++;
        }

        function addMaterial() {
            const container = document.getElementById('materiais-container');
            const html = `
                <div class="item-row">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Material</label>
                            <input type="text" name="materiais[${materialIndex}][nome]" 
                                   class="form-control" placeholder="Ex: Cimento">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantidade</label>
                            <input type="number" name="materiais[${materialIndex}][quantidade]" 
                                   class="form-control" step="0.01" placeholder="50">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unidade</label>
                            <input type="text" name="materiais[${materialIndex}][unidade]" 
                                   class="form-control" placeholder="Ex: Sacos, m³, kg">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-remove btn-sm w-100" 
                                    onclick="removeItem(this)">
                                <i class="bi bi-trash"></i> Remover
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            materialIndex++;
        }

        function removeItem(button) {
            button.closest('.item-row').remove();
        }

        // Validação antes de enviar
        document.getElementById('formRDO').addEventListener('submit', function(e) {
            const acao = document.activeElement.value;
            if (acao === 'enviar') {
                if (!confirm('Tem certeza que deseja enviar este RDO? Após enviado, ele ficará pendente de aprovação.')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>