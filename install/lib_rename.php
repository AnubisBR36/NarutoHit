<?php
/**
 * lib_rename.php — substitui ocorrências da marca "narutoHIT" pelo nome
 * escolhido pelo dono do servidor em todos os arquivos de texto do projeto.
 *
 * Função pública: renomear_projeto(string $rootDir, string $nomeNovo, array &$log = []): int
 *   Retorna o número de arquivos modificados.
 *
 * Estratégia: lista de mapeamentos case-sensitive aplicada via strtr() em cada
 * arquivo de texto (php/css/js/html/json/md/txt), pulando: pasta install/,
 * imagens, fonts, .git, vendor/, node_modules/, _js/tinymce/ e arquivos > 1 MB.
 */

function renomear_projeto(string $rootDir, string $nomeNovo, array &$log = []): int
{
    $nomeNovo = trim($nomeNovo);
    if ($nomeNovo === '' || strcasecmp($nomeNovo, 'narutoHIT') === 0) {
        $log[] = '[skip] Nome do jogo não foi alterado.';
        return 0;
    }

    // Deriva variantes a partir do nome digitado pelo usuário
    $comEspaco = preg_replace('/\s+/', ' ', $nomeNovo);            // "Meu Jogo"
    $semEspaco = str_replace(' ', '',  $comEspaco);                 // "MeuJogo"
    $minusculo = mb_strtolower($semEspaco, 'UTF-8');                // "meujogo"
    $maiusculo = mb_strtoupper($semEspaco, 'UTF-8');                // "MEUJOGO"
    $comEspacoUpper = mb_strtoupper($comEspaco, 'UTF-8');           // "MEU JOGO"

    // ORDEM IMPORTA: tokens com espaço primeiro, depois sem espaço, depois lowercase puro.
    $mapa = [
        'NARUTO HIT'  => $comEspacoUpper,
        'Naruto HIT'  => $comEspaco,
        'Naruto Hit'  => $comEspaco,
        'naruto Hit'  => $comEspaco,
        'naruto hit'  => mb_strtolower($comEspaco, 'UTF-8'),
        'NARUTOHIT'   => $maiusculo,
        'NarutoHIT'   => $semEspaco,   // ← variante mais usada (ex.: adm/adm.php, install)
        'narutoHIT'   => $semEspaco,
        'NarutoHit'   => $semEspaco,
        'Narutohit'   => $semEspaco,
        'narutohit'   => $minusculo,
        // Variantes "NarutoOGame" / "Naruto OGame" — antiga marca usada no front
        'NARUTOOGAME' => $maiusculo,
        'NarutoOGame' => $semEspaco,
        'NarutoOgame' => $semEspaco,
        'Narutoogame' => $semEspaco,
        'narutoOGame' => $semEspaco,
        'narutoogame' => $minusculo,
        'Naruto OGame'=> $comEspaco,
        'Naruto Ogame'=> $comEspaco,
        'naruto ogame'=> mb_strtolower($comEspaco, 'UTF-8'),
    ];

    // Diretórios e padrões a pular
    $skipDirs = [
        '/install',
        '/.git', '/.local', '/.upm', '/.cache', '/.config',
        '/vendor', '/node_modules',
        '/_img', '/fontes',
        '/_js/tinymce', '/_js/wz',
        '/_cache',
        '/news',         // CuteNews legado de terceiros — não tocar
        '/attached_assets',
    ];
    $extPermitidas = ['php','css','js','html','htm','json','md','txt','xml','htaccess'];

    $modificados = 0;

    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($rii as $f) {
        if (!$f->isFile()) continue;
        $path = $f->getPathname();
        $rel  = str_replace($rootDir, '', $path);
        $rel  = str_replace('\\', '/', $rel);

        // pula dirs proibidos
        foreach ($skipDirs as $sd) {
            if (strpos($rel, $sd . '/') === 0 || strpos($rel, $sd) === 0) {
                continue 2;
            }
        }

        // pula dumps do banco
        $base = $f->getFilename();
        if (in_array($base, ['database.sql', 'forum.sql'])) continue;

        // checa extensão
        $ext = strtolower($f->getExtension());
        if ($ext === '' || !in_array($ext, $extPermitidas)) continue;

        // pula arquivos enormes (> 1 MB)
        if ($f->getSize() > 1024 * 1024) continue;

        $orig = @file_get_contents($path);
        if ($orig === false) continue;

        $novo = strtr($orig, $mapa);
        if ($novo !== $orig) {
            if (@file_put_contents($path, $novo) !== false) {
                $modificados++;
            } else {
                $log[] = "[ERRO] Não foi possível gravar: $rel";
            }
        }
    }

    $log[] = "[OK] Renomeação aplicada: $modificados arquivo(s) atualizado(s) → \"$comEspaco\".";

    // ─── Regravar config/brand.php com o novo BRAND_NAME ──────────────
    // O nome do servidor é lido em runtime via nome_servidor() / BRAND_NAME.
    // O strtr() acima não altera essa string porque o valor padrão é
    // 'NarutoTheGame' (não bate com nenhum token do mapa). Por isso forçamos
    // a regravação aqui para o nome digitado pelo dono do servidor pegar
    // efeito em emails, rodapés, login, FAQ etc.
    $brandFile = $rootDir . '/config/brand.php';
    if (is_file($brandFile)) {
        $escName = str_replace(["\\", "'"], ["\\\\", "\\'"], $comEspaco);
        $brandPhp = "<?php\n"
            . "/**\n"
            . " * Configuração da marca/identidade do servidor.\n"
            . " * Arquivo gerado pelo instalador. Edite BRAND_NAME para mudar o nome.\n"
            . " */\n\n"
            . "if (!defined('BRAND_NAME')) {\n"
            . "    define('BRAND_NAME', '$escName');\n"
            . "}\n\n"
            . "if (!function_exists('nome_servidor')) {\n"
            . "    function nome_servidor(): string { return BRAND_NAME; }\n"
            . "}\n\n"
            . "if (!function_exists('nome_servidor_safe')) {\n"
            . "    function nome_servidor_safe(): string {\n"
            . "        return htmlspecialchars(nome_servidor(), ENT_QUOTES, 'UTF-8');\n"
            . "    }\n"
            . "}\n";
        if (@file_put_contents($brandFile, $brandPhp) !== false) {
            $log[] = "[OK] config/brand.php regravado com BRAND_NAME=\"$comEspaco\".";
        } else {
            $log[] = "[ERRO] Não foi possível regravar config/brand.php.";
        }
    } else {
        $log[] = "[skip] config/brand.php não encontrado (regravação pulada).";
    }

    return $modificados;
}
