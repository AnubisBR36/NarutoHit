<?php require_once('trava.php'); ?>
<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=school'</script>"; return; }
if($db['nivel']<15){ echo "<script>self.location='?p=room&id=".$_GET['id']."&msg=1'</script>"; return; }
if($db['natureza1']<>''){ echo "<script>self.location='?p=room&id=".$_GET['id']."&msg=5'</script>"; return; }

// Verificar se o usuário tem acesso à sala
$room_id = (int)$_GET['id'];
$atual = date('Y-m-d H:i:s');

try {
    $check_room = $conexao->prepare("SELECT * FROM salas WHERE id=? AND usuarioid=? AND fim>?");
    $check_room->execute([$room_id, $db['id'], $atual]);
    $dbr = $check_room->fetch(PDO::FETCH_ASSOC);
    
    if(!$dbr) {
        echo "<script>self.location='?p=school'</script>";
        return;
    }
    
    $fim = $dbr['fim'];
    if($atual < $dbr['fim']){
        // Calcular tempo restante usando PHP 
        $tempo_restante = strtotime($dbr['fim']) - strtotime($atual);
        if($tempo_restante > 0) {
            $horas = floor($tempo_restante / 3600);
            $minutos = floor(($tempo_restante % 3600) / 60);
            $segundos = $tempo_restante % 60;
            $fim_display = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
            $msgconc = '<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim_display.'</span></b>';
            $msg = '<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim_display.'</span></b>';
        } else {
            echo "<script>self.location='?p=school'</script>"; 
            return;
        }
    } else { 
        echo "<script>self.location='?p=school'</script>"; 
        return; 
    }
} catch (PDOException $e) {
    echo "<script>self.location='?p=school'</script>";
    return;
}
?>
<script language="javascript" type="text/javascript">
var conc=0;
function calculafim(div,divtotal){
        if(conc==0){
        var navegador=navigator.appName;
        var tmp = document.getElementById(div).innerHTML.split(":");
        var s = tmp[2];
        var m = tmp[1];
        var h = tmp[0];
        s--;
        if (s < 00){ s = 59;    m--; }
        if (m < 00){ m = 59;    h--; };
        s = new String(s); if (s.length < 2) s = "0" + s;
        m = new String(m); if (m.length < 2) m = "0" + m;
        h = new String(h); if (h.length < 2) h = "0" + h;
        
        var temp = h + ":" + m + ":" + s;
        
        document.getElementById(div).innerHTML = temp;
        document.getElementById(div).value = temp;
        atualiza(div,divtotal);
        }
}
<?php if($atual<$dbr['fim']) echo "window.setInterval('calculafim(\"sala_tempo\",\"mensagem\")',1000);"; ?>
function atualiza(div,divtotal){
        var element = document.getElementById(div);
        if(!element) return;
        var timeValue = element.value || element.innerHTML;
        if(timeValue && timeValue <= "00:00:01"){
                conc=1;
                self.location="?p=school";
        }
}
</script>
<?php
$nat=rand(1,5);
switch($nat){
        case 1: $txtnat='fogo'; $desc='Fogo (Katon)'; break;
        case 2: $txtnat='agua'; $desc='Água (Suiton)'; break;
        case 3: $txtnat='vento'; $desc='Vento (Fuuton)'; break;
        case 4: $txtnat='raio'; $desc='Raio (Raiton)'; break;
        case 5: $txtnat='terra'; $desc='Terra (Doton)'; break;
}
if($db['natureza1']=='') {
    try {
        $stmt = $conexao->prepare("UPDATE usuarios SET natureza1=? WHERE id=?");
        $stmt->execute([$txtnat, $db['id']]);
    } catch (PDOException $e) {
        // Handle error silently
    }
}
?>
<div class="box_top">Natureza do Chakra</div>
<div class="box_middle">Parabéns, o papel reagiu ao seu chakra! Veja abaixo como ele ficou.<div class="sep"></div>
        <div class="aviso" id="mensagem">
    <?php
        if(isset($_GET['msg'])){
                switch($_GET['msg']){
                        case 1: $errmsg='Você não está pronto para controlar a natureza do seu chakra.<br />Volte quando estiver no nível 15.'; break;
                        case 2: $errmsg='Parabéns! Você aprendeu um novo jutsu!<br />Utilize nossa área de treinamento para aperfeiçoá-lo assim que desejar.'; break;
                        case 3: $errmsg='Você não está pronto para treinar sua linhagem avançada.<br />Volte quando estiver no nível 5.'; break;
                        case 4: $errmsg='Seu doujutsu já foi liberado.<br />O aprimoramento de seu doujtsu depende da utilização.'; break;
                }
        echo $errmsg.'<div class="sep"></div>';
        }
        ?>
    <b>
        <?php
        if($atual<$dbr['fim'])
                echo $msg; 
        else
                echo $msgconc;
        ?>
    </b></div>
        <div class="sep"></div><div align="center"><img src="_img/jutsus/papel_<?php echo $txtnat; ?>.jpg" /><div class="sep"></div>Significa que o seu chakra dominante é do elemento <?php echo $desc; ?>.<br />Abaixo está um dos jutsus que você conseguirá dominar.
        <?php
    $jutsus_list = [];
    try {
        $stmt_jutsus = $conexao->prepare("SELECT * FROM table_jutsus WHERE natureza=?");
        $stmt_jutsus->execute([$txtnat]);
        $result = $stmt_jutsus->fetchAll(PDO::FETCH_ASSOC);
        if($result !== false && is_array($result)) {
            $jutsus_list = $result;
        }
    } catch (PDOException $e) {
        $jutsus_list = [];
    }
        ?>
    <table width="100%" cellpadding="0" cellspacing="1">
    <?php if(count($jutsus_list) > 0): ?>
    <?php foreach($jutsus_list as $dbj): ?>
    <?php if(is_array($dbj) && isset($dbj['id']) && isset($dbj['nome']) && isset($dbj['forca'])): ?>
    <tr>
        <td colspan="3"><div class="sep"></div></td>
    </tr>
    <?php
        $texto1='<div align=center><b>Elemento</b><br /><span class="sub2"><img src="_img/jutsus/'.$txtnat.'.png" height="14" /> '.$desc.'</span>';
        $texto2='<b>Força do Jutsu</b><br /><span class="sub2">'.$dbj['forca'].' pontos</span></div>';
        ?>
    <tr class="table_dados" style="background:#323232">
      <td width="180" rowspan="2"><img src="_img/jutsus/<?php echo $dbj['id']; ?>.jpg" /></td>
      <td colspan="2"><b><?php echo htmlspecialchars($dbj['nome']); ?></b></td>
      </tr>
    <tr class="table_dados" style="background:#323232">
        <td><?php echo $texto1; ?></td>
        <td><?php echo $texto2; ?></td>
    </tr>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php else: ?>
    <tr>
        <td colspan="3"><div class="aviso">Nenhum jutsu disponível para este elemento no momento.</div></td>
    </tr>
    <?php endif; ?>
    </table>
    </div>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" onclick="location.href='?p=room&id=<?php echo $_GET['id']; ?>'" value="Voltar" /></div>
</div>
<div class="box_bottom"></div>