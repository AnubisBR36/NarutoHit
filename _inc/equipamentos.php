<script>
var taijutsu=0;
var ninjutsu=0;
var genjutsu=0;
</script>
<?php
// Buscar equipamentos do usuário
try {
    $stmt = $conexao->prepare("SELECT i.id as inv_id, i.upgrade, t.* FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND status='on' ORDER BY categoria");
    $stmt->execute([$db['id']]);
    $sqle = $stmt;
    $equipamentos = $sqle->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sqle = null;
    $equipamentos = [];
}

// Organizar equipamentos por categoria (7 slots)
$equip_arma = null;
$equip_vestimenta = null;
$equip_calcado = null;
$equip_mascara = null;
$equip_pergaminho = null;
$equip_calca = null;
$equip_luva = null;

foreach($equipamentos as $eq) {
    switch($eq['categoria']) {
        case 'arma':
            $equip_arma = $eq;
            break;
        case 'vestimenta':
            $equip_vestimenta = $eq;
            break;
        case 'calcado':
            $equip_calcado = $eq;
            break;
        case 'mascara':
            $equip_mascara = $eq;
            break;
        case 'pergaminho':
            $equip_pergaminho = $eq;
            break;
        case 'calca':
            $equip_calca = $eq;
            break;
        case 'luva':
            $equip_luva = $eq;
            break;
    }
}
?>
<div class="box_top" style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" onclick="nhToggle('bloco-equipamentos')">
    <span>Meus Equipamentos</span>
    <span id="nhbtn-bloco-equipamentos" style="font-size:11px;color:#ffaa00;padding:0 6px;">▲</span>
</div>
<div id="bloco-equipamentos">
<div class="box_middle">
    <div style="text-align:center; padding:10px;">
        <div style="position:relative; display:inline-block; width:500px; height:350px;">
            <!-- Imagem base do corpo (aumentada) -->
            <img src="_img/equipamentos/body.jpg" style="width:100%; height:100%; position:absolute; top:0; left:0;" />
            
            <!-- LADO ESQUERDO (3 slots) -->
            
            <!-- Slot 1 Esquerda: Máscara (superior esquerdo) -->
            <div id="slot-mascara" class="equipment-slot" data-categoria="mascara" data-inv-id="<?php echo $equip_mascara ? $equip_mascara['inv_id'] : ''; ?>" data-upgrade="<?php echo $equip_mascara ? intval($equip_mascara['upgrade']) : 0; ?>" style="position:absolute; top:5px; left:5px; width:70px; height:70px; background:rgba(0,0,0,0.3); border:2px solid #666; display:flex; align-items:center; justify-content:center;">
                <?php if($equip_mascara) { ?>
                    <img src="_img/equipamentos/<?php echo htmlspecialchars($equip_mascara['imagem']); ?>.png" style="max-width:100%; max-height:100%; object-fit:contain;" />
                    <button class="unequip-btn" onclick="unequipItem(<?php echo $equip_mascara['inv_id']; ?>)">✕</button>
                <?php } ?>
            </div>
            
            <!-- Slot 2 Esquerda: Arma (meio esquerdo) -->
            <div id="slot-arma" class="equipment-slot" data-categoria="arma" data-inv-id="<?php echo $equip_arma ? $equip_arma['inv_id'] : ''; ?>" data-upgrade="<?php echo $equip_arma ? intval($equip_arma['upgrade']) : 0; ?>" style="position:absolute; top:110px; left:5px; width:70px; height:70px; background:rgba(0,0,0,0.3); border:2px solid #666; display:flex; align-items:center; justify-content:center;">
                <?php if($equip_arma) { ?>
                    <img src="_img/equipamentos/<?php echo htmlspecialchars($equip_arma['imagem']); ?>.png" style="max-width:100%; max-height:100%; object-fit:contain;" />
                    <button class="unequip-btn" onclick="unequipItem(<?php echo $equip_arma['inv_id']; ?>)">✕</button>
                <?php } ?>
            </div>
            
            <!-- Slot 3 Esquerda: Pergaminho (inferior esquerdo) -->
            <div id="slot-pergaminho" class="equipment-slot" data-categoria="pergaminho" data-inv-id="<?php echo $equip_pergaminho ? $equip_pergaminho['inv_id'] : ''; ?>" data-upgrade="<?php echo $equip_pergaminho ? intval($equip_pergaminho['upgrade']) : 0; ?>" style="position:absolute; bottom:5px; left:5px; width:70px; height:70px; background:rgba(0,0,0,0.3); border:2px solid #666; display:flex; align-items:center; justify-content:center;">
                <?php if($equip_pergaminho) { ?>
                    <img src="_img/equipamentos/<?php echo htmlspecialchars($equip_pergaminho['imagem']); ?>.png" style="max-width:100%; max-height:100%; object-fit:contain;" />
                    <button class="unequip-btn" onclick="unequipItem(<?php echo $equip_pergaminho['inv_id']; ?>)">✕</button>
                <?php } ?>
            </div>
            
            <!-- LADO DIREITO (4 slots) -->
            
            <!-- Slot 1 Direita: Vestimenta (superior direito) -->
            <div id="slot-vestimenta" class="equipment-slot" data-categoria="vestimenta" data-inv-id="<?php echo $equip_vestimenta ? $equip_vestimenta['inv_id'] : ''; ?>" data-upgrade="<?php echo $equip_vestimenta ? intval($equip_vestimenta['upgrade']) : 0; ?>" style="position:absolute; top:5px; right:5px; width:70px; height:70px; background:rgba(0,0,0,0.3); border:2px solid #666; display:flex; align-items:center; justify-content:center;">
                <?php if($equip_vestimenta) { ?>
                    <img src="_img/equipamentos/<?php echo htmlspecialchars($equip_vestimenta['imagem']); ?>.png" style="max-width:100%; max-height:100%; object-fit:contain;" />
                    <button class="unequip-btn" onclick="unequipItem(<?php echo $equip_vestimenta['inv_id']; ?>)">✕</button>
                <?php } ?>
            </div>
            
            <!-- Slot 2 Direita: Luva (meio superior direito) -->
            <div id="slot-luva" class="equipment-slot" data-categoria="luva" data-inv-id="<?php echo $equip_luva ? $equip_luva['inv_id'] : ''; ?>" data-upgrade="<?php echo $equip_luva ? intval($equip_luva['upgrade']) : 0; ?>" style="position:absolute; top:85px; right:5px; width:70px; height:70px; background:rgba(0,0,0,0.3); border:2px solid #666; display:flex; align-items:center; justify-content:center;">
                <?php if($equip_luva) { ?>
                    <img src="_img/equipamentos/<?php echo htmlspecialchars($equip_luva['imagem']); ?>.png" style="max-width:100%; max-height:100%; object-fit:contain;" />
                    <button class="unequip-btn" onclick="unequipItem(<?php echo $equip_luva['inv_id']; ?>)">✕</button>
                <?php } ?>
            </div>
            
            <!-- Slot 3 Direita: Calça (meio inferior direito) -->
            <div id="slot-calca" class="equipment-slot" data-categoria="calca" data-inv-id="<?php echo $equip_calca ? $equip_calca['inv_id'] : ''; ?>" data-upgrade="<?php echo $equip_calca ? intval($equip_calca['upgrade']) : 0; ?>" style="position:absolute; top:185px; right:5px; width:70px; height:70px; background:rgba(0,0,0,0.3); border:2px solid #666; display:flex; align-items:center; justify-content:center;">
                <?php if($equip_calca) { ?>
                    <img src="_img/equipamentos/<?php echo htmlspecialchars($equip_calca['imagem']); ?>.png" style="max-width:100%; max-height:100%; object-fit:contain;" />
                    <button class="unequip-btn" onclick="unequipItem(<?php echo $equip_calca['inv_id']; ?>)">✕</button>
                <?php } ?>
            </div>
            
            <!-- Slot 4 Direita: Sapato/Calçado (inferior direito) -->
            <div id="slot-calcado" class="equipment-slot" data-categoria="calcado" data-inv-id="<?php echo $equip_calcado ? $equip_calcado['inv_id'] : ''; ?>" data-upgrade="<?php echo $equip_calcado ? intval($equip_calcado['upgrade']) : 0; ?>" style="position:absolute; bottom:5px; right:5px; width:70px; height:70px; background:rgba(0,0,0,0.3); border:2px solid #666; display:flex; align-items:center; justify-content:center;">
                <?php if($equip_calcado) { ?>
                    <img src="_img/equipamentos/<?php echo htmlspecialchars($equip_calcado['imagem']); ?>.png" style="max-width:100%; max-height:100%; object-fit:contain;" />
                    <button class="unequip-btn" onclick="unequipItem(<?php echo $equip_calcado['inv_id']; ?>)">✕</button>
                <?php } ?>
            </div>
        </div>
    </div>
    
    <?php 
    foreach($equipamentos as $dbe) { 
    ?>
    <script>
        if(document.getElementById('atrtai')) document.getElementById('atrtai').innerHTML=((document.getElementById('atrtai').innerHTML)*1)+<?php echo intval($dbe['taijutsu'])+intval($dbe['upgrade']); ?>;
        if(document.getElementById('atrnin')) document.getElementById('atrnin').innerHTML=((document.getElementById('atrnin').innerHTML)*1)+<?php echo intval($dbe['ninjutsu'])+intval($dbe['upgrade']); ?>;
        if(document.getElementById('atrgen')) document.getElementById('atrgen').innerHTML=((document.getElementById('atrgen').innerHTML)*1)+<?php echo intval($dbe['genjutsu'])+intval($dbe['upgrade']); ?>;
    </script>
    <?php } ?>
</div>
<div class="box_bottom"></div>
</div>

<style>
.unequip-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 20px;
    height: 20px;
    background: #e74c3c;
    color: white;
    border: 2px solid #000;
    border-radius: 50%;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: all 0.2s;
    padding: 0;
    line-height: 1;
}

.unequip-btn:hover {
    background: #c0392b;
    transform: scale(1.1);
}

.equipment-slot {
    position: relative;
}
</style>

<script>
function unequipItem(invId) {
    const formData = new FormData();
    formData.append('inv_id', invId);
    
    fetch('_inc/ajax_inventory.php?action=unequip', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.error) {
            alert('Erro: ' + data.error);
            return;
        }
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao remover equipamento.');
    });
}

// Função para aplicar brilhos aos equipamentos baseados no nível (DEVE VIR PRIMEIRO!)
function applyEquipmentGlows() {
    const slots = document.querySelectorAll('.equipment-slot');
    
    slots.forEach(slot => {
        const upgrade = parseInt(slot.dataset.upgrade) || 0;
        
        // Remove classes de brilho anteriores
        slot.classList.remove('glow-basic', 'glow-intermediate', 'glow-advanced', 'glow-max');
        
        if(upgrade === 0) {
            // Sem brilho para itens sem upgrade
            slot.style.boxShadow = 'none';
            slot.style.border = '2px solid #666';
        } else if(upgrade >= 1 && upgrade <= 6) {
            // Nível 1-6: Verde brilhante (chakra básico)
            slot.style.border = '3px solid #2ecc71';
            slot.style.boxShadow = '0 0 15px #2ecc71, inset 0 0 10px rgba(46, 204, 113, 0.3)';
            slot.classList.add('glow-basic');
        } else if(upgrade >= 7 && upgrade <= 9) {
            // Nível 7-9: Azul elétrico (chakra avançado)
            slot.style.border = '3px solid #3498db';
            slot.style.boxShadow = '0 0 20px #3498db, inset 0 0 15px rgba(52, 152, 219, 0.4)';
            slot.classList.add('glow-intermediate');
        } else if(upgrade >= 10 && upgrade <= 14) {
            // Nível 10-14: Roxo místico (poder sábio)
            slot.style.border = '3px solid #9b59b6';
            slot.style.boxShadow = '0 0 25px #9b59b6, inset 0 0 20px rgba(155, 89, 182, 0.5)';
            slot.classList.add('glow-advanced');
        } else if(upgrade === 15) {
            // Nível 15: DOURADO MÁXIMO com efeito especial pulsante!
            slot.style.border = '4px solid #ffd700';
            slot.classList.add('glow-max');
        }
    });
}

// Sistema de drag-and-drop para equipar itens
(function() {
    const equipmentSlots = document.querySelectorAll('.equipment-slot');
    let currentDraggedElement = null;
    
    // Escuta eventos de drag nos itens do inventário
    document.addEventListener('dragstart', function(e) {
        if(e.target.classList.contains('inventory-item')) {
            currentDraggedElement = e.target;
        }
    });
    
    document.addEventListener('dragend', function(e) {
        if(e.target.classList.contains('inventory-item')) {
            currentDraggedElement = null;
        }
    });
    
    equipmentSlots.forEach(slot => {
        // Evento quando item está sendo arrastado sobre o slot
        slot.addEventListener('dragover', function(e) {
            e.preventDefault();
            
            // Pega categoria do elemento sendo arrastado
            if(currentDraggedElement) {
                const categoria = currentDraggedElement.dataset.categoria;
                
                // Só aceita se a categoria corresponder ao slot
                if(categoria === this.dataset.categoria) {
                    this.classList.add('drag-over');
                    e.dataTransfer.dropEffect = 'move';
                } else {
                    e.dataTransfer.dropEffect = 'none';
                }
            }
        });
        
        // Evento quando item sai da área do slot
        slot.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        // Evento quando item é dropado no slot
        slot.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            if(!currentDraggedElement) {
                alert('Erro ao identificar o item arrastado.');
                return;
            }
            
            const inv_id = currentDraggedElement.dataset.invId;
            const categoria = currentDraggedElement.dataset.categoria;
            
            // Verifica se a categoria corresponde
            if(categoria !== this.dataset.categoria) {
                alert('Este item não pode ser equipado neste slot!\nSlot: ' + this.dataset.categoria + '\nItem: ' + categoria);
                return;
            }
            
            // Mostra feedback visual no slot
            this.style.opacity = '0.5';
            
            // Faz requisição AJAX para equipar
            const formData = new FormData();
            formData.append('inv_id', inv_id);
            
            fetch('_inc/ajax_inventory.php?action=equip', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    alert('Erro ao equipar item: ' + data.error);
                    this.style.opacity = '1';
                    return;
                }
                
                // Sucesso! Atualiza visualmente todos os slots
                updateEquipmentSlots(data.equipped);
                
                // Recarrega o inventário se a função existir
                if(typeof window.reloadInventoryCategory === 'function') {
                    window.reloadInventoryCategory();
                }
                
                // Mostra mensagem de sucesso
                showEquipMessage('Item equipado com sucesso!', 'success');
            })
            .catch(error => {
                alert('Erro ao equipar item. Tente novamente.');
                this.style.opacity = '1';
                console.error('Error equipping item:', error);
            });
        });
    });
    
    // Função para atualizar visualmente os slots de equipamento
    function updateEquipmentSlots(equipped) {
        // Limpa todos os slots primeiro
        equipmentSlots.forEach(slot => {
            slot.innerHTML = '';
            slot.style.opacity = '1';
            slot.dataset.upgrade = '0';
            slot.dataset.invId = '';
        });
        
        // Preenche com os itens equipados
        equipped.forEach(item => {
            const slot = document.getElementById('slot-' + item.categoria);
            if(slot) {
                const img = document.createElement('img');
                img.src = '_img/equipamentos/' + item.imagem + '.png';
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100%';
                img.style.objectFit = 'contain';
                slot.appendChild(img);
                
                // Adiciona botão X para remover
                const unequipBtn = document.createElement('button');
                unequipBtn.className = 'unequip-btn';
                unequipBtn.innerHTML = '✕';
                unequipBtn.onclick = function() { unequipItem(item.inv_id); };
                slot.appendChild(unequipBtn);
                
                // Atualiza o nível de upgrade e inv_id do slot
                slot.dataset.upgrade = item.upgrade || '0';
                slot.dataset.invId = item.inv_id;
            }
        });
        
        // Reaplica os brilhos após atualizar os slots
        if(typeof applyEquipmentGlows === 'function') {
            applyEquipmentGlows();
        }
    }
    
    // Função para mostrar mensagem de feedback
    function showEquipMessage(message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 25px';
        messageDiv.style.background = type === 'success' ? '#00ff00' : '#ff0000';
        messageDiv.style.color = '#000';
        messageDiv.style.fontWeight = 'bold';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.animation = 'slideIn 0.3s ease-out';
        messageDiv.textContent = message;
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                document.body.removeChild(messageDiv);
            }, 300);
        }, 2000);
    }
    
    // Adiciona animações CSS
    if(!document.getElementById('equip-animations')) {
        const style = document.createElement('style');
        style.id = 'equip-animations';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Aplica brilhos coloridos baseados no nível do equipamento
    applyEquipmentGlows();
})();

// Adiciona estilos de animação para o nível máximo
if(!document.getElementById('equipment-glow-styles')) {
    const glowStyle = document.createElement('style');
    glowStyle.id = 'equipment-glow-styles';
    glowStyle.textContent = `
        /* Animação pulsante para nível máximo */
        @keyframes pulseGold {
            0%, 100% {
                box-shadow: 0 0 20px #ffd700, 
                            0 0 40px #ffd700, 
                            inset 0 0 20px rgba(255, 215, 0, 0.6);
            }
            50% {
                box-shadow: 0 0 30px #ffd700, 
                            0 0 60px #ffd700, 
                            0 0 80px #ffa500,
                            inset 0 0 30px rgba(255, 215, 0, 0.8);
            }
        }
        
        /* Partículas brilhantes flutuantes para nível 15 */
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: translateY(0) scale(0); }
            50% { opacity: 1; transform: translateY(-10px) scale(1); }
        }
        
        .glow-max {
            animation: pulseGold 2s ease-in-out infinite;
            position: relative;
            overflow: visible !important;
        }
        
        /* Cria efeito de partículas ao redor do equipamento nível 15 */
        .glow-max::before,
        .glow-max::after {
            content: '✨';
            position: absolute;
            font-size: 16px;
            color: #ffd700;
            animation: sparkle 3s ease-in-out infinite;
            pointer-events: none;
        }
        
        .glow-max::before {
            top: -10px;
            right: -10px;
            animation-delay: 0s;
        }
        
        .glow-max::after {
            bottom: -10px;
            left: -10px;
            animation-delay: 1.5s;
        }
        
        /* Efeitos suaves para os outros níveis */
        .glow-basic {
            transition: all 0.3s ease;
        }
        
        .glow-intermediate {
            transition: all 0.3s ease;
        }
        
        .glow-advanced {
            transition: all 0.3s ease;
        }
        
        .glow-basic:hover,
        .glow-intermediate:hover,
        .glow-advanced:hover {
            transform: scale(1.05);
        }
    `;
    document.head.appendChild(glowStyle);
}
</script>
