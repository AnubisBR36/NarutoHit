<?php
// Verificar se o usuário está logado
if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Determinar o ID do usuário logado
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];

// Processar ataque ao monstro
if(isset($_POST['action']) && $_POST['action'] == 'attack_monster') {
    // Buscar dados do usuário
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar energia do player
    if($player['energia'] < 25) {
        echo "<script>alert('Você precisa de pelo menos 25 pontos de energia para atacar!'); window.location.href='?p=invasao';</script>";
        exit;
    }

    // Buscar servidor_id do jogador direto do banco (fonte autoritativa)
    $srv_atk = (int)($player['servidor_id'] ?? 0);
    // Atualizar sessão para manter coerente
    $_SESSION['servidor_id'] = $srv_atk;
    $stmt = $conexao->prepare("SELECT * FROM invasoes WHERE status = 'ativa' AND servidor_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$srv_atk]);
    $invasao = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$invasao) {
        echo "<script>alert('Nenhuma invasão ativa encontrada!'); window.location.href='?p=invasao';</script>";
        exit;
    }

    // Calcular status do monstro (dobro do player)
    $monster_tai = $player['taijutsu'] * 2;
    $monster_nin = $player['ninjutsu'] * 2;
    $monster_gen = $player['genjutsu'] * 2;

    // Calcular dano (usando lógica similar ao sistema de ataque existente) incluindo bônus
    $player_tai_com_bonus = $player['taijutsu'] + ($player['bonus_invasao_tai'] ?? 0);
    $player_nin_com_bonus = $player['ninjutsu'] + ($player['bonus_invasao_nin'] ?? 0);
    $player_gen_com_bonus = $player['genjutsu'] + ($player['bonus_invasao_gen'] ?? 0);

    $player_total = $player_tai_com_bonus + $player_nin_com_bonus + $player_gen_com_bonus;
    $monster_total = $monster_tai + $monster_nin + $monster_gen;

    // Fator de aleatoriedade
    $random_factor = rand(80, 120) / 100;

    // Calcular dano base
    $dano_base = ($player_total * $random_factor) - ($monster_total * 0.3);
    $dano_final = max(1, round($dano_base));

    // Verificar se o player foi derrotado
    $player_derrotado = false;
    if($monster_total > $player_total * 1.5) {
        $player_derrotado = true;
        // Atualizar contador de players derrotados
        $stmt = $conexao->prepare("UPDATE invasoes SET players_derrotados = players_derrotados + 1 WHERE id = ?");
        $stmt->execute([$invasao['id']]);
    }

    // Aplicar dano ao monstro
    $novo_hp = max(0, $invasao['hp_atual'] - $dano_final);

    // Reduzir energia do player
    $nova_energia = max(0, $player['energia'] - 25);
    $stmt = $conexao->prepare("UPDATE usuarios SET energia = ? WHERE id = ?");
    $stmt->execute([$nova_energia, $user_id]);

    // Registrar o ataque
    $stmt = $conexao->prepare("INSERT INTO ataques_invasao (invasao_id, usuario_id, dano_causado, data_ataque) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$invasao['id'], $user_id, $dano_final]);

    if($novo_hp <= 0) {
        // Monstro foi derrotado!
        $stmt = $conexao->prepare("UPDATE invasoes SET hp_atual = 0, status = 'finalizada', vencedor_id = ?, data_fim = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user_id, $invasao['id']]);

        // Dar prêmio ao vencedor
        $stmt = $conexao->prepare("UPDATE usuarios SET yens = yens + ? WHERE id = ?");
        $stmt->execute([$invasao['premio_yens'], $user_id]);

        // Aplicar bônus de vila para todos os membros da vila do vencedor
        $bonus_percent = $invasao['bonus_vila'] / 100;

        // Buscar vila do vencedor
        $stmt = $conexao->prepare("SELECT vila FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $vencedor_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $vila_vencedora = $vencedor_data['vila'];

        // Usar sempre o servidor_id da própria invasão (não da sessão, que pode estar errada)
        $srv_invasao = (int)$invasao['servidor_id'];

        // Remover bônus anterior de todas as vilas do mesmo servidor (caso exista)
        $stmt = $conexao->prepare("UPDATE usuarios SET bonus_invasao_tai = 0, bonus_invasao_nin = 0, bonus_invasao_gen = 0, bonus_invasao_pct = 0 WHERE servidor_id = ?");
        $stmt->execute([$srv_invasao]);

        // Aplicar novo bônus para toda a vila vencedora do mesmo servidor
        // Salva a PORCENTAGEM (bonus_invasao_pct) e os valores absolutos (recalculados sempre)
        $stmt = $conexao->prepare("
            UPDATE usuarios 
            SET 
                bonus_invasao_tai = ROUND(taijutsu * ?),
                bonus_invasao_nin = ROUND(ninjutsu * ?),
                bonus_invasao_gen = ROUND(genjutsu * ?),
                bonus_invasao_pct = ?
            WHERE vila = ? AND servidor_id = ?
        ");
        $result = $stmt->execute([$bonus_percent, $bonus_percent, $bonus_percent, $invasao['bonus_vila'], $vila_vencedora, $srv_invasao]);

        // Verificar quantos usuários foram afetados
        $affected_rows = $stmt->rowCount();

        // Log para debug - verificar se o bônus foi aplicado
        error_log("Bônus aplicado para vila $vila_vencedora com $bonus_percent% de aumento. Usuários afetados: $affected_rows");

        $vilas = [1 => 'Folha', 2 => 'Areia', 3 => 'Névoa', 4 => 'Pedra', 5 => 'Nuvem', 6 => 'Som', 7 => 'Chuva', 8 => 'Akatsuki'];
        $nome_vila = $vilas[$vila_vencedora] ?? 'Desconhecida';

        echo "<script>alert('Parabéns! Você derrotou o invasor e ganhou " . number_format($invasao['premio_yens'], 2, ',', '.') . " yens!\\n\\nToda a vila " . $nome_vila . " recebeu +" . $invasao['bonus_vila'] . "% de bônus em todos os status!'); window.location.href='?p=invasao';</script>";
        exit;
    } else {
        // Atualizar HP do monstro
        $stmt = $conexao->prepare("UPDATE invasoes SET hp_atual = ? WHERE id = ?");
        $stmt->execute([$novo_hp, $invasao['id']]);

        if($player_derrotado) {
            echo "<script>alert('Você foi derrotado pelo invasor! Causou " . number_format($dano_final) . " de dano.'); window.location.href='?p=invasao';</script>";
        } else {
            echo "<script>alert('Você causou " . number_format($dano_final) . " de dano ao invasor!'); window.location.href='?p=invasao';</script>";
        }
        exit;
    }
}

// Verificar se existe invasão ativa no servidor do jogador
// Sempre usa o banco como fonte autoritativa para evitar bugs de sessão desatualizada
$stmt_srv2 = $conexao->prepare("SELECT servidor_id FROM usuarios WHERE id = ?");
$stmt_srv2->execute([$user_id]);
$srv_row2 = $stmt_srv2->fetch(PDO::FETCH_ASSOC);
$srv_inv = (int)($srv_row2['servidor_id'] ?? 0);
$_SESSION['servidor_id'] = $srv_inv;
$stmt = $conexao->prepare("SELECT * FROM invasoes WHERE status = 'ativa' AND servidor_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$srv_inv]);
$invasao_ativa = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não há invasão ativa, buscar a última invasão finalizada do mesmo servidor
if(!$invasao_ativa) {
    $stmt = $conexao->prepare("SELECT * FROM invasoes WHERE status = 'finalizada' AND servidor_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$srv_inv]);
    $invasao_ativa = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar últimos ataques
$stmt = $conexao->prepare("
    SELECT ai.*, u.usuario 
    FROM ataques_invasao ai 
    LEFT JOIN usuarios u ON ai.usuario_id = u.id 
    WHERE ai.invasao_id = ? 
    ORDER BY ai.data_ataque DESC 
    LIMIT 10
");
$stmt->execute([$invasao_ativa['id']]);
$ultimos_ataques = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar dados do usuário atual
$stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario_atual = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php
if(!defined('INDEX')) die('Acesso direto a este arquivo não é permitido!');

// Adicionar CSS inline para a página de invasão
echo '<style>
.invasao-main-container {
    background-color: #1b1b1a !important;
    padding: 20px;
    border-radius: 8px;
    margin: 10px 0;
}
.invasao-main-container table,
.invasao-main-container td,
.invasao-main-container tr {
    background-color: #1b1b1a !important;
}
.invasao-image-container {
    background-color: #1b1b1a !important;
    padding: 15px;
    border-radius: 5px;
}
</style>';

// Adicionar CSS inline para a página de invasão
echo '<style>
.invasao-main-container {
    background-color: #1b1b1a !important;
    padding: 20px;
    border-radius: 8px;
    margin: 10px 0;
}
.invasao-main-container table,
.invasao-main-container td,
.invasao-main-container tr {
    background-color: #1b1b1a !important;
}
.invasao-image-container {
    background-color: #1b1b1a !important;
    padding: 15px;
    border-radius: 5px;
}
</style>';

// Adicionar CSS inline para a página de invasão
echo '<style>
.invasao-main-container {
    background-color: #1b1b1a !important;
    padding: 20px;
    border-radius: 8px;
    margin: 10px 0;
}
.invasao-main-container table,
.invasao-main-container td,
.invasao-main-container tr {
    background-color: #1b1b1a !important;
}
.invasao-image-container {
    background-color: #1b1b1a !important;
    padding: 15px;
    border-radius: 5px;
}
</style>';
?>

<div class="box_top">🔥 Invasão em Andamento</div>
<div class="box_middle">
    <!-- Imagem do Invasor em Tamanho Real -->
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="_img/Invasao/<?php echo htmlspecialchars($invasao_ativa['imagem_arquivo']); ?>" 
             style="max-width: 100%; height: auto; border: 3px solid #ff0000; border-radius: 10px;" 
             alt="<?php echo htmlspecialchars($invasao_ativa['nome_invasor']); ?>">
    </div>

    <!-- Informações do Invasor -->
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="color: #ff0000; margin: 0 0 15px 0; text-shadow: 2px 2px 4px #000;">
            👹 <?php echo htmlspecialchars($invasao_ativa['nome_invasor']); ?>
        </h2>

        <div style="margin: 10px 0; font-size: 14px;">
            <strong>📅 Data da Invasão:</strong> 
            <?php echo date('d/m/Y H:i', strtotime($invasao_ativa['data_inicio'])); ?>
        </div>

        <div style="margin: 10px 0; font-size: 14px;">
            <strong>💰 Reserva de Yens:</strong> 
            <span style="color: #ffd700; font-weight: bold;">
                <?php echo number_format($invasao_ativa['premio_yens'], 2, ',', '.'); ?> Yens
            </span>
        </div>

        <div style="margin: 10px 0; font-size: 14px;">
            <strong>⚡ Bônus da Vila Vencedora:</strong> 
            <span style="color: #00ff00; font-weight: bold;">
                +<?php echo $invasao_ativa['bonus_vila']; ?>% em todos os status
            </span>
        </div>

        <?php if(($usuario_atual['bonus_invasao_tai'] ?? 0) > 0): ?>
        <div style="margin: 10px 0; padding: 10px; background: #0f4f0f; border: 1px solid #00ff00; border-radius: 5px;">
            <div style="color: #00ff00; font-weight: bold; text-align: center;">
                🎉 SUA VILA TEM BÔNUS ATIVO! 🎉
            </div>
            <div style="color: #fff; font-size: 12px; text-align: center; margin-top: 5px;">
                Taijutsu: +<?php echo $usuario_atual['bonus_invasao_tai']; ?> | 
                Ninjutsu: +<?php echo $usuario_atual['bonus_invasao_nin']; ?> | 
                Genjutsu: +<?php echo $usuario_atual['bonus_invasao_gen']; ?>
            </div>
        </div>
        <?php endif; ?>

        <div style="margin: 10px 0; font-size: 14px;">
            <strong>💀 Players Derrotados:</strong> 
            <span style="color: #ff4444;">
                <?php echo number_format($invasao_ativa['players_derrotados']); ?>
            </span>
        </div>
    </div>

    <!-- HP do Invasor -->
    <div style="margin: 10px 0;">
        <div style="text-align: center; margin: 0 10px;">
            <?php 
            $max_hp_width = 280; // Largura máxima da barra de HP reduzida para evitar quebra
            $hp_width = ($invasao_ativa['hp_atual'] / $invasao_ativa['hp_total']) * $max_hp_width;
            ?>
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: nowrap; max-width: 100%; overflow: hidden;">
                <span style="font-size: 20px; flex-shrink: 0;">💔</span>
                <div style="position: relative; width: <?php echo $max_hp_width; ?>px; height: 40px; flex-shrink: 0;">
                    <div style="position: absolute; left: 0; top: 0; display: flex; align-items: center;">
                        <img src="_img/NewsBar/Vermelha/ponta_barra.jpg" height="40" style="display: block;" />
                        <img src="_img/NewsBar/Vermelha/barra_centro.jpg" width="<?php echo $hp_width; ?>" height="40" style="display: block;" />
                        <img src="_img/NewsBar/Vermelha/fim_barra.jpg" height="40" style="display: block;" />
                    </div>
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; z-index: 20;">
                        <span style="color: #ffff00; font-weight: bold; font-size: 14px; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;">
                            <?php echo number_format($invasao_ativa['hp_atual']); ?> / <?php echo number_format($invasao_ativa['hp_total']); ?>
                        </span>
                    </div>
                </div>
                <span style="font-size: 20px; flex-shrink: 0;">💔</span>
            </div>
        </div>
    </div>

    <!-- Status do Monstro (calculados dinamicamente baseado no player atual) -->
    <?php 
    // Calcular status do monstro baseado no player atual (com bônus se houver)
    $player_tai_total = $usuario_atual['taijutsu'] + ($usuario_atual['bonus_invasao_tai'] ?? 0);
    $player_nin_total = $usuario_atual['ninjutsu'] + ($usuario_atual['bonus_invasao_nin'] ?? 0);
    $player_gen_total = $usuario_atual['genjutsu'] + ($usuario_atual['bonus_invasao_gen'] ?? 0);

    $monster_tai = $player_tai_total * 2;
    $monster_nin = $player_nin_total * 2;
    $monster_gen = $player_gen_total * 2;
    ?>
    <div style="margin: 10px 0;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <?php 
            // Calcular larguras das barras baseado no maior valor
            $max_monster = max($monster_tai, $monster_nin, $monster_gen);
            $max_width = 280;

            $tai_width = $max_monster > 0 ? ($monster_tai * $max_width) / $max_monster : 0;
            $nin_width = $max_monster > 0 ? ($monster_nin * $max_width) / $max_monster : 0;
            $gen_width = $max_monster > 0 ? ($monster_gen * $max_width) / $max_monster : 0;
            ?>
            <tr style="background-color: #1b1b1a !important;">
                <td width="15%" align="right" style="padding-right:5px; background-color: #1b1b1a !important;"><b>Taijutsu:</b></td>
                <td style="background-color: #1b1b1a !important;"><img src="_img/NewsBar/Azul/ponta_barra.jpg" height="22" /><img src="_img/NewsBar/Azul/barra_centro.jpg" width="<?php echo $tai_width; ?>" height="22" /><img src="_img/NewsBar/Azul/fim_barra.jpg" height="22" /></td>
                <td width="20%" style="padding-left:5px; background-color: #1b1b1a !important;"><b>| <?php echo number_format($monster_tai); ?> |</b></td>
            </tr>
            <tr style="background-color: #1b1b1a !important;">
                <td align="right" style="padding-right:5px; background-color: #1b1b1a !important;"><b>Ninjutsu:</b></td>
                <td style="background-color: #1b1b1a !important;"><img src="_img/NewsBar/Roxo/ponta_barra.jpg" height="22" /><img src="_img/NewsBar/Roxo/barra_centro.jpg" width="<?php echo $nin_width; ?>" height="22" /><img src="_img/NewsBar/Roxo/fim_barra.jpg" height="22" /></td>
                <td style="padding-left:5px; background-color: #1b1b1a !important;"><b>| <?php echo number_format($monster_nin); ?> |</b></td>
            </tr>
            <tr style="background-color: #1b1b1a !important;">
                <td align="right" style="padding-right:5px; background-color: #1b1b1a !important;"><b>Genjutsu:</b></td>
                <td style="background-color: #1b1b1a !important;"><img src="_img/NewsBar/Verde/ponta_barra.jpg" height="22" /><img src="_img/NewsBar/Verde/barra_centro.jpg" width="<?php echo $gen_width; ?>" height="22" /><img src="_img/NewsBar/Verde/fim_barra.jpg" height="22" /></td>
                <td style="padding-left:5px; background-color: #1b1b1a !important;"><b>| <?php echo number_format($monster_gen); ?> |</b></td>
            </tr>
        </table>
    </div>

    <?php if($invasao_ativa['status'] == 'ativa' && $invasao_ativa['hp_atual'] > 0): ?>
        <div style="text-align: center; margin: 15px 0;">
            <?php if($usuario_atual['energia'] >= 25): ?>
                <a href="?p=prepareIn&invasao=1" style="background: url('_img/fundo_botao.jpg') no-repeat center; background-size: cover; color: white; padding: 8px 20px; border: none; cursor: pointer; font-size: 14px; border-radius: 4px; text-shadow: 1px 1px 2px #000; text-decoration: none; display: inline-block;">
                    ⚔️ Batalhar
                </a>
            <?php else: ?>
                <div style="background: #444; color: #ccc; padding: 8px 20px; border-radius: 4px; display: inline-block; font-size: 14px;">
                    ⚡ Energia Insuficiente (<?php echo $usuario_atual['energia']; ?>/25)
                </div>
            <?php endif; ?>
        </div>
    <?php elseif($invasao_ativa['status'] == 'finalizada' || $invasao_ativa['hp_atual'] <= 0): ?>
        <div style="text-align: center; margin: 20px 0;">
            <div style="color: #00ff00; font-size: 18px; font-weight: bold; margin-bottom: 15px;">
                🎉 INVASOR DERROTADO! 🎉
            </div>

            <?php if($invasao_ativa['vencedor_id']): ?>
                <?php
                // Buscar dados do vencedor
                $stmt = $conexao->prepare("SELECT usuario FROM usuarios WHERE id = ?");
                $stmt->execute([$invasao_ativa['vencedor_id']]);
                $vencedor = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <div style="background: url('_img/config_pass.jpg') no-repeat center; background-size: cover; border: 2px solid #00ff00; padding: 20px; border-radius: 10px; margin: 20px auto; max-width: 400px; position: relative;">
                    <div style="background: rgba(0,0,0,0.7); padding: 15px; border-radius: 8px;">
                        <h3 style="color: #ffd700; margin: 0 0 10px 0; text-shadow: 2px 2px 4px #000;">🏆 VENCEDOR DA INVASÃO</h3>
                        <div style="color: #fff; font-size: 16px; margin: 10px 0;">
                            <strong>Ninja Vencedor:</strong> <span style="color: #00ff00;"><?php echo htmlspecialchars($vencedor['usuario'] ?? 'Desconhecido'); ?></span>
                        </div>
                        <div style="color: #fff; font-size: 14px; margin: 10px 0;">
                            <strong>Data da Vitória:</strong> 
                            <span style="color: #ffd700;">
                                <?php 
                                if($invasao_ativa['data_fim']) {
                                    echo date('d/m/Y H:i', strtotime($invasao_ativa['data_fim']));
                                } else {
                                    echo date('d/m/Y H:i');
                                }
                                ?>
                            </span>
                        </div>
                        <div style="color: #fff; font-size: 14px; margin: 10px 0;">
                            <strong>Prêmio Conquistado:</strong> <span style="color: #ffd700;"><?php echo number_format($invasao_ativa['premio_yens']); ?> Yens</span>
                        </div>
                        <div style="color: #fff; font-size: 14px; margin: 10px 0;">
                            <strong>Players Derrotados:</strong> <span style="color: #ff6666;"><?php echo number_format($invasao_ativa['players_derrotados']); ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #444; color: #fff; padding: 15px; border-radius: 5px; margin: 20px auto; max-width: 400px;">
                    <p>A invasão foi finalizada pelo administrador.</p>
                    <?php if($invasao_ativa['data_fim']): ?>
                        <div style="margin: 10px 0;">
                            <strong>Data de Finalização:</strong> <?php echo date('d/m/Y H:i', strtotime($invasao_ativa['data_fim'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="background: #2a2a2a; color: #ccc; padding: 10px; border-radius: 5px; margin: 20px auto; max-width: 400px; font-size: 14px;">
                ⏳ Aguardando nova invasão ⏳
            </div>
        </div>
    <?php else: ?>
        <div style="text-align: center; margin: 20px 0;">
            <div style="background: #444; color: #fff; padding: 20px; border-radius: 5px; margin: 20px auto; max-width: 400px;">
                <h3>⚠️ Nenhuma Invasão Disponível</h3>
                <p>Aguarde o administrador abrir uma nova invasão.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if(!empty($ultimos_ataques) && $invasao_ativa['status'] == 'ativa'): ?>
        <div style="margin-top: 30px;">
            <h4>📊 Últimos Ataques:</h4>
            <div style="max-height: 200px; overflow-y: auto; background: #222; padding: 10px; border-radius: 5px;">
                <?php foreach($ultimos_ataques as $ataque): ?>
                    <div style="margin: 5px 0; padding: 5px; background: #333; border-radius: 3px;">
                        <strong><?php echo htmlspecialchars($ataque['usuario']); ?></strong> 
                        causou <span style="color: #ff6666;"><?php echo number_format($ataque['dano_causado']); ?></span> de dano
                        <small style="color: #888; float: right;">
                            <?php echo date('H:i:s', strtotime($ataque['data_ataque'])); ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif($invasao_ativa['status'] == 'finalizada'): ?>
        <div style="margin-top: 30px;">
            <h4>📊 Estatísticas da Invasão Finalizada:</h4>
            <div style="background: #222; padding: 15px; border-radius: 5px; text-align: center;">
                <div style="margin: 10px 0;">
                    <strong>Duração Total:</strong> 
                    <?php 
                    // Debug: verificar valores
                    // echo "Status: " . $invasao_ativa['status'] . " | Início: " . $invasao_ativa['data_inicio'] . " | Fim: " . $invasao_ativa['data_fim'];

                    if($invasao_ativa['data_inicio']) {
                        if($invasao_ativa['status'] == 'finalizada' && $invasao_ativa['data_fim']) {
                            // Invasão finalizada com data de fim
                            $inicio_timestamp = strtotime($invasao_ativa['data_inicio']);
                            $fim_timestamp = strtotime($invasao_ativa['data_fim']);

                            if($inicio_timestamp && $fim_timestamp && $fim_timestamp > $inicio_timestamp) {
                                $diferenca = $fim_timestamp - $inicio_timestamp;

                                $dias = floor($diferenca / 86400);
                                $horas = floor(($diferenca % 86400) / 3600);
                                $minutos = floor(($diferenca % 3600) / 60);
                                $segundos = $diferenca % 60;

                                $tempo = '';
                                if($dias > 0) $tempo .= $dias . ' dias ';
                                if($horas > 0) $tempo .= $horas . ' horas ';
                                if($minutos > 0) $tempo .= $minutos . ' minutos ';
                                if($segundos > 0 && $dias == 0 && $horas == 0 && $minutos == 0) $tempo .= $segundos . ' segundos';

                                echo trim($tempo) ?: 'Menos de 1 segundo';
                            } else {
                                echo 'Dados de tempo inválidos';
                            }
                        } elseif($invasao_ativa['status'] == 'finalizada') {
                            // Invasão finalizada mas sem data de fim (finalizada manualmente)
                            echo 'Finalizada pelo administrador';
                        } elseif($invasao_ativa['status'] == 'ativa') {
                            // Invasão ativa, calcular duração até agora
                            $inicio_timestamp = strtotime($invasao_ativa['data_inicio']);
                            $agora_timestamp = time();

                            if($inicio_timestamp) {
                                $diferenca = $agora_timestamp - $inicio_timestamp;

                                $dias = floor($diferenca / 86400);
                                $horas = floor(($diferenca % 86400) / 3600);
                                $minutos = floor(($diferenca % 3600) / 60);

                                $tempo = '';
                                if($dias > 0) $tempo .= $dias . ' dias ';
                                if($horas > 0) $tempo .= $horas . ' horas ';
                                if($minutos > 0) $tempo .= $minutos . ' minutos';

                                echo 'Em andamento: ' . (trim($tempo) ?: 'Menos de 1 minuto');
                            } else {
                                echo 'Em andamento';
                            }
                        } else {
                            echo 'Status desconhecido';
                        }
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div style="margin: 10px 0;">
                    <strong>Total de Participantes Derrotados:</strong> 
                    <span style="color: #ff6666;"><?php echo number_format($invasao_ativa['players_derrotados']); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>
</replit_final_file>