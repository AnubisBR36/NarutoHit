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
        if((document.getElementById(div).value) < "00:00:01"){
                self.location="?p=school";
                conc=1;
        }
}
</script>
<div class="box_top">Natureza do Chakra</div>
<div class="box_middle">Acredito que você está pronto para dominar a natureza do seu chakra. Como vamos descobrir a natureza primária de seu chakra, utilizaremos um método muito simples, que é através de papéis sensíveis ao chakra. Basta você concentrar sua energia que o papel irá reagir, e então saberemos os tipos de jutsus que você poderá usar!<div class="sep"></div>
        <div class="aviso" id="mensagem">
    <?php
        if(isset($_GET['msg'])){
                switch($_GET['msg']){
                        case 1: $errmsg='Você não está pronto para controlar a natureza do seu chakra.<br />Volte quando estiver no nível 15.'; break;
                        case 2: $errmsg='Parabéns! Você aprendeu um novo jutsu!<br />Utilize nossa área de treinamento para aperfeiçoá-lo assim que desejar.'; break;
                        case 3: $errmsg='Você não está pronto para treinar sua linhagem avançada.<br />Volte quando estiver no nível 5.'; break;
                        case 4: $errmsg='Seu doujutsu já foi liberado.<br />O aprimoramento de seu doujtsu depende da utilização.'; break;
                        case 5: $errmsg='Você já possui uma natureza de chakra definida.'; break;
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
    </b></div><div class="sep"></div>
    <div align="center"><img src="_img/jutsus/papel_chakra.jpg" /><div class="sep"></div><span class="sub2">Este método é muito eficiente, e lhe ajudará a descobrir a natureza primária de seu chakra.<br />Assim que estiver pronto...<div class="sep"></div><input type="button" class="botao" onclick="location.href='?p=discover&id=<?php echo $_GET['id']; ?>'" value="Concentrar Chakra" /></span></div>
</div>
<div class="box_bottom"></div>