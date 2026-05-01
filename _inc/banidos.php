
<?php
// Paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Buscar usuários banidos com paginação
try {
    // Contar total de banidos
    $count_stmt = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE status = 'banido'");
    $count_stmt->execute();
    $total_banidos = $count_stmt->fetchColumn();
    $total_pages = ceil($total_banidos / $per_page);
    
    // Buscar banidos da página atual
    $stmt = $conexao->prepare("SELECT u.id, u.usuario, u.personagem, u.vila, u.ban_motivo, u.ban_data, u.ban_duracao FROM usuarios u WHERE u.status = 'banido' ORDER BY u.ban_data DESC LIMIT ? OFFSET ?");
    $stmt->execute([$per_page, $offset]);
    $banidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $banidos = [];
    $total_banidos = 0;
    $total_pages = 0;
}

// Função para mapear vila para imagem
function getVilaImage($vila) {
    $vilas = [
        'folha' => '1.png',
        'areia' => '4.png',
        'nevoa' => '2.png',
        'pedra' => '3.png',
        'nuvem' => '5.png',
        'som' => '6.png',
        'chuva' => '7.png',
        'akatsuki' => '8.png'
    ];
    return isset($vilas[$vila]) ? $vilas[$vila] : '1.png';
}

// Função para calcular tempo restante do ban
function calcularTempoRestante($ban_data, $ban_duracao) {
    // Verificar se os dados estão presentes
    if (empty($ban_data) || empty($ban_duracao)) {
        return 'Dados incompletos';
    }
    
    // Se for ban de 10 anos ou mais, considerar eterno
    if ($ban_duracao >= 3650) return 'Eterno';
    
    try {
        $data_ban = new DateTime($ban_data);
        $data_fim = clone $data_ban;
        $data_fim->add(new DateInterval('P' . $ban_duracao . 'D'));
        $agora = new DateTime();
        
        if ($agora >= $data_fim) {
            return 'Expirado';
        }
        
        $diff = $data_fim->diff($agora);
        
        if ($diff->days >= 365) {
            $anos = floor($diff->days / 365);
            return $anos . ' ano' . ($anos > 1 ? 's' : '');
        } elseif ($diff->days >= 30) {
            $meses = floor($diff->days / 30);
            return $meses . ' mês' . ($meses > 1 ? 'es' : '');
        } elseif ($diff->days > 0) {
            return $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        } else {
            return 'Menos de 1 hora';
        }
    } catch (Exception $e) {
        return 'Erro no cálculo';
    }
}
?>

<div class="box_top">Contas Banidas</div>
<div class="box_middle">
    <div class="content-container">
        <div class="section-title">
            <img src="_img/NewsImagens/Nuvem.png" alt="Banimentos" class="section-image" />
            <h3>Lista de Ninjas Banidos</h3>
        </div>

    <?php if (empty($banidos)): ?>
        <div class="empty-message">
            <p><strong>Nenhum ninja foi banido ainda!</strong></p>
            <p>A vila está em paz... por enquanto.</p>
        </div>
    <?php else: ?>
        <div class="total-banned">
            <p><strong>Total de ninjas banidos: <?php echo $total_banidos; ?></strong></p>
            <p>Página <?php echo $page; ?> de <?php echo $total_pages; ?></p>
        </div>

        <table class="banned-table">
            <tr class="table-header">
                <th class="table-cell" style="width: 60px;">Avatar</th>
                <th class="table-cell">Nome</th>
                <th class="table-cell" style="width: 50px;">Vila</th>
                <th class="table-cell">Motivo</th>
                <th class="table-cell" style="width: 120px;">Data do Ban</th>
                <th class="table-cell" style="width: 100px;">Tempo</th>
            </tr>

            <?php foreach ($banidos as $banido): ?>
            <tr class="table-row">
                <td class="table-cell" style="text-align: center;">
                    <?php if ($banido['personagem']): ?>
                        <img src="_img/rank/<?php echo htmlspecialchars($banido['personagem']); ?>.jpg" 
                             alt="<?php echo htmlspecialchars($banido['personagem']); ?>" 
                             width="40" height="40" 
                             class="avatar"
                             style="border-radius: 4px; border: 1px solid #ccc;" />
                    <?php else: ?>
                        <img src="_img/personagens/no_avatar.jpg" 
                             alt="Sem avatar" 
                             width="40" height="40" 
                             class="avatar"
                             style="border-radius: 4px; border: 1px solid #ccc;" />
                    <?php endif; ?>
                </td>

                <td class="table-cell username">
                    <strong><?php echo htmlspecialchars($banido['usuario']); ?></strong>
                </td>

                <td class="table-cell" style="text-align: center;">
                    <?php if ($banido['vila']): ?>
                        <img src="_img/rank/<?php echo getVilaImage($banido['vila']); ?>" 
                             alt="<?php echo htmlspecialchars($banido['vila']); ?>" 
                             width="30" height="30" 
                             class="village-icon"
                             style="border-radius: 2px;"
                             title="<?php echo ucfirst($banido['vila']); ?>" />
                    <?php else: ?>
                        <img src="_img/rank/1.png" 
                             alt="Vila desconhecida" 
                             width="30" height="30" 
                             class="village-icon"
                             style="border-radius: 2px;" />
                    <?php endif; ?>
                </td>

                <td class="table-cell ban-reason">
                    <span class="texto-eterno">
                        <?php echo htmlspecialchars($banido['ban_motivo'] ?: 'Motivo não especificado'); ?>
                    </span>
                </td>

                <td class="table-cell ban-date">
                    <span class="data-amarela">
                        <?php 
                        echo $banido['ban_data'] ? date('d/m/Y H:i', strtotime($banido['ban_data'])) : 'Data não registrada';
                        ?>
                    </span>
                </td>

                <td class="table-cell ban-time" style="text-align: center;">
                    <?php 
                    $tempo_restante = calcularTempoRestante($banido['ban_data'], $banido['ban_duracao']);
                    if($tempo_restante == 'Eterno'): ?>
                        <strong class="texto-eterno" style="color: #d32f2f;">
                            <?php echo $tempo_restante; ?>
                        </strong>
                    <?php else: ?>
                        <strong class="texto-eterno" style="color: #d32f2f;">
                            <?php echo $tempo_restante; ?>
                        </strong>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination" style="text-align: center; margin: 20px 0; padding: 10px;">
                <?php if ($page > 1): ?>
                    <a href="?p=banidos&page=1" style="margin: 0 5px; padding: 8px 12px; background: #333; color: white; text-decoration: none; border-radius: 4px;">« Primeira</a>
                    <a href="?p=banidos&page=<?php echo $page - 1; ?>" style="margin: 0 5px; padding: 8px 12px; background: #555; color: white; text-decoration: none; border-radius: 4px;">‹ Anterior</a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++): ?>
                    <?php if ($i == $page): ?>
                        <strong style="margin: 0 5px; padding: 8px 12px; background: #d32f2f; color: white; border-radius: 4px;"><?php echo $i; ?></strong>
                    <?php else: ?>
                        <a href="?p=banidos&page=<?php echo $i; ?>" style="margin: 0 5px; padding: 8px 12px; background: #777; color: white; text-decoration: none; border-radius: 4px;"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?p=banidos&page=<?php echo $page + 1; ?>" style="margin: 0 5px; padding: 8px 12px; background: #555; color: white; text-decoration: none; border-radius: 4px;">Próxima ›</a>
                    <a href="?p=banidos&page=<?php echo $total_pages; ?>" style="margin: 0 5px; padding: 8px 12px; background: #333; color: white; text-decoration: none; border-radius: 4px;">Última »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="warning-message">
            <p><strong>Aviso:</strong> Os ninjas listados aqui violaram as regras do jogo e foram banidos.</p>
            <p>Respeite as regras para manter sua conta segura!</p>
        </div>
    <?php endif; ?>
    </div>
</div>
<div class="box_bottom"></div>

<style>
.texto-eterno {
    text-shadow: 
        -1px -1px 0 #000,
        1px -1px 0 #000,
        -1px 1px 0 #000,
        1px 1px 0 #000,
        -2px 0 0 #000,
        2px 0 0 #000,
        0 -2px 0 #000,
        0 2px 0 #000;
}

.data-amarela {
    color: #FFD700 !important;
    text-shadow: 
        -1px -1px 0 #000,
        1px -1px 0 #000,
        -1px 1px 0 #000,
        1px 1px 0 #000,
        -2px 0 0 #000,
        2px 0 0 #000,
        0 -2px 0 #000,
        0 2px 0 #000;
    font-weight: bold;
}

.banned-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
    background: #333333;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table-header th {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white;
    padding: 12px 8px;
    font-weight: bold;
    text-align: center;
    font-size: 14px;
    border-bottom: 2px solid #1a252f;
}

.table-row {
    border-bottom: 1px solid #e0e0e0;
    transition: background-color 0.2s;
}

.table-row:hover {
    background-color: #444444;
}

.table-row:nth-child(even) {
    background-color: #2a2a2a;
}

.table-cell {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 13px;
    border-right: 1px solid #e8e8e8;
    color: #BBBBBB;
}

.table-cell:last-child {
    border-right: none;
}

.username {
    color: #FFFFFF;
    font-weight: bold;
}

.ban-reason {
    color: #BBBBBB;
    max-width: 200px;
    word-wrap: break-word;
}

.ban-date {
    color: #BBBBBB;
    font-size: 12px;
    text-align: center;
}

.ban-time {
    font-weight: bold;
    color: #d32f2f !important;
}

.avatar {
    object-fit: cover;
    display: block;
    margin: 0 auto;
}

.village-icon {
    object-fit: cover;
    display: block;
    margin: 0 auto;
}

.section-title {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}

.section-image {
    width: 32px;
    height: 32px;
    margin-right: 10px;
}

.content-container {
    padding: 15px;
}

.total-banned {
    background: #e3f2fd;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    border-left: 4px solid #2196f3;
    color: #000000;
    font-weight: bold;
}

.empty-message {
    text-align: center;
    padding: 40px;
    color: #666;
    background: #f9f9f9;
    border-radius: 8px;
    margin: 20px 0;
}

.warning-message {
    background: #fff3cd;
    padding: 15px;
    border-radius: 6px;
    margin-top: 20px;
    border-left: 4px solid #ffc107;
    color: #856404;
}

.pagination a, .pagination strong {
    display: inline-block;
    margin: 0 3px;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination a:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>
