
<?php
// Servidor PHP embutido simples
$host = '0.0.0.0';
$port = 5000;
$document_root = __DIR__;

echo "Iniciando servidor em http://{$host}:{$port}\n";
echo "Documento raiz: {$document_root}\n";

// Iniciar servidor PHP embutido
$command = "php -S {$host}:{$port} -t {$document_root}";
exec($command);
?>
