
<?php require_once('trava.php'); ?>
<?php require_once('verificar.php'); ?>
<?php require_once('funcoes.php'); ?>
<?php

// Verificar se é invasão
$is_invasao = isset($_GET['invasao']) && $_GET['invasao'] == 1;

if($is_invasao) {
    // Para invasão, verificar se há preparação válida ou criar uma nova
    if(!isset($_SESSION['prepare_invasao'])){
        // Buscar invasão ativa
        require_once('conexao.php');
        try {
            // Sempre usa servidor_id do banco (fonte autoritativa)
            $stmt_srv_atkin = $conexao->prepare("SELECT servidor_id FROM usuarios WHERE id = ?");
            $stmt_srv_atkin->execute([$_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid']]);
            $row_srv_atkin = $stmt_srv_atkin->fetch(PDO::FETCH_ASSOC);
            $srv_atkin = (int)($row_srv_atkin['servidor_id'] ?? 0);
            $_SESSION['servidor_id'] = $srv_atkin;
            $stmt = $conexao->prepare("SELECT * FROM invasoes WHERE status = 'ativa' AND servidor_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$srv_atkin]);
            $invasao_check = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$invasao_check) {
                echo "<script>alert('Nenhuma invasão ativa encontrada!'); self.location='?p=invasao'</script>";
                exit;
            }

            if($invasao_check['hp_atual'] <= 0) {
                echo "<script>alert('Esta invasão já foi finalizada!'); self.location='?p=invasao'</script>";
                exit;
            }

            // Definir sessão automaticamente
            $_SESSION['prepare_invasao'] = $invasao_check['id'];
        } catch(PDOException $e) {
            echo "<script>self.location='?p=invasao'</script>";
            exit;
        }
    }
} else {
    // Sistema normal de ataque
    if(!isset($_GET['bot'])){ echo "<script>self.location='?p=home'</script>"; exit; }
    if(!isset($_SESSION['errobot'])) $_SESSION['errobot']=0;
    if($_GET['bot']<>$_SESSION['bot']){
        $_SESSION['errobot']=$_SESSION['errobot']+1;
        if($_SESSION['errobot']>=2){ echo "<script>self.location='?p=logout'</script>"; exit; }
        echo "<script>self.location='?p=prepare&msg=1'</script>"; exit;
    }
    $_SESSION['errobot']=0;
    if(!isset($_SESSION['prepare'])){ echo "<script>self.location='?p=home'</script>"; exit; }
}

require_once('conexao.php');

if($is_invasao) {
    // Buscar dados da invasão
    try {
        $stmt = $conexao->prepare("SELECT * FROM invasoes WHERE id = ? AND status = 'ativa'");
        $stmt->execute([$_SESSION['prepare_invasao']]);
        $invasao_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$invasao_data || $invasao_data['hp_atual'] <= 0) {
            echo "<script>alert('Invasão não encontrada ou já finalizada!'); self.location='?p=invasao'</script>";
            exit;
        }

        // Criar dados fictícios do invasor para usar no sistema de combate
        $invasor_nivel = $db['nivel'] * 2;
        $invasor_tai = $db['taijutsu'] * 2;
        $invasor_nin = $db['ninjutsu'] * 2;
        $invasor_gen = $db['genjutsu'] * 2;

        $dbi = array(
            'id' => 'invasao_' . $invasao_data['id'],
            'usuario' => $invasao_data['nome_invasor'],
            'yens' => $invasao_data['premio_yens'],
            'yens_fat' => 0,
            'nivel' => $invasor_nivel,
            'orgid' => 0,
            'energia' => 100,
            'energiamax' => 100,
            'taijutsu' => $invasor_tai,
            'ninjutsu' => $invasor_nin,
            'genjutsu' => $invasor_gen,
            'personagem' => 'invasor',
            'avatar' => $invasao_data['imagem_arquivo'],
            'renegado' => 'nao',
            'vila' => 99,
            'doujutsu' => 0,
            'doujutsu_nivel' => 0,
            'doujutsu_exp' => 0,
            'doujutsu_expmax' => 0,
            'exp' => 0,
            'expmax' => 0,
            'vip' => '0000-00-00 00:00:00',
            'missao' => 0,
            'loginip' => '0.0.0.0',
            'tipo' => 'invasor',
            'orgnivel' => 0
        );

    } catch(PDOException $e) {
        echo "<script>self.location='?p=invasao'</script>";
        exit;
    }
} else {
    // Sistema normal de busca do oponente
    try {
        $stmt = $conexao->prepare("SELECT u.id, u.usuario, u.yens, u.yens_fat, u.nivel, u.orgid, u.energia, u.energiamax, u.taijutsu, u.ninjutsu, u.genjutsu, u.personagem, u.avatar, u.renegado, u.vila, u.doujutsu, u.doujutsu_nivel, u.doujutsu_exp, u.doujutsu_expmax, u.exp, u.expmax, u.vip, u.missao, u.loginip, u.tipo, o.nivel as orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE u.id=?");
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
}

if(!$is_invasao) {
    // Verificações apenas para combate normal (não invasão)
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
    if($dbi['avatar']==0){ echo "<script>self.location='?p=hunt&msg=12'</script>"; exit; }
    if(($dbi['renegado']=='sim')&&($db['renegado']=='sim')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; exit; }
    if(($dbi['vila']==$db['vila'])&&($dbi['renegado']=='nao')&&($db['renegado']=='nao')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; exit; }
}

// Verificação de energia sempre (invasão e normal)
if($db['energia']<25){ echo "<script>self.location='?p=hunt&msg=13'</script>"; exit; }

// Player perde 25 de energia ao entrar no duelo
$nova_energia = $db['energia'] - 25;
try {
    $stmt_update_energia = $conexao->prepare("UPDATE usuarios SET energia = ? WHERE id = ?");
    $stmt_update_energia->execute([$nova_energia, $db['id']]);
    $db['energia'] = $nova_energia; // Atualizar valor local
} catch(PDOException $e) {
    // Se falhar, continua sem atualizar
}

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
if(isset($db['orgnivel']) && $db['orgnivel']>0){
    $addtai1=$addtai1+$db['orgnivel'];
    $addnin1=$addnin1+$db['orgnivel'];
    $addgen1=$addgen1+$db['orgnivel'];
}
$equips1='';
$equips2='';
while($dbs=$sqls->fetch(PDO::FETCH_ASSOC)){
    if($equips1=='') $equips1=$dbs['itemid']; else $equips1.=','.$dbs['itemid'];
    if($equips1<>'') substr($equips1,0,strlen($equips1)-1);
    $addtai1=$addtai1+$dbs['taijutsu']+$dbs['upgrade'];
    $addnin1=$addnin1+$dbs['ninjutsu']+$dbs['upgrade'];
    $addgen1=$addgen1+$dbs['genjutsu']+$dbs['upgrade'];
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
if(isset($dbi['orgnivel']) && $dbi['orgnivel']>0){
    $addtai2=$addtai2+$dbi['orgnivel'];
    $addnin2=$addnin2+$dbi['orgnivel'];
    $addgen2=$addgen2+$dbi['orgnivel'];
}

if($sqls2){
    while($dbs2=$sqls2->fetch(PDO::FETCH_ASSOC)){
        if($equips2=='') $equips2=$dbs2['itemid']; else $equips2.=','.$dbs2['itemid'];
        if($equips2<>'') substr($equips2,0,strlen($equips2)-1);
        $addtai2=$addtai2+$dbs2['taijutsu']+$dbs2['upgrade'];
        $addnin2=$addnin2+$dbs2['ninjutsu']+$dbs2['upgrade'];
        $addgen2=$addgen2+$dbs2['genjutsu']+$dbs2['upgrade'];
    }
}

// Reset statement pointers if needed
if($sqls) $sqls->execute([$db['id']]);
if($sqls2) $sqls2->execute([$dbi['id']]);

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
if($is_invasao) {
    $vilai='invasao'; 
    $txtvilai='Invasor';
} else {
    switch($dbi['vila']){
        case 1: $vilai='folha'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Folha)'; else $txtvilai='Vila da Folha'; break;
        case 2: $vilai='areia'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Areia)'; else $txtvilai='Vila da Areia'; break;
        case 3: $vilai='som'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila do Som)'; else $txtvilai='Vila do Som'; break;
        case 4: $vilai='chuva'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Chuva)'; else $txtvilai='Vila da Chuva'; break;
        case 5: $vilai='nuvem'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Nuvem)'; else $txtvilai='Vila da Nuvem'; break;
        case 6: $vilai='nevoa'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Névoa)'; else $txtvilai='Vila da Névoa'; break;
        case 8: $vilai='pedra'; if($dbi['renegado']=='sim') $txtvilai='Akatsuki (Vila da Pedra)'; else $txtvilai='Vila da Pedra'; break;
        case 99: $vilai='folha'; $txtvilai='Vila da Pedra'; break;
    }
} ?>
<?php
$tai=($db['taijutsu']+$addtai1).','.($dbi['taijutsu']+$addtai2);
$nin=($db['ninjutsu']+$addnin1).','.($dbi['ninjutsu']+$addnin2);
$gen=($db['genjutsu']+$addgen1).','.($dbi['genjutsu']+$addgen2);
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
                <img src="_img/personagens/<?php echo $db['personagem']; ?>/<?php echo $db['avatar']; ?>.jpg" />
                <div style="background:#444444; padding:2px;">
                    <div style="color:white; text-align:center; font-size:10px;">
                        <?php
                        if($db['renegado']=='sim') {
                            echo 'NUKENIN';
                        } else {
                            echo strtoupper(rankNinja($db['nivel']));
                        }
                        ?>
                    </div>
                </div>
            </td>
            <td align="center" width="16%"><img src="_img/versus.jpg" /></td>
            <td colspan="2" align="center" width="42%" style="background:url(_img/personagens/no_avatar.jpg) no-repeat center #444444;">
                <?php if($is_invasao): ?>
                    <img src="_img/Invasao/<?php echo htmlspecialchars($dbi['avatar']); ?>" 
                         style="width: 162px; height: 150px;" />
                <?php else: ?>
                    <img src="_img/personagens/<?php echo $dbi['personagem']; ?>/<?php echo $dbi['avatar']; ?>.jpg" />
                <?php endif; ?>
                <?php if($is_invasao): ?>
                    <div style="background:#444444; padding:2px;">
                        <div style="color:white; text-align:center; font-size:10px;">
                            INVASOR
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background:#444444; padding:2px;">
                        <div style="color:white; text-align:center; font-size:10px;">
                            <?php
                            if($dbi['renegado']=='sim') {
                                echo 'NUKENIN';
                            } else {
                                echo strtoupper(rankNinja($dbi['nivel']));
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#444444" align="center"><img src="_img/<?php echo (!isset($db['bandana_estilo']) || $db['bandana_estilo'] == 'classico') ? 'vilas' : 'NewsVilas'; ?>/<?php if($db['renegado']=='sim') echo 'akatsuki'; else echo $vila; ?><?php if((!isset($db['bandana_estilo']) || $db['bandana_estilo'] == 'classico') && $db['renegado']=='sim') echo '_folha'; ?>.jpg" style="width: 117px; height: 55px;" /></td>
            <td align="center">&nbsp;</td>
            <td colspan="2" bgcolor="#444444" align="center">
                <?php if($is_invasao): ?>
                    <img src="_img/Invasao/bandana_invasao.png" style="width: 117px; height: 55px;" />
                <?php else: ?>
                    <img src="_img/<?php echo (!isset($dbi['bandana_estilo']) || $dbi['bandana_estilo'] == 'classico') ? 'vilas' : 'NewsVilas'; ?>/<?php if($dbi['renegado']=='sim') echo 'akatsuki'; else echo $vilai; ?><?php if((!isset($dbi['bandana_estilo']) || $dbi['bandana_estilo'] == 'classico') && $dbi['renegado']=='sim') echo '_folha'; ?>.jpg" style="width: 117px; height: 55px;" />
                <?php endif; ?>
            </td>
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
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Taijutsu:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $db['taijutsu']; ?>]</b> <?php if($addtai1>0) echo '+'.$addtai1; ?></td>
            <td></td>
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Taijutsu:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $dbi['taijutsu']; ?>]</b> <?php if($addtai2>0) echo '+'.$addtai2; ?></td>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#323232;padding-right:10px;">Ninjutsu:</td>
            <td style="background:#323232;"><b>[<?php echo $db['ninjutsu']; ?>]</b> <?php if($addnin1>0) echo '+'.$addnin1; ?></td>
            <td></td>
            <td align="right" style="background:#323232;padding-right:10px;">Ninjutsu:</td>
            <td style="background:#323232;"><b>[<?php echo $dbi['ninjutsu']; ?>]</b> <?php if($addnin2>0) echo '+'.$addnin2; ?></td>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Genjutsu:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $db['genjutsu']; ?>]</b> <?php if($addgen1>0) echo '+'.$addgen1; ?></td>
            <td></td>
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Genjutsu:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $dbi['genjutsu']; ?>]</b> <?php if($addgen2>0) echo '+'.$addgen2; ?></td>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#323232;padding-right:10px;">Experiência:</td>
            <td style="background:#323232;"><b>[<?php echo $db['exp']; ?> / <?php echo $db['expmax']; ?>]</b></td>
            <td></td>
            <?php if($is_invasao): ?>
                <td align="right" style="background:#323232;padding-right:10px;">Status:</td>
                <td style="background:#323232;"><b>INVASOR ATIVO</b></td>
            <?php else: ?>
                <td align="right" style="background:#323232;padding-right:10px;">Experiência:</td>
                <td style="background:#323232;"><b>[<?php echo $dbi['exp']; ?> / <?php echo $dbi['expmax']; ?>]</b></td>
            <?php endif; ?>
        </tr>
        <tr style="height:17px;">
            <td align="right" style="background:#2C2C2C;padding-right:10px;">Energia:</td>
            <td style="background:#2C2C2C;"><b>[<?php echo $db['energia']; ?> / <?php echo $db['energiamax']; ?>]</b></td>
            <td></td>
            <?php if($is_invasao): ?>
                <td align="right" style="background:#2C2C2C;padding-right:10px;">Tipo:</td>
                <td style="background:#2C2C2C;"><b>COMBATE ESPECIAL</b></td>
            <?php else: ?>
                <td align="right" style="background:#2C2C2C;padding-right:10px;">Energia:</td>
                <td style="background:#2C2C2C;"><b>[<?php echo $dbi['energia']; ?> / <?php echo $dbi['energiamax']; ?>]</b></td>
            <?php endif; ?>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#323232" style="text-align:center">
                <?php 
                $arma1=0; 
                if($sqls && $sqls->rowCount()>0) {
                    while($dbs=$sqls->fetch(PDO::FETCH_ASSOC)){
                        if($arma1==0){
                            if($dbs['categoria']=='arma') $arma1=1; else $arma1=0;
                        }
                        $tip='<b>'.$dbs['nome'];
                        if($dbs['upgrade']>0) $tip.=' +'.$dbs['upgrade'];
                        $tip.='</b><br />';
                        if($dbs['taijutsu']>0) $tip.='[+'.($dbs['taijutsu']+$dbs['upgrade']).'] em Taijutsu';
                        if($dbs['ninjutsu']>0) $tip.='<br />[+'.($dbs['ninjutsu']+$dbs['upgrade']).'] em Ninjutsu';
                        if($dbs['genjutsu']>0) $tip.='<br />[+'.($dbs['genjutsu']+$dbs['upgrade']).'] em Genjutsu';
                        ?>
                        <img src="_img/equipamentos/<?php echo $dbs['imagem']; ?>.png" width="70" />
                <?php }
                } else {
                    echo 'Nenhum equipamento.';
                } ?>
             </td>
            <td align="center"></td>
            <td colspan="2" bgcolor="#323232" style="text-align:center">
                <?php 
                $arma2=0; 
                if($sqls2 && $sqls2->rowCount()>0) {
                    while($dbs2=$sqls2->fetch(PDO::FETCH_ASSOC)){
                        if($arma2==0){
                            if($dbs2['categoria']=='arma') $arma2=1; else $arma2=0;
                        }
                        $tip='<b>'.$dbs2['nome'];
                        if($dbs2['upgrade']>0) $tip.=' +'.$dbs2['upgrade'];
                        $tip.='</b><br />';
                        if($dbs2['taijutsu']>0) $tip.='[+'.($dbs2['taijutsu']+$dbs2['upgrade']).'] em Taijutsu';
                        if($dbs2['ninjutsu']>0) $tip.='<br />[+'.($dbs2['ninjutsu']+$dbs2['upgrade']).'] em Ninjutsu';
                        if($dbs2['genjutsu']>0) $tip.='<br />[+'.($dbs2['genjutsu']+$dbs2['upgrade']).'] em Genjutsu';
                        ?>
                        <img src="_img/equipamentos/<?php echo $dbs2['imagem']; ?>.png" width="70" />
                <?php }
                } else {
                    echo 'Nenhum equipamento.';
                } ?>
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div class="sep"></div></td>
            <td align="center"></td>
            <td colspan="2" align="center"><div class="sep"></div></td>
        </tr>
    </table>
</div>
<div class="box_bottom"></div>

<?php
// Simulação de batalha mais detalhada
$player_hp = 100;
$invasor_hp = 100;
$rounds = rand(25, 35);
$log_combate = array();

// Arrays de emotions e ações variadas
$acoes_player = array(
    "lança um ataque furioso 😠",
    "executa um golpe certeiro 🎯", 
    "desfere um poderoso soco 👊",
    "realiza um combo devastador 💥",
    "ataca com determinação 😤",
    "contra-ataca rapidamente ⚡",
    "golpeia com precisão 🗡️",
    "investe com tudo 🔥"
);

$acoes_invasor = array(
    "revida com fúria 😡",
    "ataca selvagemente 🐺",
    "golpeia brutalmente 💀", 
    "investe com força total 💪",
    "contra-ataca violentamente ⚔️",
    "desfere um golpe poderoso 🔨",
    "ataca sem piedade 👹",
    "investe ferozmente 🦾"
);

$defesas = array(
    "defendeu o ataque! 🛡️",
    "esquivou habilmente! 🏃‍♂️",
    "bloqueou o golpe! ✋",
    "desviou no último segundo! 💨",
    "absorveu o impacto! 🏋️‍♂️"
);

for($i = 1; $i <= $rounds; $i++) {
    // Turno do player
    if($invasor_hp > 0) {
        $chance_defesa = rand(1, 10);
        if($chance_defesa <= 2) {
            // Invasor defende
            $acao_defesa = $defesas[array_rand($defesas)];
            $log_combate[] = $db['usuario'] . " " . $acoes_player[array_rand($acoes_player)] . " mas " . $dbi['usuario'] . " " . $acao_defesa;
        } else {
            // Ataque bem sucedido
            $dano = rand(3, 8);
            $invasor_hp -= $dano;
            if($invasor_hp < 0) $invasor_hp = 0;
            
            $acao = $acoes_player[array_rand($acoes_player)];
            $log_combate[] = $db['usuario'] . " " . $acao . " em " . $dbi['usuario'] . " e causa " . $dano . " pontos de dano! 💢";
        }
    }
    
    // Turno do invasor (só se ainda tiver HP)
    if($player_hp > 0 && $invasor_hp > 0) {
        $chance_defesa = rand(1, 10);
        if($chance_defesa <= 2) {
            // Player defende
            $acao_defesa = $defesas[array_rand($defesas)];
            $log_combate[] = $dbi['usuario'] . " " . $acoes_invasor[array_rand($acoes_invasor)] . " mas " . $db['usuario'] . " " . $acao_defesa;
        } else {
            // Ataque bem sucedido
            $dano = rand(2, 6);
            $player_hp -= $dano;
            if($player_hp < 0) $player_hp = 0;
            
            $acao = $acoes_invasor[array_rand($acoes_invasor)];
            $log_combate[] = $dbi['usuario'] . " " . $acao . " em " . $db['usuario'] . " e causa " . $dano . " pontos de dano! 💥";
        }
    }
    
    if($player_hp <= 0 || $invasor_hp <= 0) {
        break;
    }
}

// Se o player perdeu numa invasão real, contabiliza no contador de "Players Derrotados"
if($is_invasao && $player_hp <= 0 && $invasor_hp > 0) {
    try {
        $stmt_pd = $conexao->prepare("UPDATE invasoes SET players_derrotados = players_derrotados + 1 WHERE id = ?");
        $stmt_pd->execute([$invasao_data['id']]);
    } catch (PDOException $e) {
        // silencioso
    }
}

// Lógica corrigida: Player só vence se derrotar completamente a invasão
$invasao_derrotada = false;
$player_venceu = false;
$dano_causado_invasao = rand(8, 15); // Dano que o player causa na invasão real
$exp_ganha = rand(3, 10);

if($is_invasao) {
    try {
        // Aplicar dano na invasão real
        $novo_hp_invasao = max(0, $invasao_data['hp_atual'] - $dano_causado_invasao);
        $stmt_update = $conexao->prepare("UPDATE invasoes SET hp_atual = ? WHERE id = ?");
        $stmt_update->execute([$novo_hp_invasao, $invasao_data['id']]);
        
        // Verificar se a invasão foi completamente derrotada
        if($novo_hp_invasao <= 0) {
            $invasao_derrotada = true;
            $player_venceu = true;
            
            // Finalizar invasão e definir vencedor
            $stmt_finalizar = $conexao->prepare("UPDATE invasoes SET status = 'finalizada', vencedor_id = ? WHERE id = ?");
            $stmt_finalizar->execute([$db['id'], $invasao_data['id']]);
            
            // Dar prêmio de yens
            $stmt_premio = $conexao->prepare("UPDATE usuarios SET yens = yens + ? WHERE id = ?");
            $stmt_premio->execute([$invasao_data['premio_yens'], $db['id']]);

            // Aplicar bônus de vila para todos da vila vencedora no mesmo servidor
            $bonus_percent = $invasao_data['bonus_vila'] / 100;
            $srv_invasao = (int)$invasao_data['servidor_id'];
            // Limpar bônus anterior de todos no servidor
            $stmt_clear = $conexao->prepare("UPDATE usuarios SET bonus_invasao_tai = 0, bonus_invasao_nin = 0, bonus_invasao_gen = 0, bonus_invasao_pct = 0 WHERE servidor_id = ?");
            $stmt_clear->execute([$srv_invasao]);
            // Aplicar novo bônus para toda a vila vencedora no mesmo servidor
            $stmt_bonus = $conexao->prepare("UPDATE usuarios SET bonus_invasao_tai = ROUND(taijutsu * ?), bonus_invasao_nin = ROUND(ninjutsu * ?), bonus_invasao_gen = ROUND(genjutsu * ?), bonus_invasao_pct = ? WHERE vila = ? AND servidor_id = ?");
            $stmt_bonus->execute([$bonus_percent, $bonus_percent, $bonus_percent, $invasao_data['bonus_vila'], $db['vila'], $srv_invasao]);
        }
    } catch(PDOException $e) {
        // Erro ao atualizar
    }
}

// Dar EXP sempre (independente de vitória ou derrota)
try {
    $stmt_exp = $conexao->prepare("UPDATE usuarios SET exp = exp + ? WHERE id = ?");
    $stmt_exp->execute([$exp_ganha, $db['id']]);
} catch(PDOException $e) {
    // Erro ao dar EXP
}

?>

<div class="box_top">Relatório Detalhado do Combate</div>
<div class="box_middle">
    <div style="padding: 10px; max-height: 300px; overflow-y: auto;">
        <?php foreach($log_combate as $linha): ?>
            <div style="margin: 2px 0; padding: 3px; background: #2a2a2a; border-left: 3px solid #ff6600;">
                <?php echo $linha; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<div class="box_bottom"></div>

<div class="box_top">🎯 Resultado da Batalha</div>
<div class="box_middle">
    <div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border-radius: 8px; margin: 10px;">
        
        <!-- Resultado Principal -->
        <div style="margin-bottom: 25px; padding: 20px; border-radius: 10px; border: 2px solid <?php echo $player_venceu ? '#00ff00' : '#ff6600'; ?>; background: <?php echo $player_venceu ? 'rgba(0,255,0,0.1)' : 'rgba(255,102,0,0.1)'; ?>;">
            <?php if($player_venceu && $invasao_derrotada): ?>
                <div style="color: #00ff00; font-size: 24px; font-weight: bold; margin-bottom: 15px; text-shadow: 0 0 10px #00ff00;">
                    🏆 VITÓRIA ÉPICA! 🏆
                </div>
                <div style="color: #ffd700; font-size: 16px; margin-bottom: 10px;">
                    🎉 <strong><?php echo $db['usuario']; ?></strong> derrotou completamente a invasão! 🎉
                </div>
                <div style="color: #00ff00; font-size: 14px;">
                    Você deu o golpe final e se tornou o herói da vila! ⚔️
                </div>
            <?php else: ?>
                <div style="color: #ff6600; font-size: 20px; font-weight: bold; margin-bottom: 15px; text-shadow: 0 0 10px #ff6600;">
                    💪 COMBATE VALOROSO 💪
                </div>
                <div style="color: #ffaa00; font-size: 16px; margin-bottom: 10px;">
                    <strong><?php echo $db['usuario']; ?></strong> lutou bravamente! 🥊
                </div>
                <div style="color: #ff6600; font-size: 14px;">
                    Você causou dano significativo, mas a invasão continua... 🔥
                </div>
            <?php endif; ?>
        </div>

        <!-- Estatísticas da Batalha -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0;">
            <div style="background: rgba(0,150,255,0.2); padding: 15px; border-radius: 8px; border-left: 4px solid #0096ff;">
                <div style="color: #0096ff; font-weight: bold; font-size: 14px;">💎 Experiência Ganha</div>
                <div style="color: #fff; font-size: 18px; font-weight: bold;"><?php echo $exp_ganha; ?> pontos</div>
            </div>
            
            <div style="background: rgba(255,102,0,0.2); padding: 15px; border-radius: 8px; border-left: 4px solid #ff6600;">
                <div style="color: #ff6600; font-weight: bold; font-size: 14px;">⚡ Energia Gasta</div>
                <div style="color: #fff; font-size: 18px; font-weight: bold;">25 pontos</div>
            </div>
        </div>

        <?php if($is_invasao): ?>
        <div style="background: rgba(255,0,100,0.2); padding: 15px; border-radius: 8px; border-left: 4px solid #ff0064; margin: 15px 0;">
            <div style="color: #ff0064; font-weight: bold; font-size: 14px;">🎯 Dano Causado na Invasão</div>
            <div style="color: #fff; font-size: 18px; font-weight: bold;"><?php echo number_format($dano_causado_invasao); ?> pontos de dano</div>
        </div>
        <?php endif; ?>

        <?php if($player_venceu && $invasao_derrotada): ?>
        <div style="background: rgba(255,215,0,0.2); padding: 15px; border-radius: 8px; border-left: 4px solid #ffd700; margin: 15px 0;">
            <div style="color: #ffd700; font-weight: bold; font-size: 14px;">💰 Prêmio Conquistado</div>
            <div style="color: #fff; font-size: 18px; font-weight: bold;"><?php echo number_format($invasao_data['premio_yens']); ?> Yens</div>
        </div>
        <?php endif; ?>
        
        <!-- Botões de Ação -->
        <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="?p=invasao" style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; box-shadow: 0 4px 15px rgba(76,175,80,0.3); transition: all 0.3s ease; display: inline-block;">
                🏠 Voltar às Invasões
            </a>
            
            <?php if(!$invasao_derrotada && $db['energia'] >= 25): ?>
            <a href="?p=prepareIn&invasao=1" style="background: linear-gradient(135deg, #ff6600 0%, #e55a00 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; box-shadow: 0 4px 15px rgba(255,102,0,0.3); transition: all 0.3s ease; display: inline-block;">
                ⚔️ Atacar Novamente
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="box_bottom"></div>

<?php
// Limpar sessões
if($is_invasao) {
    unset($_SESSION['prepare_invasao']);
} else {
    unset($_SESSION['prepare']);
}
?>
