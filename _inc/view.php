<?php require_once('trava.php'); ?>
<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

// Garantir que as chaves existam no array $db
$default_values = array(
    'empates' => 0,
    'config_okyoutube' => 'nao',
    'config_youtube' => '',
    'criador_conteudo' => 0,
    'exptotal' => 0,
    'yens_perd' => 0.00,
    'config_apresentacao' => '',
    'doujutsu' => 0,
    'orgid' => 0,
    'orgnome' => ''
);

foreach($default_values as $key => $default_value) {
    if (!isset($db[$key])) {
        $db[$key] = $default_value;
    }
}

$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
$tam=220;
require_once('funcoes.php');
?>
<?php
$max=250;
$src="_img/bars/bar.png";
$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
$isViewedUserVip = isset($db['vip']) && date('Y-m-d H:i:s') < $db['vip'];
?>
<div class="box_top">Avatar de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">
    <div align="center" style="padding: 15px;">
        <div style="display: inline-block; position: relative;">
            <img src="_img/personagens/<?php echo $db['personagem']; ?>/<?php echo $db['avatar']; ?>.jpg" 
                 width="162" height="150" 
                 style="border: 2px solid #555; border-radius: 8px;" 
                 title="<?php echo ucfirst($_GET['view']); ?><?php if($isViewedUserVip) echo ' - VIP'; ?>" />
            <?php if($isViewedUserVip): ?>
            <img src="_img/vip.png" alt="VIP" style="position:absolute;top:6px;left:6px;width:26px;height:26px;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.8));pointer-events:none;" />
            <?php endif; ?>
        </div>
        <?php if(!empty($db['criador_conteudo'])): ?>
        <div style="margin-top:10px;width:100%;text-align:center;">
            <span style="display:inline-block;background:linear-gradient(90deg,#660000 0%,#cc0000 50%,#660000 100%);color:#fff;font-weight:bold;font-size:11px;padding:4px 12px;border:1px solid #FF4444;border-radius:14px;text-shadow:1px 1px 0 #000;box-shadow:0 0 8px rgba(255,0,0,0.5);">
                🎬 CRIADOR DE CONTEÚDO
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>
<div class="box_bottom"></div>
<div class="box_top">Atributos de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">Atributos de combate, nível e experiência de <?php echo ucfirst($_GET['view']); ?>.<div class="sep"></div>
        <?php
                if($db['renegado']=='sim'){
                        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE renegado='sim' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
                        $stmt->execute();
                        $dbx = $stmt->fetch(PDO::FETCH_ASSOC);
                        if($dbx && $dbx['id']==$db['id']) $nivel='Líder da Akatsuki'; else $nivel='Nukenin';
                } else {
                $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE vila=? AND renegado=? ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
                        $stmt->execute([$db['vila'], $db['renegado']]);
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
                                        case 99: $nivel='Hokage'; break;
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
                        case 99: echo '1'; break;
                }
                echo '.jpg) no-repeat right top;"'; } ?>>
                <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td width="20%" align="right" style="padding-right:10px;"><b>Registro:</b></td>
      <td colspan="2"><?php $reg=explode(' ',$db['reg']); $datareg=explode('-',$reg[0]); echo $datareg[2].'/'.$datareg[1].'/'.$datareg[0].', às '.$reg[1]; ?></td>
        </tr>
        <tr>
                <td align="right" style="padding-right:10px;"><b>Personagem:</b></td>
          <td colspan="2"><?php fpersonagem($db['personagem']); ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td width="20%" align="right" style="padding-right:10px;"><b>Vila:</b></td>
      <td colspan="2"><?php echo $txtvila; ?></td>
        </tr>
        <tr>
                <td align="right" style="padding-right:10px;"><b>Clã:</b></td>
          <td colspan="2"><?php if(($db['orgid']==0)or($db['orgid']==-1)) echo '-'; else echo '<a href="?p=vieworg&id='.strtolower($db['orgid']).'">'.$db['orgnome'].'</a>'; ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td align="right" style="padding-right:10px;"><b>Nível:</b></td>
          <td colspan="2"><?php echo $nivel; ?><b> [<?php echo $db['nivel']; ?>]</b></td>
        </tr>
        <tr>
                <td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr style="background-color: #1b1b1a;">
                <td align="right" style="padding-right:10px; background-color: #1b1b1a;"><b>Taijutsu:</b></td>
          <td style="background-color: #1b1b1a;"><img src="_img/NewsBar/Azul/ponta_barra.jpg" height="22" /><?php
                        if($array[0] > 0) {
                                if($array[0]==$array2["t"]) echo '<img src="_img/NewsBar/Azul/barra_centro.jpg" width="'.$max.'" height="22" />'; else
                                if($array[1]==$array2["t"]) echo '<img src="_img/NewsBar/Azul/barra_centro.jpg" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
                                if($array[2]==$array2["t"]) echo '<img src="_img/NewsBar/Azul/barra_centro.jpg" width="'.($max*$array[2])/$array[0].'" height="22" />';
                        }
                        ?><img src="_img/NewsBar/Azul/fim_barra.jpg" height="22" />
                </td>
            <td width="25%" style="background-color: #1b1b1a;"><b>| <?php echo $db['taijutsu']; ?> |</b></td>
        </tr>
        <tr style="background-color: #1b1b1a;">
                <td align="right" style="padding-right:10px; background-color: #1b1b1a;"><b>Ninjutsu:</b></td>
          <td style="background-color: #1b1b1a;"><img src="_img/NewsBar/Roxo/ponta_barra.jpg" height="22" /><?php
                        if($array[0] > 0) {
                                if($array[0]==$array2["n"]) echo '<img src="_img/NewsBar/Roxo/barra_centro.jpg" width="'.$max.'" height="22" />'; else
                                if($array[1]==$array2["n"]) echo '<img src="_img/NewsBar/Roxo/barra_centro.jpg" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
                                if($array[2]==$array2["n"]) echo '<img src="_img/NewsBar/Roxo/barra_centro.jpg" width="'.($max*$array[2])/$array[0].'" height="22" />';
                        }
                        ?><img src="_img/NewsBar/Roxo/fim_barra.jpg" height="22" />
            </td>
            <td style="background-color: #1b1b1a;"><b>| <?php echo $db['ninjutsu']; ?> |</b></td>
        </tr>
        <tr style="background-color: #1b1b1a;">
                <td align="right" style="padding-right:10px; background-color: #1b1b1a;"><b>Genjutsu:</b></td>
          <td style="background-color: #1b1b1a;"><img src="_img/NewsBar/Verde/ponta_barra.jpg" height="22" /><?php
                        if($array[0] > 0) {
                                if($array[0]==$array2["g"]) echo '<img src="_img/NewsBar/Verde/barra_centro.jpg" width="'.$max.'" height="22" />'; else
                                if($array[1]==$array2["g"]) echo '<img src="_img/NewsBar/Verde/barra_centro.jpg" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
                                if($array[2]==$array2["g"]) echo '<img src="_img/NewsBar/Verde/barra_centro.jpg" width="'.($max*$array[2])/$array[0].'" height="22" />';
                        }
                        ?><img src="_img/NewsBar/Verde/fim_barra.jpg" height="22" />
          </td>
            <td style="background-color: #1b1b1a;"><b>| <?php echo $db['genjutsu']; ?> |</b></td>
        </tr>
        <tr>
                <td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr style="background-color: #1b1b1a;">
                <td align="right" style="padding-right:10px; background-color: #1b1b1a;"><b>Experiência:</b></td>
          <td style="background-color: #1b1b1a;"><img src="_img/NewsBar/Vermelha/ponta_barra.jpg" height="22" /><?php
                        if($db['exp'] > 0 && $db['expmax'] > 0) {
                                $exp_width = (($db['exp']*$max)/$db['expmax']);
                                echo '<img src="_img/NewsBar/Vermelha/barra_centro.jpg" width="'.$exp_width.'" height="22" />';
                        }
                        ?><img src="_img/NewsBar/Vermelha/fim_barra.jpg" height="22" /></td>
            <td style="background-color: #1b1b1a;"><b>| <?php echo $db['exp']; ?> / <?php echo $db['expmax']; ?> |</b></td>
        </tr>
    </table>
    <?php if($db['id']<>$_SESSION['logado']){ ?>
    <div class="sep"></div>
    <div align="center">
        <form method="post" action="?p=hunt" onsubmit="subm.value='Carregando...';subm.disabled=true;">
                <?php
        $stmt = $conexao->prepare("SELECT count(id) as conta FROM amigos WHERE usuarioid=? AND amigoid=?");
                $stmt->execute([$_SESSION['logado'], $db['id']]);
                $dbf = $stmt->fetch(PDO::FETCH_ASSOC);
        if($dbf['conta']==0){ ?><input type="button" class="botao" value="Buddy List" style="margin-right:15px;" onclick="location.href='?p=addfriend&id=<?php echo $_GET['view']; ?>'" />
                <?php } ?>
        <?php
        $stmt = $conexao->prepare("SELECT count(id) as conta FROM book WHERE usuarioid=? AND inimigoid=?");
                $stmt->execute([$_SESSION['logado'], $db['id']]);
                $dbf = $stmt->fetch(PDO::FETCH_ASSOC);
        if($dbf['conta']==0){ ?><input type="button" class="botao" value="Bingo Book" style="margin-right:15px;" onclick="location.href='?p=addbook&id=<?php echo $_GET['view']; ?>'" />
                <?php } ?>
        <input type="hidden" id="hunt_tipo" name="hunt_tipo" value="<?php echo $c->encode('1',$chaveuniversal); ?>" />
        <input type="hidden" id="hunt_1" name="hunt_1" value="<?php echo $_GET['view']; ?>" />
        <input type="button" class="botao" value="Mensagem" style="margin-right:15px;" onclick="location.href='?p=messages&destiny=<?php echo ucfirst($_GET['view']); ?>'" /> <input type="submit" id="subm" name="subm" class="botao" value="Atacar" />
        </form>
    </div>
    <?php } ?>
</div>
<div class="box_bottom"></div>
<div class="box_top">Estatísticas de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">Estatísticas da conta de <?php echo ucfirst($_GET['view']); ?>.<div class="sep"></div>
        <div style="background:url(_img/stats.jpg) no-repeat right top;height:120px;">
        <table width="60%" cellpadding="0" cellspacing="0">
        <tr style="background:url(_img/gradient.jpg) right;">
                <td style="padding-left:3px;"><b>Yens Faturados</b></td>
            <td><?php echo number_format($db['yens_fat'],2,',','.'); ?> yens</td>
        </tr>
        <tr>
                <td style="padding-left:3px;"><b>Yens Perdidos</b></td>
            <td><?php echo number_format($db['yens_perd'],2,',','.'); ?> yens</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) right;">
                <td style="padding-left:3px;"><b>Batalhas</b></td>
            <td><?php echo $db['batalhas']; ?> batalhas</td>
        </tr>
        <tr>
                <td style="padding-left:3px;"><b>Vitórias</b></td>
            <td><?php echo $db['vitorias']; ?> vitórias</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) right;">
                <td style="padding-left:3px;"><b>Derrotas</b></td>
            <td><?php echo $db['derrotas']; ?> derrotas</td>
        </tr>
        <tr>
                <td style="padding-left:3px;"><b>Empates</b></td>
            <td><?php echo $db['empates']; ?> empates</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) right;">
                <td style="padding-left:3px;"><b>Experiência Total</b></td>
            <td><?php echo $db['exptotal']; ?> pontos</td>
        </tr>
    </table>
    </div>
</div>
<div class="box_bottom"></div>
<div class="box_top">Apresentação de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">
    <div align="center"><?php if(isset($_GET['report'])) echo '<div class="aviso">Obrigado por reportar este perfil.<br />Uma análise será feita na mensagem de apresentação.</div>'; else { ?><a href="?p=spam&amp;id=<?php echo $c->encode($db['id'],$chaveuniversal); ?>&amp;name=<?php echo $c->encode($db['usuario'],$chaveuniversal); ?>">Reportar Perfil</a><br /><span class="sub2">Utilize este link para comunicar nossa equipe sobre o uso indevido desta função.</span><?php } ?></div>
    <div class="sep"></div>
        <div class="apresentacao" style="width:520px;"><?php if($db['config_apresentacao']=='') echo 'Nenhum texto de apresentação.'; else echo str_replace(array('<p>','</p>'),array('','<br />'),$db['config_apresentacao']); ?></div>
</div>
<div class="box_bottom"></div>
<?php if($db['doujutsu']>0) require_once('view_doujutsu.php'); ?>
<?php require_once('view_shop.php'); ?>
<?php
/* ===========================================================
 * Histórico de Vendas — últimas 10 vendas do dono do perfil
 * Lê da tabela mercado_historico (registrada em shops.php / viewshop.php).
 * =========================================================== */
$mh_vendas = [];
try {
    $mh_stmt = $conexao->prepare(
        "SELECT mh.item_nome, mh.item_imagem, mh.valor, mh.moeda_tipo, mh.data,
                uc.usuario AS comprador
         FROM mercado_historico mh
         LEFT JOIN usuarios uc ON mh.comprador_id = uc.id
         WHERE mh.vendedor_id = ?
         ORDER BY mh.data DESC LIMIT 10"
    );
    $mh_stmt->execute([(int)$db['id']]);
    $mh_vendas = $mh_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $mh_vendas = []; }

$mh_moeda_label = [
    'yens'     => 'Yens',
    'cristal1' => 'Cristal de Chakra Refinado',
    'cristal2' => 'Cristal de Chakra Bruto',
    'cristal3' => 'Chakra Forjado',
];
$mh_moeda_icon = [
    'yens'     => '_img/yens.png',
    'cristal1' => '_img/ferreiro/Cristal de Chakra Refinado.png',
    'cristal2' => '_img/ferreiro/Cristal de Chakra Bruto.png',
    'cristal3' => '_img/ferreiro/Chakra Forjado.png',
];
?>
<div class="box_top">📜 Histórico de Vendas de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">
    <?php if (empty($mh_vendas)): ?>
        <div align="center"><i>Nenhuma venda registrada no mercado dos jogadores.</i></div>
    <?php else: ?>
        <span class="sub2">Últimas <?php echo count($mh_vendas); ?> vendas concluídas no mercado dos jogadores.</span>
        <div class="sep"></div>
        <table width="100%" cellpadding="4" cellspacing="1">
            <tr class="table_dados" style="background:#1a1a1a;color:#FFD700;">
                <td>Item</td>
                <td>Comprador</td>
                <td align="right">Preço</td>
                <td align="right">Quando</td>
            </tr>
            <?php foreach ($mh_vendas as $v):
                $mt = $v['moeda_tipo'];
                $micn = $mh_moeda_icon[$mt] ?? '_img/yens.png';
                $mlbl = $mh_moeda_label[$mt] ?? $mt;
            ?>
                <tr class="table_dados" style="background:#2a2a2a;">
                    <td>
                        <?php if (!empty($v['item_imagem'])): ?>
                            <img src="_img/equipamentos/<?php echo htmlspecialchars($v['item_imagem']); ?>.png" width="20" height="20" align="absmiddle" />
                        <?php endif; ?>
                        <?php echo htmlspecialchars($v['item_nome']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($v['comprador'] ?? '—'); ?></td>
                    <td align="right" title="<?php echo htmlspecialchars($mlbl); ?>">
                        <b><?php echo number_format((float)$v['valor'], 0, ',', '.'); ?></b>
                        <img src="<?php echo $micn; ?>" width="14" height="14" align="absmiddle" />
                    </td>
                    <td align="right"><span class="sub2"><?php echo htmlspecialchars($v['data']); ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>
<?php
/* ===========================================================
 * Histórico de Batalhas PVP — últimas 10 batalhas do perfil
 * =========================================================== */
$hist_batalhas = [];
try {
    $hist_stmt = $conexao->prepare(
        "SELECT r.data, r.vencedor, r.exp, r.yens,
                r.usuarioid, r.inimigoid,
                ua.usuario AS nome_atacante,
                ui.usuario AS nome_inimigo
         FROM relatorios r
         LEFT JOIN usuarios ua ON r.usuarioid = ua.id
         LEFT JOIN usuarios ui ON r.inimigoid = ui.id
         WHERE r.usuarioid = ? OR r.inimigoid = ?
         ORDER BY r.data DESC LIMIT 10"
    );
    $hist_stmt->execute([(int)$db['id'], (int)$db['id']]);
    $hist_batalhas = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $hist_batalhas = []; }
?>
<div class="box_top">Histórico de Batalhas de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">
<?php if (empty($hist_batalhas)): ?>
    <div align="center"><i>Nenhuma batalha registrada.</i></div>
<?php else: ?>
    <span class="sub2">Últimas <?php echo count($hist_batalhas); ?> batalhas registradas.</span>
    <div class="sep"></div>
    <table width="100%" cellpadding="4" cellspacing="1">
        <tr class="table_dados" style="background:#1a1a1a;color:#FFD700;">
            <td>Resultado</td>
            <td>Adversário</td>
            <td align="right">EXP</td>
            <td align="right">Yens</td>
            <td align="right">Data</td>
        </tr>
        <?php foreach ($hist_batalhas as $bt):
            $eu_ganhei  = (int)$bt['vencedor'] === (int)$db['id'];
            $empate     = ((int)$bt['vencedor'] === 0);
            if ($empate) {
                $res_txt = 'Empate';
                $res_cor = '#aaa';
                $res_bg  = '#1e1e1e';
            } elseif ($eu_ganhei) {
                $res_txt = 'Vitória';
                $res_cor = '#90EE90';
                $res_bg  = '#0a1a0a';
            } else {
                $res_txt = 'Derrota';
                $res_cor = '#ff9999';
                $res_bg  = '#1a0a0a';
            }
            // Adversário = quem NÃO é o dono do perfil
            if ((int)$bt['usuarioid'] === (int)$db['id']) {
                $adv_nome = $bt['nome_inimigo'] ?? '?';
            } else {
                $adv_nome = $bt['nome_atacante'] ?? '?';
            }
            // Formatar data
            $data_fmt = $bt['data'] ? date('d/m/y H:i', strtotime($bt['data'])) : '—';
        ?>
        <tr class="table_dados" style="background:<?php echo $res_bg; ?>;">
            <td style="color:<?php echo $res_cor; ?>;font-weight:bold;"><?php echo $res_txt; ?></td>
            <td>
                <?php if ($adv_nome && $adv_nome !== '?'): ?>
                    <a href="?p=view&view=<?php echo htmlspecialchars($adv_nome); ?>"><?php echo htmlspecialchars($adv_nome); ?></a>
                <?php else: ?>
                    <span style="color:#555;">—</span>
                <?php endif; ?>
            </td>
            <td align="right" style="color:#FFD700;">
                <?php echo $bt['exp'] > 0 ? '+'.number_format((int)$bt['exp'],0,'.',',') : '—'; ?>
                <?php if ($bt['exp'] > 0): ?>
                <img src="_img/Icones/experiencia.png" style="width:12px;height:12px;vertical-align:middle;">
                <?php endif; ?>
            </td>
            <td align="right">
                <?php echo $bt['yens'] > 0 ? number_format((float)$bt['yens'],2,',','.') : '—'; ?>
                <?php if ($bt['yens'] > 0): ?>
                <img src="_img/yens.png" style="width:12px;height:12px;vertical-align:middle;">
                <?php endif; ?>
            </td>
            <td align="right"><span class="sub2"><?php echo $data_fmt; ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</div>
<div class="box_bottom"></div>

<?php
/* ===========================================================
 * Conquistas PVP — baseadas em vitorias/derrotas/batalhas e relatorios
 * =========================================================== */
$pvp_vit   = (int)($db['vitorias']  ?? 0);
$pvp_der   = (int)($db['derrotas']  ?? 0);
$pvp_bat   = (int)($db['batalhas']  ?? 0);
$pvp_emp   = (int)($db['empates']   ?? 0);
$pvp_nivel = (int)($db['nivel']     ?? 1);

// Query 1: Últimas 5 batalhas para checar sequência de vitórias
$ultimas5 = [];
try {
    $s5 = $conexao->prepare(
        "SELECT vencedor FROM relatorios
         WHERE usuarioid = ? OR inimigoid = ?
         ORDER BY data DESC LIMIT 5"
    );
    $s5->execute([(int)$db['id'], (int)$db['id']]);
    $ultimas5 = $s5->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

// Query 2: Vitória contra ninja de nível maior (nivel atual do oponente)
$ganhou_de_mais_forte = false;
try {
    $sfe = $conexao->prepare(
        "SELECT COUNT(*) FROM relatorios r
         INNER JOIN usuarios u ON (
             (r.usuarioid = ? AND r.inimigoid = u.id)
             OR (r.inimigoid = ? AND r.usuarioid = u.id)
         )
         WHERE r.vencedor = ? AND u.nivel > ?"
    );
    $sfe->execute([(int)$db['id'], (int)$db['id'], (int)$db['id'], $pvp_nivel]);
    $ganhou_de_mais_forte = (int)$sfe->fetchColumn() > 0;
} catch (Throwable $e) {}

// Query 3: Batalhas nos últimos 7 dias e se todas foram vitórias
$bat_semana = 0; $vit_semana = 0;
try {
    $semana_cond = Database::isMysql()
        ? "AND data >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        : "AND data >= DATETIME('now','-7 days')";
    $ssem = $conexao->prepare(
        "SELECT COUNT(*) as total,
                SUM(CASE WHEN vencedor = ? THEN 1 ELSE 0 END) as ganhou
         FROM relatorios WHERE (usuarioid = ? OR inimigoid = ?) $semana_cond"
    );
    $ssem->execute([(int)$db['id'], (int)$db['id'], (int)$db['id']]);
    $rsem = $ssem->fetch(PDO::FETCH_ASSOC);
    $bat_semana = (int)($rsem['total'] ?? 0);
    $vit_semana = (int)($rsem['ganhou'] ?? 0);
} catch (Throwable $e) {}

// ── Definir conquistas ────────────────────────────────────────────────────────
// Cada conquista: [título, descrição, ícone_cor, condição]
$conquistas_def = [
    'primeira_vitoria'  => ['Primeira Vitória',      'Venceu sua primeira batalha',                '#FFD700', $pvp_vit >= 1],
    'veterano'          => ['Veterano',               '10 vitórias conquistadas',                   '#87CEFA', $pvp_vit >= 10],
    'guerreiro'         => ['Guerreiro',              '50 vitórias conquistadas',                   '#ff6600', $pvp_vit >= 50],
    'lenda_pvp'         => ['Lenda do PVP',           '100 vitórias conquistadas',                  '#cc00cc', $pvp_vit >= 100],
    'mestre_batalhas'   => ['Mestre das Batalhas',    '250 vitórias conquistadas',                  '#ff0000', $pvp_vit >= 250],
    'centuriao'         => ['Centurião',              'Participou de 100+ batalhas',                '#5ecf6e', $pvp_bat >= 100],
    'invicto'           => ['Invicto',                'Nunca sofreu uma derrota (mín. 5 vitórias)', '#FFD700', $pvp_der === 0 && $pvp_vit >= 5],
    'dominante'         => ['Dominante',              '3x mais vitórias que derrotas (mín. 15 vit.)','#ff6600', $pvp_der > 0 && $pvp_vit >= 15 && $pvp_vit >= $pvp_der * 3],
    'serie_vencedora'   => ['Série Vencedora',        'Venceu as últimas 5 batalhas seguidas',       '#90EE90', count($ultimas5) === 5 && !in_array(0, array_map(fn($v) => (int)$v === (int)$db['id'] ? 1 : 0, $ultimas5), true) && count(array_filter($ultimas5, fn($v) => (int)$v === (int)$db['id'])) === 5],
    'cacador_elites'    => ['Caçador de Elites',      'Venceu um ninja de nível superior',           '#c97fd4', $ganhou_de_mais_forte],
    'semana_perfeita'   => ['Semana Perfeita',        'Venceu todas as batalhas dos últimos 7 dias (mín. 5)', '#FFD700', $bat_semana >= 5 && $vit_semana === $bat_semana],
    'perseverante'      => ['Perseverante',           'Continuou lutando após 10+ derrotas',         '#aaa',    $pvp_der >= 10],
];

$conquistadas   = array_filter($conquistas_def, fn($c) => $c[3]);
$nao_conquistadas = array_filter($conquistas_def, fn($c) => !$c[3]);
?>
<div class="box_top">Conquistas PVP de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">
<?php if (empty($conquistadas) && $pvp_bat === 0): ?>
    <div align="center"><i>Este ninja ainda não entrou em batalha.</i></div>
<?php else: ?>
    <style>
    .conquista-badge {
        display:inline-block; margin:4px; padding:6px 10px;
        border-radius:4px; font-size:11px; font-weight:bold;
        border:1px solid; cursor:default; position:relative;
        vertical-align:top; text-align:center; min-width:90px;
    }
    .conquista-badge .badge-titulo { font-size:10px; margin-top:3px; display:block; }
    .conquista-badge .badge-desc { display:none; font-size:10px; white-space:nowrap; }
    .conquista-badge:hover .badge-desc {
        display:block; position:absolute; left:50%; transform:translateX(-50%);
        bottom:calc(100% + 4px); background:#1a1a1a; border:1px solid #555;
        color:#ddd; padding:4px 8px; z-index:99; white-space:nowrap;
        border-radius:3px; font-weight:normal;
    }
    .conquista-locked {
        display:inline-block; margin:4px; padding:6px 10px;
        border-radius:4px; font-size:10px; border:1px solid #333;
        background:#111; color:#444; vertical-align:top;
        text-align:center; min-width:90px;
    }
    </style>

    <?php if (!empty($conquistadas)): ?>
    <div style="margin-bottom:6px;color:#888;font-size:10px;"><?php echo count($conquistadas); ?> conquista(s) desbloqueada(s):</div>
    <div>
    <?php foreach ($conquistadas as $slug => [$titulo, $desc, $cor, ]) : ?>
        <div class="conquista-badge" style="background:<?php echo $cor; ?>22;border-color:<?php echo $cor; ?>;color:<?php echo $cor; ?>;">
            <span style="font-size:16px;">&#9733;</span>
            <span class="badge-titulo"><?php echo htmlspecialchars($titulo); ?></span>
            <span class="badge-desc"><?php echo htmlspecialchars($desc); ?></span>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($nao_conquistadas)): ?>
    <div class="sep"></div>
    <div style="margin-bottom:4px;color:#444;font-size:10px;">Ainda não desbloqueadas:</div>
    <div>
    <?php foreach ($nao_conquistadas as $slug => [$titulo, $desc, , ]) : ?>
        <div class="conquista-locked" title="<?php echo htmlspecialchars($desc); ?>">
            &#128274; <?php echo htmlspecialchars($titulo); ?>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php endif; ?>
</div>
<div class="box_bottom"></div>

<?php if((($db['config_youtube'] ?? '')<>'')&&(($db['config_okyoutube'] ?? '')=='sim')) require_once('view_youtube.php'); ?>
