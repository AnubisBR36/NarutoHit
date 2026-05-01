<?php
/**
 * Substitui ocorrências literais de "AnubisServe" pelo helper nome_servidor()
 * preservando contexto PHP/HTML/strings.
 *
 * Uso: php scripts/replace_brand.php
 */

$FILES = [
    '_inc/ads.php','_inc/busyhunt.php','_inc/busymission.php','_inc/busytrain.php',
    '_inc/chat.php','_inc/config_conn.php','_inc/config_inicial.php',
    '_inc/donations.php','_inc/events.php','_inc/faq.php','_inc/first.php',
    '_inc/home.php','_inc/login.php','_inc/mail.php','_inc/manual.php','_inc/mercado.php',
    '_inc/penalty.php','_inc/polls.php','_inc/recover.php','_inc/reg.php','_inc/reg2.php',
    '_inc/terms.php','_inc/vip1.php','_inc/vip2.php','_inc/vipform.php','adm/adm.php',
    'forum/views/categorias/index.php','forum/views/layouts/header.php',
    'index.php','manual.php','newpass.php','novonivel.php','pixel.php','search_msg.php',
];

function split_php(string $src): array {
    $out = [];
    $i = 0; $n = strlen($src);
    while ($i < $n) {
        if (substr($src, $i, 5) === '<?php' || substr($src, $i, 3) === '<?=') {
            $j = strpos($src, '?>', $i);
            if ($j === false) { $out[] = ['php', substr($src, $i)]; return $out; }
            $out[] = ['php', substr($src, $i, $j - $i + 2)];
            $i = $j + 2;
        } else {
            $j = strpos($src, '<?', $i);
            if ($j === false) { $out[] = ['html', substr($src, $i)]; return $out; }
            $out[] = ['html', substr($src, $i, $j - $i)];
            $i = $j;
        }
    }
    return $out;
}

function replace_in_string(string $content, string $quote): string {
    if (strpos($content, 'AnubisServe') === false) return $content;
    $inner = substr($content, 1, -1);
    $parts = explode('AnubisServe', $inner);
    $pieces = [];
    foreach ($parts as $k => $p) {
        $pieces[] = $quote . $p . $quote;
        if ($k < count($parts) - 1) $pieces[] = 'nome_servidor()';
    }
    $filtered = array_filter($pieces, fn($x) => $x !== ($quote . $quote));
    if (empty($filtered)) return $quote . $quote;
    return implode('.', $filtered);
}

function replace_php(string $text): string {
    $out = '';
    $i = 0; $n = strlen($text);
    while ($i < $n) {
        $c = $text[$i];
        // line comment //
        if ($c === '/' && $i + 1 < $n && $text[$i+1] === '/') {
            $j = strpos($text, "\n", $i);
            if ($j === false) $j = $n;
            $out .= substr($text, $i, $j - $i);
            $i = $j; continue;
        }
        // hash comment #
        if ($c === '#' && ($i === 0 || $text[$i-1] !== '$')) {
            $j = strpos($text, "\n", $i);
            if ($j === false) $j = $n;
            $out .= substr($text, $i, $j - $i);
            $i = $j; continue;
        }
        // block comment /* */
        if ($c === '/' && $i + 1 < $n && $text[$i+1] === '*') {
            $j = strpos($text, '*/', $i + 2);
            if ($j === false) $j = $n;
            else $j += 2;
            $out .= substr($text, $i, $j - $i);
            $i = $j; continue;
        }
        // single-quoted string
        if ($c === "'") {
            $j = $i + 1;
            while ($j < $n) {
                if ($text[$j] === '\\' && $j + 1 < $n) { $j += 2; continue; }
                if ($text[$j] === "'") { $j++; break; }
                $j++;
            }
            $content = substr($text, $i, $j - $i);
            $out .= replace_in_string($content, "'");
            $i = $j; continue;
        }
        // double-quoted string
        if ($c === '"') {
            $j = $i + 1;
            while ($j < $n) {
                if ($text[$j] === '\\' && $j + 1 < $n) { $j += 2; continue; }
                if ($text[$j] === '"') { $j++; break; }
                $j++;
            }
            $content = substr($text, $i, $j - $i);
            $out .= replace_in_string($content, '"');
            $i = $j; continue;
        }
        $out .= $c;
        $i++;
    }
    return $out;
}

function replace_html(string $text): string {
    return str_replace('AnubisServe', '<?php echo nome_servidor(); ?>', $text);
}

$root = realpath(__DIR__ . '/..');
chdir($root);

foreach ($FILES as $fn) {
    if (!file_exists($fn)) { echo "[skip] $fn\n"; continue; }
    $src = file_get_contents($fn);
    if (strpos($src, 'AnubisServe') === false) { echo "[clean] $fn\n"; continue; }
    $parts = split_php($src);
    $new = '';
    foreach ($parts as [$kind, $chunk]) {
        $new .= ($kind === 'php') ? replace_php($chunk) : replace_html($chunk);
    }
    if ($new !== $src) {
        file_put_contents($fn, $new);
        echo "[updated] $fn\n";
    } else {
        echo "[nochange] $fn\n";
    }
}
