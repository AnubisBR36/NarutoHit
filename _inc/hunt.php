<?php require_once('trava.php'); ?>
<?php require_once('verificar.php'); ?>
<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

$is_adm_hunt = isset($_SESSION['adm']) && ($_SESSION['adm'] == 1 || $_SESSION['adm'] == 2);
$is_vip_hunt = date('Y-m-d H:i:s') < $db['vip'];
$meu_srv_id  = (int)(isset($_SESSION['servidor_id']) ? $_SESSION['servidor_id'] : ($db['servidor_id'] ?? 1));

// Ler estado do PVP (admins ignoram o bloqueio)
$pvp_ativo_hunt = true;
if (!$is_adm_hunt) {
    try {
        $stmt_pvp_hunt = $conexao->prepare("SELECT valor FROM configuracoes WHERE nome = 'pvp_ativo' LIMIT 1");
        $stmt_pvp_hunt->execute();
        $pvp_hunt_val = $stmt_pvp_hunt->fetchColumn();
        if ($pvp_hunt_val !== false && (string)$pvp_hunt_val === '0') {
            $pvp_ativo_hunt = false;
        }
    } catch (Exception $e) {}
}

if(isset($_POST['hunt_tipo'])){
    if(!csrf_validar()) { echo "<script>self.location='?p=hunt'</script>"; exit; }
    if (!$pvp_ativo_hunt) {
        echo "<script>alert('O PVP entre jogadores está desabilitado no momento.'); self.location='?p=hunt'</script>";
        exit;
    }
        $tipo=$c->decode($_POST['hunt_tipo'],$chaveuniversal);
        vn($tipo);
        switch($tipo){

                /* ============================================================
                   CASE 1 — Caçar ninja específico pelo nome
                   ============================================================ */
                case 1:
                        $hunt=$_POST['hunt_1'];
                        if($hunt==''){ echo "<script>self.location='?p=hunt&msg=6'</script>"; break; }
                        if(!$is_vip_hunt) if($db['yens']<5){ echo "<script>self.location='?p=hunt&msg=3'</script>"; break; }
                        if(!$is_vip_hunt) {
                                try {
                                        $stmt = $conexao->prepare("UPDATE usuarios SET yens=yens-5 WHERE id=?");
                                        $stmt->execute([$db['id']]);
                                } catch (PDOException $e) { }
                        }
                        try {
                                $sqlh = $conexao->prepare("SELECT id,vila,renegado,avatar,energia,preso,penalidade_fim,missao,loginip,tipo,servidor_id FROM usuarios WHERE LOWER(usuario)=LOWER(?)");
                                $sqlh->execute([$_POST['hunt_1']]);
                                $dbh = $sqlh->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                                echo "<script>self.location='?p=hunt&msg=1'</script>"; break;
                        }
                        if(!$dbh){ echo "<script>self.location='?p=hunt&msg=1'</script>"; break; }
                        if($dbh['energia']<25){ echo "<script>self.location='?p=hunt&msg=2'</script>"; break; }
                        /* Servidor: ninja deve pertencer ao mesmo servidor (adm ignora) */
                        if(!$is_adm_hunt && (int)($dbh['servidor_id'] ?? 1) !== $meu_srv_id){
                                echo "<script>self.location='?p=hunt&msg=1'</script>"; break;
                        }
                        /* IP igual só bloqueia se ambos têm IP real (>0) */
                        $ip_alvo = (int)$dbh['loginip'];
                        $meu_ip  = (int)$db['loginip'];
                        if(!$is_adm_hunt && $ip_alvo > 0 && $meu_ip > 0 && $ip_alvo === $meu_ip){
                                echo "<script>self.location='?p=hunt&msg=16'</script>"; break;
                        }
                        if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
                        if($dbh['missao']==999){ echo "<script>self.location='?p=hunt&msg=10'</script>"; break; }
                        if($dbh['avatar']==0){ echo "<script>self.location='?p=hunt&msg=12'</script>"; break; }
                        if($dbh['renegado']=='sim' && $db['renegado']=='sim'){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
                        if($dbh['vila']==$db['vila'] && $dbh['renegado']!='sim' && $db['renegado']!='sim'){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
                        if(!$is_adm_hunt && date('Y-m-d H:i:s')<$dbh['penalidade_fim']){ echo "<script>self.location='?p=hunt&msg=7'</script>"; break; }
                        $_SESSION['prepare']=$dbh['id'];
                        try {
                                $sqlv = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? AND inimigoid=? ORDER BY id DESC LIMIT 1");
                                $sqlv->execute([$db['id'], $dbh['id']]);
                                $dbv = $sqlv->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) { $dbv = false; }
                        if(!$is_adm_hunt && $dbv && isset($dbv['data'])){
                                $soma=mktime(date('H')-12, date('i'), date('s'));
                                $penalidade=date('Y-m-d H:i:s',$soma);
                                if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=9'</script>"; break; }
                        }
                        try {
                                $sqlv = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? OR inimigoid=? ORDER BY id DESC LIMIT 1");
                                $sqlv->execute([$dbh['id'], $dbh['id']]);
                                $dbv = $sqlv->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) { $dbv = false; }
                        if(!$is_adm_hunt && $dbh['tipo']=='player' && $dbv && isset($dbv['data'])){
                                $soma=mktime(date('H'), date('i')-30, date('s'));
                                $penalidade=date('Y-m-d H:i:s',$soma);
                                if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=8'</script>"; break; }
                        }
                        $_SESSION['hunt']=1;
                        echo "<script>self.location='?p=prepare'</script>"; break;

                /* ============================================================
                   CASE 2 — Caçar ninja por Vila
                   ============================================================ */
                case 2:
                        $hunt=$c->decode($_POST['hunt_2'],$chaveuniversal);
                        if($hunt==0){ echo "<script>self.location='?p=hunt&msg=6'</script>"; break; }
                        if(($hunt<1)or($hunt>7)){ echo "<script>self.location='?p=home'</script>"; break; }
                        if(!$is_vip_hunt) if($db['yens']<5){ echo "<script>self.location='?p=hunt&msg=3'</script>"; break; }
                        if(!$is_vip_hunt) {
                                try {
                                        $stmt = $conexao->prepare("UPDATE usuarios SET yens=yens-5 WHERE id=?");
                                        $stmt->execute([$db['id']]);
                                } catch (PDOException $e) { }
                        }
                        $vila=$hunt;
                        try {
                                if($hunt==7) {
                                        $sqlh = $conexao->prepare("SELECT id,energia,preso,vila,renegado FROM usuarios WHERE avatar>0 AND energia>=25 AND renegado='sim' AND id<>? AND missao<>999 AND servidor_id=? ORDER BY RANDOM() LIMIT 1");
                                        $sqlh->execute([$db['id'], $meu_srv_id]);
                                } else {
                                        $sqlh = $conexao->prepare("SELECT id,energia,preso,vila,renegado FROM usuarios WHERE avatar>0 AND energia>=25 AND vila=? AND (renegado='nao' OR renegado='0' OR renegado=0) AND id<>? AND missao<>999 AND servidor_id=? ORDER BY RANDOM() LIMIT 1");
                                        $sqlh->execute([$vila, $db['id'], $meu_srv_id]);
                                }
                                $dbh = $sqlh->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                                echo "<script>self.location='?p=hunt&msg=5'</script>"; break;
                        }
                        if(!$dbh){ echo "<script>self.location='?p=hunt&msg=5'</script>"; break; }
                        if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
                        if($vila==7){
                                if($dbh['renegado']=='sim' && $db['renegado']=='sim'){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
                        } else {
                                if(!$is_adm_hunt && $dbh['vila']==$db['vila'] && $dbh['renegado']!='sim' && $db['renegado']!='sim'){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
                        }
                        $_SESSION['prepare']=$dbh['id'];
                        $_SESSION['hunt']=2;
                        echo "<script>self.location='?p=prepare'</script>"; break;

                /* ============================================================
                   CASE 3 — Caçar ninja por Nível
                   ============================================================ */
                case 3:
                        $hunt=$c->decode($_POST['hunt_3'],$chaveuniversal);
                        if($hunt==0){ echo "<script>self.location='?p=hunt&msg=6'</script>"; break; }
                        if(($hunt<1)or($hunt>3)){ echo "<script>self.location='?p=home'</script>"; break; }
                        if(!$is_vip_hunt) if($db['yens']<5){ echo "<script>self.location='?p=hunt&msg=3'</script>"; break; }
                        if(!$is_vip_hunt) {
                                try {
                                        $stmt = $conexao->prepare("UPDATE usuarios SET yens=yens-5 WHERE id=?");
                                        $stmt->execute([$db['id']]);
                                } catch (PDOException $e) { }
                        }
                        $nivel=$hunt;
                        switch($nivel){
                                case 1: $filtro='nivel<'.(int)$db['nivel']; break;
                                case 2: $filtro='nivel='.(int)$db['nivel']; break;
                                case 3: $filtro='nivel>'.(int)$db['nivel']; break;
                        }
                        try {
                                if($db['renegado']!='sim') {
                                        $sqlh = $conexao->prepare("SELECT id,energia,preso FROM usuarios WHERE avatar>0 AND energia>=25 AND ".$filtro." AND vila<>? AND id<>? AND missao<>999 AND servidor_id=? ORDER BY RANDOM() LIMIT 1");
                                        $sqlh->execute([$db['vila'], $db['id'], $meu_srv_id]);
                                } else {
                                        $sqlh = $conexao->prepare("SELECT id,energia,preso FROM usuarios WHERE avatar>0 AND energia>=25 AND ".$filtro." AND (renegado='nao' OR renegado='0' OR renegado=0) AND id<>? AND missao<>999 AND servidor_id=? ORDER BY RANDOM() LIMIT 1");
                                        $sqlh->execute([$db['id'], $meu_srv_id]);
                                }
                                $dbh = $sqlh->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                                echo "<script>self.location='?p=hunt&msg=5'</script>"; break;
                        }
                        if(!$dbh){ echo "<script>self.location='?p=hunt&msg=5'</script>"; break; }
                        if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
                        $_SESSION['prepare']=$dbh['id'];
                        $_SESSION['hunt']=3;
                        echo "<script>self.location='?p=prepare'</script>"; break;

                /* ============================================================
                   CASE 4 — Caçar por Tempo
                   ============================================================ */
                case 4:
                        $hunt=$c->decode($_POST['hunt_4'],$chaveuniversal);
                        if($db['hunt_restantes']<$hunt){ echo "<script>self.location='?p=home'</script>"; break; }
                        $horas=0;
                        if(($hunt<1)or($hunt>12)){ echo "<script>self.location='?p=home'</script>"; break; }
                        if($hunt<6){ $minutos=$hunt; } else { $aux=$hunt; do{ $aux=$aux-6; $horas=$horas+1; } while($aux>=6); $minutos=$aux; }
                        $soma=mktime(date('H')+$horas, date('i')+($minutos*10), date('s'));
                        $huntfim=date('Y-m-d H:i:s',$soma);
                        $_SESSION['hunt']=4;
                        try {
                                $stmt = $conexao->prepare("UPDATE usuarios SET hunt=4, hunt_fim=?, hunt_restantes=hunt_restantes-? WHERE id=?");
                                $stmt->execute([$huntfim, $hunt, $db['id']]);
                        } catch (PDOException $e) { }
                        echo "<script>self.location='?p=busyhunt'</script>"; break;

                /* ============================================================
                   CASE 5 — Caçar por Status (VIP)
                   ============================================================ */
                case 5:
                        $hunt=$c->decode($_POST['hunt_5'],$chaveuniversal);
                        if($hunt==0){ echo "<script>self.location='?p=hunt&msg=6'</script>"; break; }
                        if(!$is_vip_hunt && !$is_adm_hunt){ echo "<script>self.location='?p=hunt&msg=14'</script>"; break; }
                        $timeout=time()-900;
                        if($hunt==1) $tempo='AND timestamp>='.$timeout;
                        if($hunt==2) $tempo='AND timestamp<'.$timeout;
                        try {
                                if($db['renegado']=='sim') {
                                        /* Renegado: busca não-renegados de qualquer vila */
                                        $sqlh = $conexao->prepare("SELECT id,energia,preso,vila,renegado,penalidade_fim FROM usuarios WHERE energia>=25 AND avatar>0 AND id<>? AND missao<>999 ".$tempo." AND (renegado='nao' OR renegado='0' OR renegado=0) AND servidor_id=? ORDER BY RANDOM() LIMIT 1");
                                        $sqlh->execute([$db['id'], $meu_srv_id]);
                                } else {
                                        /* Não-renegado: busca não-renegados de vilas diferentes */
                                        $sqlh = $conexao->prepare("SELECT id,energia,preso,vila,renegado,penalidade_fim FROM usuarios WHERE energia>=25 AND avatar>0 AND id<>? AND missao<>999 ".$tempo." AND (renegado='nao' OR renegado='0' OR renegado=0) AND vila<>? AND servidor_id=? ORDER BY RANDOM() LIMIT 1");
                                        $sqlh->execute([$db['id'], $db['vila'], $meu_srv_id]);
                                }
                                $dbh = $sqlh->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                                echo "<script>self.location='?p=hunt&msg=5'</script>"; break;
                        }
                        if(!$dbh){ echo "<script>self.location='?p=hunt&msg=5'</script>"; break; }
                        if($dbh['energia']<25){ echo "<script>self.location='?p=hunt&msg=2'</script>"; break; }
                        if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
                        if($dbh['renegado']=='sim' && $db['renegado']=='sim'){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
                        if($dbh['vila']==$db['vila'] && $dbh['renegado']!='sim' && $db['renegado']!='sim'){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
                        if(!$is_adm_hunt && date('Y-m-d H:i:s')<$dbh['penalidade_fim']){ echo "<script>self.location='?p=hunt&msg=7'</script>"; break; }
                        $_SESSION['prepare']=$dbh['id'];
                        try {
                                $sqlv = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? AND inimigoid=? ORDER BY id DESC LIMIT 1");
                                $sqlv->execute([$db['id'], $dbh['id']]);
                                $dbv = $sqlv->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) { $dbv = false; }
                        if(!$is_adm_hunt && $dbv && isset($dbv['data'])){
                                $soma=mktime(date('H')-12, date('i'), date('s'));
                                $penalidade=date('Y-m-d H:i:s',$soma);
                                if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=9'</script>"; break; }
                        }
                        try {
                                $sqlv = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? OR inimigoid=? ORDER BY id DESC LIMIT 1");
                                $sqlv->execute([$dbh['id'], $dbh['id']]);
                                $dbv = $sqlv->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) { $dbv = false; }
                        if(!$is_adm_hunt && $dbv && isset($dbv['data'])){
                                $soma=mktime(date('H'), date('i')-30, date('s'));
                                $penalidade=date('Y-m-d H:i:s',$soma);
                                if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=8'</script>"; break; }
                        }
                        $_SESSION['hunt']=5;
                        echo "<script>self.location='?p=prepare'</script>"; break;

                /* ============================================================
                   CASE 6 — Caçar ninja em Outros Servidores (VIP / ADM/GM)
                   O sistema escolhe o servidor automaticamente.
                   ============================================================ */
                case 6:
                        if(!$is_vip_hunt && !$is_adm_hunt){ echo "<script>self.location='?p=hunt&msg=14'</script>"; break; }
                        /* Tenta buscar um servidor ativo diferente do atual */
                        $outro_srv_id = null;
                        try {
                                $stmt_srv = $conexao->prepare("SELECT id FROM servidores WHERE ativo=1 AND id<>? ORDER BY RANDOM() LIMIT 1");
                                $stmt_srv->execute([$meu_srv_id]);
                                $row_srv = $stmt_srv->fetch(PDO::FETCH_ASSOC);
                                if($row_srv) $outro_srv_id = (int)$row_srv['id'];
                        } catch (PDOException $e) { }
                        /* Fallback: busca qualquer servidor_id distinto presente em usuarios */
                        if($outro_srv_id === null){
                                try {
                                        $stmt_srv = $conexao->prepare("SELECT DISTINCT servidor_id FROM usuarios WHERE servidor_id IS NOT NULL AND servidor_id<>? AND avatar>0 AND energia>=25 AND missao<>999 ORDER BY RANDOM() LIMIT 1");
                                        $stmt_srv->execute([$meu_srv_id]);
                                        $row_srv = $stmt_srv->fetch(PDO::FETCH_ASSOC);
                                        if($row_srv) $outro_srv_id = (int)$row_srv['servidor_id'];
                                } catch (PDOException $e) { }
                        }
                        if($outro_srv_id === null){ echo "<script>self.location='?p=hunt&msg=17'</script>"; break; }
                        /* Busca ninja no servidor escolhido — sem restrição de vila (servidores diferentes = mundos diferentes) */
                        try {
                                $sqlh = $conexao->prepare("SELECT id,energia,preso,vila,renegado,penalidade_fim,missao,tipo FROM usuarios WHERE avatar>0 AND energia>=25 AND id<>? AND missao<>999 AND servidor_id=? ORDER BY RANDOM() LIMIT 1");
                                $sqlh->execute([$db['id'], $outro_srv_id]);
                                $dbh = $sqlh->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                                echo "<script>self.location='?p=hunt&msg=5'</script>"; break;
                        }
                        if(!$dbh){ echo "<script>self.location='?p=hunt&msg=17'</script>"; break; }
                        if($dbh['energia']<25){ echo "<script>self.location='?p=hunt&msg=2'</script>"; break; }
                        if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
                        if(!$is_adm_hunt && date('Y-m-d H:i:s')<$dbh['penalidade_fim']){ echo "<script>self.location='?p=hunt&msg=7'</script>"; break; }
                        $_SESSION['prepare']=$dbh['id'];
                        try {
                                $sqlv = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? AND inimigoid=? ORDER BY id DESC LIMIT 1");
                                $sqlv->execute([$db['id'], $dbh['id']]);
                                $dbv = $sqlv->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) { $dbv = false; }
                        if(!$is_adm_hunt && $dbv && isset($dbv['data'])){
                                $soma=mktime(date('H')-12, date('i'), date('s'));
                                $penalidade=date('Y-m-d H:i:s',$soma);
                                if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=9'</script>"; break; }
                        }
                        try {
                                $sqlv = $conexao->prepare("SELECT data FROM relatorios WHERE usuarioid=? OR inimigoid=? ORDER BY id DESC LIMIT 1");
                                $sqlv->execute([$dbh['id'], $dbh['id']]);
                                $dbv = $sqlv->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) { $dbv = false; }
                        if(!$is_adm_hunt && $dbh['tipo']=='player' && $dbv && isset($dbv['data'])){
                                $soma=mktime(date('H'), date('i')-30, date('s'));
                                $penalidade=date('Y-m-d H:i:s',$soma);
                                if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=8'</script>"; break; }
                        }
                        $_SESSION['hunt']=6;
                        echo "<script>self.location='?p=prepare'</script>"; break;
        }
}
?>
<div class="box_top">Caças</div>
<div class="box_middle">As caças são as formas mais rápidas de se ganhar experiência e yens. Escolha um dos tipos de caça abaixo, e boa sorte!<div class="sep"></div><div style="background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;"><img src="_img/yens.png" align="absmiddle" width="14" height="14" /> <b>Meus Yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b></div><div class="sep"></div>

<?php if (!$pvp_ativo_hunt): ?>
<div class="aviso" style="background:#3a1a1a;border:1px solid #cc3333;color:#ff9999;padding:10px;margin-bottom:10px;text-align:center;">
    <b>PVP Desabilitado</b> — O combate entre ninjas foi temporariamente suspenso pelos administradores. Apenas a caça por tempo está disponível.
</div>
<?php endif; ?>
        <?php if(isset($_GET['msg'])){
        switch($_GET['msg']){
                case 1:  $msg='Nenhum ninja encontrado com o nome informado.'; break;
                case 2:  $msg='A energia do ninja está muito baixa para uma luta.'; break;
                case 3:  $msg='Yens insuficientes para realizar esta ação.'; break;
                case 4:  $msg='Ninja não pode ser atacado pois está na prisão de sua respectiva vila.'; break;
                case 5:  $msg='No momento, não existem ninjas disponíveis para batalha.'; break;
                case 6:  $msg='Dados informados não são suficientes para realizar a caça.'; break;
                case 7:  $msg='Este ninja não pode ser atacado pois acabou de enfrentar um outro ninja.'; break;
                case 8:  $msg='Este ninja esteve em uma terrível batalha nos últimos 30 minutos.<br />Tente novamente mais tarde.'; break;
                case 9:  $msg='Você já enfrentou este ninja nas últimas 12 horas.<br />Tente novamente mais tarde.'; break;
                case 10: $msg='Ninja em período de férias!'; break;
                case 11: $msg='Este ninja reside na mesma vila que você.'; break;
                case 12: $msg='Este ninja nunca realizou o login no jogo, e por isso não pode ser atacado.'; break;
                case 13: $msg='Sua energia está muito baixa para entrar em uma batalha.'; break;
                case 14: $msg='Disponível apenas para jogadores VIP.'; break;
                case 15: $msg='Código <b>CAPTCHA</b> incorreto.'; break;
                case 16: $msg='Este ninja não pode ser encontrado.'; break;
                case 17: $msg='Não há ninjas disponíveis em outros servidores no momento.'; break;
        }
        echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
        } ?>

<?php if ($pvp_ativo_hunt): ?>
    <!-- FORM 1: Caçar ninja específico -->
    <form method="post" action="?p=hunt" onsubmit="subm1.value='Carregando...';subm1.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="hunt_tipo" value="<?php echo $c->encode('1',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar Ninja Específico (<?php if($is_vip_hunt) echo 'gratuito para jogadores VIP'; else echo '5,00 yens'; ?>)</legend>
        <div style="width:320px;margin-right:15px;float:left;">Se você sabe quem deseja procurar, digite o nome ao lado. Lembre-se que as chances de encontrar um ninja mais experiente que você varia pela diferença de níveis.</div>
        <div align="center"><input type="text" id="hunt_1" name="hunt_1" /><br /><input type="submit" id="subm1" name="subm1" class="botao" value="Caçar" /></div>
        <div class="clear"></div>
    </fieldset>
    </form>

    <!-- FORM 2: Caçar por Vila -->
    <form method="post" action="?p=hunt" onsubmit="subm2.value='Carregando...';subm2.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="hunt_tipo" value="<?php echo $c->encode('2',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar Ninja por Vila (<?php if($is_vip_hunt) echo 'gratuito para jogadores VIP'; else echo '5,00 yens'; ?>)</legend>
        <div style="width:320px;margin-right:15px;float:left;">Você pode concentrar suas caças apenas em uma vila (ideal para conflitos entre vilas). Escolha uma das opções ao lado, e inicie suas buscas!</div>
        <div align="center">
                <select id="hunt_2" name="hunt_2">
                <option value="<?php echo $c->encode('0',$chaveuniversal); ?>" selected="selected">-- Selecione --</option>
                <option value="<?php echo $c->encode('1',$chaveuniversal); ?>">Vila da Folha</option>
                <option value="<?php echo $c->encode('2',$chaveuniversal); ?>">Vila da Areia</option>
                <option value="<?php echo $c->encode('3',$chaveuniversal); ?>">Vila do Som</option>
                <option value="<?php echo $c->encode('4',$chaveuniversal); ?>">Vila da Chuva</option>
                <option value="<?php echo $c->encode('5',$chaveuniversal); ?>">Vila da Nuvem</option>
                <option value="<?php echo $c->encode('6',$chaveuniversal); ?>">Vila da Névoa</option>
                <option value="<?php echo $c->encode('7',$chaveuniversal); ?>">Akatsuki</option>
            </select><br /><input type="submit" id="subm2" name="subm2" class="botao" value="Caçar" /></div>
        <div class="clear"></div>
    </fieldset>
    </form>

    <!-- FORM 3: Caçar por Nível -->
    <form method="post" action="?p=hunt" onsubmit="subm3.value='Carregando...';subm3.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="hunt_tipo" value="<?php echo $c->encode('3',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar Ninja por Nível (<?php if($is_vip_hunt) echo 'gratuito para jogadores VIP'; else echo '5,00 yens'; ?>)</legend>
        <div style="width:320px;margin-right:15px;float:left;">Procure ninjas mais fracos, equivalentes ou mais fortes que você.<br /><?php if($db['nivel']>1){ ?>- Ninjas Mais Fracos: até nível <?php echo $db['nivel']-1; ?>;<br /><?php } ?>- Ninjas Equivalentes: nível <?php echo $db['nivel']; ?>;<br />- Ninjas Mais Fortes: acima de nível <?php echo $db['nivel']+1; ?>.</div>
        <div align="center">
                <select id="hunt_3" name="hunt_3">
                <option value="<?php echo $c->encode('0',$chaveuniversal); ?>" selected="selected">-- Selecione --</option>
                <?php if($db['nivel']>1){ ?><option value="<?php echo $c->encode('1',$chaveuniversal); ?>">Ninjas Mais Fracos</option><?php } ?>
                <option value="<?php echo $c->encode('2',$chaveuniversal); ?>">Ninjas Equivalentes</option>
                <option value="<?php echo $c->encode('3',$chaveuniversal); ?>">Ninjas Mais Fortes</option>
            </select><br /><input type="submit" id="subm3" name="subm3" class="botao" value="Caçar" /></div>
        <div class="clear"></div>
    </fieldset>
    </form>

    <!-- FORM 5: Caçar por Status (VIP) -->
    <?php if($is_vip_hunt){ ?>
    <form method="post" action="?p=hunt" onsubmit="subm5.value='Carregando...';subm5.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="hunt_tipo" value="<?php echo $c->encode('5',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar Ninja por Status (gratuito - exclusivo para jogadores VIP)</legend>
        <div style="width:320px;margin-right:15px;float:left;">Procure ninjas pelo seu status atual. Você pode pesquisar por ninjas que estejam jogando neste exato momento, ou então atacar ninjas despreparados. Qualquer ninja procurado neste modo não pertencerá a mesma vila que você.</div>
        <div align="center">
                <select id="hunt_5" name="hunt_5">
                <option value="<?php echo $c->encode('0',$chaveuniversal); ?>" selected="selected">-- Selecione --</option>
                <option value="<?php echo $c->encode('1',$chaveuniversal); ?>">Online</option>
                <option value="<?php echo $c->encode('2',$chaveuniversal); ?>">Offline</option>
            </select><br /><input type="submit" id="subm5" name="subm5" class="botao" value="Caçar" /></div>
        <div class="clear"></div>
    </fieldset>
    </form>
    <?php } ?>

    <!-- FORM 6: Caçar em Outros Servidores — visível para todos, funciona apenas para VIP/ADM -->
    <form method="post" action="?p=hunt" onsubmit="subm6.value='Carregando...';subm6.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="hunt_tipo" value="<?php echo $c->encode('6',$chaveuniversal); ?>" />
    <fieldset style="border-color:#aa4400;">
        <legend style="color:#ffaa44;">&#9733; Caçar Ninja em Outros Servidores <?php if($is_vip_hunt || $is_adm_hunt){ echo '(gratuito)'; } else { echo '<span style="color:#ff6666;font-size:11px;">(exclusivo VIP)</span>'; } ?></legend>
        <div style="width:320px;margin-right:15px;float:left;">
            O sistema seleciona automaticamente um servidor diferente do seu e busca um ninja para batalha. Ideal para guerras entre servidores!<br />
            <span style="color:#ffaa44;font-size:11px;">&#9888; O servidor adversário é escolhido aleatoriamente pelo sistema.</span>
        </div>
        <div align="center">
            <br />
            <?php if($is_vip_hunt || $is_adm_hunt){ ?>
            <input type="submit" id="subm6" name="subm6" class="botao" value="&#128269; Caçar em Outro Servidor" />
            <?php } else { ?>
            <span style="color:#ff8888;font-size:12px;">Apenas jogadores VIP podem usar esta modalidade.</span>
            <?php } ?>
        </div>
        <div class="clear"></div>
    </fieldset>
    </form>
<?php endif; ?>

    <!-- FORM 4: Caçar por Tempo -->
    <form method="post" action="?p=hunt" onsubmit="subm4.value='Carregando...';subm4.disabled=true;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="hunt_tipo" value="<?php echo $c->encode('4',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar por Tempo</legend>
        <div style="width:320px;margin-right:15px;float:left;padding-bottom:50px;">O melhor método para se ganhar experiência e yens rapidamente. É recomendável caçar de 10 em 10 minutos, aproveitando ao máximo o tempo diário. Não há custos para este tipo de caça.<br /><br /><img src="_img/clock.png" align="absmiddle" /> <b>Tempo Restante: <?php echo $db['hunt_restantes']*10; ?> minutos.</b></div>
        <div align="center">
                <?php if($db['hunt_restantes']==0) echo 'Seus minutos diários de caça se esgotaram.'; else { ?>
                <select id="hunt_4" name="hunt_4">
                <?php $i=1; do{ ?>
                <option value="<?php echo $c->encode($i,$chaveuniversal); ?>"><?php echo $i*10; ?> minutos</option>
                <?php $i++; } while($i<=$db['hunt_restantes']); ?>
            </select><br /><input type="submit" id="subm4" name="subm4" class="botao" value="Caçar" /></div>
            <?php } ?>
        <div class="clear"></div>
    </fieldset>
    </form>
</div>
<div class="box_bottom"></div>
