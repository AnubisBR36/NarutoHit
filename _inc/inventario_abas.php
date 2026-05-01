<?php
// Pega categorias disponíveis
$categories = ['arma', 'vestimenta', 'calcado', 'mascara', 'pergaminho', 'calca', 'luva', 'ramen'];
$category_names = [
    'arma' => 'Armas',
    'vestimenta' => 'Vestimentas',
    'calcado' => 'Calçados',
    'mascara' => 'Máscaras',
    'pergaminho' => 'Pergaminhos',
    'calca' => 'Calças',
    'luva' => 'Luvas',
    'ramen' => 'Ichiraku Ramen'
];

// Conta cristais de refinamento, buff e fragmentos (todos os tipos)
$count_cris_ref   = 0;
$count_cris_buff  = 0;
$count_fragmentos = 0;
try {
    $stmt = $conexao->prepare("SELECT COUNT(*) FROM usaveis u JOIN table_usaveis t ON u.itemid=t.id WHERE u.usuarioid=? AND t.categoria='cristal'");
    $stmt->execute([$db['id']]);
    $count_cris_ref = (int)$stmt->fetchColumn();

    $stmt = $conexao->prepare("SELECT COUNT(*) FROM usaveis u JOIN table_usaveis t ON u.itemid=t.id WHERE u.usuarioid=? AND t.categoria='cristal_buff'");
    $stmt->execute([$db['id']]);
    $count_cris_buff = (int)$stmt->fetchColumn();

    // Soma fragmentos das 3 tabelas: equipamento + craft + buff
    $totalFrag = 0;
    foreach (['fragmentos', 'craft_fragmentos', 'buff_fragmentos'] as $tbl) {
        try {
            $stmt = $conexao->prepare("SELECT COALESCE(SUM(quantidade),0) FROM $tbl WHERE usuarioid=? AND quantidade>0");
            $stmt->execute([$db['id']]);
            $totalFrag += (int)$stmt->fetchColumn();
        } catch (PDOException $e) {}
    }
    $count_fragmentos = $totalFrag;
} catch (PDOException $e) {}

// Conta quantos itens de cada categoria o jogador tem
$category_counts = [];
try {
    foreach($categories as $cat) {
        if($cat === 'ramen') {
            // Conta ramen separadamente
            $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM ramen WHERE usuarioid=?");
            $stmt->execute([$db['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $category_counts[$cat] = $result['total'];
        } else {
            $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM inventario i LEFT JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND t.categoria=? AND venda='nao'");
            $stmt->execute([$db['id'], $cat]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $category_counts[$cat] = $result['total'];
        }
    }
} catch(PDOException $e) {
    foreach($categories as $cat) {
        $category_counts[$cat] = 0;
    }
}
?>

<div class="box_top" style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" onclick="nhToggle('bloco-inventario')">
    <span>Meu Inventário</span>
    <span id="nhbtn-bloco-inventario" style="font-size:11px;color:#ffaa00;padding:0 6px;">▲</span>
</div>
<div id="bloco-inventario">
<div class="box_middle">
    <div style="padding:10px;">
        <b>Arraste os itens abaixo para os slots de equipamento para equipá-los automaticamente!</b><br />
        <span class="sub2">Clique nas abas para ver os itens de cada categoria.</span>
    </div>
    
    <!-- Abas de categorias -->
    <div class="sep"></div>
    <div id="inventory-tabs" style="display:flex; justify-content:center; flex-wrap:wrap; gap:5px; padding:10px; background:#1a1a1a;">
        <?php foreach($categories as $cat): ?>
            <button class="inventory-tab" data-category="<?php echo $cat; ?>" 
                    style="padding:8px 12px; background:url('_img/fundo_botao.jpg') repeat-x center; color:#fff; border:1px solid #555; cursor:pointer; font-weight:bold; transition:all 0.2s; text-shadow:1px 1px 2px #000; font-size:11px;"
                    onmouseover="this.style.borderColor='#ff6600'; this.style.filter='brightness(1.2)';"
                    onmouseout="if(!this.classList.contains('active')) { this.style.borderColor='#555'; this.style.filter='none'; }">
                <?php echo $category_names[$cat]; ?> 
                <span style="background:#ff6600; padding:2px 6px; border-radius:3px; margin-left:5px; font-size:10px;">
                    <?php echo $category_counts[$cat]; ?>
                </span>
            </button>
        <?php endforeach; ?>
        <button class="inventory-tab" data-category="cristal_refinamento"
                style="padding:8px 12px; background:url('_img/fundo_botao.jpg') repeat-x center; color:#fff; border:1px solid #555; cursor:pointer; font-weight:bold; transition:all 0.2s; text-shadow:1px 1px 2px #000; font-size:11px;"
                onmouseover="this.style.borderColor='#ff6600'; this.style.filter='brightness(1.2)';"
                onmouseout="if(!this.classList.contains('active')) { this.style.borderColor='#555'; this.style.filter='none'; }">
            C. Refinamento
            <span style="background:#ff6600; padding:2px 6px; border-radius:3px; margin-left:5px; font-size:10px;"><?php echo $count_cris_ref; ?></span>
        </button>
        <button class="inventory-tab" data-category="cristal_buff"
                style="padding:8px 12px; background:url('_img/fundo_botao.jpg') repeat-x center; color:#fff; border:1px solid #555; cursor:pointer; font-weight:bold; transition:all 0.2s; text-shadow:1px 1px 2px #000; font-size:11px;"
                onmouseover="this.style.borderColor='#ff6600'; this.style.filter='brightness(1.2)';"
                onmouseout="if(!this.classList.contains('active')) { this.style.borderColor='#555'; this.style.filter='none'; }">
            C. de Buff
            <span style="background:#ff6600; padding:2px 6px; border-radius:3px; margin-left:5px; font-size:10px;"><?php echo $count_cris_buff; ?></span>
        </button>
        <button class="inventory-tab" data-category="fragmentos"
                style="padding:8px 12px; background:url('_img/fundo_botao.jpg') repeat-x center; color:#fff; border:1px solid #555; cursor:pointer; font-weight:bold; transition:all 0.2s; text-shadow:1px 1px 2px #000; font-size:11px;"
                onmouseover="this.style.borderColor='#ff6600'; this.style.filter='brightness(1.2)';"
                onmouseout="if(!this.classList.contains('active')) { this.style.borderColor='#555'; this.style.filter='none'; }">
            Fragmento
            <span style="background:#ff6600; padding:2px 6px; border-radius:3px; margin-left:5px; font-size:10px;"><?php echo $count_fragmentos; ?></span>
        </button>
    </div>
    
    <div class="sep"></div>
    
    <!-- Área de loading -->
    <div id="inventory-loading" style="text-align:center; padding:20px; display:none;">
        <div style="font-size:24px; color:#ff6600;">⏳</div>
        <span class="sub2">Carregando itens...</span>
    </div>
    
    <!-- Grid de itens -->
    <div id="inventory-items" style="padding:10px; display:flex; flex-wrap:wrap; gap:10px; min-height:20px; justify-content:flex-start;">
        <div style="width:100%; text-align:center; color:#999;">
            Selecione uma categoria acima para ver seus itens
        </div>
    </div>
</div>
<div class="box_bottom"></div>
</div>

<style>
.inventory-tab.active {
    border-color:#ff6600 !important;
    box-shadow:0 0 5px #ff6600;
    filter:brightness(1.3) !important;
}

.inventory-item {
    width:58px;
    background:#2a2a2a;
    border:2px solid #444;
    padding:3px;
    text-align:center;
    cursor:grab;
    transition:all 0.2s;
    position:relative;
}

.inventory-item:hover {
    border-color:#ff6600;
    transform:translateY(-2px);
}

.inventory-item.equipped {
    border-color:#00ff00;
    box-shadow:0 0 8px rgba(0,255,0,0.3);
}

.inventory-item.equipped::after {
    content:'EQUIPADO';
    position:absolute;
    top:2px;
    right:2px;
    background:#00ff00;
    color:#000;
    padding:1px 3px;
    border-radius:2px;
    font-size:7px;
    font-weight:bold;
}

.inventory-item.dragging {
    opacity:0.5;
    cursor:grabbing;
}

.inventory-item img {
    width:50px;
    height:75px;
    object-fit:contain;
}

.inventory-item .item-name {
    font-size:9px;
    color:#fff;
    margin-top:5px;
    line-height:1.1;
    height:20px;
    overflow:hidden;
}

.inventory-item .item-stats {
    font-size:8px;
    color:#aaa;
    line-height:1.1;
}

.inventory-item .stat-boost {
    color:#ffd700;
    font-weight:bold;
}

.inventory-item.ramen-item {
    cursor:pointer;
}

.inventory-item.ramen-item:hover {
    border-color:#ffd700;
    box-shadow:0 0 10px rgba(255,215,0,0.4);
}

.equipment-slot.drag-over {
    background:rgba(255,102,0,0.3) !important;
    border-color:#ff6600 !important;
}
</style>

<script>
// Sistema de inventário com abas e drag-and-drop
(function() {
    const inventoryTabs = document.querySelectorAll('.inventory-tab');
    const inventoryItems = document.getElementById('inventory-items');
    const inventoryLoading = document.getElementById('inventory-loading');
    let currentCategory = null;
    
    // Função para carregar itens de uma categoria
    function loadCategory(category) {
        currentCategory = category;
        
        // Atualiza tabs ativas (mantém background do fundo_botao.jpg)
        inventoryTabs.forEach(tab => {
            if(tab.dataset.category === category) {
                tab.classList.add('active');
                tab.style.borderColor = '#ff6600';
                tab.style.filter = 'brightness(1.3)';
                tab.style.boxShadow = '0 0 5px #ff6600';
            } else {
                tab.classList.remove('active');
                tab.style.borderColor = '#555';
                tab.style.filter = 'none';
                tab.style.boxShadow = 'none';
            }
        });
        
        // Mostra loading
        inventoryLoading.style.display = 'block';
        inventoryItems.innerHTML = '';
        
        // Busca itens via AJAX
        fetch('_inc/ajax_inventory.php?action=get_items&categoria=' + category)
            .then(response => response.json())
            .then(data => {
                inventoryLoading.style.display = 'none';
                
                if(data.error) {
                    inventoryItems.innerHTML = '<div style="grid-column:1/-1; text-align:center; color:#ff0000;">Erro ao carregar itens</div>';
                    return;
                }
                
                if(data.items.length === 0) {
                    inventoryItems.innerHTML = '<div style="grid-column:1/-1; text-align:center; color:#999;">Nenhum item nesta categoria</div>';
                    return;
                }
                
                // Renderiza itens
                inventoryItems.innerHTML = '';
                data.items.forEach(item => {
                    const itemDiv = createItemElement(item);
                    inventoryItems.appendChild(itemDiv);
                });
                
                // Ativa drag-and-drop
                activateDragAndDrop();
            })
            .catch(error => {
                inventoryLoading.style.display = 'none';
                inventoryItems.innerHTML = '<div style="grid-column:1/-1; text-align:center; color:#ff0000;">Erro ao carregar itens</div>';
                console.error('Error loading items:', error);
            });
    }
    
    // Função para criar elemento de item
    function createItemElement(item) {
        const div = document.createElement('div');
        const isRamen = item.categoria === 'ramen';
        const isCristal = item.categoria === 'cristal_refinamento' || item.categoria === 'cristal_buff' || item.categoria === 'cristal_craft';
        const isFragmento = item.categoria === 'fragmentos';

        // ── Aba "Fragmento" (todos os tipos juntos) ───────────────────
        if (isFragmento) {
            div.className = 'inventory-item';
            div.draggable = false;
            div.dataset.categoria = item.categoria;
            const qty = parseInt(item.quantidade) || 0;
            const tipoLabel = item.tipo_label || 'FRAG';
            const tipoColor = item.tipo_color || '#FFD700';
            div.innerHTML = `
                <img src="${item.image_path}" alt="${item.nome}" onerror="this.style.display='none'" />
                <div class="item-name">${item.nome}</div>
                <div style="position:absolute;top:2px;right:2px;background:#000;color:#FFD700;font-size:9px;font-weight:bold;padding:1px 4px;border:1px solid #FFD700;">x${qty}</div>
                <div style="position:absolute;bottom:2px;left:2px;background:#000;color:${tipoColor};font-size:7px;font-weight:bold;padding:1px 3px;border:1px solid ${tipoColor};">${tipoLabel}</div>
            `;
            div.style.cursor = 'pointer';
            div.addEventListener('click', function() {
                window.location.href = '?p=blacksmith';
            });
            return div;
        }

        // ── Cristais (refinamento, buff, craft) ───────────────────────
        if (isCristal) {
            div.className = 'inventory-item';
            div.draggable = false;
            div.dataset.categoria = item.categoria;
            const qty = parseInt(item.quantidade) || 0;
            const fragLabel = item.is_fragment ? '<div style="position:absolute;bottom:2px;left:2px;background:#000;color:#FFD700;font-size:7px;font-weight:bold;padding:1px 3px;border:1px solid #FFD700;">FRAG</div>' : '';
            const filter = item.is_fragment ? 'filter:grayscale(0.6) brightness(0.8);' : '';
            div.innerHTML = `
                <img src="${item.image_path}" alt="${item.nome}" onerror="this.style.display='none'" style="${filter}" />
                <div class="item-name">${item.nome}</div>
                <div style="position:absolute;top:2px;right:2px;background:#000;color:#FFD700;font-size:9px;font-weight:bold;padding:1px 4px;border:1px solid #FFD700;">x${qty}</div>
                ${fragLabel}
            `;
            div.style.cursor = 'pointer';
            div.addEventListener('click', function() {
                if (item.categoria === 'cristal_refinamento') {
                    window.location.href = '?p=blacksmith';
                } else if (item.categoria === 'cristal_buff') {
                    if (item.is_fragment) {
                        window.location.href = '?p=cristais_buff';
                        return;
                    }
                    if (confirm('Ativar ' + item.nome + '? Qualquer buff ativo será substituído.')) {
                        window.location.href = '?p=usar_cristal_buff&id=' + item.inv_id;
                    }
                } else if (item.categoria === 'cristal_craft') {
                    window.location.href = '?p=blacksmith';
                }
            });
            div.addEventListener('mouseenter', function(e) {
                window.showCristalPopup && window.showCristalPopup(item, e);
            });
            div.addEventListener('mouseleave', function() { window.hideCristalPopup && window.hideCristalPopup(); });
            div.addEventListener('mousemove', function(e) { window.moveCristalPopup && window.moveCristalPopup(e); });
            return div;
        }

        div.className = 'inventory-item' + (item.status === 'on' ? ' equipped' : '') + (isRamen ? ' ramen-item' : '');
        div.draggable = !isRamen;
        div.dataset.invId = item.inv_id;
        div.dataset.categoria = item.categoria;
        
        if(isRamen) {
            div.dataset.ramenId = item.inv_id;
            div.dataset.ramenTipo = item.tipo || item.itemid;
        }
        
        let stats = [];
        
        if(isRamen && item.energia) {
            stats.push('<span class="stat-boost">🍜 +' + item.energia + ' Energia</span>');
        } else {
            if(item.taijutsu > 0) stats.push('<span class="stat-boost">+' + (parseInt(item.taijutsu) + parseInt(item.upgrade || 0)) + ' Tai</span>');
            if(item.ninjutsu > 0) stats.push('<span class="stat-boost">+' + (parseInt(item.ninjutsu) + parseInt(item.upgrade || 0)) + ' Nin</span>');
            if(item.genjutsu > 0) stats.push('<span class="stat-boost">+' + (parseInt(item.genjutsu) + parseInt(item.upgrade || 0)) + ' Gen</span>');
        }
        
        const imagemSrc = item.imagem.includes('.') ? `_img/equipamentos/${item.imagem}` : `_img/equipamentos/${item.imagem}.png`;
        
        div.innerHTML = `
            <img src="${imagemSrc}" alt="${item.nome}" />
            <div class="item-name">${item.nome}${item.upgrade > 0 ? ' +' + item.upgrade : ''}</div>
            <div class="item-stats">${stats.join('<br>')}</div>
        `;
        
        if(isRamen) {
            div.addEventListener('click', function() {
                consumeRamen(item.inv_id, item.tipo || item.itemid, item.nome);
            });
        } else {
            div.addEventListener('mouseenter', function(e) { window.showInvEquipPopup && window.showInvEquipPopup(item, e); });
            div.addEventListener('mouseleave', function() { window.hideInvEquipPopup && window.hideInvEquipPopup(); });
            div.addEventListener('mousemove', function(e) { window.moveInvEquipPopup && window.moveInvEquipPopup(e); });
        }
        
        return div;
    }
    
    function consumeRamen(id, tipo, nome) {
        if(!confirm('Deseja consumir ' + nome + '?')) return;
        
        const formData = new FormData();
        formData.append('ram_id', id);
        formData.append('ram_tipo', tipo);
        
        fetch('_inc/ajax_inventory.php?action=consume_ramen', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.error) {
                alert('Erro: ' + data.error);
                return;
            }
            if(data.success) {
                alert(data.message);
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error consuming ramen:', error);
            alert('Erro ao consumir item.');
        });
    }
    
    // Ativa drag-and-drop nos itens
    function activateDragAndDrop() {
        const items = document.querySelectorAll('.inventory-item');
        
        items.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
        });
    }
    
    function handleDragStart(e) {
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('inv_id', this.dataset.invId);
        e.dataTransfer.setData('categoria', this.dataset.categoria);
    }
    
    function handleDragEnd(e) {
        this.classList.remove('dragging');
    }
    
    // Event listeners para as tabs
    inventoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            loadCategory(this.dataset.category);
        });
    });
    
    // Expor função global para recarregar categoria atual
    window.reloadInventoryCategory = function() {
        if(currentCategory) {
            loadCategory(currentCategory);
        }
    };
})();
</script>

<div id="inv-equip-popup" style="
    display:none; position:fixed; z-index:9999; pointer-events:none;
    background:linear-gradient(160deg,#1e1e1e,#141414);
    border:1px solid #ff6600; border-radius:6px; padding:8px 10px;
    min-width:155px; max-width:195px;
    box-shadow:0 4px 18px rgba(0,0,0,0.8),0 0 8px rgba(255,102,0,0.25);
    font-size:11px; color:#ddd; font-family:Arial,sans-serif;
"></div>

<style>
#inv-equip-popup .ep-header { display:flex; align-items:center; gap:8px; margin-bottom:6px; border-bottom:1px solid #333; padding-bottom:5px; }
#inv-equip-popup .ep-img { width:34px; height:34px; object-fit:cover; border:1px solid #555; border-radius:3px; flex-shrink:0; }
#inv-equip-popup .ep-name { font-weight:bold; color:#fff; font-size:11px; line-height:1.3; }
#inv-equip-popup .ep-level { display:inline-block; background:#ff6600; color:#fff; font-size:10px; font-weight:bold; padding:1px 5px; border-radius:3px; margin-top:2px; }
#inv-equip-popup .ep-stat { display:flex; align-items:center; gap:5px; margin:3px 0; }
#inv-equip-popup .ep-stat img { width:13px; height:13px; }
#inv-equip-popup .ep-stat-label { color:#aaa; flex:1; }
#inv-equip-popup .ep-stat-total { color:#2ecc71; font-weight:bold; }
#inv-equip-popup .ep-stat-bonus { color:#ff6600; font-size:10px; }
#inv-equip-popup .ep-equipped { font-size:10px; color:#2ecc71; margin-top:4px; border-top:1px solid #2a2a2a; padding-top:4px; }
</style>

<script>
(function() {
    var invPopup = document.getElementById('inv-equip-popup');

    function posInvPopup(e) {
        var pw = invPopup.offsetWidth || 195;
        var ph = invPopup.offsetHeight || 110;
        var x = e.clientX + 14;
        var y = e.clientY + 14;
        if(x + pw > window.innerWidth - 10) x = e.clientX - pw - 10;
        if(y + ph > window.innerHeight - 10) y = e.clientY - ph - 10;
        invPopup.style.left = x + 'px';
        invPopup.style.top  = y + 'px';
    }

    function statRow(icon, label, total, bonus) {
        if(total <= 0) return '';
        var bonusPart = bonus > 0 ? ' <span class="ep-stat-bonus">(+' + bonus + ' bônus)</span>' : '';
        return '<div class="ep-stat">' +
            '<img src="_img/Icones/' + icon + '.png" />' +
            '<span class="ep-stat-label">' + label + ':</span>' +
            '<span class="ep-stat-total">+' + total + '</span>' + bonusPart +
        '</div>';
    }

    window.showInvEquipPopup = function(item, e) {
        var upgrade = parseInt(item.upgrade) || 0;
        var tai = parseInt(item.taijutsu) || 0;
        var nin = parseInt(item.ninjutsu) || 0;
        var gen = parseInt(item.genjutsu) || 0;
        var taiTotal = tai + upgrade;
        var ninTotal = nin + upgrade;
        var genTotal = gen + upgrade;

        var imgSrc = item.imagem.includes('.')
            ? '_img/equipamentos/' + item.imagem
            : '_img/equipamentos/' + item.imagem + '.png';

        var lvlBadge = upgrade > 0
            ? '<span class="ep-level">+' + upgrade + '</span>'
            : '<span class="ep-level" style="background:#555;">Base</span>';

        var equipped = item.status === 'on'
            ? '<div class="ep-equipped">✔ Equipado</div>' : '';

        var html =
            '<div class="ep-header">' +
                '<img class="ep-img" src="' + imgSrc + '" />' +
                '<div><div class="ep-name">' + item.nome + '</div>' + lvlBadge + '</div>' +
            '</div>' +
            statRow('tai', 'Taijutsu', taiTotal, upgrade) +
            statRow('nin', 'Ninjutsu', ninTotal, upgrade) +
            statRow('gen', 'Genjutsu', genTotal, upgrade);

        if(tai === 0 && nin === 0 && gen === 0) {
            html += '<div style="color:#666;font-size:10px;text-align:center;">Sem bônus de atributo</div>';
        }
        html += equipped;

        invPopup.innerHTML = html;
        invPopup.style.display = 'block';
        posInvPopup(e);
    };

    window.hideInvEquipPopup = function() { invPopup.style.display = 'none'; };
    window.moveInvEquipPopup = posInvPopup;
})();
</script>
