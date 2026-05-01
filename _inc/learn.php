<?php
require_once('verificar_sala.php');
require_once('conexao.php');
require_once('Encrypt.php');

// Inicializar classe de criptografia se não existir
if(!isset($c)){
    $c = new Crypt();
}
if(!isset($chaveuniversal)){
    $chaveuniversal = "anubisserve"; // Defina a chave universal se não estiver definida
}

if(!isset($_GET['id'])){ echo "<script>self.location='?p=school'</script>"; return; }

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
$sqltext='';
$sqladd='';

// Get user's jutsus
try {
    $stmt = $conexao->prepare("SELECT jutsu FROM jutsus WHERE usuarioid = ?");
    $stmt->execute([$db['id']]);
    while($row = $stmt->fetch()){
        $sqltext .= " AND id<>".$row['jutsu']." ";
    }
} catch (PDOException $e) {
    // Handle error silently or log it
}

if($db['natureza1']<>'') $sqltext.=" OR nivel<=".$db['nivel']." AND natureza='".$db['natureza1']."' ".$sqladd;
if($db['natureza2']<>'') $sqltext.=" OR nivel<=".$db['nivel']." AND natureza='".$db['natureza2']."' ".$sqladd;
if($db['natureza3']<>'') $sqltext.=" OR nivel<=".$db['nivel']." AND natureza='".$db['natureza3']."' ".$sqladd;

// Get available jutsus
try {
    $nivel_max = $db['nivel'] + 2;
    $sql = "SELECT * FROM table_jutsus WHERE (nivel <= ? AND natureza='nenhum') ".$sqltext." ORDER BY natureza DESC, nivel ASC";
    $stmt = $conexao->prepare($sql);
    $stmt->execute([$nivel_max]);
    $jutsus = $stmt->fetchAll();
} catch (PDOException $e) {
    $jutsus = [];
}
?>
<div class="box_top">Aprender Jutsu</div>
<div class="box_middle">Estes são os jutsus que posso lhe ensinar no momento, por isso não mostrarei os outros. Escolha um deles, e vamos ao treinamento. Caso deseje voltar, clique <a href="?p=room&amp;id=<?php echo $_GET['id']; ?>">aqui</a>.<div class="sep"></div>
        <div class="aviso" id="mensagem">
    <?php
        if(isset($_GET['msg'])){
                switch($_GET['msg']){
                        case 1: $errmsg='Você não está pronto para controlar a natureza do seu chakra.<br />Volte quando estiver no nível 15.'; break;
                        case 2: $errmsg='Yens insuficientes para aprender este jutsu.'; break;
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
    <table width="100%" cellpadding="0" cellspacing="1">
        <?php if(count($jutsus)==0) echo '<tr><td colspan="3"><div class="aviso">Nenhum jutsu disponível.</div><div class="sep"></div></td></tr>'; else foreach($jutsus as $dbj){ ?>
        <?php if(($dbj['doujutsu']>0)&&($db['doujutsu']<>$dbj['doujutsu'])){ } else { ?>
        <?php if($db['doujutsu_nivel']>=$dbj['doujutsu_nivel']){ ?>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
                <td width="180"><img src="_img/jutsus/<?php echo $dbj['id']; ?>.jpg" /></td>
            <td><b>Natureza</b><br /><?php if($dbj['natureza']<>'nenhum'){
                                switch($dbj['natureza']){
                                        case 'agua': $nat='Água (Suiton)'; break;
                                        case 'fogo': $nat='Fogo (Katon)'; break;
                                        case 'raio': $nat='Raio (Raiton)'; break;
                                        case 'terra': $nat='Terra (Doton)'; break;
                                        case 'vento': $nat='Vento (Fuuton)'; break;
                                }
                        echo '<span class="sub2"><img src="_img/jutsus/'.$dbj['natureza'].'.png" height="14" /> '.$nat.'</span>'; } else echo '<span class="sub2">Nenhuma</span>'; ?><br /><br /><b>Força do Jutsu</b><br /><span class="sub2"><?php echo $dbj['forca']; ?> pontos</span></td>
            <td><b>Requerimentos</b><br /><span class="sub2">Nível <?php echo $dbj['nivel']; ?><br /><?php echo number_format($dbj['valor'],2,',','.'); ?> yens</span><br /><br /><?php if($db['nivel']>=$dbj['nivel'] && $db['yens']>=$dbj['valor']){ ?><input type="button" class="botao" value="Aprender" onclick="location.href='?p=pratice&id=<?php echo $_GET['id']; ?>&jutsu=<?php echo $c->encode($dbj['id'],$chaveuniversal); ?>'" /><?php } else { ?><span class="sub2">Requisitos não atendidos</span><?php } ?></td>
        </tr>
        <tr>
                <td colspan="3"><div class="sep"></div></td>
        </tr>
        <?php }} ?>
        <?php } ?>
    </table>
    <div align="center"><input type="button" class="botao" value="Sair da Sala" onclick="location.href='?p=room&leave=true'" /></div>
</div>
<div class="box_bottom"></div>