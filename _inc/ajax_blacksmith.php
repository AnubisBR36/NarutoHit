<?php
header('Content-Type: application/json');

require_once(dirname(__FILE__) . '/conexao.php');
require_once(dirname(__FILE__) . '/ProvablyFair.php');

if(!isset($_SESSION['logado']) || !isset($_COOKIE['logado'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['logado'];

ProvablyFair::createTablesIfNeeded($conexao);

if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_items') {
    $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'arma';
    
    try {
        if($categoria === 'cristais') {
            $stmt = $conexao->prepare("
                SELECT 
                    MIN(u.id) as id, 
                    u.itemid as tipo, 
                    t.nome, 
                    t.imagem, 
                    t.descricao, 
                    'cristais' as categoria, 
                    0 as upgrade,
                    COUNT(*) as quantidade
                FROM usaveis u 
                LEFT JOIN table_usaveis t ON u.itemid = t.id 
                WHERE u.usuarioid = ? AND u.status = 'off' AND t.categoria = 'cristal'
                GROUP BY u.itemid, t.nome, t.imagem, t.descricao
                ORDER BY t.nome
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $conexao->prepare("
                SELECT i.id, i.upgrade, i.status, t.nome, t.imagem, t.categoria, t.taijutsu, t.ninjutsu, t.genjutsu
                FROM inventario i 
                LEFT JOIN table_itens t ON i.itemid = t.id 
                WHERE i.usuarioid = ? AND t.categoria = ? AND i.venda = 'nao' AND i.status = 'off'
                ORDER BY i.upgrade DESC, t.nome
            ");
            $stmt->execute([$userId, $categoria]);
        }
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($items as &$item) {
            if($categoria === 'cristais') {
                $item['imagem'] = '_img/ferreiro/' . $item['imagem'];
            } else {
                $catPaths = [
                    'arma' => 'Armas/',
                    'vestimenta' => 'Roupa/',
                    'calcado' => 'Sapatos/',
                    'mascara' => 'Mascara/',
                    'calca' => 'Calça/',
                    'luva' => 'Luva/',
                    'pergaminho' => 'Pergaminhos/'
                ];
                $path = isset($catPaths[$categoria]) ? $catPaths[$categoria] : '';
                if(strpos($item['imagem'], '/') === false) {
                    $item['imagem'] = $path . $item['imagem'];
                }
            }
        }
        
        echo json_encode(['success' => true, 'items' => $items]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar itens: ' . $e->getMessage()]);
    }
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_server_seed_hash') {
    try {
        $hashedSeed = ProvablyFair::getHashedServerSeed($conexao, $userId);
        echo json_encode(['success' => true, 'server_seed_hash' => $hashedSeed]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao gerar seed: ' . $e->getMessage()]);
    }
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upgrade') {
    $equipmentId = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
    $crystalType = isset($_POST['crystal_type']) ? intval($_POST['crystal_type']) : 0;
    $clientSeed = isset($_POST['client_seed']) ? trim($_POST['client_seed']) : '';
    
    if(!$equipmentId || !$crystalType) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit;
    }
    
    if(empty($clientSeed)) {
        $clientSeed = bin2hex(random_bytes(16));
    }
    
    try {
        $stmt = $conexao->prepare("SELECT i.*, t.nome FROM inventario i LEFT JOIN table_itens t ON i.itemid = t.id WHERE i.id = ? AND i.usuarioid = ?");
        $stmt->execute([$equipmentId, $userId]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$equipment) {
            echo json_encode(['success' => false, 'message' => 'Equipamento não encontrado']);
            exit;
        }
        
        if($equipment['upgrade'] >= 15) {
            echo json_encode(['success' => false, 'message' => 'Equipamento já está no nível máximo']);
            exit;
        }
        
        $stmt = $conexao->prepare("SELECT * FROM usaveis WHERE itemid = ? AND usuarioid = ? AND status = 'off' LIMIT 1");
        $stmt->execute([$crystalType, $userId]);
        $crystal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$crystal) {
            echo json_encode(['success' => false, 'message' => 'Cristal não encontrado']);
            exit;
        }
        
        $currentLevel = $equipment['upgrade'];
        $crystalType = $crystal['itemid'];
        
        $crystalRanges = [
            1 => ['min' => 0, 'max' => 6],
            2 => ['min' => 6, 'max' => 12],
            3 => ['min' => 12, 'max' => 15]
        ];
        
        if(isset($crystalRanges[$crystalType])) {
            $range = $crystalRanges[$crystalType];
            if($currentLevel < $range['min'] || $currentLevel >= $range['max']) {
                echo json_encode(['success' => false, 'message' => 'Cristal incompatível com o nível do equipamento']);
                exit;
            }
        }
        
        $upgradeChances = [
            0 => 100, 1 => 100, 2 => 100, 3 => 85, 4 => 70, 5 => 55, 6 => 40,
            7 => 30, 8 => 20, 9 => 15, 10 => 8, 11 => 6, 12 => 5, 13 => 4, 14 => 3
        ];
        
        $chance = isset($upgradeChances[$currentLevel]) ? $upgradeChances[$currentLevel] : 0;
        
        $committedSeed = ProvablyFair::getCommittedSeed($conexao, $userId);
        
        if (!$committedSeed) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma seed comprometida encontrada. Recarregue a pagina do ferreiro.']);
            exit;
        }
        
        $seedId = $committedSeed['id'];
        $serverSeed = $committedSeed['server_seed'];
        $nonce = ProvablyFair::getNextNonce($conexao, $seedId);
        
        $rollResult = ProvablyFair::roll($serverSeed, $clientSeed, $nonce);
        $hash = $rollResult['hash'];
        $number = $rollResult['number'];
        
        $success = ProvablyFair::isSuccess($number, $chance);
        
        ProvablyFair::logRoll($conexao, $userId, $equipmentId, $serverSeed, $clientSeed, $nonce, $hash, $number, $chance, $success);
        
        ProvablyFair::markSeedAsUsed($conexao, $seedId);
        
        ProvablyFair::createNewSeedForUser($conexao, $userId);
        
        $stmt = $conexao->prepare("DELETE FROM usaveis WHERE id = ?");
        $stmt->execute([$crystal['id']]);
        
        if($success) {
            $newLevel = $currentLevel + 1;
            $stmt = $conexao->prepare("UPDATE inventario SET upgrade = ? WHERE id = ?");
            $stmt->execute([$newLevel, $equipmentId]);
            
            echo json_encode([
                'success' => true, 
                'newLevel' => $newLevel, 
                'message' => 'Aprimoramento bem sucedido!',
                'provably_fair' => [
                    'server_seed' => $serverSeed,
                    'client_seed' => $clientSeed,
                    'nonce' => $nonce,
                    'hash' => $hash,
                    'number' => $number,
                    'chance' => $chance,
                    'result' => 'SUCESSO'
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'failed' => true, 
                'message' => 'O aprimoramento falhou',
                'provably_fair' => [
                    'server_seed' => $serverSeed,
                    'client_seed' => $clientSeed,
                    'nonce' => $nonce,
                    'hash' => $hash,
                    'number' => $number,
                    'chance' => $chance,
                    'result' => 'FALHOU'
                ]
            ]);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_history') {
    try {
        $stmt = $conexao->prepare("
            SELECT pfl.*, t.nome as equipment_name 
            FROM provably_fair_logs pfl
            LEFT JOIN inventario i ON pfl.equipment_id = i.id
            LEFT JOIN table_itens t ON i.itemid = t.id
            WHERE pfl.usuario_id = ?
            ORDER BY pfl.id DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'history' => $history]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_fragments') {
    try {
        // ---- Fragmentos de EQUIPAMENTO ----
        $stmt = $conexao->prepare("
            SELECT f.itemid, f.quantidade, t.nome, t.imagem, t.categoria, t.taijutsu, t.ninjutsu, t.genjutsu, t.descricao
            FROM fragmentos f
            LEFT JOIN table_itens t ON f.itemid = t.id
            WHERE f.usuarioid = ? AND f.quantidade > 0
            ORDER BY f.quantidade DESC, t.nome
        ");
        $stmt->execute([$userId]);
        $frags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $catPaths = [
            'arma' => 'Armas/', 'vestimenta' => 'Roupa/', 'calcado' => 'Sapatos/',
            'mascara' => 'Mascara/', 'calca' => 'Calça/', 'luva' => 'Luva/', 'pergaminho' => 'Pergaminhos/'
        ];
        foreach($frags as &$f) {
            $cat  = $f['categoria'] ?? '';
            $path = isset($catPaths[$cat]) ? $catPaths[$cat] : '';
            if(strpos($f['imagem'] ?? '', '/') === false) {
                $f['imagem'] = $path . $f['imagem'];
            }
            $f['tipo']     = 'equipment';
            $f['img_base'] = '_img/equipamentos/';
            $f['precisa'] = 5;
            $f['chance']  = 20;
        }
        unset($f);

        // ---- Fragmentos de CRISTAL DE CRAFT ----
        // Cria a tabela e colunas necessárias se não existirem
        try {
            $pkCF = Database::autoIncPK('id');
            $conexao->exec("CREATE TABLE IF NOT EXISTS craft_fragmentos (
                $pkCF,
                usuarioid INTEGER NOT NULL,
                itemid INTEGER NOT NULL,
                quantidade INTEGER NOT NULL DEFAULT 0,
                UNIQUE(usuarioid, itemid)
            )");
        } catch (PDOException $e) {}
        try { $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN IF NOT EXISTS cristal_alvo_id INTEGER NULL DEFAULT NULL"); } catch (PDOException $e) {}
        try { $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN IF NOT EXISTS fragmentos_necessarios INTEGER NULL DEFAULT NULL"); } catch (PDOException $e) {}

        $cristalFrags = [];
        try {
            // Fragmentos de craft são entidades próprias (cat='fragmento_craft').
            // O cristal_alvo_id aponta para o cristal completo (cat='cristal')
            // que vai ser entregue ao combinar com sucesso (30% chance).
            $stmtC = $conexao->prepare("
                SELECT cf.itemid, cf.quantidade, t.nome, t.imagem, t.descricao,
                       t.fragmentos_necessarios AS precisa_cfg,
                       t.cristal_alvo_id        AS alvo_id,
                       c.nome   AS alvo_nome,
                       c.imagem AS alvo_imagem
                FROM craft_fragmentos cf
                LEFT JOIN table_usaveis t ON cf.itemid = t.id
                LEFT JOIN table_usaveis c ON t.cristal_alvo_id = c.id AND c.categoria = 'cristal'
                WHERE cf.usuarioid = ? AND cf.quantidade > 0 AND t.categoria = 'fragmento_craft'
                ORDER BY cf.quantidade DESC, t.nome
            ");
            $stmtC->execute([$userId]);
            $cristalFrags = $stmtC->fetchAll(PDO::FETCH_ASSOC);
            foreach($cristalFrags as &$cf) {
                $cf['categoria']   = 'fragmento_craft';
                $cf['tipo']        = 'crystal';
                $cf['img_base']    = '_img/Fragmento de Cristal/';
                $cf['has_frag_img'] = 1; // já é imagem própria — front NÃO aplica filtro
                $precisa_cfg = isset($cf['precisa_cfg']) ? (int)$cf['precisa_cfg'] : 0;
                $cf['precisa'] = ($precisa_cfg >= 2 && $precisa_cfg <= 20) ? $precisa_cfg : 5;
                $cf['chance']  = 100; // forja garantida
                unset($cf['precisa_cfg']);
            }
            unset($cf);
        } catch (PDOException $e) {}

        // ---- Fragmentos de CRISTAL DE BUFF ----
        // Usa a tabela buff_fragmentos (já criada em rewardorgmission.php).
        // O itemid aponta diretamente para a entrada cristal_buff em table_usaveis.
        // N fragmentos do mesmo cristal → 30% PF → recebe o próprio cristal_buff.
        $buffFrags = [];
        try {
            $pkBF = Database::autoIncPK('id');
            $conexao->exec("CREATE TABLE IF NOT EXISTS buff_fragmentos (
                $pkBF,
                usuarioid INTEGER NOT NULL,
                itemid INTEGER NOT NULL,
                quantidade INTEGER NOT NULL DEFAULT 0,
                UNIQUE(usuarioid, itemid)
            )");
        } catch (PDOException $e) {}
        try {
            $stmtB = $conexao->prepare("
                SELECT bf.itemid, bf.quantidade, t.nome, t.imagem,
                       t.fragmentos_necessarios AS precisa_cfg
                FROM buff_fragmentos bf
                LEFT JOIN table_usaveis t ON bf.itemid = t.id AND t.categoria = 'cristal_buff'
                WHERE bf.usuarioid = ? AND bf.quantidade > 0 AND t.id IS NOT NULL
                ORDER BY bf.quantidade DESC, t.nome
            ");
            $stmtB->execute([$userId]);
            $buffFrags = $stmtB->fetchAll(PDO::FETCH_ASSOC);
            foreach($buffFrags as &$bf) {
                $bf['categoria']    = 'cristal_buff';
                $bf['tipo']         = 'buff';
                $bf['img_base']     = '_img/Buff/';
                $bf['has_frag_img'] = 0;
                $precisa_cfg = isset($bf['precisa_cfg']) ? (int)$bf['precisa_cfg'] : 0;
                $bf['precisa'] = ($precisa_cfg >= 2 && $precisa_cfg <= 20) ? $precisa_cfg : 3;
                $bf['chance']  = 30;
                unset($bf['precisa_cfg']);
            }
            unset($bf);
        } catch (PDOException $e) {}

        $all = array_merge($frags, $cristalFrags, $buffFrags);

        echo json_encode(['success' => true, 'fragments' => $all]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'combine_fragments') {
    $itemId     = isset($_POST['item_id'])    ? intval($_POST['item_id'])    : 0;
    $clientSeed = isset($_POST['client_seed']) ? trim($_POST['client_seed']) : '';
    $tipo       = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'equipment';

    if(!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Item inválido']);
        exit;
    }

    // ========== FRAGMENTO DE CRAFT (entidade própria → cristal alvo) ==========
    // 30% de chance (Provably Fair): N fragmentos do mesmo tipo → 1 cristal completo
    // (cristal_alvo_id, cat='cristal'). N configurado pelo admin em
    // adm/cristal.php (fragmentos_necessarios), default 5.
    if($tipo === 'crystal') {
        if(empty($clientSeed)) {
            $clientSeed = bin2hex(random_bytes(16));
        }
        try {
            $stmtC = $conexao->prepare("
                SELECT cf.quantidade, t.nome AS frag_nome,
                       t.fragmentos_necessarios AS precisa_cfg,
                       t.cristal_alvo_id        AS alvo_id,
                       c.id     AS alvo_id_chk,
                       c.nome   AS alvo_nome,
                       c.imagem AS alvo_imagem
                FROM craft_fragmentos cf
                LEFT JOIN table_usaveis t ON cf.itemid = t.id
                LEFT JOIN table_usaveis c ON t.cristal_alvo_id = c.id AND c.categoria = 'cristal'
                WHERE cf.usuarioid = ? AND cf.itemid = ? AND t.categoria = 'fragmento_craft'
            ");
            $stmtC->execute([$userId, $itemId]);
            $fragC = $stmtC->fetch(PDO::FETCH_ASSOC);

            if(!$fragC) {
                echo json_encode(['success' => false, 'message' => 'Fragmento não encontrado no seu inventário.']);
                exit;
            }

            $precisa_cfg = isset($fragC['precisa_cfg']) ? (int)$fragC['precisa_cfg'] : 0;
            $precisa = ($precisa_cfg >= 2 && $precisa_cfg <= 20) ? $precisa_cfg : 5;

            if($fragC['quantidade'] < $precisa) {
                echo json_encode(['success' => false, 'message' => 'Você precisa de pelo menos '.$precisa.' fragmentos "'.$fragC['frag_nome'].'".']);
                exit;
            }
            $alvoId = (int)($fragC['alvo_id_chk'] ?? 0);
            if ($alvoId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Este fragmento não tem cristal alvo configurado. Verifique em adm → Cristal se o fragmento tem um cristal-alvo da categoria "cristal" vinculado.']);
                exit;
            }

            // Provably Fair — 30% de chance para cristais
            $chance = 30;
            $committedSeed = ProvablyFair::getCommittedSeed($conexao, $userId);
            if(!$committedSeed) {
                echo json_encode(['success' => false, 'message' => 'Nenhuma seed encontrada. Recarregue a página.']);
                exit;
            }
            $seedId     = $committedSeed['id'];
            $serverSeed = $committedSeed['server_seed'];
            $nonce      = ProvablyFair::getNextNonce($conexao, $seedId);
            $rollResult = ProvablyFair::roll($serverSeed, $clientSeed, $nonce);
            $hash       = $rollResult['hash'];
            $number     = $rollResult['number'];
            $success    = ProvablyFair::isSuccess($number, $chance);

            ProvablyFair::logRoll($conexao, $userId, $itemId, $serverSeed, $clientSeed, $nonce, $hash, $number, $chance, $success);
            ProvablyFair::markSeedAsUsed($conexao, $seedId);
            ProvablyFair::createNewSeedForUser($conexao, $userId);

            // Consumir N fragmentos independente do resultado
            $stmtUpd = $conexao->prepare("UPDATE craft_fragmentos SET quantidade = quantidade - ? WHERE usuarioid = ? AND itemid = ?");
            $stmtUpd->execute([$precisa, $userId, $itemId]);
            $conexao->prepare("DELETE FROM craft_fragmentos WHERE usuarioid = ? AND itemid = ? AND quantidade <= 0")
                    ->execute([$userId, $itemId]);

            $pf_data = [
                'server_seed' => $serverSeed,
                'client_seed' => $clientSeed,
                'nonce'       => $nonce,
                'hash'        => $hash,
                'number'      => $number,
                'chance'      => $chance,
                'result'      => $success ? 'SUCESSO' : 'FALHOU'
            ];

            if($success) {
                // Entregar 1 cristal alvo (cat=cristal) na tabela usaveis
                $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid) VALUES (?, ?)")
                        ->execute([$userId, $alvoId]);
                echo json_encode([
                    'success'       => true,
                    'tipo'          => 'crystal',
                    'item_nome'     => $fragC['alvo_nome'],
                    'item_imagem'   => $fragC['alvo_imagem'] ?? '',
                    'message'       => 'Forja bem-sucedida! '.$precisa.' fragmentos "'.$fragC['frag_nome'].'" forjados → você recebeu "'.$fragC['alvo_nome'].'"!',
                    'provably_fair' => $pf_data
                ]);
            } else {
                echo json_encode([
                    'success'       => false,
                    'failed'        => true,
                    'tipo'          => 'crystal',
                    'item_nome'     => $fragC['alvo_nome'],
                    'message'       => 'A forja falhou! Os '.$precisa.' fragmentos foram destruídos.',
                    'provably_fair' => $pf_data
                ]);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao forjar cristal: ' . $e->getMessage()]);
        }
        exit;
    }

    // ========== FRAGMENTO DE CRISTAL DE BUFF (buff_fragmentos → cristal_buff → usaveis) ==========
    if($tipo === 'buff') {
        if(empty($clientSeed)) {
            $clientSeed = bin2hex(random_bytes(16));
        }
        try {
            $pkBF = Database::autoIncPK('id');
            try { $conexao->exec("CREATE TABLE IF NOT EXISTS buff_fragmentos (
                $pkBF,
                usuarioid INTEGER NOT NULL,
                itemid INTEGER NOT NULL,
                quantidade INTEGER NOT NULL DEFAULT 0,
                UNIQUE(usuarioid, itemid)
            )"); } catch (PDOException $e) {}

            $stmtB = $conexao->prepare("
                SELECT bf.quantidade, t.nome AS buff_nome, t.imagem AS buff_imagem,
                       t.fragmentos_necessarios AS precisa_cfg, t.id AS buff_id
                FROM buff_fragmentos bf
                LEFT JOIN table_usaveis t ON bf.itemid = t.id AND t.categoria = 'cristal_buff'
                WHERE bf.usuarioid = ? AND bf.itemid = ?
            ");
            $stmtB->execute([$userId, $itemId]);
            $fragB = $stmtB->fetch(PDO::FETCH_ASSOC);

            if(!$fragB || !$fragB['buff_id']) {
                echo json_encode(['success' => false, 'message' => 'Fragmento de buff não encontrado no seu inventário.']);
                exit;
            }

            $precisa_cfg = isset($fragB['precisa_cfg']) ? (int)$fragB['precisa_cfg'] : 0;
            $precisa = ($precisa_cfg >= 2 && $precisa_cfg <= 20) ? $precisa_cfg : 3;

            if((int)$fragB['quantidade'] < $precisa) {
                echo json_encode(['success' => false, 'message' => 'Você precisa de pelo menos '.$precisa.' fragmentos de "'.$fragB['buff_nome'].'".']);
                exit;
            }

            // Provably Fair — 40% de chance
            $chance = 40;
            $committedSeed = ProvablyFair::getCommittedSeed($conexao, $userId);
            if(!$committedSeed) {
                echo json_encode(['success' => false, 'message' => 'Nenhuma seed encontrada. Recarregue a página.']);
                exit;
            }
            $seedId     = $committedSeed['id'];
            $serverSeed = $committedSeed['server_seed'];
            $nonce      = ProvablyFair::getNextNonce($conexao, $seedId);
            $rollResult = ProvablyFair::roll($serverSeed, $clientSeed, $nonce);
            $hash       = $rollResult['hash'];
            $number     = $rollResult['number'];
            $success    = ProvablyFair::isSuccess($number, $chance);

            ProvablyFair::logRoll($conexao, $userId, $itemId, $serverSeed, $clientSeed, $nonce, $hash, $number, $chance, $success);
            ProvablyFair::markSeedAsUsed($conexao, $seedId);
            ProvablyFair::createNewSeedForUser($conexao, $userId);

            // Consumir N fragmentos independente do resultado
            $conexao->prepare("UPDATE buff_fragmentos SET quantidade = quantidade - ? WHERE usuarioid = ? AND itemid = ?")
                    ->execute([$precisa, $userId, $itemId]);
            $conexao->prepare("DELETE FROM buff_fragmentos WHERE usuarioid = ? AND itemid = ? AND quantidade <= 0")
                    ->execute([$userId, $itemId]);

            $pf_data = [
                'server_seed' => $serverSeed,
                'client_seed' => $clientSeed,
                'nonce'       => $nonce,
                'hash'        => $hash,
                'number'      => $number,
                'chance'      => $chance,
                'result'      => $success ? 'SUCESSO' : 'FALHOU'
            ];

            if($success) {
                $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid) VALUES (?, ?)")
                        ->execute([$userId, $itemId]);
                echo json_encode([
                    'success'       => true,
                    'tipo'          => 'buff',
                    'item_nome'     => $fragB['buff_nome'],
                    'item_imagem'   => $fragB['buff_imagem'] ?? '',
                    'message'       => 'Forja bem-sucedida! '.$precisa.' fragmentos forjados → você recebeu "'.$fragB['buff_nome'].'"!',
                    'provably_fair' => $pf_data
                ]);
            } else {
                echo json_encode([
                    'success'       => false,
                    'failed'        => true,
                    'tipo'          => 'buff',
                    'item_nome'     => $fragB['buff_nome'],
                    'message'       => 'A forja falhou! Os '.$precisa.' fragmentos foram destruídos.',
                    'provably_fair' => $pf_data
                ]);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao forjar cristal de buff: ' . $e->getMessage()]);
        }
        exit;
    }

    // ========== FRAGMENTO DE EQUIPAMENTO (fluxo antigo, com provably fair 20%) ==========
    if(empty($clientSeed)) {
        $clientSeed = bin2hex(random_bytes(16));
    }

    try {
        // Verificar fragmentos disponíveis
        $stmt = $conexao->prepare("SELECT f.quantidade, t.nome FROM fragmentos f LEFT JOIN table_itens t ON f.itemid = t.id WHERE f.usuarioid = ? AND f.itemid = ?");
        $stmt->execute([$userId, $itemId]);
        $frag = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$frag || $frag['quantidade'] < 5) {
            echo json_encode(['success' => false, 'message' => 'Você precisa de pelo menos 5 fragmentos do mesmo item']);
            exit;
        }

        // Provably Fair — 20% de chance
        $chance = 20;

        $committedSeed = ProvablyFair::getCommittedSeed($conexao, $userId);
        if(!$committedSeed) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma seed encontrada. Recarregue a página.']);
            exit;
        }

        $seedId     = $committedSeed['id'];
        $serverSeed = $committedSeed['server_seed'];
        $nonce      = ProvablyFair::getNextNonce($conexao, $seedId);

        $rollResult = ProvablyFair::roll($serverSeed, $clientSeed, $nonce);
        $hash       = $rollResult['hash'];
        $number     = $rollResult['number'];

        $success = ProvablyFair::isSuccess($number, $chance);

        // Logar roll (equipment_id = itemId para distinguir)
        ProvablyFair::logRoll($conexao, $userId, $itemId, $serverSeed, $clientSeed, $nonce, $hash, $number, $chance, $success);
        ProvablyFair::markSeedAsUsed($conexao, $seedId);
        ProvablyFair::createNewSeedForUser($conexao, $userId);

        // Consumir 5 fragmentos independente do resultado
        $stmt = $conexao->prepare("UPDATE fragmentos SET quantidade = quantidade - 5 WHERE usuarioid = ? AND itemid = ?");
        $stmt->execute([$userId, $itemId]);
        // Limpar linha se zerou
        $stmt = $conexao->prepare("DELETE FROM fragmentos WHERE usuarioid = ? AND itemid = ? AND quantidade <= 0");
        $stmt->execute([$userId, $itemId]);

        $pf_data = [
            'server_seed' => $serverSeed,
            'client_seed' => $clientSeed,
            'nonce'       => $nonce,
            'hash'        => $hash,
            'number'      => $number,
            'chance'      => $chance,
            'result'      => $success ? 'SUCESSO' : 'FALHOU'
        ];

        if($success) {
            // Dar item ao jogador
            $stmt = $conexao->prepare("INSERT INTO inventario (usuarioid, itemid, upgrade, status) VALUES (?, ?, 0, 'off')");
            $stmt->execute([$userId, $itemId]);

            echo json_encode([
                'success'       => true,
                'item_nome'     => $frag['nome'],
                'message'       => 'Forja bem-sucedida! O item foi adicionado ao seu inventário.',
                'provably_fair' => $pf_data
            ]);
        } else {
            echo json_encode([
                'success'       => false,
                'failed'        => true,
                'item_nome'     => $frag['nome'],
                'message'       => 'A forja falhou! Os 5 fragmentos foram destruídos.',
                'provably_fair' => $pf_data
            ]);
        }

    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação inválida']);
