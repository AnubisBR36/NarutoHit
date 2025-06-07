
<?php
$imagePath = '_img/mapa_konoha.png';

echo "<h2>Teste de Imagem do Mapa</h2>";

if (file_exists($imagePath)) {
    echo "<p style='color: green;'>✓ Arquivo encontrado: $imagePath</p>";
    echo "<img src='$imagePath' style='max-width: 400px; border: 2px solid #333;' alt='Mapa de Konoha'>";
    
    $imageInfo = getimagesize($imagePath);
    if ($imageInfo) {
        echo "<p>Dimensões: {$imageInfo[0]}x{$imageInfo[1]} pixels</p>";
        echo "<p>Tipo: {$imageInfo['mime']}</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Arquivo não encontrado: $imagePath</p>";
    echo "<p>Verifique se o arquivo existe no diretório correto.</p>";
}
?>
