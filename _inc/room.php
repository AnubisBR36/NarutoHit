<?php
$atual = date('Y-m-d H:i:s');

// Validate room ID first
if(!isset($_GET['id'])) { 
    echo "<script>self.location='?p=school'</script>"; 
    return; 
}

$room_id = (int)$_GET['id'];
if($room_id <= 0) { 
    echo "<script>self.location='?p=school'</script>"; 
    return; 
}

// Clean up expired reservations first
try {
    $cleanup_stmt = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE usuarioid<>0 AND fim<=?");
    $cleanup_stmt->execute([$atual]);
} catch (PDOException $e) {
    // Handle cleanup error silently
}

// Handle leave action
if(isset($_GET['leave'])){
    try {
        $update_stmt = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE id=?");
        $update_stmt->execute([$room_id]);
    } catch (PDOException $e) {
        // Handle database error during update
    }
    echo "<script>self.location='?p=school'</script>";
    return;
}

// Check if user already has a room reserved
try {
    $check_user_room = $conexao->prepare("SELECT id FROM salas WHERE usuarioid=? AND fim>?");
    $check_user_room->execute([$db['id'], $atual]);
    $existing_room = $check_user_room->fetch(PDO::FETCH_ASSOC);

    if($existing_room && $existing_room['id'] != $room_id) {
        // User has another room, redirect to that room
        echo "<script>self.location='?p=room&id=".$existing_room['id']."'</script>";
        return;
    }
} catch (PDOException $e) {
    echo "<script>self.location='?p=school'</script>";
    return;
}

// Check if the requested room exists and is available
try {
    $check_room = $conexao->prepare("SELECT * FROM salas WHERE id=?");
    $check_room->execute([$room_id]);
    $room_data = $check_room->fetch(PDO::FETCH_ASSOC);

    if(!$room_data) {
        // Room doesn't exist
        echo "<script>self.location='?p=school'</script>";
        return;
    }

    // Check if room is occupied by someone else
    if($room_data['usuarioid'] != 0 && $room_data['usuarioid'] != $db['id'] && $room_data['fim'] > $atual) {
        // Room is occupied by someone else
        echo "<script>alert('Esta sala está ocupada por outro usuário!'); self.location='?p=school';</script>";
        return;
    }
} catch (PDOException $e) {
    echo "<script>self.location='?p=school'</script>";
    return;
}

// Reserve the room for this user (5 minutes from now)
$soma = mktime(date('H'), date('i') + 5, date('s'));
$fim = date('Y-m-d H:i:s', $soma);

try {
    $update_stmt = $conexao->prepare("UPDATE salas SET usuarioid=?, fim=? WHERE id=?");
    $update_stmt->execute([$db['id'], $fim, $room_id]);
} catch (PDOException $e) {
    echo "<script>self.location='?p=school'</script>";
    return;
}

// Get the updated room data for display
try {
    $sqlr = $conexao->prepare("SELECT usuarioid, fim FROM salas WHERE id=?");
    $sqlr->execute([$room_id]);
    $dbr = $sqlr->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbr = false;
}

// Calculate remaining time
$msg = '';
$msgconc = '';

if($dbr && $atual < $dbr['fim']) {
    try {
        $tempo_restante = strtotime($dbr['fim']) - strtotime($atual);
        if($tempo_restante > 0) {
            $horas = floor($tempo_restante / 3600);
            $minutos = floor(($tempo_restante % 3600) / 60);
            $segundos = $tempo_restante % 60;
            $fim_display = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
            $msg = '<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim_display.'</span></b>';
            $msgconc = '<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim_display.'</span></b>';
        } else {
            $msg = 'Sua reserva expirou.';
            $msgconc = 'Sua reserva expirou.';
        }
    } catch (Exception $e) {
        $msg = 'Erro ao calcular tempo restante.';
        $msgconc = 'Erro ao calcular tempo restante.';
    }
} else {
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
<div class="box_top">Escola Ninja - Sala <?php echo $room_id; ?></div>
<div class="box_middle">
	Bem-vindo à Sala <?php echo $room_id; ?> da Escola Ninja! Aqui você pode aprender novas habilidades e aprimorar seus conhecimentos ninja. Sua sessão de treinamento tem duração de 5 minutos.<div class="sep"></div>
	<?php echo $msg; ?>
	<div class="sep"></div>
	<table width="100%" cellpadding="2" cellspacing="0">
  	  <tr>
            <td align="center">
            <?php if($db['nivel']>=15) { ?>
            <a href="?p=elements&amp;id=<?php echo $_GET['id']; ?>"><img src="_img/school/chakra.jpg" border="0" /></a>
            <?php } else { ?>
            <img src="_img/school/chakra.jpg" border="0" style="opacity: 0.5;" />
            <div style="background: url('_img/fundo_botao.jpg') no-repeat center; width: 150px; height: 20px; margin: 3px auto; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: bold; font-size: 10px;">
                Level 15 (<?php echo max(0, 15 - $db['nivel']); ?>)
            </div>
            <?php } ?>
            </td>
          	<td align="center">
            <a href="?p=learn&amp;id=<?php echo $_GET['id']; ?>"><img src="_img/school/jutsu.jpg" border="0" /></a>
            <div style="background: url('_img/fundo_botao.jpg') no-repeat center; width: 150px; height: 20px; margin: 3px auto; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: bold; font-size: 10px;">
                Aprender Jutsus
            </div>
            </td>
            <td align="center">
            <a href="?p=schooltrain&amp;id=<?php echo $_GET['id']; ?>"><img src="_img/school/treino.jpg" border="0" /></a>
            <div style="background: url('_img/fundo_botao.jpg') no-repeat center; width: 150px; height: 20px; margin: 3px auto; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: bold; font-size: 10px;">
                Treinar Jutsus
            </div>
            </td>
      </tr>
    </table>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Sair da Sala" onclick="location.href='?p=room&id=<?php echo $_GET['id']; ?>&leave=true'" /></div>
</div>
<div class="box_bottom"></div>
<script>
<?php if(isset($dbr) && $atual < $dbr['fim']) { ?>
setInterval(function(){ 
    if(conc==0) calculafim('sala_tempo','sala_tempo'); 
}, 1000);
<?php } ?>
</script>