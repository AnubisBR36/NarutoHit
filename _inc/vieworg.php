<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; return; }

try {
    $stmt = $conexao->prepare("SELECT * FROM organizacoes WHERE id = ? AND servidor_id = ?");
    $stmt->execute([$_GET['id'], (int)($_SESSION['servidor_id'] ?? 1)]);
    $dbo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$dbo){ echo "<script>self.location='?p=home'</script>"; return; }
    
    $stmt = $conexao->prepare("SELECT m.*,u.usuario,u.nivel niveluser FROM membros m LEFT OUTER JOIN usuarios u ON m.usuarioid=u.id WHERE m.status='sim' AND m.orgid=? ORDER BY posicao ASC, niveluser DESC");
    $stmt->execute([$_GET['id']]);
    $membros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dbm = isset($membros[0]) ? $membros[0] : null;
    
    // Verificar se o usuário logado é membro do clã
    $userIsMember = false;
    foreach($membros as $membro) {
        if($membro['usuarioid'] == $db['id']) {
            $userIsMember = true;
            break;
        }
    }
} catch (PDOException $e) {
    echo "<script>self.location='?p=home'</script>";
    return;
}
?>
<div class="box_top">[<?php echo $dbo['sigla']; ?>] <?php echo $dbo['nome']; ?></div>
<div class="box_middle">
        <table width="100%" cellpadding="0" cellspacing="0">
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td width="20%" style="padding-right:20px;"><b>Clã:</b></td>
            <td><?php echo $dbo['nome']; ?></td>
            <td rowspan="8" style="background:#282828;text-align:center;" width="200"><?php if($dbo['logo']<>'') echo '<img src="'.$dbo['logo'].'" width="195" height="140" border="0" />'; ?></td>
        </tr>
        <tr>
                <td style="padding-right:20px;"><b>Sigla:</b></td>
            <td>[<?php echo $dbo['sigla']; ?>]</td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td style="padding-right:20px;"><b>Vila:</b></td>
            <td>Vila</td>
        </tr>
        <tr>
                <td style="padding-right:20px;"><b>Líder:</b></td>
            <td><?php if($dbm) echo '<a href="?p=view&amp;view='.$dbm['usuario'].'">'.$dbm['usuario'].'</a>'; else echo 'Nenhum'; ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td style="padding-right:20px;"><b>Fundação:</b></td>
            <td><?php $ex=explode(' ',$dbo['data']); $data=explode('-',$ex[0]); echo $data[2].'/'.$data[1].'/'.$data[0].', às '.$ex[1]; ?></td>
        </tr>
        <tr>
                <td style="padding-right:20px;"><b>Nível:</b></td>
            <td>Nível <?php echo $dbo['nivel']; ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
                <td style="padding-right:20px;"><b>Nível Mínimo:</b></td>
            <td>Nível <?php echo $dbo['minimo']; ?></td>
        </tr>
        <tr style="padding-right:20px;">
                <td style="padding-right:20px;"><b>Membros:</b></td>
            <td><?php echo count($membros); ?>/<?php echo 5+($dbo['nivel']*5); ?></td>
        </tr>
    </table>
        <div class="sep"></div>
    <div class="apresentacao"><?php if($dbo['descricao']<>'') echo str_replace(array('<p>','</p>'),array('','<br />'),$dbo['descricao']); else echo 'Nenhuma descrição para este clã.'; ?></div>
        <div class="sep"></div>
    <table width="100%" cellpadding="0" cellspacing="1">
        <?php 
        $i=1; 
        if(count($membros)==0) {
            echo '<tr><td colspan="4"><div class="aviso">Nenhum membro neste clã.</div></td></tr>'; 
        } else {
            foreach($membros as $membro) { ?>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
                <td width="5%"><?php echo $i; ?></td>
            <td width="35%" style="text-align:left"><a href="?p=view&amp;view=<?php echo strtolower($membro['usuario']); ?>"><?php echo str_replace(array('<','>'),array('&lt;','&gt;'),$membro['usuario']); ?></a><br /><span class="sub2">Nível <?php echo $membro['niveluser']; ?></span></td>
            <td width="27%"><?php echo $membro['rank']; ?></td>
            <td width="33%"><?php
                        switch($membro['posicao']){
                                case 1: echo 'Administrador'; break;
                                case 2: echo 'Moderador'; break;
                                case 3: echo 'Membro'; break;
                        } ?></td>
        </tr>
        <?php $i++; } 
        } ?>
    </table>
    <?php if(($db['orgid']==0)&&(!$userIsMember)&&(count($membros)<(5+($dbo['nivel']*5)))){ ?>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" onclick="if(confirm('Deseja requisitar ingresso neste clã?')==true) location.href='?p=requestorg&id=<?php echo $c->encode($_GET['id'].','.$dbo['minimo'],$chaveuniversal); ?>';" value="Requisitar Ingresso" /></div>
    <?php } elseif($userIsMember) { ?>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" onclick="location.href='?p=myorg';" value="Ir para Meu Clã" /></div>
    <?php } ?>
</div>
<div class="box_bottom"></div>
