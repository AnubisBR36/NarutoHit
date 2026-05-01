<?php require_once('verificar_sala.php'); ?>
<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=school'</script>"; return; }
$id=$_GET['id'];

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
        var navegador=navigator.appName;
        var element = document.getElementById(div);
        if(!element || !element.innerHTML) return;

        var tmp = element.innerHTML.split(":");
        if(tmp.length != 3) return;

        var s = parseInt(tmp[2]);
        var m = parseInt(tmp[1]);
        var h = parseInt(tmp[0]);
        s--;
        if (s < 0){ s = 59; m--; }
        if (m < 0){ m = 59; h--; }
        if (h < 0){ h = 0; m = 0; s = 0; }

        s = new String(s); if (s.length < 2) s = "0" + s;
        m = new String(m); if (m.length < 2) m = "0" + m;
        h = new String(h); if (h.length < 2) h = "0" + h;

        var temp = h + ":" + m + ":" + s;

        element.innerHTML = temp;
        if(element.value !== undefined) element.value = temp;
        atualiza(div,divtotal);
}
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
    </b><br /><a href="?p=room&amp;id=<?php echo $_GET['id']; ?>&leave=true">Sair da Sala</a></div><div class="sep"></div>

    <div align="center">
        <img src="_img/jutsus/papel_chakra.jpg" />
        <div class="sep"></div>
        <span class="sub2">Este método é muito eficiente, e lhe ajudará a descobrir a natureza primária de seu chakra.<br />Assim que estiver pronto...</span>
        <div class="sep"></div>
        <table width="100%" cellpadding="2" cellspacing="0">
      <tr>
        <td align="center">
        <?php if($db['nivel']>=15) { ?>
        <a href="?p=discover&amp;id=<?php echo $_GET['id']; ?>"><img src="_img/school/papel_chakra.jpg" border="0" /></a>
        <?php } else { ?>
        <img src="_img/school/papel_chakra.jpg" border="0" style="opacity: 0.5;" />
        <div style="background: url('_img/fundo_botao.jpg') no-repeat center; width: 200px; height: 25px; margin: 5px auto; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: bold; font-size: 12px;">
            Level 15 para treinar (<?php echo max(0, 15 - $db['nivel']); ?>)
        </div>
        <?php } ?>
        </td>
      </tr>
    </table>
    </div>
</div>
<div class="box_bottom"></div>

<script>
<?php if(isset($dbr) && $atual < $dbr['fim']) { ?>
setInterval(function(){ 
    if(conc==0) calculafim('sala_tempo','sala_tempo'); 
}, 1000);
<?php } ?>

function mostrarAvisoNivel() {
    alert('⚠️ Nível Insuficiente\n\nVocê não possui o nível necessário para descobrir a natureza do seu chakra.\n\nSeu nível atual: <?php echo isset($db['nivel']) ? $db['nivel'] : 0; ?>\nNível necessário: 15\nNíveis restantes: <?php echo max(0, 15 - (isset($db['nivel']) ? $db['nivel'] : 0)); ?>');
    return false;
}
</script>