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
<?php if((($db['config_youtube'] ?? '')<>'')&&(($db['config_okyoutube'] ?? '')=='sim')) require_once('view_youtube.php'); ?>
