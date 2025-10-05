<?php
// Suprimir warnings de preg_match
error_reporting(E_ERROR | E_PARSE);

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
           o.cliente as obra_cliente,
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
    die('RDO não encontrado');
}

// Buscar dados relacionados
$mao_obra = $db->prepare("SELECT * FROM rdo_mao_obra WHERE rdo_id = ? ORDER BY id");
$mao_obra->execute([$rdo_id]);
$mao_obra_items = $mao_obra->fetchAll();

$equipamentos = $db->prepare("SELECT * FROM rdo_equipamentos WHERE rdo_id = ? ORDER BY id");
$equipamentos->execute([$rdo_id]);
$equipamentos_items = $equipamentos->fetchAll();

$materiais = $db->prepare("SELECT * FROM rdo_materiais WHERE rdo_id = ? ORDER BY id");
$materiais->execute([$rdo_id]);
$materiais_items = $materiais->fetchAll();

$fotos = $db->prepare("SELECT * FROM rdo_anexos WHERE rdo_id = ? AND tipo = 'foto' ORDER BY id");
$fotos->execute([$rdo_id]);
$fotos_items = $fotos->fetchAll();

// Configurar para PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RDO #<?= $rdo['id'] ?> - <?= htmlspecialchars($rdo['obra_nome']) ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24pt;
            margin-bottom: 5px;
        }
        
        .header p {
            margin: 3px 0;
            font-size: 11pt;
        }
        
        .section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .section-title {
            background: #f8f9fa;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 12pt;
            border-bottom: 2px solid #667eea;
        }
        
        .section-content {
            padding: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item label {
            display: block;
            font-weight: bold;
            color: #666;
            font-size: 9pt;
            margin-bottom: 3px;
        }
        
        .info-item p {
            font-size: 11pt;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table th {
            background: #f8f9fa;
            padding: 8px;
            text-align: left;
            font-size: 10pt;
            border: 1px solid #ddd;
        }
        
        table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 10pt;
        }
        
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        table tfoot td {
            font-weight: bold;
            background: #e9ecef;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 10pt;
            font-weight: bold;
        }
        
        .status-aprovado { background: #d4edda; color: #155724; }
        .status-enviado { background: #fff3cd; color: #856404; }
        .status-rejeitado { background: #f8d7da; color: #721c24; }
        .status-rascunho { background: #e2e3e5; color: #383d41; }
        
        .text-box {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            margin-top: 10px;
            white-space: pre-wrap;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        .signature-box {
            margin-top: 40px;
            text-align: center;
        }
        
        .signature-line {
            display: inline-block;
            width: 300px;
            border-top: 1px solid #333;
            margin: 0 20px;
            padding-top: 5px;
        }
        
        .btn-print {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14pt;
            margin: 20px 0;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-print:hover {
            background: #5568d3;
        }
        
        .foto-item {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Botões de Ação -->
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()" class="btn-print">🖨️ Imprimir / Salvar PDF</button>
            <a href="rdo_visualizar.php?id=<?= $rdo_id ?>" class="btn-print" style="background: #6c757d;">
                ← Voltar
            </a>
        </div>

        <!-- Header -->
        <div class="header">
            <h1>RELATÓRIO DIÁRIO DE OBRA (RDO)</h1>
            <p><strong>RDO Nº:</strong> <?= str_pad($rdo['id'], 6, '0', STR_PAD_LEFT) ?></p>
            <p><strong>Data:</strong> <?= formatarDataBR($rdo['data_rdo']) ?> | 
               <strong>Período:</strong> <?= ucfirst(str_replace('_', ' ', $rdo['periodo'])) ?></p>
            <?php
            $status_classes = [
                'rascunho' => 'status-rascunho',
                'enviado' => 'status-enviado',
                'aprovado' => 'status-aprovado',
                'rejeitado' => 'status-rejeitado'
            ];
            $status_textos = [
                'rascunho' => 'RASCUNHO',
                'enviado' => 'AGUARDANDO APROVAÇÃO',
                'aprovado' => '✓ APROVADO',
                'rejeitado' => '✗ REJEITADO'
            ];
            ?>
            <span class="status-badge <?= $status_classes[$rdo['status']] ?>">
                <?= $status_textos[$rdo['status']] ?>
            </span>
        </div>

        <!-- Informações da Obra -->
        <div class="section">
            <div class="section-title">📋 INFORMAÇÕES DA OBRA</div>
            <div class="section-content">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Código da Obra</label>
                        <p><?= htmlspecialchars($rdo['obra_codigo']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Nome da Obra</label>
                        <p><?= htmlspecialchars($rdo['obra_nome']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Cliente</label>
                        <p><?= htmlspecialchars($rdo['obra_cliente'] ?: 'Não informado') ?></p>
                    </div>
                    <div class="info-item">
                        <label>Local</label>
                        <p><?= htmlspecialchars($rdo['obra_endereco'] ?: 'Não informado') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Condições Climáticas -->
        <div class="section">
            <div class="section-title">🌤️ CONDIÇÕES CLIMÁTICAS</div>
            <div class="section-content">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Clima</label>
                        <p>
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
                    <div class="info-item">
                        <label>Temperatura</label>
                        <p><?= $rdo['temperatura'] ? $rdo['temperatura'] . '°C' : 'Não informada' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Serviços Executados -->
        <div class="section">
            <div class="section-title">✅ SERVIÇOS EXECUTADOS</div>
            <div class="section-content">
                <div class="text-box">
                    <?= htmlspecialchars($rdo['servicos_executados'] ?: 'Não informado') ?>
                </div>
            </div>
        </div>

        <!-- Mão de Obra -->
        <?php if (!empty($mao_obra_items)): ?>
        <div class="section">
            <div class="section-title">👷 MÃO DE OBRA</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Função</th>
                            <th style="text-align: center;">Quantidade</th>
                            <th style="text-align: center;">Horas Trabalhadas</th>
                            <th style="text-align: right;">Total Horas</th>
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
                            <td style="text-align: center;"><?= $item['quantidade'] ?></td>
                            <td style="text-align: center;"><?= $item['horas_trabalhadas'] ?? '-' ?></td>
                            <td style="text-align: right;"><?= number_format($subtotal, 1) ?>h</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>TOTAL</td>
                            <td style="text-align: center;"><?= $total_pessoas ?> pessoas</td>
                            <td></td>
                            <td style="text-align: right;"><?= number_format($total_horas, 1) ?>h</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Equipamentos -->
        <?php if (!empty($equipamentos_items)): ?>
        <div class="section">
            <div class="section-title">🔧 EQUIPAMENTOS UTILIZADOS</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Equipamento</th>
                            <th style="text-align: center;">Quantidade</th>
                            <th style="text-align: right;">Horas Utilizadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($equipamentos_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['equipamento']) ?></td>
                            <td style="text-align: center;"><?= $item['quantidade'] ?></td>
                            <td style="text-align: right;"><?= $item['horas_utilizadas'] ?? '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Materiais -->
        <?php if (!empty($materiais_items)): ?>
        <div class="section">
            <div class="section-title">📦 MATERIAIS UTILIZADOS</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th style="text-align: center;">Quantidade</th>
                            <th>Unidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($materiais_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['material']) ?></td>
                            <td style="text-align: center;"><?= number_format($item['quantidade'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($item['unidade']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Observações -->
        <?php if ($rdo['observacoes']): ?>
        <div class="section">
            <div class="section-title">📝 OBSERVAÇÕES</div>
            <div class="section-content">
                <div class="text-box">
                    <?= htmlspecialchars($rdo['observacoes']) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Fotos -->
        <?php if (!empty($fotos_items)): ?>
        <div class="section" style="page-break-inside: avoid;">
            <div class="section-title">📸 REGISTRO FOTOGRÁFICO</div>
            <div class="section-content">
                <?php 
                // Organizar fotos em linhas de 2
                $fotos_por_linha = 2;
                $total_fotos = count($fotos_items);
                ?>
                
                <table style="width: 100%; border: none;">
                    <?php for($i = 0; $i < $total_fotos; $i += $fotos_por_linha): ?>
                    <tr>
                        <?php for($j = 0; $j < $fotos_por_linha; $j++): ?>
                            <?php if(isset($fotos_items[$i + $j])): 
                                $foto = $fotos_items[$i + $j];
                                
                                // Limpar o caminho da foto
                                $caminho_foto = $foto['caminho_arquivo'];
                                
                                // Remover avisos de preg_match adicionando @ antes
                                if (@preg_match('/^[a-z]:\\/i', $caminho_foto)) {
                                    if (@preg_match('/uploads[\\/]rdos[\\/](\d+)[\\/](.+)$/i', $caminho_foto, $matches)) {
                                        $caminho_foto = "uploads/rdos/{$matches[1]}/{$matches[2]}";
                                    }
                                }
                                
                                // Garantir barras corretas
                                $caminho_foto = str_replace('\\', '/', $caminho_foto);
                                
                                // Remover qualquer prefixo de sistema
                                $caminho_foto = @preg_replace('/^.*?(uploads\/rdos\/.+)$/i', '$1', $caminho_foto);
                            ?>
                            <td style="width: 50%; padding: 10px; text-align: center; border: none; vertical-align: top;">
                                <div style="border: 2px solid #ddd; padding: 10px; border-radius: 8px; background: #f9f9f9; min-height: 250px;">
                                    <img src="<?= htmlspecialchars($caminho_foto) ?>" 
                                         style="max-width: 100%; height: 180px; object-fit: contain; display: block; margin: 0 auto; border-radius: 5px;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div style="display: none; padding: 60px 20px; background: #f0f0f0; color: #999; border-radius: 5px; text-align: center;">
                                        📷<br>Imagem não disponível
                                    </div>
                                    <p style="margin-top: 10px; margin-bottom: 0; font-size: 9pt; color: #333; font-weight: bold; word-break: break-word;">
                                        <?= htmlspecialchars($foto['nome_arquivo']) ?>
                                    </p>
                                </div>
                            </td>
                            <?php else: ?>
                            <td style="width: 50%; padding: 10px; border: none;"></td>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </tr>
                    <?php endfor; ?>
                </table>
                
                <p style="margin-top: 20px; font-size: 9pt; color: #666; text-align: center;">
                    <em>Total de <?= count($fotos_items) ?> foto(s) registrada(s)</em>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Responsáveis -->
        <div class="section">
            <div class="section-title">👤 RESPONSÁVEIS</div>
            <div class="section-content">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Elaborado por</label>
                        <p><?= htmlspecialchars($rdo['criador_nome']) ?></p>
                        <p style="font-size: 9pt; color: #666;"><?= $rdo['criador_email'] ?></p>
                        <p style="font-size: 9pt; color: #666;">Em <?= date('d/m/Y H:i', strtotime($rdo['created_at'])) ?></p>
                    </div>
                    <?php if ($rdo['status'] == 'aprovado' || $rdo['status'] == 'rejeitado'): ?>
                    <div class="info-item">
                        <label><?= $rdo['status'] == 'aprovado' ? 'Aprovado por' : 'Rejeitado por' ?></label>
                        <p><?= htmlspecialchars($rdo['aprovador_nome']) ?></p>
                        <p style="font-size: 9pt; color: #666;"><?= $rdo['aprovador_email'] ?></p>
                        <p style="font-size: 9pt; color: #666;">Em <?= date('d/m/Y H:i', strtotime($rdo['data_aprovacao'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Assinaturas -->
        <div class="signature-box">
            <p><strong>ASSINATURAS</strong></p>
            <div style="margin-top: 50px;">
                <div class="signature-line">
                    <p><?= htmlspecialchars($rdo['criador_nome']) ?></p>
                    <p style="font-size: 9pt;">Elaborado por</p>
                </div>
                <?php if ($rdo['aprovador_nome']): ?>
                <div class="signature-line">
                    <p><?= htmlspecialchars($rdo['aprovador_nome']) ?></p>
                    <p style="font-size: 9pt;"><?= $rdo['status'] == 'aprovado' ? 'Aprovado por' : 'Rejeitado por' ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Sistema de Gestão de RDO</strong></p>
            <p>Documento gerado em <?= date('d/m/Y H:i:s') ?></p>
            <p>RDO #<?= str_pad($rdo['id'], 6, '0', STR_PAD_LEFT) ?> | <?= htmlspecialchars($rdo['obra_nome']) ?></p>
        </div>
    </div>

    <script>
        // Auto-print ao carregar (opcional - comente se não quiser)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>