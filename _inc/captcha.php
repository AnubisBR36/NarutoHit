<?php
// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o parâmetro cod foi passado
if (!isset($_GET['cod']) || empty($_GET['cod'])) {
    exit('Parâmetro inválido');
}

$codigo = base64_decode($_GET['cod']);

// Verificar se a decodificação foi bem-sucedida
if (!$codigo || strlen($codigo) !== 5 || !preg_match('/^[1-5]{5}$/', $codigo)) {
    exit('Código inválido');
}

// Definir o cabeçalho
header("Content-Type: image/png");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Criar uma imagem simples sempre (mais confiável)
$img = imagecreate(120, 40);

// Cores
$bg = imagecolorallocate($img, 245, 245, 245);
$border = imagecolorallocate($img, 180, 180, 180);
$text_color = imagecolorallocate($img, 50, 50, 50);

// Adicionar bordas
imagerectangle($img, 0, 0, 119, 39, $border);
imagerectangle($img, 1, 1, 118, 38, $border);

// Adicionar algumas linhas decorativas
for ($i = 0; $i < 3; $i++) {
    $line_color = imagecolorallocate($img, rand(200, 230), rand(200, 230), rand(200, 230));
    imageline($img, rand(0, 120), rand(0, 40), rand(0, 120), rand(0, 40), $line_color);
}

// Coordenadas para o texto
$x = rand(20, 50);
$y = rand(12, 25);

// Adicionar o texto do CAPTCHA
imagestring($img, 5, $x, $y, $codigo, $text_color);

// Adicionar ruído
for ($i = 0; $i < 30; $i++) {
    $noise_color = imagecolorallocate($img, rand(150, 200), rand(150, 200), rand(150, 200));
    imagesetpixel($img, rand(0, 120), rand(0, 40), $noise_color);
}

// Gerar a imagem
imagepng($img);
imagedestroy($img);
?>