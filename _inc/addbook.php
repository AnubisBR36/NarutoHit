
<?php
if(!isset($_GET['id'])){ 
	echo "<script>self.location='?p=home'</script>"; 
	exit; 
}

$id = $_GET['id'];

try {
	// Check if user exists
	$stmt = $conexao->prepare("SELECT id FROM usuarios WHERE usuario = ?");
	$stmt->execute([$id]);
	$dbf = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if(!$dbf) {
		echo "<script>self.location='?p=home'</script>"; 
		exit;
	}
	
	// Check if already in book
	$stmt = $conexao->prepare("SELECT COUNT(id) as conta FROM book WHERE usuarioid = ? AND inimigoid = ?");
	$stmt->execute([$db['id'], $dbf['id']]);
	$dbc = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if($dbc['conta'] > 0){ 
		echo "<script>self.location='?p=home'</script>"; 
		exit; 
	}
	
	// Add to book
	$stmt = $conexao->prepare("INSERT INTO book (usuarioid, inimigoid) VALUES (?, ?)");
	$stmt->execute([$db['id'], $dbf['id']]);
	
	echo "<script>self.location='?p=book&msg=1'</script>";
	
} catch(PDOException $e) {
	error_log("MySQL Query Error: " . $e->getMessage());
	echo "<script>self.location='?p=home'</script>";
	exit;
}
?>
