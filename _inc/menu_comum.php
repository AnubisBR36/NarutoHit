<div style="width:170px;">

<div class="box2_top">Navegação</div>
<div class="box2_middle">
<div style="padding: 5px;">
<?php
$current_page = isset($_GET['p']) ? $_GET['p'] : 'home';
$nav_links = array(
    'home' => 'Home',
    'messages' => 'Mensagens', 
    'reports' => 'Relatórios',
    'inventory' => 'Inventário',
    'jutsus' => 'Jutsus',
    'selos' => 'Selos',
    'city' => 'Cidade',
    'blacksmith' => 'Ferreiro',
    'mapa' => 'Mapa',
    'myshop' => 'Minha Loja',
    'invasao' => 'Invasão',
    'forum' => 'Fórum',
    'news' => 'News',
    'book' => 'Bingo Book',
    'config' => 'Configurações',
    'rank' => 'Ranking',
    'support' => 'Suporte',
    'vip' => 'VIP',
    'faq' => 'FAQ',
    'manual' => 'Manual',
    'logout' => 'Logout'
);

// Verificar se o jogador está na floresta
$in_forest = false;
if(isset($_SESSION['logado'])) {
    $in_forest = isPlayerInForest($_SESSION['logado']);
}

foreach($nav_links as $page => $title) {
    $is_current = ($current_page == $page);
    $icon = $is_current ? 'online.png' : 'offline.png';
    $style = $is_current ? 'color: #00ff00; font-weight: bold; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;' : 'color: #ffff00; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;';

    echo '<div style="margin: 2px 0; padding: 2px; border-bottom: 1px solid #333;">';
    echo '<img src="_img/'.$icon.'" style="vertical-align: middle; margin-right: 5px;" />';

    // Lógica especial para o link do mapa
    if($page == 'mapa') {
        if($in_forest) {
            echo '<a href="?p=floresta" style="'.$style.' text-decoration: none; font-size: 11px;">Mapa</a>';
        } else {
            echo '<a href="?p=mapa" style="'.$style.' text-decoration: none; font-size: 11px;">Mapa</a>';
        }
    } else {
        echo '<a href="?p='.$page.'" style="'.$style.' text-decoration: none; font-size: 11px;">'.$title.'</a>';
    }

    echo '</div>';
}
?>
</div>
</div>
<div class="box2_bottom"></div>

<?php /*<div class="box2_top">Anúncios</div>
<div class="box2_middle">Reservado para anúncios.</div>
<div class="box2_bottom"></div>*/ ?>


<?php /*if(!isset($_SESSION['logado'])) require_once('_inc/anuncio_lateral.php'); else {
                if((date('Y-m-d H:i:s')>=$db['vip'])&&(isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')) require_once('_inc/anuncio_lateral.php');
}*/ ?>

<?php
// Servidor do jogador logado (filtro para todas as queries de estatísticas)
$mc_srv_id = isset($_SESSION['servidor_id']) ? (int)$_SESSION['servidor_id'] : (isset($db['servidor_id']) ? (int)$db['servidor_id'] : null);

$nomes_vilas = array(
        1 => 'Folha', 2 => 'Areia', 3 => 'Som', 4 => 'Chuva',
        5 => 'Nuvem', 6 => 'Névoa', 8 => 'Pedra', 99 => 'Folha',
        'akatsuki' => 'Akatsuki'
);

// Online: considera "online" todo usuário cujo timestamp foi atualizado nos últimos 5 min
// (não usamos mais loginip — está vazio/legado em muitos cadastros).
$online_threshold_sql = "(timestamp >= (UNIX_TIMESTAMP() - 300))";

if ($mc_srv_id !== null) {
        try {
                $stmt_online = $conexao->prepare("SELECT COUNT(*) as online FROM usuarios WHERE $online_threshold_sql AND status != 'banido' AND servidor_id = ?");
                $stmt_online->execute([$mc_srv_id]);
                $online_count = $stmt_online->fetch(PDO::FETCH_ASSOC)['online'];
        } catch (PDOException $e) { $online_count = 0; }
        try {
                $stmt_total = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios WHERE status != 'banido' AND servidor_id = ?");
                $stmt_total->execute([$mc_srv_id]);
                $total_count = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) { $total_count = 0; }
        try {
                // Renegados aparecem como "Akatsuki", o restante agrupa por vila
                $stmt_vilas = $conexao->prepare("SELECT CASE WHEN renegado='sim' THEN 'akatsuki' ELSE CAST(vila AS CHAR) END AS grupo, COUNT(*) AS count FROM usuarios WHERE status != 'banido' AND servidor_id = ? GROUP BY grupo ORDER BY grupo");
                $stmt_vilas->execute([$mc_srv_id]);
                $vilas_count = $stmt_vilas->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $vilas_count = []; }
        try {
                $stmt_snome = $conexao->prepare("SELECT nome FROM servidores WHERE id = ? LIMIT 1");
                $stmt_snome->execute([$mc_srv_id]);
                $srv_label = $stmt_snome->fetch(PDO::FETCH_ASSOC)['nome'] ?? null;
        } catch (PDOException $e) { $srv_label = null; }
} else {
        try {
                $stmt_online = $conexao->prepare("SELECT COUNT(*) as online FROM usuarios WHERE $online_threshold_sql AND status != 'banido'");
                $stmt_online->execute(); $online_count = $stmt_online->fetch(PDO::FETCH_ASSOC)['online'];
        } catch (PDOException $e) { $online_count = 0; }
        try {
                $stmt_total = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios WHERE status != 'banido'");
                $stmt_total->execute(); $total_count = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) { $total_count = 0; }
        try {
                $stmt_vilas = $conexao->prepare("SELECT CASE WHEN renegado='sim' THEN 'akatsuki' ELSE CAST(vila AS CHAR) END AS grupo, COUNT(*) AS count FROM usuarios WHERE status != 'banido' GROUP BY grupo ORDER BY grupo");
                $stmt_vilas->execute(); $vilas_count = $stmt_vilas->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $vilas_count = []; }
        $srv_label = null;
}
?>
<div class="box2_top">Estatísticas</div>
<div class="box2_middle" style="text-align:center;">
<?php if ($srv_label): ?>
        <div style="font-size:10px;color:#ffdd88;margin-bottom:3px;"><?php echo htmlspecialchars($srv_label); ?></div>
<?php endif; ?>
        Usuários online: <strong><?php echo $online_count; ?></strong><br />
    <div class="sep"></div>
    Total de usuários: <strong><?php echo number_format($total_count, 0, ',', '.'); ?></strong><br />
    <div class="sep"></div>

    <div style="font-size: 11px;">
        <strong>Por Vila:</strong><br />
        <?php
        foreach($vilas_count as $vila_data):
                $grupo = $vila_data['grupo'];
                if ($grupo === 'akatsuki') {
                        $vila_nome = 'Akatsuki';
                } else {
                        $vila_nome = isset($nomes_vilas[(int)$grupo]) ? $nomes_vilas[(int)$grupo] : 'Desconhecida';
                }
        ?>
                <?php echo $vila_nome; ?>: <?php echo $vila_data['count']; ?><br />
        <?php endforeach; ?>
    </div>
    <div class="sep"></div>
    <!-- Site Meter removido temporariamente -->
<?php /*<!-- AddThis Button BEGIN -->
<!-- Comentado scripts externos WAU que podem causar erros -->
<!-- <a class="addthis_button" href="http://www.addthis.com/bookmark.php?v=250&amp;username=anubisserve"><img src="http://s7.addthis.com/static/btn/v2/lg-share-en.gif" width="125" height="16" alt="Bookmark and Share" style="border:0"/></a><script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#username=anubisserve"></script> -->
<!-- <script type="text/javascript" src="http://widgets.amung.us/classic.js"></script><script type="text/javascript">WAU_classic('da6rpwwysyc4')</script> -->
<!-- <script type="text/javascript" src="http://widgets.amung.us/colored.js"></script><script type="text/javascript">WAU_colored('sg1uw9ltr03f', '000000a7a9ac')</script> -->
<!-- <div class="sep"></div><a href="http://www.melhoresdanet.com/index.php?a=in&u=anubisserve" target='_blank'>MelhoresDaNet</a> -->*/ ?></div>
<div class="box2_bottom"></div>
</div>