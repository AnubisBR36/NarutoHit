<?php
require_once('trava.php');

// Verificar se é invasão ou luta normal
$is_invasao = isset($_GET['invasao']) && $_GET['invasao'] == 1;

if($is_invasao) {
    // Buscar invasão ativa do servidor do jogador
    $srv_prepin = isset($_SESSION['servidor_id']) ? (int)$_SESSION['servidor_id'] : 0;
    $stmt = $conexao->prepare("SELECT * FROM invasoes WHERE status = 'ativa' AND servidor_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$srv_prepin]);
    $invasao_ativa = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$invasao_ativa) {
        echo "<script>alert('Nenhuma invasão ativa encontrada!'); window.location.href='?p=invasao';</script>";
        exit;
    }

    if($invasao_ativa['hp_atual'] <= 0) {
        echo "<script>alert('Esta invasão já foi finalizada!'); window.location.href='?p=invasao';</script>";
        exit;
    }

    if($db['energia'] < 25) {
        echo "<script>alert('Você precisa de pelo menos 25 pontos de energia para atacar!'); window.location.href='?p=invasao';</script>";
        exit;
    }

    // Definir sessão de preparação para invasão
    $_SESSION['prepare_invasao'] = $invasao_ativa['id'];

    // Criar dados fictícios do invasor para mostrar na preparação
    $invasor = array(
        'id' => 'invasao_' . $invasao_ativa['id'],
        'usuario' => $invasao_ativa['nome_invasor'],
        'nivel' => $db['nivel'] * 2,
        'taijutsu' => $db['taijutsu'] * 2,
        'ninjutsu' => $db['ninjutsu'] * 2,
        'genjutsu' => $db['genjutsu'] * 2,
        'avatar' => $invasao_ativa['imagem_arquivo'],
        'vila' => 99, // Vila especial para invasão
        'renegado' => 'nao',
        'energia' => 100,
        'energiamax' => 100,
        'personagem' => 'invasor'
    );
} else {
    // Sistema normal de preparação
    if(!isset($_GET['id'])) {
        echo "<script>window.location.href='?p=hunt';</script>";
        exit;
    }

    $inimigo_id = (int)$_GET['id'];

    // Buscar dados do inimigo
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$inimigo_id]);
    $invasor = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$invasor) {
        echo "<script>alert('Usuário não encontrado!'); window.location.href='?p=hunt';</script>";
        exit;
    }

    // Verificações de ataque normal
    if($invasor['energia'] < 25) {
        echo "<script>alert('Este usuário não tem energia suficiente para batalhar!'); window.location.href='?p=hunt';</script>";
        exit;
    }

    if($db['energia'] < 25) {
        echo "<script>alert('Você precisa de pelo menos 25 pontos de energia para atacar!'); window.location.href='?p=hunt';</script>";
        exit;
    }

    $_SESSION['prepare'] = $invasor['id'];
}

// Calcular barras de status do oponente
$array = array("t" => $invasor['taijutsu'], "n" => $invasor['ninjutsu'], "g" => $invasor['genjutsu']);
rsort($array);
$array2 = array("t" => $invasor['taijutsu'], "n" => $invasor['ninjutsu'], "g" => $invasor['genjutsu']);
arsort($array2);
$max = 250;
$src = "_img/bars/bar.png";

// Determinar vila do oponente
if($is_invasao) {
    $txtvila = "Invasor";
} else {
    switch($invasor['vila']) {
        case 1: $txtvila = ($invasor['renegado'] == 'sim') ? 'Akatsuki (Vila da Folha)' : 'Vila da Folha'; break;
        case 2: $txtvila = ($invasor['renegado'] == 'sim') ? 'Akatsuki (Vila da Areia)' : 'Vila da Areia'; break;
        case 3: $txtvila = ($invasor['renegado'] == 'sim') ? 'Akatsuki (Vila do Som)' : 'Vila do Som'; break;
        case 4: $txtvila = ($invasor['renegado'] == 'sim') ? 'Akatsuki (Vila da Chuva)' : 'Vila da Chuva'; break;
        case 5: $txtvila = ($invasor['renegado'] == 'sim') ? 'Akatsuki (Vila da Nuvem)' : 'Vila da Nuvem'; break;
        case 6: $txtvila = ($invasor['renegado'] == 'sim') ? 'Akatsuki (Vila da Névoa)' : 'Vila da Névoa'; break;
        case 8: $txtvila = ($invasor['renegado'] == 'sim') ? 'Akatsuki (Vila da Pedra)' : 'Vila da Pedra'; break;
        default: $txtvila = 'Vila Desconhecida';
    }
}

// Determinar nível/rank do oponente
if($is_invasao) {
    $nivel_oponente = "Invasor Nível " . $invasor['nivel'];
} else {
    require_once('funcoes.php');
    if($invasor['renegado'] == 'sim') {
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE renegado='sim' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
        $stmt->execute();
        $dbx = $stmt->fetch(PDO::FETCH_ASSOC);
        if($dbx['id'] == $invasor['id']) $nivel_oponente = 'Líder da Akatsuki'; 
        else $nivel_oponente = 'Nukenin';
    } else {
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE vila=? ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
        $stmt->execute([$invasor['vila']]);
        $dbx = $stmt->fetch(PDO::FETCH_ASSOC);
        if($dbx['id'] == $invasor['id']) {
            switch($invasor['vila']) {
                case 1: $nivel_oponente = 'Hokage'; break;
                case 2: $nivel_oponente = 'Kazekage'; break;
                case 3: $nivel_oponente = 'Otokage'; break;
                case 4: $nivel_oponente = 'Líder da Vila da Chuva'; break;
                case 5: $nivel_oponente = 'Raikage'; break;
                case 6: $nivel_oponente = 'Mizukage'; break;
                case 8: $nivel_oponente = 'Tsuchikage'; break;
                default: $nivel_oponente = 'Kage';
            }
        } else {
            $nivel_oponente = rankNinja($invasor['nivel']);
        }
    }
}
?>

<div class="box_top"><?php echo ucfirst($invasor['usuario']); ?></div>
<div class="box_middle">
    <div class="sep"></div>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
            <td width="20%" align="right" style="padding-right:10px;"><b>Vila:</b></td>
            <td colspan="2"><?php echo $txtvila; ?></td>
        </tr>
        <?php if(!$is_invasao): ?>
        <tr>
            <td align="right" style="padding-right:10px;"><b>Clã:</b></td>
            <td colspan="2"><?php echo ($invasor['orgid'] == 0 || $invasor['orgid'] == -1) ? '-' : '<a href="?p=vieworg&id='.strtolower($invasor['orgid']).'">'.$invasor['orgnome'].'</a>'; ?></td>
        </tr>
        <?php endif; ?>
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
            <td align="right" style="padding-right:10px;"><b>Nível:</b></td>
            <td colspan="2"><?php echo $nivel_oponente; ?><b> [<?php echo $invasor['nivel']; ?>]</b></td>
        </tr>
         <!-- Avatar do oponente -->
        <tr >
        <td colspan="3">
            <div style="text-align: center; margin: 20px 0;">
                <?php if($is_invasao): ?>
                    <div style="position: relative; display: inline-block;">
                        <img src="_img/Invasao/<?php echo htmlspecialchars($invasor['avatar']); ?>" 
                             style="max-width: 250px; height: auto; border: 5px solid #ff0000; border-radius: 15px; box-shadow: 0 0 20px rgba(255, 0, 0, 0.6);" 
                             alt="<?php echo htmlspecialchars($invasor['usuario']); ?>">
                        <!-- Coroa vermelha para invasor -->
                        <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: linear-gradient(45deg, #ff0000, #cc0000); color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; text-shadow: 1px 1px 2px #000; border: 2px solid #ffff00; box-shadow: 0 0 10px rgba(255, 255, 0, 0.5);">
                            👑 INVASOR LV.<?php echo $invasor['nivel']; ?>
                        </div>
                    </div>
                     <!-- Barras de atributos do invasor -->
                     <div style="margin-top: 15px;">
                         <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                             <tr>
                                 <td width="70px" align="right" style="padding:0; margin:0; background-color:#1b1b1a; white-space:nowrap;"><b>Taijutsu:</b></td>
                                 <td style="background-color:#1b1b1a; padding:0; margin:0; line-height:0;">
                                     <img src="_img/NewsBar/Azul/ponta_barra.jpg" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;" /><?php
                                     $max_atributo = max($invasor['taijutsu'], $invasor['ninjutsu'], $invasor['genjutsu']);
                                     $tai_width = $max_atributo > 0 ? (($invasor['taijutsu'] * 200) / $max_atributo) : 0;
                                     if($tai_width > 0) {
                                         echo '<img src="_img/NewsBar/Azul/barra_centro.jpg" width="'.$tai_width.'" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;"/>';
                                     }
                                     ?><img src="_img/NewsBar/Azul/fim_barra.jpg" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;"/>
                                     <div style="clear:both;"></div>
                                 </td>
                                 <td width="80px" align="center" style="background-color:#1b1b1a; padding:0; margin:0;"><b>| <?php echo number_format($invasor['taijutsu']); ?> |</b></td>
                             </tr>
                             <tr>
                                 <td align="right" style="padding:0; margin:0; background-color:#1b1b1a; white-space:nowrap;"><b>Ninjutsu:</b></td>
                                 <td style="background-color:#1b1b1a; padding:0; margin:0; line-height:0;">
                                     <img src="_img/NewsBar/Roxo/ponta_barra.jpg" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;" /><?php
                                     $nin_width = $max_atributo > 0 ? (($invasor['ninjutsu'] * 200) / $max_atributo) : 0;
                                     if($nin_width > 0) {
                                         echo '<img src="_img/NewsBar/Roxo/barra_centro.jpg" width="'.$nin_width.'" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;"/>';
                                     }
                                     ?><img src="_img/NewsBar/Roxo/fim_barra.jpg" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;"/>
                                     <div style="clear:both;"></div>
                                 </td>
                                 <td align="center" style="background-color:#1b1b1a; padding:0; margin:0;"><b>| <?php echo number_format($invasor['ninjutsu']); ?> |</b></td>
                             </tr>
                             <tr>
                                 <td align="right" style="padding:0; margin:0; background-color:#1b1b1a; white-space:nowrap;"><b>Genjutsu:</b></td>
                                 <td style="background-color:#1b1b1a; padding:0; margin:0; line-height:0;">
                                     <img src="_img/NewsBar/Verde/ponta_barra.jpg" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;" /><?php
                                     $gen_width = $max_atributo > 0 ? (($invasor['genjutsu'] * 200) / $max_atributo) : 0;
                                     if($gen_width > 0) {
                                         echo '<img src="_img/NewsBar/Verde/barra_centro.jpg" width="'.$gen_width.'" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;"/>';
                                     }
                                     ?><img src="_img/NewsBar/Verde/fim_barra.jpg" height="22" style="margin:0; padding:0; border:0; vertical-align:top; display:block; float:left;"/>
                                     <div style="clear:both;"></div>
                                 </td>
                                 <td align="center" style="background-color:#1b1b1a; padding:0; margin:0;"><b>| <?php echo number_format($invasor['genjutsu']); ?> |</b></td>
                             </tr>
                         </table>
                     </div>
                <?php else: ?>
                    <img src="_img/personagens/<?php echo $invasor['personagem']; ?>/<?php echo $invasor['avatar']; ?>.jpg" 
                         style="width: 100px; height: 100px; border: 2px solid #333;" />
                <?php endif; ?>
            </div>
        </td>
    </tr>

        <tr>
            <td colspan="3"><div class="sep"></div></td>
        </tr>
        </table>

    <?php if($is_invasao): ?>
        <div style="text-align: center; margin: 5px 0 0 0;">
            <a href="?p=attackinIn&invasao=1" 
               style="background: linear-gradient(135deg, #ff0000, #cc0000); color: white; padding: 6px 12px; border: 1px solid #ffff00; cursor: pointer; font-size: 11px; font-weight: bold; border-radius: 4px; text-shadow: 1px 1px 2px #000; text-decoration: none; display: inline-block; box-shadow: 0 0 6px rgba(255, 0, 0, 0.4); transition: all 0.3s ease;">
                ⚔️ ATACAR ⚔️
            </a>
        </div>
    <?php else: ?>
        <!-- Sistema anti-bot para PvP normal -->
        <div style="background:#333333;text-align:center; padding: 15px; margin: 20px 0;">
            <?php $_SESSION['bot'] = rand(1,5); ?>
            <b>Sistema Anti-Bot</b>: Para atacar, clique no <b><?php echo $_SESSION['bot']; ?>&ordm;</b> botão.<br />
            <span style="color: #888; font-size: 12px;">Ao errar 2 vezes seguidas, sua conta é deslogada.</span>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=1'" style="margin: 5px;" />
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=2'" style="margin: 5px;" />
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=3'" style="margin: 5px;" />
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=4'" style="margin: 5px;" />
            <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=5'" style="margin: 5px;" />
        </div>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>

<?php if(!$is_invasao): ?>
<!-- Estatísticas do oponente (apenas para PvP normal) -->
<div class="box_top">Estatísticas de <?php echo ucfirst($invasor['usuario']); ?></div>
<div class="box_middle">
    Estatísticas da conta de <?php echo ucfirst($invasor['usuario']); ?>.
    <div class="sep"></div>
    <div style="background:url(_img/stats.jpg) no-repeat right top;height:120px;">
        <table width="60%" cellpadding="0" cellspacing="0">
            <tr style="background:url(_img/gradient.jpg);">
                <td style="padding-left:3px;"><b>Yens Faturados</b></td>
                <td><?php echo number_format($invasor['yens_fat'], 2, ',', '.'); ?> yens</td>
            </tr>
            <tr>
                <td style="padding-left:3px;"><b>Yens Perdidos</b></td>
                <td><?php echo number_format($invasor['yens_perd'] ?? 0, 2, ',', '.'); ?> yens</td>
            </tr>
            <tr style="background:url(_img/gradient.jpg);">
                <td style="padding-left:3px;"><b>Batalhas</b></td>
                <td><?php echo $invasor['batalhas'] ?? 0; ?> batalhas</td>
            </tr>
            <tr>
                <td style="padding-left:3px;"><b>Vitórias</b></td>
                <td><?php echo $invasor['vitorias'] ?? 0; ?> vitórias</td>
            </tr>
            <tr style="background:url(_img/gradient.jpg);">
                <td style="padding-left:3px;"><b>Derrotas</b></td>
                <td><?php echo $invasor['derrotas'] ?? 0; ?> derrotas</td>
            </tr>
            <tr>
                <td style="padding-left:3px;"><b>Empates</b></td>
                <td><?php echo $invasor['empates'] ?? 0; ?> empates</td>
            </tr>
            <tr style="background:url(_img/gradient.jpg);">
                <td style="padding-left:3px;"><b>Experiência Total</b></td>
                <td><?php echo $invasor['exptotal'] ?? 0; ?> pontos</td>
            </tr>
        </table>
    </div>
</div>
<div class="box_bottom"></div>

<!-- Apresentação do jogador -->
<div class="box_top">Apresentação de <?php echo ucfirst($invasor['usuario']); ?></div>
<div class="box_middle">
    <div class="apresentacao">
        <?php 
        if(empty($invasor['config_apresentacao'])) {
            echo 'Nenhum texto de apresentação.';
        } else {
            echo str_replace(array('<p>','</p>'), array('','<br />'), $invasor['config_apresentacao']);
        }
        ?>
    </div>
</div>
<div class="box_bottom"></div>
<?php endif; ?>