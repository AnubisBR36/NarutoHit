
<?php
if(isset($_GET['remove'])){
	try {
		$stmt = $conexao->prepare("SELECT usuarioid FROM book WHERE id = ?");
		$stmt->execute([$_GET['remove']]);
		
		if($stmt->rowCount() == 0){ 
			echo "<script>self.location='?p=home'</script>"; 
			exit; 
		}
		
		$dbr = $stmt->fetch(PDO::FETCH_ASSOC);
		if($dbr['usuarioid'] != $db['id']){ 
			echo "<script>self.location='?p=home'</script>"; 
			exit; 
		}
		
		$deleteStmt = $conexao->prepare("DELETE FROM book WHERE id = ?");
		$deleteStmt->execute([$_GET['remove']]);
		
		echo "<script>self.location='?p=book&msg=2'</script>";
	} catch(PDOException $e) {
		error_log("MySQL Query Error: " . $e->getMessage());
		echo "<script>self.location='?p=home'</script>";
		exit;
	}
}

try {
	$stmt = $conexao->prepare("SELECT b.*, u.usuario, u.nivel FROM book b LEFT OUTER JOIN usuarios u ON b.inimigoid = u.id WHERE b.usuarioid = ? ORDER BY u.usuario");
	$stmt->execute([$db['id']]);
	$bookResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
	error_log("MySQL Query Error: " . $e->getMessage());
	$bookResults = [];
}
?>
<div class="box_top">Bingo Book</div>
<div class="box_middle">Utilize seu Bingo Book para criar estratégias de ataque, e administrar melhor suas batalhas.<div class="sep"></div>
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Adicionado ao Bingo Book!'; break;
			case 2: $msg='Apagado com sucesso!'; break;
		}
		echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
	<table width="100%" cellpadding="0" cellspacing="1">
	<?php 
	$i = 1; 
	if(count($bookResults) == 0) {
		echo '<div class="aviso">Seu Bingo Book está vazio. Visite o perfil dos usuários para adiciná-los ao Bingo Book!</div>'; 
	} else {
		foreach($bookResults as $dbb) { 
			if(!$dbb['usuario']) continue; // Skip if user doesn't exist
	?>
    	<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        	<td width="20"><?php echo $i; ?></td>
        	<td width="130"><a href="?p=view&view=<?php echo strtolower($dbb['usuario']); ?>"><?php echo htmlspecialchars($dbb['usuario']); ?></a><br />Nível <?php echo $dbb['nivel']; ?></td>
            <td width="170">
            	<?php 
            	if(isset($dbb['ultimo']) && $dbb['ultimo']) {
            		$ex = explode(' ', $dbb['ultimo']); 
            		$data = explode('-', $ex[0]); 
            		echo 'Último ataque em<br />' . $data[2].'/'.$data[1].'/'.$data[0].', às '.$ex[1]; 
            	} else {
            		echo 'Nunca atacado';
            	}
            	?>
            </td>
            <td>
            	<?php 
            	if(!isset($dbb['yens']) || $dbb['yens'] == 0) {
            		echo '-'; 
            	} else { 
            		echo number_format($dbb['yens'], 2, ',', '.') . ' yens'; 
            	} 
            	?>
            </td>
            <td width="120">
            <form method="post" action="?p=hunt" onsubmit="subm.value='Carregando...';subm.disabled=true;">
            <input type="hidden" id="hunt_1" name="hunt_1" value="<?php echo strtolower($dbb['usuario']); ?>" />
            <input type="hidden" id="hunt_tipo" name="hunt_tipo" value="<?php echo isset($c) ? $c->encode('1', $chaveuniversal) : '1'; ?>" />
            <?php
            $soma = mktime(date('H')-12, date('i'), date('s'));
			$tempo = date('Y-m-d H:i:s', $soma);
			$ultimo = isset($dbb['ultimo']) ? $dbb['ultimo'] : '2000-01-01 00:00:00';
			if($tempo >= $ultimo){ 
			?><input type="submit" class="botao" id="subm" name="subm" value="Atacar" /><?php 
			} 
			?><br /><span class="sub2"><a href="?p=book&remove=<?php echo $dbb['id']; ?>">Remover</a></span></form></td>
        </tr>
    <?php 
    		$i++; 
    	}
    } 
    ?>
    </table>
</div>
<div class="box_bottom"></div>
