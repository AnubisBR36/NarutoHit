<?php
require_once('conexao.php');
header('Content-Type: application/json');

// Verificar e carregar dados do usuário (sem redirects HTML)
if(!isset($_SESSION['logado']) || empty($_SESSION['logado'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Buscar dados do usuário
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['logado']]);
    $db = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$db) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
    exit;
}

if(!isset($_GET['action'])) {
    echo json_encode(['error' => 'No action specified']);
    exit;
}

$action = $_GET['action'];

// Get inventory items by category
if($action === 'get_items') {
    $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'all';
    
    try {
        // Categoria especial: ramen vem de outra tabela
        if($categoria === 'ramen') {
            $stmt = $conexao->prepare("SELECT id, ramenid, usuarioid FROM ramen WHERE usuarioid=? ORDER BY ramenid");
            $stmt->execute([$db['id']]);
            $ramens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Definir nomes e energia de cada ramen
            $ramen_info = [
                1 => ['nome' => 'Gohan', 'energia' => 50],
                2 => ['nome' => 'Sushi', 'energia' => 100],
                3 => ['nome' => 'Peixe Empanado', 'energia' => 250],
                4 => ['nome' => 'Sashimi', 'energia' => 500],
                5 => ['nome' => 'Ramen', 'energia' => 1000]
            ];
            
            // Formatar ramen para exibição
            $items = [];
            foreach($ramens as $r) {
                $ramen_id = $r['ramenid'];
                $info = $ramen_info[$ramen_id] ?? ['nome' => 'Ramen', 'energia' => 0];
                
                $items[] = [
                    'inv_id' => $r['id'],
                    'nome' => $info['nome'],
                    'imagem' => '../ramen/ramen'.$ramen_id.'.jpg',
                    'categoria' => 'ramen',
                    'tipo' => $ramen_id,
                    'descricao' => 'Restaura '.$info['energia'].' de energia',
                    'energia' => $info['energia'],
                    'taijutsu' => 0,
                    'ninjutsu' => 0,
                    'genjutsu' => 0,
                    'upgrade' => 0,
                    'status' => 'off'
                ];
            }
            echo json_encode(['success' => true, 'items' => $items]);
            exit;
        }
        
        // ── Aba "Fragmento": agrega TODOS os fragmentos do jogador (eq + craft + buff)
        if ($categoria === 'fragmentos') {
            $items = [];

            // 1) Fragmentos de EQUIPAMENTO (table fragmentos → table_itens)
            try {
                $stmt = $conexao->prepare("
                    SELECT f.itemid AS item_id, f.quantidade, t.nome, t.imagem, t.categoria AS subcat
                    FROM fragmentos f
                    LEFT JOIN table_itens t ON f.itemid = t.id
                    WHERE f.usuarioid = ? AND f.quantidade > 0
                    ORDER BY t.nome
                ");
                $stmt->execute([$db['id']]);
                $catPaths = [
                    'arma' => 'Armas/', 'vestimenta' => 'Roupa/', 'calcado' => 'Sapatos/',
                    'mascara' => 'Mascara/', 'calca' => 'Calça/', 'luva' => 'Luva/', 'pergaminho' => 'Pergaminhos/'
                ];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $sub = $r['subcat'] ?? '';
                    $img = $r['imagem'] ?? '';
                    if (strpos($img, '/') === false && isset($catPaths[$sub])) {
                        $img = $catPaths[$sub] . $img;
                    }
                    $items[] = [
                        'inv_id'     => 0,
                        'item_id'    => $r['item_id'],
                        'nome'       => 'Fragmento de '.$r['nome'],
                        'imagem'     => $r['imagem'],
                        'image_path' => '_img/equipamentos/'.$img,
                        'categoria'  => 'fragmentos',
                        'quantidade' => (int)$r['quantidade'],
                        'tipo_label' => 'EQUIP',
                        'tipo_color' => '#ff6600',
                    ];
                }
            } catch (PDOException $e) {}

            // 2) Fragmentos de CRISTAL DE CRAFT (cat='fragmento_craft')
            try {
                $stmt = $conexao->prepare("
                    SELECT cf.itemid AS item_id, cf.quantidade, t.nome, t.imagem
                    FROM craft_fragmentos cf
                    LEFT JOIN table_usaveis t ON cf.itemid = t.id
                    WHERE cf.usuarioid = ? AND cf.quantidade > 0 AND t.categoria = 'fragmento_craft'
                    ORDER BY t.nome
                ");
                $stmt->execute([$db['id']]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $items[] = [
                        'inv_id'     => 0,
                        'item_id'    => $r['item_id'],
                        'nome'       => $r['nome'],
                        'imagem'     => $r['imagem'],
                        'image_path' => '_img/Fragmento de Cristal/'.rawurlencode($r['imagem'] ?? ''),
                        'categoria'  => 'fragmentos',
                        'quantidade' => (int)$r['quantidade'],
                        'tipo_label' => 'CRAFT',
                        'tipo_color' => '#cf6ecf',
                    ];
                }
            } catch (PDOException $e) {}

            // 3) Fragmentos de CRISTAL DE BUFF (table buff_fragmentos)
            try {
                $stmt = $conexao->prepare("
                    SELECT bf.itemid AS item_id, bf.quantidade, t.nome, t.imagem
                    FROM buff_fragmentos bf
                    LEFT JOIN table_usaveis t ON bf.itemid = t.id
                    WHERE bf.usuarioid = ? AND bf.quantidade > 0
                    ORDER BY t.nome
                ");
                $stmt->execute([$db['id']]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $items[] = [
                        'inv_id'     => 0,
                        'item_id'    => $r['item_id'],
                        'nome'       => 'Fragmento de '.$r['nome'],
                        'imagem'     => $r['imagem'],
                        'image_path' => '_img/Buff/'.($r['imagem'] ?? ''),
                        'categoria'  => 'fragmentos',
                        'quantidade' => (int)$r['quantidade'],
                        'tipo_label' => 'BUFF',
                        'tipo_color' => '#5ecf6e',
                    ];
                }
            } catch (PDOException $e) {}

            echo json_encode(['success' => true, 'items' => $items]);
            exit;
        }

        // Categorias especiais: cristais (agrupa por itemid e mostra quantidade)
        if($categoria === 'cristal_refinamento' || $categoria === 'cristal_buff' || $categoria === 'cristal_craft') {
            $cat_map = [
                'cristal_refinamento' => ['cristal',       'ferreiro'],
                'cristal_buff'        => ['cristal_buff',  'Buff'],
                'cristal_craft'       => ['cristal_craft', 'Craft'],
            ];
            $cat_db  = $cat_map[$categoria][0];
            $img_dir = $cat_map[$categoria][1];
            $stmt = $conexao->prepare("
                SELECT MIN(u.id) AS inv_id, t.id AS item_id, t.nome, t.imagem, t.descricao,
                       COUNT(u.id) AS quantidade
                FROM usaveis u
                JOIN table_usaveis t ON u.itemid = t.id
                WHERE u.usuarioid = ? AND t.categoria = ?
                GROUP BY t.id
                ORDER BY t.id ASC
            ");
            $stmt->execute([$db['id'], $cat_db]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Inclui também fragmentos para a aba cristal_buff
            $frags = [];
            if ($categoria === 'cristal_buff') {
                try {
                    $stmtf = $conexao->prepare("
                        SELECT bf.itemid AS item_id, bf.quantidade, t.nome, t.imagem, t.descricao
                        FROM buff_fragmentos bf
                        JOIN table_usaveis t ON bf.itemid = t.id
                        WHERE bf.usuarioid = ? AND bf.quantidade > 0
                        ORDER BY t.id ASC
                    ");
                    $stmtf->execute([$db['id']]);
                    $frags = $stmtf->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {}
            }

            $items = [];
            foreach ($rows as $r) {
                $items[] = [
                    'inv_id'    => $r['inv_id'],
                    'item_id'   => $r['item_id'],
                    'nome'      => $r['nome'],
                    'descricao' => $r['descricao'],
                    'imagem'    => $r['imagem'],
                    'image_path'=> '_img/'.$img_dir.'/'.$r['imagem'],
                    'categoria' => $categoria,
                    'quantidade'=> (int)$r['quantidade'],
                    'is_fragment' => false,
                ];
            }
            foreach ($frags as $f) {
                $items[] = [
                    'inv_id'    => 0,
                    'item_id'   => $f['item_id'],
                    'nome'      => 'Fragmento de '.$f['nome'],
                    'descricao' => 'Junte 3 para formar o cristal completo.',
                    'imagem'    => $f['imagem'],
                    'image_path'=> '_img/'.$img_dir.'/'.$f['imagem'],
                    'categoria' => $categoria,
                    'quantidade'=> (int)$f['quantidade'],
                    'is_fragment' => true,
                ];
            }
            echo json_encode(['success' => true, 'items' => $items]);
            exit;
        }

        // Categorias normais de equipamentos
        if($categoria === 'all') {
            $stmt = $conexao->prepare("SELECT i.id as inv_id, i.status, i.upgrade, t.* FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND venda='nao' ORDER BY categoria, nome");
            $stmt->execute([$db['id']]);
        } else {
            $stmt = $conexao->prepare("SELECT i.id as inv_id, i.status, i.upgrade, t.* FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND t.categoria=? AND venda='nao' ORDER BY nome");
            $stmt->execute([$db['id'], $categoria]);
        }
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'items' => $items]);
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// Equip item via drag and drop
if($action === 'equip') {
    if(!isset($_POST['inv_id'])) {
        echo json_encode(['error' => 'No item ID specified']);
        exit;
    }
    
    $inv_id = intval($_POST['inv_id']);
    
    try {
        // Get item category
        $stmt = $conexao->prepare("SELECT i.usuarioid, t.categoria FROM inventario i LEFT JOIN table_itens t ON i.itemid=t.id WHERE i.id=?");
        $stmt->execute([$inv_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$item || $item['usuarioid'] != $db['id']) {
            echo json_encode(['error' => 'Invalid item']);
            exit;
        }
        
        $categoria = $item['categoria'];
        
        // Unequip all items of the same category
        $stmt = $conexao->prepare("UPDATE inventario SET status='off' WHERE usuarioid=? AND itemid IN (SELECT id FROM table_itens WHERE categoria=?)");
        $stmt->execute([$db['id'], $categoria]);
        
        // Equip the new item
        $stmt = $conexao->prepare("UPDATE inventario SET status='on' WHERE id=?");
        $stmt->execute([$inv_id]);
        
        // Get updated equipped items
        $stmt = $conexao->prepare("SELECT i.id as inv_id, i.upgrade, t.* FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND status='on' ORDER BY categoria");
        $stmt->execute([$db['id']]);
        $equipped = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'equipped' => $equipped]);
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Unequip item
if($action === 'unequip') {
    if(!isset($_POST['inv_id'])) {
        echo json_encode(['error' => 'No item ID specified']);
        exit;
    }
    
    $inv_id = intval($_POST['inv_id']);
    
    try {
        // Verify ownership
        $stmt = $conexao->prepare("SELECT usuarioid FROM inventario WHERE id=?");
        $stmt->execute([$inv_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$item || $item['usuarioid'] != $db['id']) {
            echo json_encode(['error' => 'Invalid item']);
            exit;
        }
        
        // Unequip item
        $stmt = $conexao->prepare("UPDATE inventario SET status='off' WHERE id=?");
        $stmt->execute([$inv_id]);
        
        // Get updated equipped items
        $stmt = $conexao->prepare("SELECT i.id as inv_id, i.upgrade, t.* FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND status='on' ORDER BY categoria");
        $stmt->execute([$db['id']]);
        $equipped = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'equipped' => $equipped]);
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// Consume ramen
if($action === 'consume_ramen') {
    if(!isset($_POST['ram_id']) || !isset($_POST['ram_tipo'])) {
        echo json_encode(['error' => 'Dados incompletos']);
        exit;
    }
    
    $ramen_id = intval($_POST['ram_id']);
    $tipo = intval($_POST['ram_tipo']);
    
    try {
        // Verify ownership
        $stmt = $conexao->prepare("SELECT id, ramenid, usuarioid FROM ramen WHERE id=? AND usuarioid=?");
        $stmt->execute([$ramen_id, $db['id']]);
        $ramen = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$ramen) {
            echo json_encode(['error' => 'Ramen não encontrado']);
            exit;
        }
        
        // Calculate energy based on type
        $energia_atual = intval($db['energia']);
        $energia_max = intval($db['energiamax']);
        
        switch($ramen['ramenid']) {
            case 1: $hp = 50; break;
            case 2: $hp = 100; break;
            case 3: $hp = 250; break;
            case 4: $hp = 500; break;
            case 5: $hp = 1000; break;
            default: $hp = 50;
        }
        
        $nova_energia = min($energia_atual + $hp, $energia_max);
        
        // Delete the ramen
        $stmt = $conexao->prepare("DELETE FROM ramen WHERE id=?");
        $stmt->execute([$ramen_id]);
        
        // Update user energy
        $stmt = $conexao->prepare("UPDATE usuarios SET energia=? WHERE id=?");
        $stmt->execute([$nova_energia, $db['id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Você recuperou +' . $hp . ' de energia! (Energia atual: ' . $nova_energia . '/' . $energia_max . ')',
            'energia' => $nova_energia,
            'energiamax' => $energia_max
        ]);
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Erro ao consumir ramen']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
