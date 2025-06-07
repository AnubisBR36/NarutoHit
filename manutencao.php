<?php
$inicio='2010-04-04 18:00:00';
$fim='2010-04-06 08:00:00';
$atual=date('Y-m-d H:i:s');
if(($atual>=$inicio)&&($atual<$fim)){ echo "<script>self.location='preparing.php'</script>"; exit; }
?>
<!DOCTYPE html>
<html>
<head>
    <title>NarutoHIT - Manutenção</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; background: #333; color: #fff; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #ff6600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>NarutoHIT</h1>
        <h2>Site em Manutenção</h2>
        <p>O site está temporariamente em manutenção. Tente novamente em alguns minutos.</p>
        <p>Para administradores: <a href="?allowgm=1" style="color: #ff6600;">Acessar mesmo assim</a></p>
    </div>
</body>
</html>