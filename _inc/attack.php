<?php require_once('trava.php'); ?>
<?php require_once('conexao.php'); ?>
<?php require_once('verificar.php'); ?>
<?php require_once('funcoes.php'); ?>
<?php require_once('funcoes_selos.php'); ?>
<?php

// Verificar se é ataque de invasão (não precisa validar anti-bot)
$is_invasao_attack = isset($_GET['invasao']) && $_GET['invasao'] == 1;

if(!$is_invasao_attack) {
    // Sistema anti-bot com sequência de imagens
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){ 
        echo "<script>self.location='?p=home'</script>"; 
        exit; 
    }
    
    if(!isset($_SESSION['errobot'])) $_SESSION['errobot']=0;
    
    // Validar token de segurança
    if(!isset($_POST['bot_token']) || !isset($_SESSION['bot_token']) || $_POST['bot_token'] !== $_SESSION['bot_token']) {
        $_SESSION['errobot']=$_SESSION['errobot']+1;
        if($_SESSION['errobot']>=2){ 
            echo "<script>alert('Token inválido. Conta deslogada por segurança.'); self.location='?p=logout'</script>"; 
            exit; 
        }
        echo "<script>alert('Token inválido!'); self.location='?p=prepare'</script>"; 
        exit;
    }
    
    // Validar sequência de cliques
    if(!isset($_POST['bot_sequence']) || !isset($_SESSION['bot_sequence'])) {
        $_SESSION['errobot']=$_SESSION['errobot']+1;
        if($_SESSION['errobot']>=2){ 
            echo "<script>alert('Sequência inválida. Conta deslogada por segurança.'); self.location='?p=logout'</script>"; 
            exit; 
        }
        echo "<script>alert('Sequência não encontrada!'); self.location='?p=prepare'</script>"; 
        exit;
    }
    
    // Decodificar sequência enviada
    $sequencia_enviada = json_decode($_POST['bot_sequence'], true);
    
    // Validar formato
    if(!is_array($sequencia_enviada) || count($sequencia_enviada) !== count($_SESSION['bot_sequence'])) {
        $_SESSION['errobot']=$_SESSION['errobot']+1;
        if($_SESSION['errobot']>=2){ 
            echo "<script>alert('Formato de sequência inválido. Conta deslogada por segurança.'); self.location='?p=logout'</script>"; 
            exit; 
        }
        echo "<script>alert('Formato de sequência inválido!'); self.location='?p=prepare'</script>"; 
        exit;
    }
    
    // Validar cada índice
    $sequencia_correta = true;
    for($i = 0; $i < count($_SESSION['bot_sequence']); $i++) {
        // Verificar se o índice é válido (0-4)
        if(!is_numeric($sequencia_enviada[$i]) || $sequencia_enviada[$i] < 0 || $sequencia_enviada[$i] > 4) {
            $sequencia_correta = false;
            break;
        }
        
        // Comparar com sequência esperada
        if((int)$sequencia_enviada[$i] !== (int)$_SESSION['bot_sequence'][$i]) {
            $sequencia_correta = false;
            break;
        }
    }
    
    if(!$sequencia_correta) {
        $_SESSION['errobot']=$_SESSION['errobot']+1;
        if($_SESSION['errobot']>=2){ 
            echo "<script>alert('Você errou a sequência 2 vezes. Conta deslogada por segurança.'); self.location='?p=logout'</script>"; 
            exit; 
        }
        echo "<script>alert('Sequência incorreta! Tente novamente.'); self.location='?p=prepare'</script>"; 
        exit;
    }
    
    // Sequência correta - limpar variáveis de sessão do anti-bot
    $_SESSION['errobot']=0;
    unset($_SESSION['bot_images']);
    unset($_SESSION['bot_sequence']);
    unset($_SESSION['bot_token']);
    unset($_SESSION['bot_clicks']);
}

if(!isset($_SESSION['prepare']) && !$is_invasao_attack){ echo "<script>self.location='?p=home'</script>"; exit; }

try {
    $stmt = $conexao->prepare("SELECT u.id, u.usuario, u.yens, u.yens_fat, u.nivel, u.orgid, u.energia, u.energiamax, u.taijutsu, u.ninjutsu, u.genjutsu, u.personagem, u.avatar, u.renegado, u.vila, u.doujutsu, u.doujutsu_nivel, u.doujutsu_exp, u.doujutsu_expmax, u.exp, u.expmax, u.vip, u.missao, u.loginip, u.tipo, u.selo_tipo, u.selo_nivel, u.selo_vitorias, u.energia_travada, o.nivel as orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE u.id=?");
    $stmt->execute([$_SESSION['prepare']]);
    $dbi = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$dbi){ 
        echo "<script>self.location='?p=hunt&msg=1'</script>"; 
        exit; 
    }
} catch(PDOException $e) {
    echo "<script>self.location='?p=home'</script>"; 
    exit;
}
try {
    $stmt_relatorio = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? AND inimigoid=? ORDER BY id DESC LIMIT 1");
    $stmt_relatorio->execute([$db['id'], $dbi['id']]);
    $dbv = $stmt_relatorio->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $dbv = false;
}
$soma=mktime(date('H')-12, date('i'), date('s'));
$penalidade=date('Y-m-d H:i:s',$soma);
if($dbv && $penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=9'</script>"; exit; }

try {
    $stmt_relatorio2 = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? OR inimigoid=? ORDER BY id DESC LIMIT 1");
    $stmt_relatorio2->execute([$dbi['id'], $dbi['id']]);
    $dbv = $stmt_relatorio2->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $dbv = false;
}
$soma=mktime(date('H'), date('i')-30, date('s'));
$penalidade=date('Y-m-d H:i:s',$soma);
if($dbi['tipo']=='player'){
    if($dbv && $penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=8'</script>"; exit; }
}
if($dbi['missao']==999){ echo "<script>self.location='?p=hunt&msg=10'</script>"; exit; }
require_once('verifica_nivelatk.php');
if($dbi['energia']<25){ echo "<script>self.location='?p=hunt&msg=2'</script>"; exit; }
if($db['energia']<25){ echo "<script>self.location='?p=hunt&msg=13'</script>"; exit; }
if($dbi['avatar']==0){ echo "<script>self.location='?p=hunt&msg=12'</script>"; exit; }
if(($dbi['renegado']=='sim')&&($db['renegado']=='sim')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; exit; }
/* Mesma vila é bloqueada, exceto em caças de outros servidores (hunt=6) onde vilas diferentes mundos são válidas */
if(($_SESSION['hunt']??0)!=6 && ($dbi['vila']==$db['vila'])&&($dbi['renegado']=='nao')&&($db['renegado']=='nao')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; exit; }
try {
    $stmt = $conexao->prepare("SELECT i.id, i.upgrade, t.id itemid, t.nome, t.taijutsu, t.ninjutsu, t.genjutsu, t.imagem, t.categoria FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND i.status='on'");
    $stmt->execute([$db['id']]);
    $sqls = $stmt;
} catch (PDOException $e) {
    $sqls = false;
}
// Doujutsu: bônus mínimo de 1 ponto se desbloqueado (consistente com home.php).
if($db['doujutsu']==2){ $txtdoujutsu1='Byakugan'; $addtai1=max(1,(int)round($db['taijutsu']*($db['doujutsu_nivel']/50))); } else $addtai1=0;
if($db['doujutsu']==3){ $txtdoujutsu1='Rinnegan'; $addnin1=max(1,(int)round($db['ninjutsu']*($db['doujutsu_nivel']/50))); } else $addnin1=0;
if($db['doujutsu']==1){ $txtdoujutsu1='Sharingan'; $addgen1=max(1,(int)round($db['genjutsu']*($db['doujutsu_nivel']/50))); } else $addgen1=0;
// Penalidade do Doujutsu player 1: Sharingan/-Tai, Byakugan/-Nin, Rinnegan/-Tai
// Penalidade mínima de 1 ponto quando o doujutsu está desbloqueado.
if($db['doujutsu_nivel']>0){
    if($db['doujutsu']==1) $addtai1 -= max(1, (int)round($db['taijutsu']*($db['doujutsu_nivel']/100)));
    if($db['doujutsu']==2) $addnin1 -= max(1, (int)round($db['ninjutsu']*($db['doujutsu_nivel']/100)));
    if($db['doujutsu']==3) $addtai1 -= max(1, (int)round($db['taijutsu']*($db['doujutsu_nivel']/100)));
}
if(isset($db['orgnivel']) && $db['orgnivel']>0){
    $addtai1=$addtai1+$db['orgnivel'];
    $addnin1=$addnin1+$db['orgnivel'];
    $addgen1=$addgen1+$db['orgnivel'];
}
$equips1='';
$equips2='';
$items1 = [];
$arma1 = 0;
$arma2 = 0;
if($sqls){
    while($dbs=$sqls->fetch(PDO::FETCH_ASSOC)){
        if($equips1=='') $equips1=$dbs['itemid']; else $equips1.=','.$dbs['itemid'];
        $addtai1=$addtai1+$dbs['taijutsu']+$dbs['upgrade'];
        $addnin1=$addnin1+$dbs['ninjutsu']+$dbs['upgrade'];
        $addgen1=$addgen1+$dbs['genjutsu']+$dbs['upgrade'];
        if($dbs['categoria']=='arma') $arma1=1;
        $items1[] = $dbs;
    }
}
try {
    $stmt2 = $conexao->prepare("SELECT i.id, i.upgrade, t.id itemid, t.nome, t.taijutsu, t.ninjutsu, t.genjutsu, t.imagem, t.categoria FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND i.status='on'");
    $stmt2->execute([$dbi['id']]);
    $sqls2 = $stmt2;
} catch (PDOException $e) {
    $sqls2 = false;
}
if($dbi['doujutsu']==2){ $txtdoujutsu2='Byakugan'; $addtai2=max(1,(int)round($dbi['taijutsu']*($dbi['doujutsu_nivel']/50))); } else $addtai2=0;
if($dbi['doujutsu']==3){ $txtdoujutsu2='Rinnegan'; $addnin2=max(1,(int)round($dbi['ninjutsu']*($dbi['doujutsu_nivel']/50))); } else $addnin2=0;
if($dbi['doujutsu']==1){ $txtdoujutsu2='Sharingan'; $addgen2=max(1,(int)round($dbi['genjutsu']*($dbi['doujutsu_nivel']/50))); } else $addgen2=0;
// Penalidade do Doujutsu player 2: Sharingan/-Tai, Byakugan/-Nin, Rinnegan/-Tai
// Penalidade mínima de 1 ponto quando o doujutsu está desbloqueado.
if($dbi['doujutsu_nivel']>0){
    if($dbi['doujutsu']==1) $addtai2 -= max(1, (int)round($dbi['taijutsu']*($dbi['doujutsu_nivel']/100)));
    if($dbi['doujutsu']==2) $addnin2 -= max(1, (int)round($dbi['ninjutsu']*($dbi['doujutsu_nivel']/100)));
    if($dbi['doujutsu']==3) $addtai2 -= max(1, (int)round($dbi['taijutsu']*($dbi['doujutsu_nivel']/100)));
}
if(isset($dbi['orgnivel']) && $dbi['orgnivel']>0){
    $addtai2=$addtai2+$dbi['orgnivel'];
    $addnin2=$addnin2+$dbi['orgnivel'];
    $addgen2=$addgen2+$dbi['orgnivel'];
}
$items2 = [];
if($sqls2){
    while($dbs2=$sqls2->fetch(PDO::FETCH_ASSOC)){
        if($equips2=='') $equips2=$dbs2['itemid']; else $equips2.=','.$dbs2['itemid'];
        $addtai2=$addtai2+$dbs2['taijutsu']+$dbs2['upgrade'];
        $addnin2=$addnin2+$dbs2['ninjutsu']+$dbs2['upgrade'];
        $addgen2=$addgen2+$dbs2['genjutsu']+$dbs2['upgrade'];
        if($dbs2['categoria']=='arma') $arma2=1;
        $items2[] = $dbs2;
    }
}

try {
    $stmt_j = $conexao->prepare("SELECT j.nivel, t.id, t.nome, t.forca, t.texto FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE j.usuarioid=? AND j.status='ativo' ORDER BY RANDOM()");
    $stmt_j->execute([$db['id']]);
    $sqlj = $stmt_j;
    $jutsu1_results = $sqlj->fetchAll(PDO::FETCH_ASSOC);
    $maxj1 = count($jutsu1_results);

    if($maxj1>0){
        $i=1;
        foreach($jutsu1_results as $dbj){
            $idjutsu1[$i]=$dbj['id'];
            $nomejutsu1[$i]=$dbj['nome'];
            $forcajutsu1[$i]=$dbj['forca'];
            $niveljutsu1[$i]=$dbj['nivel'];
            $textojutsu1[$i]=$dbj['texto'];
            $i++;
        }
    }
} catch (PDOException $e) {
    $maxj1 = 0;
}

try {
    $stmt_j2 = $conexao->prepare("SELECT j.nivel, t.id, t.nome, t.forca, t.texto FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE j.usuarioid=? AND j.status='ativo' ORDER BY RANDOM()");
    $stmt_j2->execute([$dbi['id']]);
    $sqlj2 = $stmt_j2;
    $jutsu2_results = $sqlj2->fetchAll(PDO::FETCH_ASSOC);
    $maxj2 = count($jutsu2_results);

    if($maxj2>0){
        $i=1;
        foreach($jutsu2_results as $dbj2){
            $idjutsu2[$i]=$dbj2['id'];
            $nomejutsu2[$i]=$dbj2['nome'];
            $forcajutsu2[$i]=$dbj2['forca'];
            $niveljutsu2[$i]=$dbj2['nivel'];
            $textojutsu2[$i]=$dbj2['texto'];
            $i++;
        }
    }
} catch (PDOException $e) {
    $maxj2 = 0;
}
/*do{
    echo $dbj['nome'].'.......';
} while($dbj=mysql_fetch_assoc($sqlj));*/
?>
<?php
switch($db['vila']){
    case 1: $vila='folha'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Folha)'; else $txtvila='Vila da Folha'; break;
    case 2: $vila='areia'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Areia)'; else $txtvila='Vila da Areia'; break;
    case 3: $vila='som'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila do Som)'; else $txtvila='Vila do Som'; break;
    case 4: $vila='chuva'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Chuva)'; else $txtvila='Vila da Chuva'; break;
    case 5: $vila='nuvem'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Nuvem)'; else $txtvila='Vila da Nuvem'; break;
    case 6: $vila='nevoa'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Névoa)'; else $txtvila='Vila da Névoa'; break;
    case 8: $vila='pedra'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Pedra)'; else $txtvila='Vila da Pedra'; break;
    case 99: $vila='folha'; $txtvila='Vila da Pedra'; break;
} ?>
<?php
switch($dbi['vila']){
    case 1: $vilai='folha'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Folha)'; else $txtvilai='Vila da Folha'; break;
    case 2: $vilai='areia'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Areia)'; else $txtvilai='Vila da Areia'; break;
    case 3: $vilai='som'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila do Som)'; else $txtvilai='Vila do Som'; break;
    case 4: $vilai='chuva'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Chuva)'; else $txtvilai='Vila da Chuva'; break;
    case 5: $vilai='nuvem'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Nuvem)'; else $txtvilai='Vila da Nuvem'; break;
    case 6: $vilai='nevoa'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Névoa)'; else $txtvilai='Vila da Névoa'; break;
    case 8: $vilai='pedra'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Pedra)'; else $txtvilai='Vila da Pedra'; break;
    case 99: $vilai='folha'; $txtvilai='Vila da Pedra'; break;
} ?>
<?php
$bonus1 = aplicarBonusSelo($db);
$bonus2 = aplicarBonusSelo($dbi);

// Buffs de cristal ativos
$buff1 = null; $buff2 = null;
try {
    $sb = $conexao->prepare("SELECT tipo_buff, pct FROM buff_ativos WHERE usuarioid = ? AND expira_em > CURRENT_TIMESTAMP");
    $sb->execute([$db['id']]); $buff1 = $sb->fetch(PDO::FETCH_ASSOC);
    $sb->execute([$dbi['id']]); $buff2 = $sb->fetch(PDO::FETCH_ASSOC);
    // Remove buffs expirados
    $conexao->exec("DELETE FROM buff_ativos WHERE expira_em <= CURRENT_TIMESTAMP");
} catch (PDOException $e) {}

function aplicarCristalBuff($stat, $tipo_stat, $buff) {
    if (!$buff || $buff['tipo_buff'] !== $tipo_stat) return $stat;
    return round($stat * (1 + $buff['pct'] / 100));
}

$tai1_final = round((aplicarCristalBuff($db['taijutsu'], 'taijutsu', $buff1)+$addtai1) * $bonus1['taijutsu_mult']);
$nin1_final = round((aplicarCristalBuff($db['ninjutsu'], 'ninjutsu', $buff1)+$addnin1) * $bonus1['ninjutsu_mult']);
$gen1_final = round((aplicarCristalBuff($db['genjutsu'], 'genjutsu', $buff1)+$addgen1) * $bonus1['genjutsu_mult']);

$tai2_final = round((aplicarCristalBuff($dbi['taijutsu'], 'taijutsu', $buff2)+$addtai2) * $bonus2['taijutsu_mult']);
$nin2_final = round((aplicarCristalBuff($dbi['ninjutsu'], 'ninjutsu', $buff2)+$addnin2) * $bonus2['ninjutsu_mult']);
$gen2_final = round((aplicarCristalBuff($dbi['genjutsu'], 'genjutsu', $buff2)+$addgen2) * $bonus2['genjutsu_mult']);

$tai=$tai1_final.','.$tai2_final;
$nin=$nin1_final.','.$nin2_final;
$gen=$gen1_final.','.$gen2_final;
$ene=$db['energia'].' / '.$db['energiamax'].','.$dbi['energia'].' / '.$dbi['energiamax'];
?>
<div class="box_top"><?php echo $db['usuario']; ?> x <?php echo $dbi['usuario']; ?></div>
<div class="box_middle">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td colspan="2" align="center"><b><?php echo $db['usuario']; ?></b></td>
            <td align="center">&nbsp;</td>
            <td colspan="2" align="center"><b><?php echo $dbi['usuario']; ?></b></td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr>
            <td colspan="2" align="center" width="42%" style="background:url(_img/personagens/no_avatar.jpg) no-repeat center #444444;">
                <img src="_img/personagens/<?php echo $db['personagem']; ?>/<?php echo $db['avatar']; ?>.jpg" onerror="this.onerror=null;this.src='_img/personagens/no_avatar.jpg';" />
            </td>
            <td align="center" width="16%"><img src="_img/versus.jpg" /></td>
            <td colspan="2" align="center" width="42%" style="background:url(_img/personagens/no_avatar.jpg) no-repeat center #444444;">
                <img src="_img/personagens/<?php echo $dbi['personagem']; ?>/<?php echo $dbi['avatar']; ?>.jpg" onerror="this.onerror=null;this.src='_img/personagens/no_avatar.jpg';" />
            </td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#444444" align="center"><img src="_img/<?php echo (!isset($db['bandana_estilo']) || $db['bandana_estilo'] == 'classico') ? 'vilas' : 'NewsVilas'; ?>/<?php if($db['renegado']=='sim') echo 'akatsuki'; else echo $vila; ?><?php if((!isset($db['bandana_estilo']) || $db['bandana_estilo'] == 'classico') && $db['renegado']=='sim') echo '_folha'; ?>.jpg" style="width: 117px; height: 55px;" /></td>
            <td align="center">&nbsp;</td>
            <td colspan="2" bgcolor="#444444" align="center"><img src="_img/<?php echo (!isset($dbi['bandana_estilo']) || $dbi['bandana_estilo'] == 'classico') ? 'vilas' : 'NewsVilas'; ?>/<?php if($dbi['renegado']=='sim') echo 'akatsuki'; else echo $vilai; ?><?php if((!isset($dbi['bandana_estilo']) || $dbi['bandana_estilo'] == 'classico') && $dbi['renegado']=='sim') echo '_' . $vilai; ?>.jpg" style="width: 117px; height: 55px;" /></td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr style="height:17px;">
            <td width="20%" align="right" style="background:#323232;padding-right:10px;">Nível:</td>
            <td width="22%" style="background:#323232;"><?php if($db['renegado']=='sim') echo 'Nukenin'; else rankNinja($db['nivel']); ?> <b>[Nível <?php echo $db['nivel']; ?>]</b></td>
            <td width="16%"></td>
            <td width="20%" align="right" style="background:#323232;padding-right:10px;">Nível:</td>
            <td width="22%" style="background:#323232;"><?php if($dbi['renegado']=='sim') echo 'Nukenin'; else rankNinja($dbi['nivel']); ?> <b>[Nível <?php echo $dbi['nivel']; ?>]</b></td>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#2C2C2C;padding-right:5px;white-space:nowrap;"><img src="_img/Icones/tai.png" style="width:14px;height:14px;vertical-align:middle;margin-right:2px;">Tai:</td>
            <td style="background:#2C2C2C;white-space:nowrap;font-size:11px;"><b><?php echo $db['taijutsu']; ?></b><?php if($addtai1>0) echo '<span style="color:#87CEEB;">+'.$addtai1.'</span>'; ?><?php if($bonus1['taijutsu_mult'] != 1.0) { $bonus_tai1 = round(($db['taijutsu']+$addtai1) * ($bonus1['taijutsu_mult'] - 1)); if($bonus_tai1 > 0) echo '<span style="color:#FFD700;">+'.$bonus_tai1.'</span>'; else echo '<span style="color:#FF6347;">'.$bonus_tai1.'</span>'; } ?>=<b style="color:#00FF00;"><?php echo $tai1_final; ?></b></td>
            <td></td>
            <td align="right" style="background:#2C2C2C;padding-right:5px;white-space:nowrap;"><img src="_img/Icones/tai.png" style="width:14px;height:14px;vertical-align:middle;margin-right:2px;">Tai:</td>
            <td style="background:#2C2C2C;white-space:nowrap;font-size:11px;"><b><?php echo $dbi['taijutsu']; ?></b><?php if($addtai2>0) echo '<span style="color:#87CEEB;">+'.$addtai2.'</span>'; ?><?php if($bonus2['taijutsu_mult'] != 1.0) { $bonus_tai2 = round(($dbi['taijutsu']+$addtai2) * ($bonus2['taijutsu_mult'] - 1)); if($bonus_tai2 > 0) echo '<span style="color:#FFD700;">+'.$bonus_tai2.'</span>'; else echo '<span style="color:#FF6347;">'.$bonus_tai2.'</span>'; } ?>=<b style="color:#00FF00;"><?php echo $tai2_final; ?></b></td>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#323232;padding-right:5px;white-space:nowrap;"><img src="_img/Icones/nin.png" style="width:14px;height:14px;vertical-align:middle;margin-right:2px;">Nin:</td>
            <td style="background:#323232;white-space:nowrap;font-size:11px;"><b><?php echo $db['ninjutsu']; ?></b><?php if($addnin1>0) echo '<span style="color:#87CEEB;">+'.$addnin1.'</span>'; ?><?php if($bonus1['ninjutsu_mult'] != 1.0) { $bonus_nin1 = round(($db['ninjutsu']+$addnin1) * ($bonus1['ninjutsu_mult'] - 1)); if($bonus_nin1 > 0) echo '<span style="color:#FFD700;">+'.$bonus_nin1.'</span>'; else echo '<span style="color:#FF6347;">'.$bonus_nin1.'</span>'; } ?>=<b style="color:#00FF00;"><?php echo $nin1_final; ?></b></td>
            <td></td>
            <td align="right" style="background:#323232;padding-right:5px;white-space:nowrap;"><img src="_img/Icones/nin.png" style="width:14px;height:14px;vertical-align:middle;margin-right:2px;">Nin:</td>
            <td style="background:#323232;white-space:nowrap;font-size:11px;"><b><?php echo $dbi['ninjutsu']; ?></b><?php if($addnin2>0) echo '<span style="color:#87CEEB;">+'.$addnin2.'</span>'; ?><?php if($bonus2['ninjutsu_mult'] != 1.0) { $bonus_nin2 = round(($dbi['ninjutsu']+$addnin2) * ($bonus2['ninjutsu_mult'] - 1)); if($bonus_nin2 > 0) echo '<span style="color:#FFD700;">+'.$bonus_nin2.'</span>'; else echo '<span style="color:#FF6347;">'.$bonus_nin2.'</span>'; } ?>=<b style="color:#00FF00;"><?php echo $nin2_final; ?></b></td>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#2C2C2C;padding-right:5px;white-space:nowrap;"><img src="_img/Icones/gen.png" style="width:14px;height:14px;vertical-align:middle;margin-right:2px;">Gen:</td>
            <td style="background:#2C2C2C;white-space:nowrap;font-size:11px;"><b><?php echo $db['genjutsu']; ?></b><?php if($addgen1>0) echo '<span style="color:#87CEEB;">+'.$addgen1.'</span>'; ?><?php if($bonus1['genjutsu_mult'] != 1.0) { $bonus_gen1 = round(($db['genjutsu']+$addgen1) * ($bonus1['genjutsu_mult'] - 1)); if($bonus_gen1 > 0) echo '<span style="color:#FFD700;">+'.$bonus_gen1.'</span>'; else echo '<span style="color:#FF6347;">'.$bonus_gen1.'</span>'; } ?>=<b style="color:#00FF00;"><?php echo $gen1_final; ?></b></td>
            <td></td>
            <td align="right" style="background:#2C2C2C;padding-right:5px;white-space:nowrap;"><img src="_img/Icones/gen.png" style="width:14px;height:14px;vertical-align:middle;margin-right:2px;">Gen:</td>
            <td style="background:#2C2C2C;white-space:nowrap;font-size:11px;"><b><?php echo $dbi['genjutsu']; ?></b><?php if($addgen2>0) echo '<span style="color:#87CEEB;">+'.$addgen2.'</span>'; ?><?php if($bonus2['genjutsu_mult'] != 1.0) { $bonus_gen2 = round(($dbi['genjutsu']+$addgen2) * ($bonus2['genjutsu_mult'] - 1)); if($bonus_gen2 > 0) echo '<span style="color:#FFD700;">+'.$bonus_gen2.'</span>'; else echo '<span style="color:#FF6347;">'.$bonus_gen2.'</span>'; } ?>=<b style="color:#00FF00;"><?php echo $gen2_final; ?></b></td>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#323232;padding-right:10px;">Experiência:</td>
            <td style="background:#323232;"><b>[<?php echo $db['exp']; ?> / <?php echo $db['expmax']; ?>]</b></td>
            <td></td>
            <td align="right" style="background:#323232;padding-right:10px;">Experiência:</td>
            <td style="background:#323232;"><b>[<?php echo $dbi['exp']; ?> / <?php echo $dbi['expmax']; ?>]</b></td>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Energia:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $db['energia']; ?> / <?php echo $db['energiamax']; ?>]</b></td>
            <td></td>
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Energia:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $dbi['energia']; ?> / <?php echo $dbi['energiamax']; ?>]</b></td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#323232" style="text-align:center; padding:4px;">
                <?php
                function upgradeColorAtk($upg) {
                    if($upg <= 0)  return '#666';
                    if($upg <= 6)  return '#2ecc71';
                    if($upg <= 9)  return '#3498db';
                    if($upg <= 14) return '#9b59b6';
                    return '#ffd700';
                }
                if(empty($items1)) {
                    echo 'Nenhum equipamento.';
                } else {
                    foreach($items1 as $dbs) {
                        $upg = intval($dbs['upgrade']);
                        $cor = upgradeColorAtk($upg);
                        $bw  = $upg >= 15 ? '3px' : '2px';
                        $tip = '<b>'.htmlspecialchars($dbs['nome']);
                        if($upg > 0) $tip .= ' +'.$upg;
                        $tip .= '</b><br />';
                        if($dbs['taijutsu']>0) $tip .= '[+'.($dbs['taijutsu']+$upg).'] em Taijutsu';
                        if($dbs['ninjutsu']>0) $tip .= '<br />[+'.($dbs['ninjutsu']+$upg).'] em Ninjutsu';
                        if($dbs['genjutsu']>0) $tip .= '<br />[+'.($dbs['genjutsu']+$upg).'] em Genjutsu';
                        echo '<div style="display:inline-block;position:relative;margin:2px;border:'.$bw.' solid '.$cor.';border-radius:4px;background:#1a1a1a;">';
                        echo '<img src="_img/equipamentos/'.htmlspecialchars($dbs['imagem']).'.png" width="45" height="45" style="display:block;object-fit:contain;" />';
                        if($upg > 0) echo '<span style="position:absolute;bottom:0;right:0;background:'.$cor.';color:#000;font-size:9px;font-weight:bold;line-height:1;padding:1px 2px;border-radius:3px 0 0 0;">+'.$upg.'</span>';
                        echo '</div>';
                    }
                } ?>
            </td>
            <td align="center"></td>
            <td colspan="2" bgcolor="#323232" style="text-align:center; padding:4px;">
                <?php
                if(empty($items2)) {
                    echo 'Nenhum equipamento.';
                } else {
                    foreach($items2 as $dbs2) {
                        $upg = intval($dbs2['upgrade']);
                        $cor = upgradeColorAtk($upg);
                        $bw  = $upg >= 15 ? '3px' : '2px';
                        $tip = '<b>'.htmlspecialchars($dbs2['nome']);
                        if($upg > 0) $tip .= ' +'.$upg;
                        $tip .= '</b><br />';
                        if($dbs2['taijutsu']>0) $tip .= '[+'.($dbs2['taijutsu']+$upg).'] em Taijutsu';
                        if($dbs2['ninjutsu']>0) $tip .= '<br />[+'.($dbs2['ninjutsu']+$upg).'] em Ninjutsu';
                        if($dbs2['genjutsu']>0) $tip .= '<br />[+'.($dbs2['genjutsu']+$upg).'] em Genjutsu';
                        echo '<div style="display:inline-block;position:relative;margin:2px;border:'.$bw.' solid '.$cor.';border-radius:4px;background:#1a1a1a;">';
                        echo '<img src="_img/equipamentos/'.htmlspecialchars($dbs2['imagem']).'.png" width="45" height="45" style="display:block;object-fit:contain;" />';
                        if($upg > 0) echo '<span style="position:absolute;bottom:0;right:0;background:'.$cor.';color:#000;font-size:9px;font-weight:bold;line-height:1;padding:1px 2px;border-radius:3px 0 0 0;">+'.$upg.'</span>';
                        echo '</div>';
                    }
                } ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#323232" style="text-align:center"><?php if($db['doujutsu']==0) echo 'Nenhum doujutsu.'; else { ?><img src="_img/doujutsus/<?php echo strtolower($txtdoujutsu1); ?>.jpg" width="200" /><?php } ?></td>
            <td></td>
            <td colspan="2" bgcolor="#323232" style="text-align:center"><?php if($dbi['doujutsu']==0) echo 'Nenhum doujutsu.'; else { ?><img src="_img/doujutsus/<?php echo strtolower($txtdoujutsu2); ?>.jpg" width="200" /><?php } ?></td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#323232" style="text-align:center;padding:8px;">
                <?php if($maxj1 > 0) { ?>
                    <div style="color:#FFD700;font-weight:bold;margin-bottom:5px;">Jutsus Aprendidos (<?php echo $maxj1; ?>)</div>
                    <?php 
                    for($jx=1; $jx<=$maxj1; $jx++) {
                        $jutsu_forca = $forcajutsu1[$jx] + floor(($niveljutsu1[$jx]-1)*5);
                        $tip = '<b>'.$nomejutsu1[$jx].'</b><br />Nível '.$niveljutsu1[$jx].'<br />Força: '.$jutsu_forca.' pontos';
                    ?>
                    <img src="_img/jutsus/<?php echo $idjutsu1[$jx]; ?>.jpg" width="60" style="margin:2px;border:2px solid #FFD700;border-radius:5px;" />
                    <?php } ?>
                <?php } else { ?>
                    <span style="color:#888;">Nenhum jutsu aprendido.</span>
                <?php } ?>
            </td>
            <td></td>
            <td colspan="2" bgcolor="#323232" style="text-align:center;padding:8px;">
                <?php if($maxj2 > 0) { ?>
                    <div style="color:#FFD700;font-weight:bold;margin-bottom:5px;">Jutsus Aprendidos (<?php echo $maxj2; ?>)</div>
                    <?php 
                    for($jx=1; $jx<=$maxj2; $jx++) {
                        $jutsu_forca2 = $forcajutsu2[$jx] + floor(($niveljutsu2[$jx]-1)*5);
                        $tip2 = '<b>'.$nomejutsu2[$jx].'</b><br />Nível '.$niveljutsu2[$jx].'<br />Força: '.$jutsu_forca2.' pontos';
                    ?>
                    <img src="_img/jutsus/<?php echo $idjutsu2[$jx]; ?>.jpg" width="60" style="margin:2px;border:2px solid #FFD700;border-radius:5px;" />
                    <?php } ?>
                <?php } else { ?>
                    <span style="color:#888;">Nenhum jutsu aprendido.</span>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#323232" style="text-align:center">
                <?php 
                if(!isset($db['selo_tipo']) || $db['selo_tipo'] == 0) {
                    echo 'Nenhum selo amaldiçoado.';
                } else {
                    $selo_img = getImagemSelo($db['selo_tipo'], $db['selo_nivel']);
                    $selo_nome = getNomeSelo($db['selo_tipo']);
                    if($selo_img) {
                        echo '<img src="'.$selo_img.'" width="120" />';
                    } else {
                        echo '<b style=\'color:#FFD700;\'>'.$selo_nome.' - Nível '.$db['selo_nivel'].'</b>';
                    }
                }
                ?>
            </td>
            <td></td>
            <td colspan="2" bgcolor="#323232" style="text-align:center">
                <?php 
                if(!isset($dbi['selo_tipo']) || $dbi['selo_tipo'] == 0) {
                    echo 'Nenhum selo amaldiçoado.';
                } else {
                    $selo_img = getImagemSelo($dbi['selo_tipo'], $dbi['selo_nivel']);
                    $selo_nome = getNomeSelo($dbi['selo_tipo']);
                    if($selo_img) {
                        echo '<img src="'.$selo_img.'" width="120" />';
                    } else {
                        echo '<b style=\'color:#FFD700;\'>'.$selo_nome.' - Nível '.$dbi['selo_nivel'].'</b>';
                    }
                }
                ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
    </table>
    <div class="sep"></div>
    <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%); border: 1px solid #444; border-radius: 8px; padding: 10px; margin: 5px 0;">
        <div style="text-align: center; color: #FFD700; font-weight: bold; font-size: 12px; margin-bottom: 8px; text-shadow: 1px 1px 2px #000;">
            Legenda de Status
        </div>
        <table width="100%" cellpadding="2" cellspacing="0" style="font-size: 11px;">
            <tr>
                <td width="33%" style="padding: 3px 5px;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: #87CEEB; border-radius: 3px; vertical-align: middle; margin-right: 5px; border: 1px solid #5BA0C8;"></span>
                    <span style="color: #87CEEB;">Azul</span> = <span style="color: #ccc;">Equipamento</span>
                </td>
                <td width="33%" style="padding: 3px 5px;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: #FFD700; border-radius: 3px; vertical-align: middle; margin-right: 5px; border: 1px solid #BFA200;"></span>
                    <span style="color: #FFD700;">Dourado</span> = <span style="color: #ccc;">Selo/Multiplicador</span>
                </td>
                <td width="33%" style="padding: 3px 5px;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: #ff6600; border-radius: 3px; vertical-align: middle; margin-right: 5px; border: 1px solid #CC5200;"></span>
                    <span style="color: #ff6600;">Laranja</span> = <span style="color: #ccc;">Doujutsu</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 3px 5px;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: #00FF00; border-radius: 3px; vertical-align: middle; margin-right: 5px; border: 1px solid #00CC00;"></span>
                    <span style="color: #00FF00;">Verde</span> = <span style="color: #ccc;">Valor Final</span>
                </td>
                <td style="padding: 3px 5px;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: #FF6347; border-radius: 3px; vertical-align: middle; margin-right: 5px; border: 1px solid #CC4F39;"></span>
                    <span style="color: #FF6347;">Vermelho</span> = <span style="color: #ccc;">Penalidade</span>
                </td>
                <td style="padding: 3px 5px;"></td>
            </tr>
            <tr>
                <td style="padding: 3px 5px;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: #9b59b6; border-radius: 3px; vertical-align: middle; margin-right: 5px; border: 1px solid #7D4791;"></span>
                    <span style="color: #9b59b6;">Roxo</span> = <span style="color: #ccc;">Clã/Organização</span>
                </td>
                <td colspan="2" style="padding: 3px 5px;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: #ffff00; border-radius: 3px; vertical-align: middle; margin-right: 5px; border: 1px solid #CCCC00;"></span>
                    <span style="color: #ffff00;">Amarelo</span> = <span style="color: #ccc;">Bônus de Invasão</span>
                </td>
            </tr>
        </table>
    </div>
</div>
<div class="box_bottom"></div>

<div class="box_top">Relatório Detalhado do Combate</div>
<div class="box_middle">
    <table width="100%" cellpadding="0" cellspacing="0">
        <?php
        $relatoriofinal='';
        $energia_original_db  = $db['energia'];
        $energia_original_dbi = $dbi['energia'];
        $db['taijutsu']=$db['taijutsu']+$addtai1;
        $db['ninjutsu']=$db['ninjutsu']+$addnin1;
        $db['genjutsu']=$db['genjutsu']+$addgen1;
        $dbi['taijutsu']=$dbi['taijutsu']+$addtai2;
        $dbi['ninjutsu']=$dbi['ninjutsu']+$addnin2;
        $dbi['genjutsu']=$dbi['genjutsu']+$addgen2;
        $i=1;
        $jutsu1=1;
        $jutsu2=1;
        $perdido1=0;
        $dano1=0;
        do{
        ?>
        <tr>
            <td colspan="2"><div class="sep"></div></td>
        </tr>
        <?php
        if($i%2){
            if($jutsu1<=$maxj1){
                $icone='jutsus/'.$idjutsu1[$jutsu1].'.jpg';
                $dano=danojutsu($db['ninjutsu'],$dbi['genjutsu'],($forcajutsu1[$jutsu1]+(floor(($niveljutsu1[$jutsu1]-1)*5))));
                if($dano==1) $msgdano='que perdeu <b>'.floor($dano).' ponto de energia</b>.';
                else if($dano==0) $msgdano='que conseguiu desviar-se.';
                else $msgdano='que perdeu <b>'.floor($dano).' pontos de energia</b>.';
                $msg=str_replace('$player1','<b>'.$db['usuario'].'</b>',$textojutsu1[$jutsu1]);
                $msg=str_replace('$player2','<b>'.$dbi['usuario'].'</b>',$msg);
                $msg=str_replace('$jutsu',$nomejutsu1[$jutsu1],$msg);
                $msg=str_replace('$dano',$msgdano,$msg);
                $jutsu1++;
            } else {
                $icone='';
                $dano=dano($db['taijutsu'],$dbi['genjutsu']);
                if($dano>0){
                    $msg='<b>'.$db['usuario'].'</b> atacou '.$dbi['usuario'];
                    if(rand(1,3)==1){
                        if($arma1==1) $msg.=' com sua arma.'; else $msg.=' com danos físicos.';
                    } else $msg.=' com danos físicos.';
                    $msg.='<br />'.$dbi['usuario'].' perdeu <b>'.floor($dano).' pontos de energia</b>.';
                } else {
                    $msg='<b>'.$db['usuario'].'</b> atacou '.$dbi['usuario'].', mas o inimigo desviou.';
                }
            }
            $dano1=$dano1+floor($dano);
            $dbi['energia']=$dbi['energia']-floor($dano);
        } else {
            if($jutsu2<=$maxj2){
                $icone='jutsus/'.$idjutsu2[$jutsu2].'.jpg';
                $dano=danojutsu($dbi['ninjutsu'],$db['genjutsu'],($forcajutsu2[$jutsu2]+(floor(($niveljutsu2[$jutsu2]-1)*5))));
                if($dano==1) $msgdano='que perdeu <b>'.floor($dano).' ponto de energia</b>.';
                else if($dano==0) $msgdano='que conseguiu desviar-se.';
                else $msgdano='que perdeu <b>'.floor($dano).' pontos de energia</b>.';
                $msg=str_replace('$player1','<b>'.$dbi['usuario'].'</b>',$textojutsu2[$jutsu2]);
                $msg=str_replace('$player2','<b>'.$db['usuario'].'</b>',$msg);
                $msg=str_replace('$jutsu',$nomejutsu2[$jutsu2],$msg);
                $msg=str_replace('$dano',$msgdano,$msg);
                $jutsu2++;
            } else {
                $icone='';
                $dano=dano($dbi['taijutsu'],$db['genjutsu']);
                if($dano>0){
                    $msg='<b>'.$dbi['usuario'].'</b> atacou '.$db['usuario'];
                    if(rand(1,3)==1){
                        if($arma2==1) $msg.=' com sua arma.'; else $msg.=' com danos físicos.';
                    } else $msg.=' com danos físicos.';
                    $msg.='<br />'.$db['usuario'].' perdeu <b>'.floor($dano).' pontos de energia</b>.';
                } else {
                    $msg='<b>'.$dbi['usuario'].'</b> atacou '.$db['usuario'].', mas o inimigo desviou.';
                }
            }
            $perdido1=$perdido1+floor($dano);
            $db['energia']=$db['energia']-floor($dano);
        }
        ?>
        <tr>
            <td <?php if($icone=='') echo 'colspan="2" '; ?>style="padding-left:5px;padding-right:15px;<?php if($i%2) echo 'background:url(_img/gradient.jpg) repeat-y;'; ?>"><?php echo $msg; $relatoriofinal.=$msg.'<div class="sep"></div>'; ?></td>
            <?php if($icone<>''){ ?><td><img src="_img/<?php echo $icone; ?>" /></td><?php } ?>
        </tr>
        <?php
        $i++;
        } while(($db['energia']>15)and($dbi['energia']>15)); ?>
    </table>
</div>
<div class="box_bottom"></div>

<div class="box_top">Resultado do Combate</div>
<div class="box_middle">
    <?php
    $a=0;
    if($dbi['energia']<15) $a=1; else
    if($db['energia']<15) $a=2; else
    if($dano1>$perdido1) $a=1; else
    if($perdido1>$dano1) $a=2; else
    $a=3;
    if(date('Y-m-d')<='2010-04-04'){
        if(date('Y-m-d H:i:s')<$db['vip']){ $mpen=3; $spen=5; } else { $mpen=7; $spen=0; }
    } else {
        if(date('Y-m-d H:i:s')<$db['vip']){ $mpen=5; $spen=0; } else { $mpen=10; $spen=0; }
    }
    $is_adm_attack = isset($_SESSION['adm']) && ($_SESSION['adm']==1 || $_SESSION['adm']==2);
    if($a==1){
        $vencedorid=$db['id'];
        $vencedor=$db['usuario'];
        $perdedor=$dbi['usuario'];
        if(date('Y-m-d H:i:s')<$db['vip'])
            $yens=floor($dbi['yens']*0.15);
        else
            $yens=floor($dbi['yens']*0.1);
        if($db['nivel']>$dbi['nivel']) $exp_1=1; else
        if($db['nivel']==$dbi['nivel']) $exp_1=2; else
        if($db['nivel']<$dbi['nivel']) $exp_1=3;
        $exp_2=4-($exp_1);
        if(($db['energia']-$perdido1)<0) $energia_1=0; else $energia_1=$db['energia']-$perdido1;
        if(($dbi['energia']-$dano1)<0) $energia_2=0; else $energia_2=$dbi['energia']-$dano1;
        if($db['doujutsu']>0) $expd_1=$exp_1; else $expd_1=0;
        if($dbi['doujutsu']>0) $expd_2=$exp_2; else $expd_2=0;
        //if(date('Y-m-d H:i:s')<$db['vip']) $pen=5; else $pen=10;
        $soma=mktime(date('H'), date('i')+($mpen), date('s')+($spen));
        $penalidade=date('Y-m-d H:i:s',$soma);
        if($is_adm_attack) $penalidade='2000-01-01 00:00:00';
        if($db['energia']<0) $db['energia']=0;
        if($dbi['energia']<0) $dbi['energia']=0;
        $energia_salvar_db  = (!empty($db['energia_travada'])  && $db['energia_travada']==1)  ? $energia_original_db  : $db['energia'];
        $energia_salvar_dbi = (!empty($dbi['energia_travada']) && $dbi['energia_travada']==1) ? $energia_original_dbi : $dbi['energia'];
        $conexao->prepare("UPDATE usuarios SET yens=yens+?, yens_fat=yens_fat+?, exp=exp+?, exptotal=exptotal+?, penalidade_fim=?, vitorias=vitorias+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+?, energia=?, selo_vitorias=selo_vitorias+1 WHERE id=?")->execute([$yens, $yens, $exp_1, $exp_1, $penalidade, $expd_1, $energia_salvar_db, $db['id']]);
        $conexao->prepare("UPDATE usuarios SET yens=yens-?, yens_perd=yens_perd+?, exp=exp+?, exptotal=exptotal+?, derrotas=derrotas+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+?, energia=? WHERE id=?")->execute([$yens, $yens, $exp_2, $exp_2, $expd_2, $energia_salvar_dbi, $dbi['id']]);
    } else
    if($a==2){
        $vencedorid=$dbi['id'];
        $vencedor=$dbi['usuario'];
        $perdedor=$db['usuario'];
        if(date('Y-m-d H:i:s')<$dbi['vip'])
            $yens=floor($db['yens']*0.15);
        else
            $yens=floor($db['yens']*0.1);
        if($db['nivel']>$dbi['nivel']) $exp_1=1; else
        if($db['nivel']==$dbi['nivel']) $exp_1=2; else
        if($db['nivel']<$dbi['nivel']) $exp_1=3;
        $exp_2=4-$exp_1;
        if(($db['energia']-$perdido1)<0) $energia_1=0; else $energia_1=$db['energia']-$perdido1;
        if(($dbi['energia']-$dano1)<0) $energia_2=0; else $energia_2=$dbi['energia']-$dano1;
        if($db['doujutsu']>0) $expd_1=$exp_1; else $expd_1=0;
        if($dbi['doujutsu']>0) $expd_2=$exp_2; else $expd_2=0;
        //if(date('Y-m-d H:i:s')<$db['vip']) $pen=5; else $pen=10;
        $soma=mktime(date('H'), date('i')+($mpen), date('s')+($spen));
        $penalidade=date('Y-m-d H:i:s',$soma);
        if($is_adm_attack) $penalidade='2000-01-01 00:00:00';
        if($db['energia']<0) $db['energia']=0;
        if($dbi['energia']<0) $dbi['energia']=0;
        $energia_salvar_db  = (!empty($db['energia_travada'])  && $db['energia_travada']==1)  ? $energia_original_db  : $db['energia'];
        $energia_salvar_dbi = (!empty($dbi['energia_travada']) && $dbi['energia_travada']==1) ? $energia_original_dbi : $dbi['energia'];
        $conexao->prepare("UPDATE usuarios SET yens=yens-?, yens_perd=yens_perd+?, exp=exp+?, exptotal=exptotal+?, penalidade_fim=?, derrotas=derrotas+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+?, energia=? WHERE id=?")->execute([$yens, $yens, $exp_1, $exp_1, $penalidade, $expd_1, $energia_salvar_db, $db['id']]);
        $conexao->prepare("UPDATE usuarios SET yens=yens+?, yens_fat=yens_fat+?, exp=exp+?, exptotal=exptotal+?, vitorias=vitorias+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+?, energia=?, selo_vitorias=selo_vitorias+1 WHERE id=?")->execute([$yens, $yens, $exp_2, $exp_2, $expd_2, $energia_salvar_dbi, $dbi['id']]);
    } else
    if($a==3){
        $vencedorid=0;
        $vencedor='Empate';
        $perdedor='Empate';
        $yens=0;
        $exp_1=1;
        $exp_2=1;
        if(($db['energia']-$perdido1)<0) $energia_1=0; else $energia_1=$db['energia']-$perdido1;
        if(($dbi['energia']-$dano1)<0) $energia_2=0; else $energia_2=$dbi['energia']-$dano1;
        if($db['doujutsu']>0) $expd_1=$exp_1; else $expd_1=0;
        if($dbi['doujutsu']>0) $expd_2=$exp_2; else $expd_2=0;
        //if(date('Y-m-d H:i:s')<$db['vip']) $pen=5; else $pen=10;
        $soma=mktime(date('H'), date('i')+($mpen), date('s')+($spen));
        $penalidade=date('Y-m-d H:i:s',$soma);
        if($is_adm_attack) $penalidade='2000-01-01 00:00:00';
        if($db['energia']<0) $db['energia']=0;
        if($dbi['energia']<0) $dbi['energia']=0;
        $energia_salvar_db  = (!empty($db['energia_travada'])  && $db['energia_travada']==1)  ? $energia_original_db  : $db['energia'];
        $energia_salvar_dbi = (!empty($dbi['energia_travada']) && $dbi['energia_travada']==1) ? $energia_original_dbi : $dbi['energia'];
        $conexao->prepare("UPDATE usuarios SET exp=exp+?, exptotal=exptotal+?, penalidade_fim=?, empates=empates+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+?, energia=? WHERE id=?")->execute([$exp_1, $exp_1, $penalidade, $expd_1, $energia_salvar_db, $db['id']]);
        $conexao->prepare("UPDATE usuarios SET exp=exp+?, exptotal=exptotal+?, empates=empates+1, batalhas=batalhas+1, doujutsu_exp=doujutsu_exp+?, energia=? WHERE id=?")->execute([$exp_2, $exp_2, $expd_2, $energia_salvar_dbi, $dbi['id']]);
    }
    $exp=$exp_1.','.(4-$exp_2);
    $danos=$dano1.','.$perdido1;
    $reldoujutsu=$db['doujutsu'].','.$dbi['doujutsu'];
    $nivel=$db['nivel'].','.$dbi['nivel'];
    
    $stmt_rel = $conexao->prepare("INSERT INTO relatorios (data, usuarioid, inimigoid, vencedor, exp, yens, taijutsu, ninjutsu, genjutsu, energia, equips1, equips2, danos, nivel, doujutsu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_rel->execute([date('Y-m-d H:i:s'), $db['id'], $dbi['id'], $vencedorid, $exp, $yens, $tai, $nin, $gen, $ene, $equips1, $equips2, $danos, $nivel, $reldoujutsu]);
    $relid = $conexao->lastInsertId();

    // Se o atacante é admin, restaura energia e cooldown da conta atacada para testes
    if (isset($_SESSION['adm']) && ($_SESSION['adm'] == 1 || $_SESSION['adm'] == 2)) {
        $conexao->prepare("UPDATE usuarios SET energia = energiamax, penalidade_fim = '2000-01-01 00:00:00' WHERE id = ?")->execute([$dbi['id']]);
    }

    if(!is_dir('reports')) {
        mkdir('reports', 0755, true);
    }
    
    $fp=fopen('reports/r'.$relid.'.txt','w');
    if($fp !== false) {
        fwrite($fp,$relatoriofinal);
        fclose($fp);
    }
    try {
        $stmt_w = $conexao->prepare("SELECT id FROM verificador WHERE status='off' AND usuarioid=?");
        $stmt_w->execute([$dbi['id']]);
        $dbw = $stmt_w->fetch(PDO::FETCH_ASSOC);
        if($dbw && isset($dbw['id'])){
            $conexao->prepare("UPDATE verificador SET status='on', yens=?, hora_ataque=?, inimigoid=?, hunt=? WHERE id=?")->execute([$yens, date('Y-m-d H:i:s'), $db['id'], $_SESSION['hunt'], $dbw['id']]);
        }
    } catch (PDOException $e) {
        // Ignore if verificador table doesn't exist
    }
    if($dbi['tipo']=='bot'){
        $conexao->prepare("UPDATE usuarios SET penalidade='0000-00-00 00:00:00' WHERE id=?")->execute([$dbi['id']]);
    }
    // Atualizar snapshot de stats no book após batalha
    try {
        $conexao->prepare("
            UPDATE book SET
                snap_tai = ?, snap_nin = ?, snap_gen = ?,
                snap_nivel = ?, snap_vila = ?, snap_renegado = ?,
                snap_personagem = ?, snap_avatar = ?
            WHERE usuarioid = ? AND inimigoid = ?
        ")->execute([
            $dbi['taijutsu'], $dbi['ninjutsu'], $dbi['genjutsu'],
            $dbi['nivel'], $dbi['vila'], $dbi['renegado'],
            $dbi['personagem'], $dbi['avatar'],
            $db['id'], $dbi['id']
        ]);
    } catch (PDOException $e) {
        // Ignorar se tabela não existir
    }
    // Drop de Cristal de Buff (3% para o vencedor) — apenas em PvP real
    $drop_buff_pvp = null;
    if ($vencedorid > 0 && ($dbi['tipo'] ?? '') !== 'bot') {
        try {
            if (rand(1, 100) <= 3) {
                $stmt_buff = $conexao->prepare("SELECT id, nome, imagem FROM table_usaveis WHERE categoria = 'cristal_buff' ORDER BY RANDOM() LIMIT 1");
                $stmt_buff->execute();
                $drop_buff_pvp = $stmt_buff->fetch(PDO::FETCH_ASSOC);
                if ($drop_buff_pvp) {
                    $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid, status) VALUES (?, ?, 'off')")
                        ->execute([$vencedorid, $drop_buff_pvp['id']]);
                }
            }
        } catch (PDOException $e) { $drop_buff_pvp = null; }
    }
    ?>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
            <td width="25%"><b><?php echo $db['usuario']; ?></b></td>
            <td>causou <?php echo $dano1; ?> pontos de dano</td>
            <td>perdeu <?php echo $perdido1; ?> pontos de energia</td>
        </tr>
        <tr>
            <td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
            <td><b><?php echo $dbi['usuario']; ?></b></td>
            <td>causou <?php echo $perdido1; ?> pontos de dano</td>
            <td>perdeu <?php echo $dano1; ?> pontos de energia</td>
        </tr>
        <tr>
            <td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr>
            <td colspan="3"><div class="aviso"><b><?php echo $vencedor; ?></b> venceu o combate.<br /><span class="sub2"><?php echo $vencedor; ?> recebeu <?php echo number_format($yens,2,',','.'); ?> yen<?php if($yens>1) echo 's'; ?> e adquiriu <?php echo $exp_1; ?> ponto<?php if($exp_1>1) echo 's'; ?> de experiência;<br /><?php echo $perdedor; ?> apenas adquiriu <?php echo $exp_2; ?> ponto<?php if($exp_2>1) echo 's'; ?> de experiência.<?php /*<br /><div id="atk" style="text-align:left;"><a href="javascript:carregaAjax('atk','search_atk.php?value=<?php echo base64_encode($yens); ?>&id=<?php echo base64_encode($db['id']); ?>&name=<?php echo base64_encode($db['usuario']); ?>','n');">Postar em minhas atualizações.</a></div>*/ ?></span></div></td>
        </tr>
    </table>
    <?php if (!empty($drop_buff_pvp)): ?>
    <div class="sep"></div>
    <div align="center" style="padding:8px;background:#0d1a00;border-top:1px solid #3a5a00;">
        <span style="color:#FFD700;font-size:13px;font-weight:bold;">💎 Drop de Batalha!</span><br/>
        <img src="_img/Buff/<?php echo htmlspecialchars($drop_buff_pvp['imagem']); ?>"
             onerror="this.style.display='none'"
             style="width:40px;height:40px;object-fit:contain;vertical-align:middle;margin:4px;" />
        <span style="color:#90EE90;font-size:12px;">
            <b><?php echo htmlspecialchars($drop_buff_pvp['nome']); ?></b> adicionado ao seu inventário!
        </span>
    </div>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>
<?php
// PDO automatically handles memory cleanup
?>