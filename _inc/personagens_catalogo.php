<?php
/**
 * Catálogo central de personagens do jogo.
 *
 * O catálogo agora vive no banco (tabela `personagens_catalogo`) e é
 * gerenciável pelo painel ADM (`adm/gerenciar_personagens.php`).
 * O array em `_personagens_catalogo_padrao()` serve apenas como SEED
 * inicial: ele é inserido na tabela na primeira execução e funciona
 * como fallback se o banco estiver indisponível.
 *
 * Personagens iniciais: naruto, sakura, sasuke, kakashi.
 *   - Sempre disponíveis para qualquer jogador desde o registro.
 *
 * Personagens desbloqueáveis: liberados automaticamente quando o jogador
 * atinge o `nivel` requerido. Personagens com `vip=true` exigem que o
 * jogador esteja com VIP ativo no momento do desbloqueio.
 *
 * Pastas de imagem: `_img/equipamentos`-style — `_img/personagens/<chave>/`
 * (avatares 0–9.jpg). Imagem de bloqueio: `_img/personagens/unlock_<chave>.jpg`.
 */

function personagens_iniciais(): array {
    return ['naruto', 'sakura', 'sasuke', 'kakashi'];
}

/**
 * Verifica se a chave de personagem possui arquivos de avatar reais
 * (1.jpg .. 9.jpg) — e não apenas o placeholder 0.jpg.
 */
function personagem_tem_avatares(string $chave): bool {
    if ($chave === '' || !preg_match('/^[a-z0-9_\-]+$/i', $chave)) return false;
    $base = __DIR__ . '/../_img/personagens/' . $chave;
    if (!is_dir($base)) return false;
    return is_file($base . '/1.jpg');
}

/**
 * Catálogo padrão (SEED) — usado para popular a tabela `personagens_catalogo`
 * na primeira execução e como fallback se o banco estiver indisponível.
 */
function _personagens_catalogo_padrao(): array {
    return [
        'iruka'      => ['nome' => 'Iruka',       'nivel' => 5,  'vip' => false, 'descricao' => 'Professor da Academia Ninja'],
        'konohamaru' => ['nome' => 'Konohamaru',  'nivel' => 8,  'vip' => false, 'descricao' => 'Neto do Terceiro Hokage'],
        'hayate'     => ['nome' => 'Hayate',      'nivel' => 10, 'vip' => false, 'descricao' => 'Examinador Chuunin'],
        'hagane'     => ['nome' => 'Hagane',      'nivel' => 12, 'vip' => false, 'descricao' => 'Membro da segurança da Vila'],
        'hinata'     => ['nome' => 'Hinata',      'nivel' => 14, 'vip' => false, 'descricao' => 'Herdeira do clã Hyuuga'],
        'ino'        => ['nome' => 'Ino',         'nivel' => 14, 'vip' => false, 'descricao' => 'Clã Yamanaka'],
        'kiba'       => ['nome' => 'Kiba',        'nivel' => 14, 'vip' => false, 'descricao' => 'Clã Inuzuka'],
        'shino'      => ['nome' => 'Shino',       'nivel' => 14, 'vip' => false, 'descricao' => 'Clã Aburame'],
        'chouji'     => ['nome' => 'Chouji',      'nivel' => 15, 'vip' => false, 'descricao' => 'Clã Akimichi'],
        'shikamaru'  => ['nome' => 'Shikamaru',   'nivel' => 15, 'vip' => false, 'descricao' => 'Clã Nara'],
        'asuma'      => ['nome' => 'Asuma',       'nivel' => 18, 'vip' => false, 'descricao' => 'Sensei do Time 10'],
        'kurenai'    => ['nome' => 'Kurenai',     'nivel' => 18, 'vip' => false, 'descricao' => 'Sensei do Time 8'],
        'tenten'     => ['nome' => 'Tenten',      'nivel' => 20, 'vip' => false, 'descricao' => 'Especialista em armas'],
        'lee'        => ['nome' => 'Lee',         'nivel' => 22, 'vip' => false, 'descricao' => 'Mestre do Taijutsu'],
        'neji'       => ['nome' => 'Neji',        'nivel' => 25, 'vip' => false, 'descricao' => 'Prodígio do clã Hyuuga'],
        'temari'     => ['nome' => 'Temari',      'nivel' => 28, 'vip' => false, 'descricao' => 'Irmã de Gaara'],
        'kankurou'   => ['nome' => 'Kankurou',    'nivel' => 28, 'vip' => false, 'descricao' => 'Marionetista da Areia'],
        'haku'       => ['nome' => 'Haku',        'nivel' => 30, 'vip' => false, 'descricao' => 'Caçador-ninja da Névoa'],
        'gai'        => ['nome' => 'Gai',         'nivel' => 30, 'vip' => false, 'descricao' => 'Sensei do Time 9'],
        'zabuza'     => ['nome' => 'Zabuza',      'nivel' => 35, 'vip' => false, 'descricao' => 'Demônio da Névoa Oculta'],
        'gaara'      => ['nome' => 'Gaara',       'nivel' => 40, 'vip' => false, 'descricao' => 'Kazekage da Vila da Areia'],
        'kabuto'     => ['nome' => 'Kabuto',      'nivel' => 45, 'vip' => true,  'descricao' => 'Espião e médico ninja'],
        'sai'        => ['nome' => 'Sai',         'nivel' => 45, 'vip' => true,  'descricao' => 'Ninja da Raiz da ANBU'],
        'tayuya'     => ['nome' => 'Tayuya',      'nivel' => 50, 'vip' => true,  'descricao' => 'Som dos Quatro'],
        'sakon'      => ['nome' => 'Sakon',       'nivel' => 50, 'vip' => true,  'descricao' => 'Som dos Quatro'],
        'jiroubo'    => ['nome' => 'Jiroubo',     'nivel' => 50, 'vip' => true,  'descricao' => 'Som dos Quatro'],
        'kidoumaru'  => ['nome' => 'Kidoumaru',   'nivel' => 50, 'vip' => true,  'descricao' => 'Som dos Quatro'],
        'kimimaro'   => ['nome' => 'Kimimaro',    'nivel' => 55, 'vip' => true,  'descricao' => 'Líder do Som dos Quatro'],
        'itachi'     => ['nome' => 'Itachi',      'nivel' => 60, 'vip' => true,  'descricao' => 'Membro da Akatsuki'],
    ];
}

/**
 * Cria a tabela `personagens_catalogo` se não existir e faz seed inicial
 * com `_personagens_catalogo_padrao()` quando a tabela estiver vazia.
 */
function _personagens_catalogo_garantir_tabela(PDO $conexao): void {
    static $checked = false;
    if ($checked) return;
    try {
        $conexao->exec("CREATE TABLE IF NOT EXISTS personagens_catalogo (
            id INT(11) NOT NULL AUTO_INCREMENT,
            chave VARCHAR(40) NOT NULL,
            nome VARCHAR(80) NOT NULL,
            nivel INT(11) NOT NULL DEFAULT 1,
            vip TINYINT(1) NOT NULL DEFAULT 0,
            descricao VARCHAR(255) DEFAULT '',
            ordem INT(11) NOT NULL DEFAULT 0,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uk_chave (chave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $count = (int)$conexao->query("SELECT COUNT(*) FROM personagens_catalogo")->fetchColumn();
        if ($count === 0) {
            $padrao = _personagens_catalogo_padrao();
            $stmt = $conexao->prepare("INSERT INTO personagens_catalogo
                (chave, nome, nivel, vip, descricao, ordem, ativo) VALUES (?,?,?,?,?,?,1)");
            $ord = 10;
            foreach ($padrao as $chave => $info) {
                $stmt->execute([
                    $chave, $info['nome'], (int)$info['nivel'],
                    !empty($info['vip']) ? 1 : 0, $info['descricao'] ?? '', $ord
                ]);
                $ord += 10;
            }
        }
        $checked = true;
    } catch (PDOException $e) {
        error_log('_personagens_catalogo_garantir_tabela: ' . $e->getMessage());
    }
}

/**
 * Retorna o catálogo de personagens desbloqueáveis (NÃO inclui iniciais).
 * Lê do banco via `$GLOBALS['conexao']` quando disponível; caso contrário,
 * faz fallback para `_personagens_catalogo_padrao()`.
 */
function personagens_catalogo(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $conexao = $GLOBALS['conexao'] ?? null;
    if ($conexao instanceof PDO) {
        try {
            _personagens_catalogo_garantir_tabela($conexao);
            $stmt = $conexao->query("SELECT chave, nome, nivel, vip, descricao FROM personagens_catalogo
                                     WHERE ativo=1 ORDER BY ordem ASC, nivel ASC, nome ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $cache = [];
                foreach ($rows as $r) {
                    $cache[$r['chave']] = [
                        'nome'      => $r['nome'],
                        'nivel'     => (int)$r['nivel'],
                        'vip'       => (int)$r['vip'] === 1,
                        'descricao' => (string)($r['descricao'] ?? ''),
                    ];
                }
                return $cache;
            }
        } catch (PDOException $e) {
            error_log('personagens_catalogo (db): ' . $e->getMessage());
        }
    }
    $cache = _personagens_catalogo_padrao();
    return $cache;
}

/**
 * Lista todos os nomes (chaves) de personagens não-iniciais — usado
 * para gerar dinamicamente as colunas da tabela `personagens`.
 */
function personagens_lista_chaves(): array {
    return array_keys(personagens_catalogo());
}

/** Verifica se o jogador tem VIP ativo (data atual < data fim VIP). */
function personagem_jogador_eh_vip(array $db): bool {
    if (empty($db['vip'])) return false;
    return date('Y-m-d H:i:s') < (string)$db['vip'];
}

/**
 * Garante que existe uma linha na tabela `personagens` para este usuário.
 */
function personagens_garantir_linha(PDO $conexao, int $usuarioid): void {
    try {
        $stmt = $conexao->prepare("SELECT id FROM personagens WHERE usuarioid=? LIMIT 1");
        $stmt->execute([$usuarioid]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) return;
        $stmt = $conexao->prepare("INSERT INTO personagens (usuarioid) VALUES (?)");
        $stmt->execute([$usuarioid]);
    } catch (PDOException $e) {
        error_log('personagens_garantir_linha: ' . $e->getMessage());
    }
}

/**
 * Retorna a linha da tabela `personagens` do usuário (mapa coluna→valor).
 */
function personagens_carregar(PDO $conexao, int $usuarioid): array {
    try {
        personagens_garantir_linha($conexao, $usuarioid);
        $stmt = $conexao->prepare("SELECT * FROM personagens WHERE usuarioid=? LIMIT 1");
        $stmt->execute([$usuarioid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Desbloqueia automaticamente todos os personagens cujo nível requerido
 * já foi atingido pelo jogador.
 */
function personagens_unlock_por_nivel(PDO $conexao, array $db): array {
    $usuarioid = (int)($db['id'] ?? 0);
    if ($usuarioid <= 0) return [];

    $nivel    = (int)($db['nivel'] ?? 1);
    $eh_vip   = personagem_jogador_eh_vip($db);
    $catalogo = personagens_catalogo();
    $linha    = personagens_carregar($conexao, $usuarioid);
    if (empty($linha)) return [];

    $novos = [];
    foreach ($catalogo as $chave => $info) {
        if (!array_key_exists($chave, $linha)) continue;
        if ((int)$linha[$chave] === 1) continue;
        if ($nivel < (int)$info['nivel']) continue;
        if (!empty($info['vip']) && !$eh_vip) continue;
        $novos[] = $chave;
    }

    if (!$novos) return [];
    $sets = [];
    foreach ($novos as $chave) $sets[] = "`{$chave}`=1";
    try {
        $sql = "UPDATE personagens SET " . implode(',', $sets) . " WHERE usuarioid=?";
        $conexao->prepare($sql)->execute([$usuarioid]);
    } catch (PDOException $e) {
        error_log('personagens_unlock_por_nivel: ' . $e->getMessage());
        return [];
    }
    return $novos;
}

/** True se o jogador tem o personagem desbloqueado (ou se é inicial). */
function personagem_esta_desbloqueado(PDO $conexao, array $db, string $chave): bool {
    if (in_array($chave, personagens_iniciais(), true)) return true;
    $linha = personagens_carregar($conexao, (int)($db['id'] ?? 0));
    return !empty($linha) && array_key_exists($chave, $linha) && (int)$linha[$chave] === 1;
}

// ─── CRUD do catálogo (usado pelo painel ADM) ─────────────────────────

/** Normaliza string em chave válida (ASCII, lowercase, _). */
function personagem_normalizar_chave(string $s): string {
    $s = trim($s);
    $tr = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','ê'=>'e','ë'=>'e','è'=>'e',
           'í'=>'i','ï'=>'i','ì'=>'i','î'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ò'=>'o',
           'ú'=>'u','ü'=>'u','ù'=>'u','û'=>'u','ç'=>'c','ñ'=>'n',
           'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a','É'=>'e','Ê'=>'e','Ë'=>'e','È'=>'e',
           'Í'=>'i','Ï'=>'i','Ì'=>'i','Î'=>'i','Ó'=>'o','Ô'=>'o','Õ'=>'o','Ö'=>'o','Ò'=>'o',
           'Ú'=>'u','Ü'=>'u','Ù'=>'u','Û'=>'u','Ç'=>'c','Ñ'=>'n'];
    $s = strtr($s, $tr);
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9_]+/', '_', $s);
    $s = trim($s, '_');
    return $s;
}

/**
 * Adiciona novo personagem ao catálogo. Cria a coluna correspondente em
 * `personagens` (para o sistema de desbloqueio funcionar) e a pasta de
 * imagens em `_img/personagens/<chave>/`. Retorna ['ok'=>bool, 'msg'=>string].
 */
function personagem_catalogo_add(PDO $conexao, array $dados): array {
    $chave = personagem_normalizar_chave((string)($dados['chave'] ?? ''));
    $nome  = trim((string)($dados['nome'] ?? ''));
    $nivel = max(1, (int)($dados['nivel'] ?? 1));
    $vip   = !empty($dados['vip']) ? 1 : 0;
    $desc  = trim((string)($dados['descricao'] ?? ''));
    $ordem = (int)($dados['ordem'] ?? 0);

    if ($chave === '' || strlen($chave) > 40) return ['ok'=>false, 'msg'=>'Chave inválida.'];
    if ($nome === '')                          return ['ok'=>false, 'msg'=>'Nome obrigatório.'];
    if (in_array($chave, personagens_iniciais(), true)) {
        return ['ok'=>false, 'msg'=>'Esta chave é reservada para um personagem inicial.'];
    }

    _personagens_catalogo_garantir_tabela($conexao);
    try {
        // 1) inserir no catálogo
        $stmt = $conexao->prepare("INSERT INTO personagens_catalogo
            (chave, nome, nivel, vip, descricao, ordem, ativo) VALUES (?,?,?,?,?,?,1)");
        $stmt->execute([$chave, $nome, $nivel, $vip, $desc, $ordem]);

        // 2) adicionar coluna em `personagens` se ainda não existir
        $col = $conexao->query("SHOW COLUMNS FROM personagens LIKE " . $conexao->quote($chave))->fetch();
        if (!$col) {
            $conexao->exec("ALTER TABLE personagens ADD COLUMN `{$chave}` TINYINT(1) NOT NULL DEFAULT 0");
        }

        // 3) criar pasta de imagens
        $dir = __DIR__ . '/../_img/personagens/' . $chave;
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        return ['ok'=>true, 'msg'=>"Personagem \"{$nome}\" criado. Envie agora as 9 imagens (1.jpg .. 9.jpg)."];
    } catch (PDOException $e) {
        if ((int)$e->getCode() === 23000) {
            return ['ok'=>false, 'msg'=>'Já existe um personagem com essa chave.'];
        }
        return ['ok'=>false, 'msg'=>'Erro ao criar personagem: ' . $e->getMessage()];
    }
}

/** Edita um personagem existente do catálogo (não muda a chave). */
function personagem_catalogo_edit(PDO $conexao, int $id, array $dados): array {
    $nome  = trim((string)($dados['nome'] ?? ''));
    $nivel = max(1, (int)($dados['nivel'] ?? 1));
    $vip   = !empty($dados['vip']) ? 1 : 0;
    $desc  = trim((string)($dados['descricao'] ?? ''));
    $ordem = (int)($dados['ordem'] ?? 0);
    $ativo = !empty($dados['ativo']) ? 1 : 0;

    if ($id <= 0) return ['ok'=>false, 'msg'=>'ID inválido.'];
    if ($nome === '') return ['ok'=>false, 'msg'=>'Nome obrigatório.'];

    try {
        $stmt = $conexao->prepare("UPDATE personagens_catalogo
            SET nome=?, nivel=?, vip=?, descricao=?, ordem=?, ativo=? WHERE id=?");
        $stmt->execute([$nome, $nivel, $vip, $desc, $ordem, $ativo, $id]);
        return ['ok'=>true, 'msg'=>'Personagem atualizado.'];
    } catch (PDOException $e) {
        return ['ok'=>false, 'msg'=>'Erro ao atualizar: ' . $e->getMessage()];
    }
}

/**
 * Remove personagem do catálogo. Bloqueia se houver jogadores usando-o.
 * NÃO remove a coluna em `personagens` (preserva histórico) nem apaga
 * arquivos no disco — apenas tira do catálogo.
 */
function personagem_catalogo_delete(PDO $conexao, int $id): array {
    if ($id <= 0) return ['ok'=>false, 'msg'=>'ID inválido.'];
    try {
        $stmt = $conexao->prepare("SELECT chave, nome FROM personagens_catalogo WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return ['ok'=>false, 'msg'=>'Personagem não encontrado.'];
        $chave = $row['chave'];

        if (in_array($chave, personagens_iniciais(), true)) {
            return ['ok'=>false, 'msg'=>'Personagens iniciais não podem ser removidos.'];
        }

        $stmt = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE personagem=?");
        $stmt->execute([$chave]);
        $usando = (int)$stmt->fetchColumn();
        if ($usando > 0) {
            return ['ok'=>false, 'msg'=>"Existem {$usando} jogador(es) usando este personagem. Desative em vez de excluir."];
        }

        $conexao->prepare("DELETE FROM personagens_catalogo WHERE id=?")->execute([$id]);
        return ['ok'=>true, 'msg'=>"Personagem \"{$row['nome']}\" removido do catálogo."];
    } catch (PDOException $e) {
        return ['ok'=>false, 'msg'=>'Erro ao remover: ' . $e->getMessage()];
    }
}
