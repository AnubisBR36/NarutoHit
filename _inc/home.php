<?php require_once('trava.php'); ?>
<?php if(isset($db['config_recuperacao']) && $db['config_recuperacao']==0) require_once('avisorecuperacao.php'); ?>
<?php
require_once('Encrypt.php');
$c=new C_Encrypt();
require_once('funcoes_selos.php');

if(isset($_GET['off'])){
        if($db['orgid']==-1) {
                $stmt = $conexao->prepare("UPDATE usuarios SET orgid=0 WHERE id=?");
                $stmt->execute([$db['id']]);
        }
        echo "<script>self.location='?p=home'</script>";
}

$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
$tam=220;
require_once('funcoes.php');

// Bônus dos equipamentos equipados (mesma fórmula do sistema de batalha)
$equip_tai = 0; $equip_nin = 0; $equip_gen = 0;
try {
    $stmt_eq = $conexao->prepare("SELECT t.taijutsu, t.ninjutsu, t.genjutsu, i.upgrade FROM inventario i LEFT JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND i.status='on'");
    $stmt_eq->execute([$db['id']]);
    foreach ($stmt_eq->fetchAll(PDO::FETCH_ASSOC) as $eq) {
        $equip_tai += (int)$eq['taijutsu'] + (int)$eq['upgrade'];
        $equip_nin += (int)$eq['ninjutsu'] + (int)$eq['upgrade'];
        $equip_gen += (int)$eq['genjutsu'] + (int)$eq['upgrade'];
    }
} catch (PDOException $e) {}
?>
<?php if($db['hunt']>0) require_once('busyhunt.php'); ?>
<?php if($db['missao']>0) require_once('busymission.php'); ?>
<?php
// Buscar URL base configurada pelo administrador
try {
    $stmt_url = $conexao->prepare("SELECT valor FROM configuracoes WHERE nome = 'site_url' LIMIT 1");
    $stmt_url->execute();
    $row_url = $stmt_url->fetch(PDO::FETCH_ASSOC);
    $site_url_nlink = $row_url ? rtrim($row_url['valor'], '/') : '';
} catch (Exception $e) {
    $site_url_nlink = '';
}
$is_criador_home = !empty($db['criador_conteudo']);
$ref_link_atual  = isset($db['ref_link']) ? trim((string)$db['ref_link']) : '';
$nlink_key = ($is_criador_home && $ref_link_atual !== '') ? $ref_link_atual : $db['usuario'];
$nlink_url = $site_url_nlink . '/?p=reg&nlink=' . urlencode($nlink_key);
?>
<div class="box_top"><?php echo $is_criador_home ? '🎬 Link de Parceria (Criador)' : 'nLink'; ?></div>
<div class="box_middle">
<?php if($is_criador_home): ?>
Este é o seu <b>link de referência personalizado</b> como Criador de Conteúdo. Compartilhe nas suas redes — cada novo jogador que se cadastrar pelo seu link entra no histórico do seu perfil de criador (visível no painel admin) e você ganha <b>100,00 yens</b> de bônus.
<?php else: ?>
Utilize seu nLink para divulgar o <?php echo nome_servidor(); ?>. Ao mesmo tempo, você ganhará <b>100,00 yens</b> para cada usuário que se cadastrar no jogo utilizando seu nLink. Avisamos desde já que qualquer prática de spam não será tolerada, resultando em banimento de sua conta. Esta função lhe ajudará apenas com os yens, e os ninjas cadastrados utilizando seu nLink não estarão ligados à sua conta.
<?php endif; ?>
<div class="sep"></div>
        <div align="center"><a style="font-size:10px;" href="<?php echo htmlspecialchars($nlink_url); ?>"><?php echo htmlspecialchars($nlink_url); ?></a></div>
</div>
<div class="box_bottom"></div>

<?php
// Top 3 Criadores de Conteúdo (jogadores com canais do YouTube ativos)
try {
    $stmt_yt = $conexao->prepare("SELECT id, usuario, personagem, vila, nivel, config_youtube FROM usuarios WHERE criador_conteudo = 1 AND config_youtube <> '' AND config_okyoutube = 'sim' ORDER BY nivel DESC, yens_fat DESC LIMIT 3");
    $stmt_yt->execute();
    $top_criadores = $stmt_yt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $top_criadores = []; }
if(!empty($top_criadores)):
$medalhas = ['🥇','🥈','🥉'];
?>
<div class="box_top" style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" onclick="nhToggle('bloco-criadores')">
    <span>▶ Top Criadores de Conteúdo</span>
    <span id="nhbtn-bloco-criadores" style="font-size:11px;color:#ffaa00;padding:0 6px;">▲</span>
</div>
<div id="bloco-criadores">
<div class="box_middle">Jogadores que compartilham conteúdo do <?php echo nome_servidor(); ?> no YouTube. Visite os canais e prestigie!<div class="sep"></div>
    <table width="100%" cellpadding="0" cellspacing="0">
        <?php foreach($top_criadores as $idx => $cr):
            $yt_id = preg_replace('/[^A-Za-z0-9_\-]/', '', $cr['config_youtube']);
            $is_uc = (strlen($yt_id) === 24 && strpos($yt_id, 'UC') === 0);
            $ch_url = $is_uc ? 'https://www.youtube.com/channel/'.$yt_id : 'https://www.youtube.com/@'.$yt_id;
            $bg = ($idx % 2 == 0) ? 'background:url(_img/gradient2.jpg) repeat-y;' : '';
        ?>
        <tr style="<?php echo $bg; ?>">
            <td width="36" align="center" style="padding:4px 6px;font-size:18px;"><?php echo $medalhas[$idx]; ?></td>
            <td style="padding:4px 6px;">
                <a href="?p=view&amp;view=<?php echo strtolower($cr['usuario']); ?>"><b><?php echo htmlspecialchars($cr['usuario']); ?></b></a>
                <span style="color:#888;font-size:10px;"> [Nv. <?php echo (int)$cr['nivel']; ?>]</span>
            </td>
            <td align="right" style="padding:4px 8px;">
                <a href="<?php echo htmlspecialchars($ch_url); ?>" target="_blank" rel="noopener" style="color:#FF0000;text-decoration:none;font-weight:bold;font-size:11px;" title="Visitar canal no YouTube">
                    ▶ Visitar canal
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<div class="box_bottom"></div>
</div>
<?php endif; ?>

<?php
$max=250;
$src="_img/bars/bar.png";
$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
?>
<div class="box_top">Meus Atributos</div>
<div class="box_middle">Seus atributos de combate, yens atuais, nível e experiência.<div class="sep"></div>
        <?php
                if($db['renegado']=='sim'){
                        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE renegado='sim' ORDER BY nivel DESC, yens_fat DESC LIMIT 1");
                        $stmt->execute();
                        $dbx = $stmt->fetch(PDO::FETCH_ASSOC);
                        if($dbx && $dbx['id']==$db['id']) $nivel='Líder da Akatsuki'; else $nivel='Nukenin';
                } else {
                $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE vila=? AND renegado='nao' ORDER BY nivel DESC, yens_fat DESC LIMIT 1");
                        $stmt->execute([$db['vila']]);
                        $dbx = $stmt->fetch(PDO::FETCH_ASSOC);
                        if($dbx && $dbx['id']==$db['id']){
                                switch($db['vila']){
                                        case 1: $nivel='Hokage'; break;
                                        case 2: $nivel='Kazekage'; break;
                                        case 3: $nivel='Otokage'; break;
                                        case 4: $nivel='Líder da Vila da Chuva'; break;
                                        case 5: $nivel='Raikage'; break;
                                        case 6: $nivel='Mizukage'; break;
                                        case 8: $nivel='Tsuchikage'; break;
                                }
                        } else $nivel=rankNinja($db['nivel']); 
                }
                ?>
        <table width="100%" cellpadding="0" cellspacing="0"<?php if($dbx && $dbx['id']==$db['id']){
                echo ' style="background:url(_img/kage/kage';
                if($db['renegado']=='sim') echo '1'; else
                switch($db['vila']){
                        case 1: echo '1'; break;
                        case 2: echo '2'; break;
                        case 3: echo '1'; break;
                        case 4: echo '1'; break;
                        case 5: echo '5'; break;
                        case 6: echo '6'; break;
                        case 8: echo '1'; break;
                }
                echo '.jpg) no-repeat right top;"'; } ?>>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;color:#FFFFAA;">
                <td align="right" style="padding-right:10px;"><img src="_img/yens.png" width="14" height="14" align="absmiddle" /> <b>Meus Yens:</b></td>
            <td colspan="2"><b><?php echo number_format($db['yens'],2,',','.'); ?> yens</b></td>
        </tr>
        <tr>
                <td colspan="3"><div class="sep"></div>
        </tr>
<tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td width="20%" align="right" style="padding-right:10px;"><b>Registro:</b></td>
      <td colspan="2"><?php $reg=explode(' ',$db['reg']); $datareg=explode('-',$reg[0]); echo $datareg[2].'/'.$datareg[1].'/'.$datareg[0].', às '.$reg[1]; ?></td>
        </tr>
        <tr>
                <td align="right" style="padding-right:10px;"><b>Personagem:</b></td>
          <td colspan="2"><?php fpersonagem($db['personagem']); ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td align="right" style="padding-right:10px;"><b>Vila:</b></td>
                <td colspan="2"><?php echo $txtvila; ?></td>
        </tr>
        <?php /*<tr>
                <td align="right" style="padding-right:10px;"><b>Aluno:</b></td>
          <td colspan="2"><?php /*if($db['alunoid']=='') echo '-'; else echo '<a href="?p=view&view='.strtolower($db['alunoid']).'">'.$db['alunoid'].'</a>'; ?></td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
                <td align="right" style="padding-right:10px;"><b>Sensei:</b></td>
      <td colspan="2"><?php /*if($db['senseiid']=='') echo '-'; else echo '<a href="?p=view&view='.strtolower($db['senseiid']).'">'.$db['senseiid'].'</a>'; ?></td>
        </tr>*/ ?>
        <tr>
                <td align="right" style="padding-right:10px;"><b>Clã:</b></td>
          <td colspan="2"><?php if($db['orgid']==-1) echo '<div class="aviso">O clã em que você estava foi destruído.<br />Recomendamos que procure um outro clã.<br /><a href="?p=home&off=1">Clique aqui para desativar esta mensagem.</a></div>'; else if($db['orgid']==0) echo '-'; else echo '<a href="?p=myorg">'.$db['orgnome'].'</a>'; ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td align="right" style="padding-right:10px;"><b>Nível:</b></td>
          <td colspan="2"><?php echo $nivel; ?><b> [<?php echo $db['nivel']; ?>]</b></td>
        </tr>
        <tr>
                <td colspan="3"><div class="sep"></div></td>
        </tr>
<?php
// Penalidades do Doujutsu (negativo que deve ser descontado) — penalidade mínima de 1 ponto
// quando o doujutsu está desbloqueado (mesmo cálculo do attack.php).
// Sharingan (1): -Tai | Byakugan (2): -Nin | Rinnegan (3): -Tai
$dou_tai_pen = 0; $dou_nin_pen = 0; $dou_gen_pen = 0;
if($db['doujutsu_nivel'] > 0) {
    if($db['doujutsu']==1) $dou_tai_pen = max(1, (int)round($db['taijutsu'] * ($db['doujutsu_nivel'] / 100)));
    if($db['doujutsu']==2) $dou_nin_pen = max(1, (int)round($db['ninjutsu'] * ($db['doujutsu_nivel'] / 100)));
    if($db['doujutsu']==3) $dou_tai_pen = max(1, (int)round($db['taijutsu'] * ($db['doujutsu_nivel'] / 100)));
}

// Taijutsu
$tai_bonus_invasao = isset($db['bonus_invasao_tai']) ? (int)$db['bonus_invasao_tai'] : 0;
// Doujutsu: bônus mínimo de 1 ponto se desbloqueado (nível 1+ deve dar status mesmo
// quando o atributo base é baixo e o arredondamento daria 0).
$dou_tai = ($db['doujutsu']==2 && $db['doujutsu_nivel']>0) ? max(1, (int)round($db['taijutsu'] * ($db['doujutsu_nivel'] / 50))) : 0;
$tai_bonus_total = $equip_tai + $dou_tai + $tai_bonus_invasao;
$tai_total = $db['taijutsu'] + $tai_bonus_total - $dou_tai_pen;
$tai_tooltip = 'Base: ' . $db['taijutsu'];
if($equip_tai > 0) $tai_tooltip .= ' | Equip: +' . $equip_tai;
if($dou_tai > 0) $tai_tooltip .= ' | Doujutsu: +' . $dou_tai;
if($tai_bonus_invasao > 0) $tai_tooltip .= ' | Invasão: +' . $tai_bonus_invasao;
if($dou_tai_pen > 0) $tai_tooltip .= ' | Penalidade: -' . $dou_tai_pen;

// Ninjutsu
$nin_bonus_invasao = isset($db['bonus_invasao_nin']) ? (int)$db['bonus_invasao_nin'] : 0;
$dou_nin = ($db['doujutsu']==3 && $db['doujutsu_nivel']>0) ? max(1, (int)round($db['ninjutsu'] * ($db['doujutsu_nivel'] / 50))) : 0;
$nin_bonus_total = $equip_nin + $dou_nin + $nin_bonus_invasao;
$nin_total = $db['ninjutsu'] + $nin_bonus_total - $dou_nin_pen;
$nin_tooltip = 'Base: ' . $db['ninjutsu'];
if($equip_nin > 0) $nin_tooltip .= ' | Equip: +' . $equip_nin;
if($dou_nin > 0) $nin_tooltip .= ' | Doujutsu: +' . $dou_nin;
if($nin_bonus_invasao > 0) $nin_tooltip .= ' | Invasão: +' . $nin_bonus_invasao;
if($dou_nin_pen > 0) $nin_tooltip .= ' | Penalidade: -' . $dou_nin_pen;

// Genjutsu
$gen_bonus_invasao = isset($db['bonus_invasao_gen']) ? (int)$db['bonus_invasao_gen'] : 0;
$dou_gen = ($db['doujutsu']==1 && $db['doujutsu_nivel']>0) ? max(1, (int)round($db['genjutsu'] * ($db['doujutsu_nivel'] / 50))) : 0;
$gen_bonus_total = $equip_gen + $dou_gen + $gen_bonus_invasao;
$gen_total = $db['genjutsu'] + $gen_bonus_total - $dou_gen_pen;
$gen_tooltip = 'Base: ' . $db['genjutsu'];
if($equip_gen > 0) $gen_tooltip .= ' | Equip: +' . $equip_gen;
if($dou_gen > 0) $gen_tooltip .= ' | Doujutsu: +' . $dou_gen;
if($gen_bonus_invasao > 0) $gen_tooltip .= ' | Invasão: +' . $gen_bonus_invasao;
if($dou_gen_pen > 0) $gen_tooltip .= ' | Penalidade: -' . $dou_gen_pen;

// Bônus de Selo (multiplicadores por tipo/nível de Selo)
try {
    $stmt_selo = $conexao->prepare("SELECT selo_tipo, selo_nivel FROM usuarios WHERE id=?");
    $stmt_selo->execute([$db['id']]);
    $row_selo = $stmt_selo->fetch(PDO::FETCH_ASSOC);
    $db['selo_tipo']  = (int)($row_selo['selo_tipo']  ?? 0);
    $db['selo_nivel'] = (int)($row_selo['selo_nivel'] ?? 0);
} catch(Exception $e) {
    $db['selo_tipo'] = 0; $db['selo_nivel'] = 0;
}
$selo_bonus = aplicarBonusSelo($db);

// Aplicar multiplicador do Selo aos totais
$tai_selo_mult = $selo_bonus['taijutsu_mult'];
$nin_selo_mult = $selo_bonus['ninjutsu_mult'];
$gen_selo_mult = $selo_bonus['genjutsu_mult'];

$tai_pre_selo  = $tai_total;
$nin_pre_selo  = $nin_total;
$gen_pre_selo  = $gen_total;

$tai_total = round($tai_pre_selo * $tai_selo_mult);
$nin_total = round($nin_pre_selo * $nin_selo_mult);
$gen_total = round($gen_pre_selo * $gen_selo_mult);

// Diferença adicionada/retirada pelo Selo (para exibir no tooltip)
$tai_selo_diff = $tai_total - $tai_pre_selo;
$nin_selo_diff = $nin_total - $nin_pre_selo;
$gen_selo_diff = $gen_total - $gen_pre_selo;

// Bônus de Cristal de Buff (aplicado após o selo, igual ao combate)
$buff_tai_pct = 0; $buff_nin_pct = 0; $buff_gen_pct = 0;
$buff_tai_diff = 0; $buff_nin_diff = 0; $buff_gen_diff = 0;
$buff_expira = ''; $buff_secs = 0;
try {
    $stmt_buff = $conexao->prepare("SELECT tipo_buff, pct, expira_em FROM buff_ativos WHERE usuarioid = ? AND expira_em > CURRENT_TIMESTAMP LIMIT 1");
    $stmt_buff->execute([$db['id']]);
    $buff_row = $stmt_buff->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $buff_row = null; }
if ($buff_row) {
    $buff_expira = $buff_row['expira_em'];
    $buff_secs = max(0, strtotime($buff_row['expira_em']) - time());
    $pct = (int)$buff_row['pct'];
    if ($buff_row['tipo_buff'] === 'taijutsu') {
        $buff_tai_pct = $pct;
        $novo = round($tai_total * (1 + $pct/100));
        $buff_tai_diff = $novo - $tai_total;
        $tai_total = $novo;
    } elseif ($buff_row['tipo_buff'] === 'ninjutsu') {
        $buff_nin_pct = $pct;
        $novo = round($nin_total * (1 + $pct/100));
        $buff_nin_diff = $novo - $nin_total;
        $nin_total = $novo;
    } elseif ($buff_row['tipo_buff'] === 'genjutsu') {
        $buff_gen_pct = $pct;
        $novo = round($gen_total * (1 + $pct/100));
        $buff_gen_diff = $novo - $gen_total;
        $gen_total = $novo;
    }
}

// Inclui o bônus do buff no contador de bônus exibido em verde ao lado do total
$tai_bonus_total += $buff_tai_diff;
$nin_bonus_total += $buff_nin_diff;
$gen_bonus_total += $buff_gen_diff;
if ($buff_tai_diff > 0) $tai_tooltip .= ' | Buff: +' . $buff_tai_diff . ' (+' . $buff_tai_pct . '%)';
if ($buff_nin_diff > 0) $nin_tooltip .= ' | Buff: +' . $buff_nin_diff . ' (+' . $buff_nin_pct . '%)';
if ($buff_gen_diff > 0) $gen_tooltip .= ' | Buff: +' . $buff_gen_diff . ' (+' . $buff_gen_pct . '%)';

// Imagem do Doujutsu ativo para o painel rico
$dou_img_map = [1=>'_img/doujutsus/sharingan.jpg', 2=>'_img/doujutsus/byakugan.jpg', 3=>'_img/doujutsus/rinnegan.jpg'];
$dou_img = $dou_img_map[$db['doujutsu']] ?? '';

// Dados estruturados para o painel rico (inclui penalidade, selo e buff)
$tai_data = json_encode(['label'=>'Taijutsu','stat_icon'=>'_img/Icones/tai.png','base'=>(int)$db['taijutsu'],'equip'=>$equip_tai,'dou'=>$dou_tai,'pen'=>$dou_tai_pen,'inv'=>$tai_bonus_invasao,'selo'=>$tai_selo_diff,'buff'=>$buff_tai_diff,'buff_pct'=>$buff_tai_pct,'buff_secs'=>($buff_tai_pct>0?$buff_secs:0),'total'=>$tai_total,'color'=>'#4ea8e8','dou_img'=>$dou_img]);
$nin_data = json_encode(['label'=>'Ninjutsu','stat_icon'=>'_img/Icones/nin.png','base'=>(int)$db['ninjutsu'],'equip'=>$equip_nin,'dou'=>$dou_nin,'pen'=>$dou_nin_pen,'inv'=>$nin_bonus_invasao,'selo'=>$nin_selo_diff,'buff'=>$buff_nin_diff,'buff_pct'=>$buff_nin_pct,'buff_secs'=>($buff_nin_pct>0?$buff_secs:0),'total'=>$nin_total,'color'=>'#c97fd4','dou_img'=>$dou_img]);
$gen_data = json_encode(['label'=>'Genjutsu','stat_icon'=>'_img/Icones/gen.png','base'=>(int)$db['genjutsu'],'equip'=>$equip_gen,'dou'=>$dou_gen,'pen'=>$dou_gen_pen,'inv'=>$gen_bonus_invasao,'selo'=>$gen_selo_diff,'buff'=>$buff_gen_diff,'buff_pct'=>$buff_gen_pct,'buff_secs'=>($buff_gen_pct>0?$buff_secs:0),'total'=>$gen_total,'color'=>'#5ecf6e','dou_img'=>$dou_img]);
?>
<style>
.bonus-tag {
    position: relative;
    color: #FFD700;
    font-size: 11px;
    font-weight: bold;
    text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
    cursor: help;
    display: inline-block;
}
.bonus-tag::after {
    content: attr(data-tip);
    position: absolute;
    left: 50%;
    bottom: calc(100% + 6px);
    transform: translateX(-50%);
    background: #1a1a1a;
    color: #FFD700;
    border: 1px solid #8B6914;
    border-radius: 5px;
    padding: 5px 8px;
    font-size: 11px;
    font-weight: normal;
    text-shadow: none;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 999;
}
.bonus-tag:hover::after {
    opacity: 1;
}
.status-icon-tip {
    display: inline-block;
    cursor: help;
    margin-left: 4px;
    vertical-align: middle;
}
@keyframes status-spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.status-icon-tip img {
    animation: status-spin 3s linear infinite;
    display: inline-block;
}
/* Painel flutuante rico */
#stat-rich-panel {
    display: none;
    position: fixed;
    z-index: 9999;
    min-width: 190px;
    background: #0f0b05;
    border: 2px solid #c8830a;
    border-radius: 7px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.75), inset 0 0 40px rgba(200,131,10,0.06);
    font-family: Arial, sans-serif;
    font-size: 12px;
    pointer-events: none;
    overflow: hidden;
}
#stat-rich-panel .srp-title {
    padding: 6px 12px;
    font-weight: bold;
    font-size: 12px;
    letter-spacing: 1px;
    text-align: center;
    background: linear-gradient(90deg, #1c1005 0%, #2a1800 50%, #1c1005 100%);
    border-bottom: 1px solid #c8830a;
    text-transform: uppercase;
}
#stat-rich-panel .srp-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 12px;
    border-bottom: 1px solid #2a1f0d;
    color: #ddd;
}
#stat-rich-panel .srp-row:last-of-type {
    border-bottom: none;
}
#stat-rich-panel .srp-row .srp-lbl {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #bfaa80;
}
#stat-rich-panel .srp-row .srp-lbl .srp-icon {
    font-size: 13px;
    width: 18px;
    text-align: center;
}
#stat-rich-panel .srp-row .srp-val {
    font-weight: bold;
    color: #FFD700;
}
#stat-rich-panel .srp-row .srp-bonus {
    font-weight: bold;
    color: #7eff7e;
}
#stat-rich-panel .srp-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, #c8830a, transparent);
    margin: 2px 0;
}
#stat-rich-panel .srp-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 12px;
    background: linear-gradient(90deg, #1c1005 0%, #2a1800 50%, #1c1005 100%);
    border-top: 1px solid #c8830a;
}
#stat-rich-panel .srp-total .srp-total-lbl {
    color: #FFD700;
    font-weight: bold;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
}
#stat-rich-panel .srp-total .srp-total-val {
    font-size: 15px;
    font-weight: bold;
}
</style>
<div id="stat-rich-panel"></div>
<script>
(function(){
    var panel = document.getElementById('stat-rich-panel');
    var hideTimer = null;

    function buildPanel(d) {
        var statImg = d.stat_icon ? '<img src="'+d.stat_icon+'" style="width:18px;height:18px;vertical-align:middle;margin-right:5px;image-rendering:auto;" />' : '';
        var html = '<div class="srp-title" style="color:'+d.color+'">'+statImg+d.label+'</div>';
        html += '<div class="srp-row"><span class="srp-lbl"><span class="srp-icon">⚔️</span>Base</span><span class="srp-val">'+d.base+'</span></div>';
        if(d.equip > 0) html += '<div class="srp-row"><span class="srp-lbl"><span class="srp-icon">🛡️</span>Equipamentos</span><span class="srp-bonus">+'+d.equip+'</span></div>';
        if(d.dou > 0) {
            var douIcon = d.dou_img ? '<img src="'+d.dou_img+'" style="width:16px;height:16px;object-fit:cover;border-radius:2px;vertical-align:middle;" />' : '👁️';
            html += '<div class="srp-row"><span class="srp-lbl"><span class="srp-icon">'+douIcon+'</span>Doujutsu</span><span class="srp-bonus">+'+d.dou+'</span></div>';
        }
        if(d.pen > 0) {
            var penIcon = d.dou_img ? '<img src="'+d.dou_img+'" style="width:16px;height:16px;object-fit:cover;border-radius:2px;vertical-align:middle;opacity:0.7;" />' : '👁️';
            html += '<div class="srp-row"><span class="srp-lbl"><span class="srp-icon">'+penIcon+'</span>Doujutsu</span><span style="font-weight:bold;color:#ff3a3a;text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;">-'+d.pen+'</span></div>';
        }
        if(d.inv > 0) html += '<div class="srp-row"><span class="srp-lbl"><span class="srp-icon">⚡</span>Invasão</span><span class="srp-bonus">+'+d.inv+'</span></div>';
        if(d.selo !== 0) {
            if(d.selo > 0) html += '<div class="srp-row"><span class="srp-lbl"><span class="srp-icon">📜</span>Selo</span><span class="srp-bonus">+'+d.selo+'</span></div>';
            else html += '<div class="srp-row"><span class="srp-lbl"><span class="srp-icon">📜</span>Selo</span><span style="font-weight:bold;color:#ff3a3a;text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;">'+d.selo+'</span></div>';
        }
        if(d.buff > 0) {
            var buffIcon = '<img src="_img/Buff/Buff.png" style="width:16px;height:16px;object-fit:contain;vertical-align:middle;" />';
            var tempo = '';
            if(d.buff_secs > 0) {
                var s = d.buff_secs, h = Math.floor(s/3600), m = Math.floor((s%3600)/60), sc = s%60;
                tempo = ' <span style="color:#888;font-size:10px;margin-left:4px;">(' + (h>0?h+'h ':'') + String(m).padStart(2,'0') + 'm ' + String(sc).padStart(2,'0') + 's)</span>';
            }
            html += '<div class="srp-row"><span class="srp-lbl"><span class="srp-icon">'+buffIcon+'</span>Cristal de Buff</span><span class="srp-bonus">+'+d.buff+' (+'+d.buff_pct+'%)'+tempo+'</span></div>';
        }
        html += '<div class="srp-total"><span class="srp-total-lbl">Total</span><span class="srp-total-val" style="color:'+d.color+'">'+d.total+'</span></div>';
        return html;
    }

    function showPanel(el) {
        clearTimeout(hideTimer);
        var raw = el.getAttribute('data-stat');
        if(!raw) return;
        var d;
        try { d = JSON.parse(raw); } catch(e){ return; }
        panel.innerHTML = buildPanel(d);
        panel.style.display = 'block';
        positionPanel(el);
    }

    function positionPanel(el) {
        var rect = el.getBoundingClientRect();
        var pw = panel.offsetWidth;
        var ph = panel.offsetHeight;
        var left = rect.left + rect.width/2 - pw/2;
        var top = rect.top - ph - 8;
        if(left < 4) left = 4;
        if(left + pw > window.innerWidth - 4) left = window.innerWidth - pw - 4;
        if(top < 4) top = rect.bottom + 8;
        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
    }

    function hidePanel() {
        hideTimer = setTimeout(function(){ panel.style.display = 'none'; }, 80);
    }

    document.addEventListener('mouseover', function(e){
        var t = e.target.closest ? e.target.closest('.status-icon-tip') : null;
        if(t) showPanel(t);
    });
    document.addEventListener('mouseout', function(e){
        var t = e.target.closest ? e.target.closest('.status-icon-tip') : null;
        if(t) hidePanel();
    });
})();
</script>
        <tr class="attribute-row">
                <td align="right" style="padding-right:10px;background-color:#1b1b1a;white-space:nowrap;"><img src="_img/Icones/tai.png" style="width:16px;height:16px;vertical-align:middle;margin-right:3px;"><b>Tai:</b></td>
          <td>
                <img src="_img/NewsBar/Azul/ponta_barra.jpg" height="22" /><?php
                        if($array[0] > 0) {
                                if($array[0]==$array2["t"]) echo '<img src="_img/NewsBar/Azul/barra_centro.jpg" width="'.$max.'" height="22" />'; else
                                if($array[1]==$array2["t"]) echo '<img src="_img/NewsBar/Azul/barra_centro.jpg" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
                                if($array[2]==$array2["t"]) echo '<img src="_img/NewsBar/Azul/barra_centro.jpg" width="'.($max*$array[2])/$array[0].'" height="22" />';
                        }
                ?><img src="_img/NewsBar/Azul/fim_barra.jpg" height="22" />
          </td>
            <td width="25%" style="vertical-align:middle;padding-top:3px;">
                <b>| <?php echo $tai_total; ?> |</b>
                <?php if($tai_bonus_total > 0): ?>
                    <span class="bonus-tag" data-tip="<?php echo htmlspecialchars($tai_tooltip); ?>">+<?php echo $tai_bonus_total; ?></span>
                <?php endif; ?>
                <span class="status-icon-tip" data-stat="<?php echo htmlspecialchars($tai_data); ?>"><img src="_img/Status.png" style="width:16px;height:16px;vertical-align:middle;" /></span><?php if($buff_tai_pct>0): ?><img src="_img/Buff/Buff.png" style="width:16px;height:16px;vertical-align:middle;margin-left:3px;filter:drop-shadow(0 0 3px #FFD700);" /><?php endif; ?>
            </td>
        </tr>
        <tr class="attribute-row">
                <td align="right" style="padding-right:10px;background-color:#1b1b1a;white-space:nowrap;"><img src="_img/Icones/nin.png" style="width:16px;height:16px;vertical-align:middle;margin-right:3px;"><b>Nin:</b></td>
          <td>
                <img src="_img/NewsBar/Roxo/ponta_barra.jpg" height="22" /><?php
                        if($array[0] > 0) {
                                if($array[0]==$array2["n"]) echo '<img src="_img/NewsBar/Roxo/barra_centro.jpg" width="'.$max.'" height="22" />'; else
                                if($array[1]==$array2["n"]) echo '<img src="_img/NewsBar/Roxo/barra_centro.jpg" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
                                if($array[2]==$array2["n"]) echo '<img src="_img/NewsBar/Roxo/barra_centro.jpg" width="'.($max*$array[2])/$array[0].'" height="22" />';
                        }
                ?><img src="_img/NewsBar/Roxo/fim_barra.jpg" height="22" />
          </td>
            <td width="25%" style="vertical-align:middle;padding-top:3px;">
                <b>| <?php echo $nin_total; ?> |</b>
                <?php if($nin_bonus_total > 0): ?>
                    <span class="bonus-tag" data-tip="<?php echo htmlspecialchars($nin_tooltip); ?>">+<?php echo $nin_bonus_total; ?></span>
                <?php endif; ?>
                <span class="status-icon-tip" data-stat="<?php echo htmlspecialchars($nin_data); ?>"><img src="_img/Status.png" style="width:16px;height:16px;vertical-align:middle;" /></span><?php if($buff_nin_pct>0): ?><img src="_img/Buff/Buff.png" style="width:16px;height:16px;vertical-align:middle;margin-left:3px;filter:drop-shadow(0 0 3px #FFD700);" /><?php endif; ?>
            </td>
        </tr>
        <tr class="attribute-row">
                <td align="right" style="padding-right:10px;background-color:#1b1b1a;white-space:nowrap;"><img src="_img/Icones/gen.png" style="width:16px;height:16px;vertical-align:middle;margin-right:3px;"><b>Gen:</b></td>
          <td>
                <img src="_img/NewsBar/Verde/ponta_barra.jpg" height="22" /><?php
                        if($array[0] > 0) {
                                if($array[0]==$array2["g"]) echo '<img src="_img/NewsBar/Verde/barra_centro.jpg" width="'.$max.'" height="22" />'; else
                                if($array[1]==$array2["g"]) echo '<img src="_img/NewsBar/Verde/barra_centro.jpg" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
                                if($array[2]==$array2["g"]) echo '<img src="_img/NewsBar/Verde/barra_centro.jpg" width="'.($max*$array[2])/$array[0].'" height="22" />';
                        }
                ?><img src="_img/NewsBar/Verde/fim_barra.jpg" height="22" />
          </td>
            <td width="25%" style="vertical-align:middle;padding-top:3px;">
                <b>| <?php echo $gen_total; ?> |</b>
                <?php if($gen_bonus_total > 0): ?>
                    <span class="bonus-tag" data-tip="<?php echo htmlspecialchars($gen_tooltip); ?>">+<?php echo $gen_bonus_total; ?></span>
                <?php endif; ?>
                <span class="status-icon-tip" data-stat="<?php echo htmlspecialchars($gen_data); ?>"><img src="_img/Status.png" style="width:16px;height:16px;vertical-align:middle;" /></span><?php if($buff_gen_pct>0): ?><img src="_img/Buff/Buff.png" style="width:16px;height:16px;vertical-align:middle;margin-left:3px;filter:drop-shadow(0 0 3px #FFD700);" /><?php endif; ?>
            </td>
        </tr>
        <tr>
                <td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr class="attribute-row">
                <td align="right" style="padding-right:10px;background-color:#1b1b1a;white-space:nowrap;"><img src="_img/Icones/Chakra.png" style="width:16px;height:16px;vertical-align:middle;margin-right:3px;"><b>Chakra:</b></td>
          <td><img src="_img/NewsBar/Dourado/ponta_barra.jpg" height="22" /><img src="_img/NewsBar/Dourado/barra_centro.jpg" width="<?php echo $db['energiamax'] > 0 ? (($db['energia']*$max)/$db['energiamax']) : 0; ?>" height="22" /><img src="_img/NewsBar/Dourado/fim_barra.jpg" height="22" /></td>
            <td><b>| <?php echo $db['energia']; ?> / <?php echo $db['energiamax']; ?> |</b></td>
        </tr>
        <tr class="attribute-row">
                <td align="right" style="padding-right:10px;background-color:#1b1b1a;white-space:nowrap;"><img src="_img/Icones/experiencia.png" style="width:16px;height:16px;vertical-align:middle;margin-right:3px;"><b>Experiência:</b></td>
          <td><img src="_img/NewsBar/Vermelha/ponta_barra.jpg" height="22" /><?php
                        if($db['exp'] > 0 && $db['expmax'] > 0) {
                                $exp_width = (($db['exp']*$max)/$db['expmax']);
                                echo '<img src="_img/NewsBar/Vermelha/barra_centro.jpg" width="'.$exp_width.'" height="22" />';
                        }
                        ?><img src="_img/NewsBar/Vermelha/fim_barra.jpg" height="22" /></td>
            <td><b>| <?php echo $db['exp']; ?> / <?php echo $db['expmax']; ?> |</b></td>
        </tr>
    </table>
    <?php if(($db['hunt']==0)&&($db['missao']==0)&&($db['treino']==0)){ ?>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Realizar Treino" onclick="location.href='?p=train'" /></div>
    <?php } ?>
</div>
<div class="box_bottom"></div>
<?php
if(isset($_POST['ram_id'])){
        $id=$c->decode($_POST['ram_id'],$chaveuniversal);
        $tipo=$c->decode($_POST['ram_tipo'],$chaveuniversal);
        vn($id); vn($tipo);
        $stmt = $conexao->prepare("SELECT count(id) as conta FROM ramen WHERE usuarioid=? AND id=?");
        $stmt->execute([$db['id'], $id]);
        $dbr = $stmt->fetch(PDO::FETCH_ASSOC);
        if($dbr['conta']>0){
                $energia=$db['energia'];
                switch($tipo){
                        case 1: $hp=50; break;
                        case 2: $hp=100; break;
                        case 3: $hp=250; break;
                        case 4: $hp=500; break;
                        case 5: $hp=1000; break;
                }
                if($energia+$hp>=$db['energiamax']) $energia=$db['energiamax']; else $energia=$energia+$hp;
                $stmt = $conexao->prepare("DELETE FROM ramen WHERE id=?");
                $stmt->execute([$id]);
                $stmt = $conexao->prepare("UPDATE usuarios SET energia=? WHERE id=?");
                $stmt->execute([$energia, $db['id']]);
                echo "<script>self.location='?p=home&msg=1&e=".$hp."'</script>";
        }
}
// Handler para retirar equipamento
if(isset($_GET['action']) && $_GET['action'] == 'off' && isset($_GET['id'])) {
    $inv_id = intval($_GET['id']);
    try {
        // Verifica se o item pertence ao usuário
        $stmt = $conexao->prepare("SELECT usuarioid FROM inventario WHERE id=?");
        $stmt->execute([$inv_id]);
        $item_check = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($item_check && $item_check['usuarioid'] == $db['id']) {
            // Desequipa o item
            $stmt = $conexao->prepare("UPDATE inventario SET status='off' WHERE id=?");
            $stmt->execute([$inv_id]);
        }
    } catch(PDOException $e) {
        // Silenciosamente ignora erros
    }
    echo "<script>self.location='?p=home'</script>";
    exit;
}

// Sempre mostrar o inventário (mesmo sem itens)
require_once('inventario_abas.php');
?>
<?php
// Sempre mostrar equipamentos (mesmo vazios) para permitir drag-and-drop
require_once('equipamentos.php');

if($db['doujutsu']>0){
    require_once('meudoujutsu.php');
} elseif($db['nivel'] >= 20){
    // Cooldown de 15 dias após perda de linhagem
    if(!empty($db['doujutsu_proxima_tentativa']) && strtotime($db['doujutsu_proxima_tentativa']) > time()){
        $dias_rest = ceil((strtotime($db['doujutsu_proxima_tentativa']) - time()) / 86400);
        ?>
<div class="box_top">🔮 Despertar da Linhagem</div>
<div class="box_middle" style="text-align:center;padding:15px;">
    <p style="color:#888;">Sua linhagem não respondeu ao ritual... aguarde para tentar novamente.</p>
    <p style="color:#FF6600;font-weight:bold;">Próxima tentativa em: <span style="font-size:16px;"><?php echo $dias_rest; ?> dia(s)</span></p>
</div>
<div class="box_bottom"></div>
        <?php
    } else {
        // Pode iniciar o ritual
        ?>
<div class="box_top" style="background:linear-gradient(90deg,#1a0033,#330011,#1a0033);">🔮 Despertar da Linhagem</div>
<div class="box_middle" style="background:radial-gradient(ellipse,#0d0005,#000);text-align:center;padding:15px;">
    <p style="color:#CC88FF;font-size:14px;font-weight:bold;">Seu chakra está inquieto... algo dorme em seu sangue.</p>
    <p style="color:#888;font-size:12px;">Você atingiu o nível 20. Uma herança ancestral pode estar dentro de você.<br/>
    Enfrente a <b style="color:#FF4400;">Sombra da Linhagem</b> e descubra seu destino.</p>
    <?php if(!empty($db['doujutsu_despertar_hp']) && $db['doujutsu_despertar_hp'] > 0): ?>
    <p style="color:#FF9900;font-size:12px;">⚔️ Batalha em andamento — HP da Sombra: <b><?php echo $db['doujutsu_despertar_hp']; ?></b></p>
    <?php endif; ?>
    <input type="button" style="background:linear-gradient(180deg,#8B0000,#3D0000);color:#FFD700;border:1px solid #FF4400;padding:10px 25px;cursor:pointer;font-weight:bold;font-size:14px;border-radius:4px;" value="⚔️ Iniciar Ritual" onclick="location.href='?p=despertar'" />
</div>
<div class="box_bottom"></div>
        <?php
    }
} ?>

<div class="box_top">Minhas Estatísticas</div>
<div class="box_middle">Todas as estatísticas de sua conta.<div class="sep"></div>
        <div style="background:url(_img/stats.jpg) no-repeat right top;">
    <table width="60%" cellpadding="0" cellspacing="0" class="stats_container">
        <tr class="stats_row" style="background:url(_img/gradient.jpg) right;">
                <td width="50%" style="padding-left:3px;"><b>Meus Yens</b></td>
            <td><?php echo number_format($db['yens'],2,',','.'); ?> yens</td>
        </tr>
        <tr class="stats_row">
                <td style="padding-left:3px;"><b>Yens Faturados</b></td>
            <td><?php echo number_format($db['yens_fat'],2,',','.'); ?> yens</td>
        </tr>
        <tr class="stats_row" style="background:url(_img/gradient.jpg) right;">
                <td style="padding-left:3px;"><b>Yens Perdidos</b></td>
            <td><?php echo number_format(isset($db['yens_perd']) ? $db['yens_perd'] : 0,2,',','.'); ?> yens</td>
        </tr>
        <tr class="stats_row">
                <td style="padding-left:3px;"><b>Batalhas</b></td>
            <td><?php echo isset($db['batalhas']) ? $db['batalhas'] : 0; ?> batalhas</td>
        </tr>
        <tr class="stats_row" style="background:url(_img/gradient.jpg) right;">
                <td style="padding-left:3px;"><b>Vitórias</b></td>
            <td><?php echo isset($db['vitorias']) ? $db['vitorias'] : 0; ?> vitórias</td>
        </tr>
        <tr class="stats_row">
                <td style="padding-left:3px;"><b>Derrotas</b></td>
            <td><?php echo isset($db['derrotas']) ? $db['derrotas'] : 0; ?> derrotas</td>
        </tr>
        <tr class="stats_row" style="background:url(_img/gradient.jpg) right;">
                <td style="padding-left:3px;"><b>Empates</b></td>
            <td><?php echo isset($db['empates']) ? $db['empates'] : 0; ?> empates</td>
        </tr>
        <tr class="stats_row">
                <td style="padding-left:3px;"><b>Experiência Total</b></td>
            <td><?php echo isset($db['exptotal']) ? $db['exptotal'] : 0; ?> pontos</td>
        </tr>
    </table>
    </div>
</div>
<div class="box_bottom"></div>
<?php require_once('atualizacoes.php'); ?>
