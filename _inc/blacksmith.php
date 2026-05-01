<?php
require_once('trava.php');
require_once('Encrypt.php');
$c = new C_Encrypt();

try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$db['id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>self.location='?p=home'</script>";
    exit;
}

$mensagem = '';
if(isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'success':
            $nivel = isset($_GET['nivel']) ? intval($_GET['nivel']) : 0;
            $mensagem = '<div class="aviso" style="background:#1a3d1a;border-color:#2ecc71;color:#2ecc71;">Aprimoramento realizado com sucesso! Seu item agora está no nível +'.$nivel.'!</div>';
            break;
        case 'fail':
            $mensagem = '<div class="aviso" style="background:#3d1a1a;border-color:#e74c3c;color:#e74c3c;">O aprimoramento falhou! Tente novamente.</div>';
            break;
        case 'error':
            $mensagem = '<div class="aviso" style="background:#3d3d1a;border-color:#f1c40f;color:#f1c40f;">Erro ao processar aprimoramento. Verifique os itens selecionados.</div>';
            break;
    }
}
?>
<style>
    .ferreiro-inventory-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-bottom: 10px;
    }
    
    .ferreiro-tab {
        padding: 8px 12px;
        background: url('_img/fundo_botao.jpg') repeat-x center;
        border: 1px solid #555;
        cursor: pointer;
        color: #fff;
        font-size: 11px;
        font-weight: bold;
        transition: all 0.2s;
        text-shadow: 1px 1px 2px #000;
    }
    
    .ferreiro-tab:hover {
        border-color: #ff6600;
        filter: brightness(1.2);
    }
    
    .ferreiro-tab.active {
        border-color: #ff6600;
        box-shadow: 0 0 5px #ff6600;
        filter: brightness(1.3);
    }
    
    .ferreiro-items {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-height: 100px;
        padding: 10px;
        background: #1a1a1a;
        border: 1px solid #333;
    }
    
    .ferreiro-item {
        width: 70px;
        background: #2a2a2a;
        border: 2px solid #444;
        padding: 5px;
        text-align: center;
        cursor: grab;
        transition: all 0.2s;
    }
    
    .ferreiro-item:hover {
        border-color: #ff6600;
        transform: translateY(-2px);
    }
    
    .ferreiro-item.dragging {
        opacity: 0.5;
        cursor: grabbing;
    }
    
    .ferreiro-item img {
        width: 55px;
        height: 55px;
        object-fit: contain;
    }
    
    .ferreiro-item .item-name {
        font-size: 9px;
        color: #fff;
        margin-top: 3px;
        line-height: 1.1;
        height: 20px;
        overflow: hidden;
    }
    
    .ferreiro-item .item-level {
        font-size: 10px;
        color: #2ecc71;
        font-weight: bold;
    }
    
    .ferreiro-item {
        position: relative;
    }
    
    .crystal-count {
        position: absolute;
        top: 5px;
        left: 5px;
        background: linear-gradient(135deg, #ff6600, #ff8833);
        color: #fff;
        font-size: 11px;
        font-weight: bold;
        min-width: 20px;
        height: 20px;
        line-height: 20px;
        text-align: center;
        border-radius: 10px;
        border: 2px solid #000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.5);
        text-shadow: 1px 1px 1px #000;
        padding: 0 5px;
    }
    
    .machine-wrapper {
        position: relative;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .machine-wrapper img.machine-img {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .drop-zone {
        position: absolute;
        border: 3px dashed #888;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    
    .drop-zone.dragover {
        border-color: #2ecc71;
        background: rgba(46,204,113,0.4);
        transform: scale(1.05);
    }
    
    .drop-zone-equipment {
        top: 52%;
        left: 8%;
        width: 100px;
        height: 100px;
        border-radius: 50%;
    }
    
    .drop-zone-crystal {
        top: 28%;
        left: 66%;
        width: 85px;
        height: 85px;
        border-radius: 5px;
    }
    
    .drop-zone-label {
        color: #fff;
        font-size: 13px;
        font-weight: bold;
        text-align: center;
        pointer-events: none;
        text-shadow: 2px 2px 4px #000, -1px -1px 2px #000, 1px -1px 2px #000, -1px 1px 2px #000;
    }
    
    .dropped-item {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .dropped-item img {
        max-width: 80%;
        max-height: 80%;
        object-fit: contain;
    }
    
    .remove-item {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        cursor: pointer;
        font-size: 12px;
        line-height: 1;
        z-index: 10;
    }
    
    .remove-item:hover {
        background: #c0392b;
    }
    
    .aprimorar-btn {
        position: absolute;
        bottom: 5%;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 35px;
        background: url('_img/fundo_botao.jpg') repeat-x center;
        border: 2px solid #ff6600;
        color: #fff;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        text-shadow: 2px 2px 3px #000;
        box-shadow: 0 3px 8px rgba(0,0,0,0.5);
        transition: all 0.2s;
    }
    
    .aprimorar-btn:hover {
        filter: brightness(1.3);
        box-shadow: 0 0 15px #ff6600;
        transform: translateX(-50%) scale(1.05);
    }
    
    .aprimorar-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        filter: grayscale(50%);
    }
    
    .aprimorar-btn:disabled:hover {
        filter: grayscale(50%);
        box-shadow: 0 3px 8px rgba(0,0,0,0.5);
        transform: translateX(-50%);
    }
    
    .upgrade-stats {
        margin-top: 15px;
        display: none;
    }
    
    .upgrade-stats.show {
        display: block;
    }
    
    .chance-bar {
        width: 100%;
        height: 20px;
        background: #1a1a1a;
        border: 1px solid #444;
        margin-top: 5px;
    }
    
    .chance-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #2ecc71 0%, #27ae60 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 11px;
        transition: width 0.5s;
    }
    
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .loading-overlay.show {
        display: flex;
    }
    
    .loading-content {
        text-align: center;
        color: white;
    }
    
    .spinner {
        border: 4px solid #333;
        border-top: 4px solid #ff6600;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 2.5s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .upgrade-animation {
        text-align: center;
        padding: 20px;
    }
    
    .forge-container {
        position: relative;
        width: 200px;
        height: 150px;
        margin: 0 auto 20px;
    }
    
    .forge-glow {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 120px;
        height: 120px;
        background: radial-gradient(circle, rgba(255,102,0,0.6) 0%, rgba(255,60,0,0.3) 50%, transparent 70%);
        border-radius: 50%;
        animation: glowPulse 2.5s ease-in-out infinite;
    }
    
    @keyframes glowPulse {
        0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.8; }
        50% { transform: translate(-50%, -50%) scale(1.3); opacity: 1; }
    }
    
    .hammer {
        position: absolute;
        top: 20%;
        left: 50%;
        transform: translateX(-50%);
        font-size: 50px;
        color: #888;
        animation: hammerHit 1.5s ease-in-out infinite;
        text-shadow: 2px 2px 4px #000;
    }
    
    @keyframes hammerHit {
        0%, 100% { transform: translateX(-50%) rotate(-30deg); }
        50% { transform: translateX(-50%) rotate(10deg) translateY(15px); }
    }
    
    .anvil {
        position: absolute;
        bottom: 10%;
        left: 50%;
        transform: translateX(-50%);
        font-size: 40px;
        color: #666;
        text-shadow: 2px 2px 4px #000;
    }
    
    .sparkles {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }
    
    .sparkle {
        position: absolute;
        top: 60%;
        font-size: 16px;
        color: #ffcc00;
        animation: sparkleFloat 2.5s ease-out forwards;
        text-shadow: 0 0 5px #ff6600;
    }
    
    @keyframes sparkleFloat {
        0% { opacity: 1; transform: translateY(0) scale(1); }
        100% { opacity: 0; transform: translateY(-80px) scale(0.5); }
    }
    
    .forge-text {
        color: #ff6600;
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px #000;
        animation: textGlow 3s ease-in-out infinite;
    }
    
    @keyframes textGlow {
        0%, 100% { text-shadow: 2px 2px 4px #000, 0 0 10px #ff6600; }
        50% { text-shadow: 2px 2px 4px #000, 0 0 20px #ff6600, 0 0 30px #ff4400; }
    }
    
    .forge-subtext {
        color: #aaa;
        font-size: 14px;
    }
    
    .upgrade-result {
        text-align: center;
        padding: 40px 20px;
        animation: resultAppear 0.5s ease-out;
    }
    
    @keyframes resultAppear {
        0% { transform: scale(0.5); opacity: 0; }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .result-icon {
        font-size: 80px;
        margin-bottom: 20px;
        animation: iconBounce 0.5s ease-out;
    }
    
    @keyframes iconBounce {
        0% { transform: scale(0); }
        60% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .upgrade-result.success .result-icon {
        text-shadow: 0 0 30px #2ecc71, 0 0 50px #27ae60;
    }
    
    .upgrade-result.failed .result-icon {
        text-shadow: 0 0 30px #e74c3c, 0 0 50px #c0392b;
    }
    
    .result-text {
        font-size: 36px;
        font-weight: bold;
        margin-bottom: 15px;
        text-shadow: 2px 2px 4px #000;
    }
    
    .result-subtext {
        font-size: 16px;
        color: #ccc;
    }
    
    .provably-fair-section {
        background: #1a1a1a;
        border: 1px solid #333;
        padding: 10px;
        margin-top: 10px;
    }
    
    .provably-fair-header {
        color: #ff6600;
        font-weight: bold;
        font-size: 13px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .provably-fair-header .fair-icon {
        font-size: 16px;
    }
    
    .seed-row {
        display: flex;
        gap: 10px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    
    .seed-input-group {
        flex: 1;
        min-width: 200px;
    }
    
    .seed-input-group label {
        display: block;
        color: #aaa;
        font-size: 11px;
        margin-bottom: 3px;
    }
    
    .seed-input-group input {
        width: 100%;
        padding: 6px 8px;
        background: #2a2a2a;
        border: 1px solid #444;
        color: #fff;
        font-size: 11px;
        font-family: monospace;
        box-sizing: border-box;
    }
    
    .seed-input-group input:focus {
        border-color: #ff6600;
        outline: none;
    }
    
    .seed-input-group input[readonly] {
        background: #1a1a1a;
        color: #888;
    }
    
    .btn-randomize {
        padding: 4px 10px;
        background: #333;
        border: 1px solid #555;
        color: #fff;
        font-size: 10px;
        cursor: pointer;
        margin-top: 3px;
    }
    
    .btn-randomize:hover {
        background: #444;
        border-color: #ff6600;
    }
    
    .provably-fair-result {
        display: none;
        background: #222;
        border: 1px solid #444;
        padding: 10px;
        margin-top: 10px;
    }
    
    .provably-fair-result.show {
        display: block;
    }
    
    .result-title {
        font-weight: bold;
        font-size: 14px;
        margin-bottom: 10px;
        text-align: center;
    }
    
    .result-title.success {
        color: #2ecc71;
    }
    
    .result-title.failed {
        color: #e74c3c;
    }
    
    .result-details {
        font-size: 10px;
        font-family: monospace;
        background: #1a1a1a;
        padding: 8px;
        border: 1px solid #333;
        word-break: break-all;
    }
    
    .result-row {
        display: flex;
        margin-bottom: 4px;
    }
    
    .result-label {
        color: #888;
        width: 100px;
        flex-shrink: 0;
    }
    
    .result-value {
        color: #fff;
        flex: 1;
    }
    
    .result-value.hash {
        color: #ff6600;
    }
    
    .result-value.number {
        font-weight: bold;
        font-size: 14px;
    }
    
    .result-value.number.success {
        color: #2ecc71;
    }
    
    .result-value.number.failed {
        color: #e74c3c;
    }
    
    .verify-link {
        display: inline-block;
        margin-top: 8px;
        color: #3498db;
        font-size: 11px;
        text-decoration: underline;
        cursor: pointer;
    }
    
    .verify-link:hover {
        color: #5dade2;
    }
    
    .history-btn {
        background: #333;
        border: 1px solid #555;
        color: #fff;
        padding: 6px 12px;
        font-size: 11px;
        cursor: pointer;
        margin-top: 8px;
    }
    
    .history-btn:hover {
        background: #444;
        border-color: #ff6600;
    }
    
    .history-container {
        max-height: 400px;
        overflow-y: auto;
        font-size: 11px;
    }
    
    .history-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .history-table th {
        padding: 8px 6px;
        background: #333;
        border: 1px solid #555;
        color: #ff6600;
        font-weight: bold;
        position: sticky;
        top: 0;
    }
    
    .history-row {
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .history-row:hover {
        background: #2a2a2a !important;
    }
    
    .history-row td {
        padding: 8px 6px;
        border: 1px solid #444;
        text-align: center;
    }
    
    .history-row.selected {
        background: #1a3d3d !important;
        border-left: 3px solid #ff6600;
    }
    
    .verification-panel {
        display: none;
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        border: 2px solid #ff6600;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        animation: panelSlide 0.3s ease-out;
    }
    
    .verification-panel.show {
        display: block;
    }
    
    @keyframes panelSlide {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .verification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #333;
    }
    
    .verification-title {
        color: #ff6600;
        font-size: 16px;
        font-weight: bold;
    }
    
    .verification-close {
        background: #e74c3c;
        border: none;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 14px;
    }
    
    .verification-close:hover {
        background: #c0392b;
    }
    
    .verification-step {
        background: #222;
        border: 1px solid #444;
        border-radius: 5px;
        padding: 12px;
        margin-bottom: 12px;
    }
    
    .verification-step-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    
    .step-number {
        background: #ff6600;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
    }
    
    .step-title {
        color: #fff;
        font-weight: bold;
        font-size: 13px;
    }
    
    .step-content {
        font-family: monospace;
        font-size: 11px;
        background: #1a1a1a;
        padding: 10px;
        border-radius: 4px;
        word-break: break-all;
        color: #aaa;
    }
    
    .step-value {
        color: #2ecc71;
        font-weight: bold;
    }
    
    .step-value.hash {
        color: #ff6600;
    }
    
    .step-value.success {
        color: #2ecc71;
    }
    
    .step-value.failed {
        color: #e74c3c;
    }
    
    .calculation-box {
        background: #1a1a1a;
        border: 1px dashed #555;
        padding: 10px;
        margin: 10px 0;
        font-family: monospace;
        font-size: 11px;
    }
    
    .calculation-line {
        margin: 5px 0;
        color: #aaa;
    }
    
    .calculation-arrow {
        color: #ff6600;
        margin: 0 5px;
    }
    
    .verification-result {
        text-align: center;
        padding: 15px;
        border-radius: 5px;
        margin-top: 15px;
    }
    
    .verification-result.success {
        background: linear-gradient(135deg, #1a3d1a, #0d260d);
        border: 2px solid #2ecc71;
    }
    
    .verification-result.failed {
        background: linear-gradient(135deg, #3d1a1a, #260d0d);
        border: 2px solid #e74c3c;
    }
    
    .verification-result-text {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .verification-result-explain {
        font-size: 12px;
        color: #aaa;
    }
    
    .copy-btn {
        background: #333;
        border: 1px solid #555;
        color: #fff;
        padding: 3px 8px;
        font-size: 10px;
        cursor: pointer;
        border-radius: 3px;
        margin-left: 5px;
    }
    
    .copy-btn:hover {
        background: #444;
        border-color: #ff6600;
    }
    
    .external-verify-link {
        display: inline-block;
        margin-top: 10px;
        padding: 8px 15px;
        background: #333;
        border: 1px solid #3498db;
        color: #3498db;
        text-decoration: none;
        border-radius: 5px;
        font-size: 11px;
    }
    
    .external-verify-link:hover {
        background: #3498db;
        color: #fff;
    }
    
    .click-hint {
        text-align: center;
        color: #888;
        font-size: 11px;
        padding: 10px;
        font-style: italic;
    }

    #equip-popup {
        display: none;
        position: fixed;
        z-index: 9999;
        pointer-events: none;
        background: linear-gradient(160deg, #1e1e1e, #141414);
        border: 1px solid #ff6600;
        border-radius: 6px;
        padding: 8px 10px;
        min-width: 160px;
        max-width: 200px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.8), 0 0 8px rgba(255,102,0,0.25);
        font-size: 11px;
        color: #ddd;
    }
    #equip-popup .ep-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 7px;
        border-bottom: 1px solid #333;
        padding-bottom: 6px;
    }
    #equip-popup .ep-img {
        width: 36px;
        height: 36px;
        object-fit: cover;
        border: 1px solid #555;
        border-radius: 3px;
        flex-shrink: 0;
    }
    #equip-popup .ep-name {
        font-weight: bold;
        color: #fff;
        font-size: 11px;
        line-height: 1.3;
    }
    #equip-popup .ep-level {
        display: inline-block;
        background: #ff6600;
        color: #fff;
        font-size: 10px;
        font-weight: bold;
        padding: 1px 5px;
        border-radius: 3px;
        margin-top: 2px;
    }
    #equip-popup .ep-stat {
        display: flex;
        align-items: center;
        gap: 5px;
        margin: 3px 0;
    }
    #equip-popup .ep-stat img {
        width: 14px;
        height: 14px;
    }
    #equip-popup .ep-stat-label {
        color: #aaa;
        flex: 1;
    }
    #equip-popup .ep-stat-base {
        color: #888;
        font-size: 10px;
    }
    #equip-popup .ep-stat-total {
        color: #2ecc71;
        font-weight: bold;
    }
    #equip-popup .ep-stat-bonus {
        color: #ff6600;
        font-size: 10px;
    }
    #equip-popup .ep-divider {
        border: none;
        border-top: 1px solid #2a2a2a;
        margin: 5px 0;
    }

    /* ===== FORJA DE FRAGMENTOS — OVERLAY ANIMADO ===== */
    #fragLoadingOverlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.93);
        z-index: 9998;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    #fragLoadingOverlay.show {
        display: flex;
    }
    .frag-forge-anim {
        text-align: center;
        padding: 30px 20px;
    }
    .frag-forge-circle {
        position: relative;
        width: 180px;
        height: 180px;
        margin: 0 auto 20px;
    }
    .frag-forge-ring {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%,-50%);
        width: 150px; height: 150px;
        border-radius: 50%;
        border: 3px solid #8B6914;
        box-shadow: 0 0 20px #8B6914, inset 0 0 20px rgba(139,105,20,0.4);
        animation: fragRingPulse 2.8s ease-in-out infinite;
    }
    @keyframes fragRingPulse {
        0%, 100% { transform: translate(-50%,-50%) scale(1); opacity: 0.7; box-shadow: 0 0 20px #8B6914; }
        50%       { transform: translate(-50%,-50%) scale(1.12); opacity: 1; box-shadow: 0 0 40px #FFD700, 0 0 60px #8B6914; }
    }
    .frag-forge-fire {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%,-50%);
        font-size: 56px;
        animation: fragFireDance 1.8s ease-in-out infinite;
        filter: drop-shadow(0 0 12px #FFD700);
    }
    @keyframes fragFireDance {
        0%   { transform: translate(-50%,-50%) scale(1) rotate(-6deg); }
        50%  { transform: translate(-50%,-56%) scale(1.12) rotate(6deg); }
        100% { transform: translate(-50%,-50%) scale(1) rotate(-6deg); }
    }
    .frag-orbit-img {
        position: absolute;
        top: 50%; left: 50%;
        width: 44px; height: 44px;
        margin: -22px 0 0 -22px;
        border-radius: 50%;
        object-fit: contain;
        background: rgba(0,0,0,0.55);
        border: 2px solid rgba(139,105,20,0.7);
        padding: 4px;
        box-sizing: border-box;
        animation: fragOrbit linear infinite;
        filter: drop-shadow(0 0 8px #FFD700);
        transition: opacity 0.25s;
    }
    @keyframes fragOrbit {
        0%   { transform: rotate(0deg) translateX(82px) rotate(0deg); }
        100% { transform: rotate(360deg) translateX(82px) rotate(-360deg); }
    }
    .frag-forge-center-img {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 130px;
        max-height: 120px;
        object-fit: contain;
        filter: drop-shadow(0 0 14px rgba(255,215,0,0.8));
        animation: fragCenterGlow 4s ease-in-out infinite;
    }
    @keyframes fragCenterGlow {
        0%, 100% { filter: drop-shadow(0 0 10px rgba(255,215,0,0.5)); }
        50%       { filter: drop-shadow(0 0 28px rgba(255,215,0,1)) drop-shadow(0 0 50px rgba(139,105,20,0.6)); }
    }
    .frag-forge-title {
        color: #FFD700;
        font-size: 22px;
        font-weight: bold;
        text-shadow: 0 0 10px #8B6914, 2px 2px 4px #000;
        animation: fragTitleGlow 3s ease-in-out infinite;
        margin-bottom: 8px;
    }
    @keyframes fragTitleGlow {
        0%, 100% { text-shadow: 0 0 10px #8B6914, 2px 2px 4px #000; }
        50%       { text-shadow: 0 0 25px #FFD700, 0 0 40px #8B6914, 2px 2px 4px #000; }
    }
    .frag-forge-subtitle {
        color: #aaa;
        font-size: 13px;
    }
    /* Result */
    .frag-forge-result {
        text-align: center;
        padding: 40px 20px;
        animation: fragResultPop 0.5s ease-out;
    }
    @keyframes fragResultPop {
        0%   { transform: scale(0.3); opacity: 0; }
        60%  { transform: scale(1.15); }
        100% { transform: scale(1); opacity: 1; }
    }
    .frag-result-burst {
        position: relative;
        width: 160px; height: 160px;
        margin: 0 auto 20px;
    }
    .frag-result-icon {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%,-50%);
        font-size: 68px;
        animation: fragIconPop 0.5s ease-out;
    }
    @keyframes fragIconPop {
        0%   { transform: translate(-50%,-50%) scale(0); }
        60%  { transform: translate(-50%,-50%) scale(1.35); }
        100% { transform: translate(-50%,-50%) scale(1); }
    }
    .frag-result-ray {
        position: absolute;
        top: 50%; left: 50%;
        width: 3px; height: 70px;
        margin-left: -1px;
        margin-top: -70px;
        transform-origin: bottom center;
        border-radius: 2px;
        animation: fragRayExpand 0.6s ease-out forwards;
    }
    @keyframes fragRayExpand {
        0%   { transform: rotate(var(--angle)) scaleY(0); opacity: 1; }
        100% { transform: rotate(var(--angle)) scaleY(1); opacity: 0.5; }
    }
    .frag-result-text {
        font-size: 34px;
        font-weight: bold;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px #000;
        animation: fragSlideUp 0.5s ease-out;
    }
    @keyframes fragSlideUp {
        0%   { transform: translateY(20px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    .frag-result-item {
        font-size: 16px;
        color: #ddd;
        animation: fragSlideUp 0.7s ease-out;
    }
    .frag-result-close {
        margin-top: 25px;
        padding: 10px 30px;
        background: url('_img/fundo_botao.jpg') repeat-x center;
        border: 2px solid #8B6914;
        color: #FFD700;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        text-shadow: 1px 1px 2px #000;
        animation: fragSlideUp 0.9s ease-out;
    }
    .frag-result-close:hover {
        filter: brightness(1.3);
        box-shadow: 0 0 15px #8B6914;
    }
    .frag-gold-particle {
        position: absolute;
        top: 50%; left: 50%;
        font-size: 16px;
        pointer-events: none;
        animation: fragGoldFloat 1.2s ease-out forwards;
        color: #FFD700;
        text-shadow: 0 0 6px #FF6600;
    }
    @keyframes fragGoldFloat {
        0%   { opacity: 1; transform: translate(-50%,-50%); }
        100% { opacity: 0; transform: translate(var(--tx), var(--ty)) scale(0.3); }
    }
</style>

<div class="box_top">Ferreiro</div>
<div class="box_middle">
    Bem-vindo ao ferreiro! Aqui você pode aprimorar seus equipamentos até o nível +15. Arraste um equipamento e um cristal para a máquina para iniciar o processo.
    <div class="sep"></div>
    
    <?php echo $mensagem; ?>
    
    <div style="background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;padding:5px;">
        <img src="_img/yens.png" width="14" height="14" /> <b>Meus Yens: <?php echo number_format($usuario['yens'],2,',','.'); ?> yens</b>
    </div>
    <div class="sep"></div>
    
    <!-- INVENTÁRIO -->
    <div style="background:#222;padding:8px;border:1px solid #444;margin-bottom:10px;">
        <div style="color:#ff6600;font-weight:bold;margin-bottom:8px;">Meu Inventário</div>
        <div class="ferreiro-inventory-tabs">
            <div class="ferreiro-tab active" data-category="arma">Armas</div>
            <div class="ferreiro-tab" data-category="vestimenta">Vestimentas</div>
            <div class="ferreiro-tab" data-category="calcado">Calçados</div>
            <div class="ferreiro-tab" data-category="mascara">Máscaras</div>
            <div class="ferreiro-tab" data-category="calca">Calças</div>
            <div class="ferreiro-tab" data-category="luva">Luvas</div>
            <div class="ferreiro-tab" data-category="cristais">Cristais</div>
        </div>
        
        <div class="ferreiro-items" id="inventoryItems">
            <span style="color: #666;">Carregando itens...</span>
        </div>
    </div>
    <div class="sep"></div>
    
    <!-- MÁQUINA -->
    <div class="machine-wrapper">
        <img src="_img/ferreiro/Maquina.png" alt="Máquina" class="machine-img">
        
        <div class="drop-zone drop-zone-equipment" id="dropEquipment">
            <div class="drop-zone-label">Equipamento</div>
        </div>
        
        <div class="drop-zone drop-zone-crystal" id="dropCrystal">
            <div class="drop-zone-label">Cristal</div>
        </div>
        
        <button class="aprimorar-btn" id="aprimorarBtn" disabled>Aprimorar</button>
    </div>
    <div class="sep"></div>
    
    <div class="upgrade-stats" id="upgradeStats">
        <table width="100%" cellpadding="0" cellspacing="1">
            <tr class="table_dados" style="background:#323232;">
                <td width="50%" align="center" style="padding:8px;">
                    <b>Equipamento:</b><br/>
                    <div style="position:relative;display:inline-block;margin-top:4px;">
                        <img id="infoEquipImg" src="" alt="" style="width:38px;height:38px;object-fit:cover;border:1px solid #555;border-radius:3px;vertical-align:middle;" />
                        <span id="infoCurrentLevel" style="position:absolute;bottom:-4px;right:-4px;background:#ff6600;color:#fff;font-size:9px;font-weight:bold;padding:1px 4px;border-radius:3px;border:1px solid #000;line-height:1.4;"></span>
                    </div>
                </td>
                <td width="50%" align="center" style="padding:8px;">
                    <b>Cristal:</b><br/>
                    <div style="display:inline-block;margin-top:4px;">
                        <img id="infoCrystalImg" src="" alt="" style="width:38px;height:38px;object-fit:cover;border:1px solid #555;border-radius:3px;vertical-align:middle;" />
                    </div>
                </td>
            </tr>
            <tr class="table_dados" style="background:#323232;">
                <td colspan="2" align="center" style="padding:8px;">
                    <b>Chance de Sucesso</b>
                    <div class="chance-bar">
                        <div class="chance-bar-fill" id="chanceBarFill" style="width: 0%;">0%</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="provably-fair-section">
        <div class="provably-fair-header">
            <span class="fair-icon">&#9878;</span> Sistema Provably Fair
        </div>
        
        <div class="seed-row">
            <div class="seed-input-group">
                <label>Server Seed Hash (SHA-256):</label>
                <input type="text" id="serverSeedHash" readonly placeholder="Carregando...">
            </div>
        </div>
        
        <div class="seed-row">
            <div class="seed-input-group">
                <label>Client Seed (sua seed - pode alterar):</label>
                <input type="text" id="clientSeed" placeholder="Gerado automaticamente">
            </div>
        </div>
        
        <div style="background:linear-gradient(135deg, #2a2a2a, #1a1a1a);border:1px solid #ff6600;border-radius:5px;padding:10px;margin-top:10px;">
            <div style="color:#ff6600;font-weight:bold;font-size:12px;margin-bottom:5px;">&#128274; Como Funciona:</div>
            <div style="color:#ddd;font-size:12px;line-height:1.5;">
                O resultado do aprimoramento e gerado combinando as duas seeds (Server + Client) com SHA-256. 
                <span style="color:#2ecc71;font-weight:bold;">Voce pode verificar o resultado a qualquer momento</span> 
                para garantir que o sistema e 100% justo e transparente.
            </div>
        </div>
        
        <div class="provably-fair-result" id="provablyFairResult">
            <div class="result-title" id="resultTitle">Resultado</div>
            <div class="result-details">
                <div class="result-row">
                    <span class="result-label">Server Seed:</span>
                    <span class="result-value" id="resultServerSeed">-</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Client Seed:</span>
                    <span class="result-value" id="resultClientSeed">-</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Nonce:</span>
                    <span class="result-value" id="resultNonce">-</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Hash:</span>
                    <span class="result-value hash" id="resultHash">-</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Numero (0-99):</span>
                    <span class="result-value number" id="resultNumber">-</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Chance:</span>
                    <span class="result-value" id="resultChance">-</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Resultado:</span>
                    <span class="result-value" id="resultFinal">-</span>
                </div>
            </div>
        </div>
        
        <button class="history-btn" id="historyBtn">Ver Historico de Rolls</button>
        
        <div id="historyContainer" style="display:none;margin-top:15px;">
            <div id="historyTableContainer"></div>
            <div class="verification-panel" id="verificationPanel"></div>
        </div>
    </div>
    
    <div class="sep"></div>
    
    <!-- FORJA DE FRAGMENTOS -->
    <div style="background:#1a1200;border:2px solid #8B6914;border-radius:6px;padding:10px;margin-top:8px;">
        <div style="color:#FFD700;font-weight:bold;font-size:14px;margin-bottom:6px;">🧩 Forja de Fragmentos</div>
        <div style="color:#aaa;font-size:12px;margin-bottom:8px;">
            Junte <b style="color:#FFD700;">5 fragmentos</b> do mesmo equipamento para tentar forjá-lo.
            O processo usa o <b style="color:#FF6600;">Sistema Provably Fair</b> com <b style="color:#e74c3c;">20% de chance de sucesso</b>.
            Ao falhar, os 5 fragmentos são destruídos.
        </div>
        
        <div id="fragmentList" style="display:flex;flex-wrap:wrap;gap:8px;min-height:60px;background:#111;border:1px solid #333;padding:8px;margin-bottom:10px;">
            <span style="color:#666;">Carregando fragmentos...</span>
        </div>
        
        <div id="fragmentSelected" style="display:none;background:#1a1a00;border:1px solid #8B6914;border-radius:4px;padding:8px;margin-bottom:8px;">
            <div style="color:#FFD700;font-weight:bold;font-size:12px;margin-bottom:4px;">Item selecionado para forjar:</div>
            <div style="display:flex;align-items:center;gap:10px;">
                <img id="fragSelImg" src="" style="width:48px;height:48px;object-fit:contain;border:1px solid #8B6914;" />
                <div>
                    <div id="fragSelNome" style="color:#FFD700;font-weight:bold;font-size:13px;"></div>
                    <div id="fragSelQty" style="color:#aaa;font-size:11px;"></div>
                    <div style="color:#FF6600;font-size:11px;">Chance de sucesso: <b>20%</b></div>
                </div>
            </div>
        </div>
        
        <div class="provably-fair-section" id="fragPFSection" style="display:none;">
            <div class="provably-fair-header">
                <span class="fair-icon">&#9878;</span> Sistema Provably Fair — Forja
            </div>
            <div class="seed-row">
                <div class="seed-input-group">
                    <label>Server Seed Hash (SHA-256):</label>
                    <input type="text" id="fragServerSeedHash" readonly placeholder="Carregando...">
                </div>
            </div>
            <div class="seed-row">
                <div class="seed-input-group">
                    <label>Client Seed (pode alterar):</label>
                    <input type="text" id="fragClientSeed" placeholder="Gerado automaticamente">
                </div>
            </div>
        </div>
        
        <button id="fragForjarBtn" style="display:none;margin-top:8px;padding:10px 25px;background:url('_img/fundo_botao.jpg') repeat-x center;border:2px solid #8B6914;color:#FFD700;font-size:14px;font-weight:bold;cursor:pointer;text-shadow:1px 1px 2px #000;width:100%;" disabled>
            🔥 Forjar (5 Fragmentos)
        </button>
        
        <div id="fragResult" style="display:none;margin-top:10px;padding:10px;text-align:center;border-radius:5px;"></div>
        
        <div class="provably-fair-result" id="fragPFResult" style="display:none;margin-top:8px;">
            <div class="result-title" id="fragResultTitle">Resultado</div>
            <div class="result-details">
                <div class="result-row"><span class="result-label">Server Seed:</span><span class="result-value" id="fragResultServerSeed">-</span></div>
                <div class="result-row"><span class="result-label">Client Seed:</span><span class="result-value" id="fragResultClientSeed">-</span></div>
                <div class="result-row"><span class="result-label">Nonce:</span><span class="result-value" id="fragResultNonce">-</span></div>
                <div class="result-row"><span class="result-label">Hash:</span><span class="result-value hash" id="fragResultHash">-</span></div>
                <div class="result-row"><span class="result-label">Numero (0-99):</span><span class="result-value number" id="fragResultNumber">-</span></div>
                <div class="result-row"><span class="result-label">Chance:</span><span class="result-value" id="fragResultChance">20%</span></div>
                <div class="result-row"><span class="result-label">Resultado:</span><span class="result-value" id="fragResultFinal">-</span></div>
            </div>
        </div>
    </div>
</div>
<div id="equip-popup"></div>
<div class="box_bottom"></div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <h3>Aprimorando equipamento...</h3>
        <p>Aguarde enquanto o ferreiro trabalha</p>
    </div>
</div>

<div id="fragLoadingOverlay">
    <div id="fragLoadingContent"></div>
</div>

<script>
    var currentCategory = 'arma';
    var droppedEquipment = null;
    var droppedCrystal = null;
    
    var upgradeChances = {
        0: 100, 1: 100, 2: 100, 3: 85, 4: 70, 5: 55, 6: 40,
        7: 30, 8: 20, 9: 15, 10: 8, 11: 6, 12: 5, 13: 4, 14: 3
    };
    
    var crystalInfo = {
        1: { nome: 'Cristal de Chakra Refinado', minLevel: 0, maxLevel: 6,
             desc: 'Cristal de Chakra Refinado\nUso: equipamentos de +0 até +5\nAumenta o nível de aprimoramento com chakra purificado.' },
        2: { nome: 'Cristal de Chakra Bruto', minLevel: 6, maxLevel: 12,
             desc: 'Cristal de Chakra Bruto\nUso: equipamentos de +6 até +11\nCanalizaforça bruta do chakra para aprimoramentos avançados.' },
        3: { nome: 'Chakra Forjado', minLevel: 12, maxLevel: 15,
             desc: 'Chakra Forjado\nUso: equipamentos de +12 até +14\nRaridade extrema — forjado com chakra condensado de alto nível.' }
    };
    
    function loadInventory(categoria) {
        $.ajax({
            url: '_inc/ajax_blacksmith.php',
            type: 'GET',
            data: { action: 'get_items', categoria: categoria },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    displayItems(response.items, categoria);
                } else {
                    $('#inventoryItems').html('<span style="color: #e74c3c;">Erro: ' + (response.message || 'Erro desconhecido') + '</span>');
                }
            },
            error: function(xhr, status, error) {
                $('#inventoryItems').html('<span style="color: #e74c3c;">Erro de conexão: ' + error + '</span>');
            }
        });
    }
    
    function displayItems(items, categoria) {
        var container = $('#inventoryItems');
        container.empty();
        
        if(items.length === 0) {
            var emptyMsg;
            if(categoria === 'cristais') {
                emptyMsg = '<div style="text-align:center;width:100%;padding:15px 0;">' +
                    '<div style="font-size:28px;margin-bottom:8px;">💎</div>' +
                    '<span style="color:#888;">Você não possui cristais no inventário.</span>' +
                    '</div>';
            } else {
                emptyMsg = '<div style="text-align:center;width:100%;padding:15px 0;">' +
                    '<div style="font-size:28px;margin-bottom:8px;">⚒️</div>' +
                    '<span style="color:#888;">Nenhum equipamento disponível nesta categoria.</span><br>' +
                    '<span style="color:#666;font-size:11px;">Se você possui um item desta categoria, verifique se ele está equipado — ' +
                    'itens equipados não aparecem aqui. <a href="?p=inventory" style="color:#ff6600;text-decoration:none;">Desequipe-o no inventário</a> para aprimorá-lo.</span>' +
                    '</div>';
            }
            container.html(emptyMsg);
            return;
        }
        
        for(var i = 0; i < items.length; i++) {
            var item = items[i];
            item.categoria = categoria;
            var itemDiv = $('<div class="ferreiro-item"></div>');
            itemDiv.attr('draggable', 'true');
            
            var imgSrc;
            if(categoria === 'cristais') {
                imgSrc = item.imagem;
            } else {
                imgSrc = '_img/equipamentos/' + item.imagem;
                if(!item.imagem.match(/\.(png|jpg|jpeg|gif)$/i)) {
                    imgSrc += '.png';
                }
            }
            
            var img = $('<img>').attr('src', imgSrc);
            var name = $('<div class="item-name"></div>').text(item.nome);
            var level = item.upgrade > 0 ? $('<div class="item-level"></div>').text('+' + item.upgrade) : '';
            
            itemDiv.append(img, name, level);
            
            if(categoria === 'cristais') {
                if(item.quantidade && item.quantidade > 1) {
                    var countBadge = $('<div class="crystal-count"></div>').text(item.quantidade);
                    itemDiv.append(countBadge);
                }
                var cData = crystalInfo[item.tipo];
                if(cData && cData.desc) {
                    itemDiv.attr('title', cData.desc);
                }
            } else {
                (function(it) {
                    it._div.bind('mouseenter', function(e) { showEquipPopup(it, e); });
                    it._div.bind('mouseleave', function() { $('#equip-popup').hide(); });
                    it._div.bind('mousemove', function(e) { positionPopup(e); });
                })({ _div: itemDiv, item: item });
            }
            
            (function(currentItem, currentDiv) {
                currentDiv.bind('dragstart', function(e) {
                    $(this).addClass('dragging');
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                    e.originalEvent.dataTransfer.setData('text/html', JSON.stringify(currentItem));
                });
                
                currentDiv.bind('dragend', function() {
                    $(this).removeClass('dragging');
                });
            })(item, itemDiv);
            
            container.append(itemDiv);
        }
    }
    
    function setupDropZones() {
        $('.drop-zone').bind('dragover', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $(this).addClass('dragover');
        });
        
        $('.drop-zone').bind('dragleave', function() {
            $(this).removeClass('dragover');
        });
        
        $('#dropEquipment').bind('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            
            try {
                var item = JSON.parse(e.originalEvent.dataTransfer.getData('text/html'));
                
                if(item.categoria === 'cristais') {
                    alert('Arraste equipamentos aqui, não cristais!');
                    return;
                }
                
                if(item.upgrade >= 15) {
                    alert('Este equipamento já está no nível máximo (+15)!');
                    return;
                }
                
                droppedEquipment = item;
                displayDroppedItem('dropEquipment', item);
                checkUpgradeReady();
            } catch(err) {
                console.error('Erro ao processar item:', err);
            }
        });
        
        $('#dropCrystal').bind('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            
            try {
                var item = JSON.parse(e.originalEvent.dataTransfer.getData('text/html'));
                
                if(item.categoria !== 'cristais') {
                    alert('Arraste cristais aqui, não equipamentos!');
                    return;
                }
                
                droppedCrystal = item;
                displayDroppedItem('dropCrystal', item);
                checkUpgradeReady();
            } catch(err) {
                console.error('Erro ao processar cristal:', err);
            }
        });
    }
    
    function displayDroppedItem(zoneId, item) {
        var zone = $('#' + zoneId);
        zone.empty();
        
        var container = $('<div class="dropped-item"></div>');
        
        var imgSrc;
        if(item.categoria === 'cristais') {
            imgSrc = item.imagem;
        } else {
            imgSrc = '_img/equipamentos/' + item.imagem;
            if(!item.imagem.match(/\.(png|jpg|jpeg|gif)$/i)) {
                imgSrc += '.png';
            }
        }
        
        var img = $('<img>').attr('src', imgSrc);
        var removeBtn = $('<button class="remove-item">×</button>');
        
        removeBtn.bind('click', function() {
            if(zoneId === 'dropEquipment') droppedEquipment = null;
            if(zoneId === 'dropCrystal') droppedCrystal = null;
            
            zone.html('<div class="drop-zone-label">' + (zoneId === 'dropEquipment' ? 'Equipamento' : 'Cristal') + '</div>');
            checkUpgradeReady();
        });
        
        container.append(img, removeBtn);
        zone.append(container);
    }
    
    function checkUpgradeReady() {
        if(droppedEquipment && droppedCrystal) {
            var currentLevel = droppedEquipment.upgrade || 0;
            var crystalData = crystalInfo[droppedCrystal.tipo];
            
            if(!crystalData) {
                alert('Tipo de cristal inválido!');
                return;
            }
            
            if(currentLevel < crystalData.minLevel || currentLevel >= crystalData.maxLevel) {
                alert('Este cristal só pode ser usado em equipamentos de nível +' + crystalData.minLevel + ' até +' + (crystalData.maxLevel - 1) + '!');
                $('#dropCrystal').html('<div class="drop-zone-label">Cristal</div>');
                droppedCrystal = null;
                return;
            }
            
            var chance = upgradeChances[currentLevel] || 0;
            
            var equipImgSrc = '_img/equipamentos/' + droppedEquipment.imagem;
            if(!droppedEquipment.imagem.match(/\.(png|jpg|jpeg|gif)$/i)) equipImgSrc += '.png';
            $('#infoEquipImg').attr('src', equipImgSrc).attr('title', droppedEquipment.nome);
            $('#infoCurrentLevel').text('+' + currentLevel);
            $('#infoCrystalImg').attr('src', droppedCrystal.imagem).attr('title', crystalData.nome);
            $('#chanceBarFill').css('width', chance + '%').text(chance + '%');
            
            $('#upgradeStats').addClass('show');
            $('#aprimorarBtn').attr('disabled', false).removeAttr('disabled');
        } else {
            $('#upgradeStats').removeClass('show');
            $('#aprimorarBtn').attr('disabled', 'disabled');
        }
    }
    
    function positionPopup(e) {
        var popup = $('#equip-popup');
        var pw = popup.outerWidth() || 200;
        var ph = popup.outerHeight() || 120;
        var x = e.clientX + 14;
        var y = e.clientY + 14;
        if(x + pw > window.innerWidth - 10) x = e.clientX - pw - 10;
        if(y + ph > window.innerHeight - 10) y = e.clientY - ph - 10;
        popup.css({ left: x, top: y });
    }

    function showEquipPopup(it, e) {
        var item = it.item;
        var upgrade = item.upgrade || 0;
        var tai = parseInt(item.taijutsu) || 0;
        var nin = parseInt(item.ninjutsu) || 0;
        var gen = parseInt(item.genjutsu) || 0;
        var taiTotal = tai + upgrade;
        var ninTotal = nin + upgrade;
        var genTotal = gen + upgrade;

        var imgSrc = '_img/equipamentos/' + item.imagem;
        if(!item.imagem.match(/\.(png|jpg|jpeg|gif)$/i)) imgSrc += '.png';

        var levelBadge = upgrade > 0
            ? '<span class="ep-level">+' + upgrade + '</span>'
            : '<span class="ep-level" style="background:#555;">Base</span>';

        function statRow(icon, label, base, bonus, total) {
            if(base === 0 && bonus === 0) return '';
            var bonusPart = bonus > 0 ? ' <span class="ep-stat-bonus">(+' + bonus + ' bônus)</span>' : '';
            return '<div class="ep-stat">' +
                '<img src="_img/Icones/' + icon + '.png" />' +
                '<span class="ep-stat-label">' + label + ':</span>' +
                '<span class="ep-stat-total">+' + total + '</span>' + bonusPart +
            '</div>';
        }

        var html =
            '<div class="ep-header">' +
                '<img class="ep-img" src="' + imgSrc + '" />' +
                '<div><div class="ep-name">' + item.nome + '</div>' + levelBadge + '</div>' +
            '</div>' +
            statRow('tai', 'Taijutsu', tai, upgrade, taiTotal) +
            statRow('nin', 'Ninjutsu', nin, upgrade, ninTotal) +
            statRow('gen', 'Genjutsu', gen, upgrade, genTotal);

        if(tai === 0 && nin === 0 && gen === 0) {
            html += '<div style="color:#666;font-size:10px;text-align:center;">Sem bônus de atributo</div>';
        }

        $('#equip-popup').html(html).show();
        positionPopup(e);
    }

    function generateRandomSeed() {
        var chars = 'abcdef0123456789';
        var result = '';
        for(var i = 0; i < 32; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
    
    function loadServerSeedHash() {
        $.ajax({
            url: '_inc/ajax_blacksmith.php',
            type: 'GET',
            data: { action: 'get_server_seed_hash' },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#serverSeedHash').val(response.server_seed_hash);
                } else {
                    $('#serverSeedHash').val('Erro ao carregar');
                }
            },
            error: function() {
                $('#serverSeedHash').val('Erro de conexao');
            }
        });
    }
    
    function displayProvablyFairResult(pf, isSuccess) {
        $('#resultServerSeed').text(pf.server_seed);
        $('#resultClientSeed').text(pf.client_seed);
        $('#resultNonce').text(pf.nonce);
        $('#resultHash').text(pf.hash);
        $('#resultNumber').text(pf.number).removeClass('success failed').addClass(isSuccess ? 'success' : 'failed');
        $('#resultChance').text(pf.chance + '% (0-' + (pf.chance - 1) + ' = sucesso)');
        $('#resultFinal').text(pf.result).css('color', isSuccess ? '#2ecc71' : '#e74c3c');
        $('#resultTitle').text(isSuccess ? 'SUCESSO!' : 'FALHOU').removeClass('success failed').addClass(isSuccess ? 'success' : 'failed');
        $('#provablyFairResult').addClass('show');
        
        loadServerSeedHash();
    }
    
    function performUpgrade() {
        if(!droppedEquipment || !droppedCrystal) {
            alert('Selecione um equipamento e um cristal!');
            return;
        }
        
        var clientSeed = $('#clientSeed').val().trim();
        if(!clientSeed) {
            clientSeed = generateRandomSeed();
            $('#clientSeed').val(clientSeed);
        }
        
        $('#loadingOverlay').addClass('show');
        $('#provablyFairResult').removeClass('show');
        startUpgradeAnimation();
        
        $.ajax({
            url: '_inc/ajax_blacksmith.php',
            type: 'POST',
            data: {
                action: 'upgrade',
                equipment_id: droppedEquipment.id,
                crystal_type: droppedCrystal.tipo,
                client_seed: clientSeed
            },
            dataType: 'json',
            success: function(response) {
                stopUpgradeAnimation();
                
                if(response.provably_fair) {
                    displayProvablyFairResult(response.provably_fair, response.success);
                }
                
                if(response.success) {
                    $('#clientSeed').val(generateRandomSeed());
                    showUpgradeResult(true, response.newLevel);
                    setTimeout(function() {
                        $('#loadingOverlay').removeClass('show');
                        droppedEquipment.upgrade = response.newLevel;
                        displayDroppedItem('dropEquipment', droppedEquipment);
                        droppedCrystal = null;
                        $('#dropCrystal').html('<div class="drop-zone-label">Cristal</div>');
                        checkUpgradeReady();
                        loadInventory(currentCategory);
                        loadServerSeedHash();
                    }, 4000);
                } else if(response.failed) {
                    showUpgradeResult(false, 0);
                    setTimeout(function() {
                        $('#loadingOverlay').removeClass('show');
                        if(droppedCrystal.quantidade && droppedCrystal.quantidade > 1) {
                            droppedCrystal.quantidade--;
                            displayDroppedItem('dropCrystal', droppedCrystal);
                        } else {
                            droppedCrystal = null;
                            $('#dropCrystal').html('<div class="drop-zone-label">Cristal</div>');
                        }
                        checkUpgradeReady();
                        loadInventory(currentCategory);
                    }, 4000);
                } else {
                    $('#loadingOverlay').removeClass('show');
                    alert(response.message || 'Erro ao processar aprimoramento');
                }
            },
            error: function() {
                stopUpgradeAnimation();
                $('#loadingOverlay').removeClass('show');
                alert('Erro de conexao. Tente novamente.');
            }
        });
    }
    
    var historyData = [];
    var selectedHistoryIndex = -1;
    
    function loadHistory() {
        $.ajax({
            url: '_inc/ajax_blacksmith.php',
            type: 'GET',
            data: { action: 'get_history' },
            dataType: 'json',
            success: function(response) {
                if(response.success && response.history.length > 0) {
                    historyData = response.history;
                    
                    var html = '<div class="history-container">';
                    html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">';
                    html += '<span style="color:#ff6600;font-weight:bold;font-size:14px;">Historico de Rolls</span>';
                    html += '<button class="history-btn" id="closeHistoryBtn" style="margin:0;padding:5px 10px;">Fechar</button>';
                    html += '</div>';
                    html += '<table class="history-table">';
                    html += '<tr><th>Data</th><th>Numero</th><th>Chance</th><th>Resultado</th></tr>';
                    
                    for(var i = 0; i < response.history.length; i++) {
                        var h = response.history[i];
                        var resultColor = h.sucesso == 1 ? '#2ecc71' : '#e74c3c';
                        var resultText = h.sucesso == 1 ? 'SUCESSO' : 'FALHOU';
                        var bgColor = i % 2 === 0 ? '#222' : '#282828';
                        html += '<tr class="history-row" data-index="' + i + '" style="background:' + bgColor + ';">';
                        html += '<td>' + (h.criado_em || '-') + '</td>';
                        html += '<td>' + h.numero_gerado + '</td>';
                        html += '<td>' + h.chance_percent + '%</td>';
                        html += '<td style="color:' + resultColor + ';font-weight:bold;">' + resultText + '</td>';
                        html += '</tr>';
                    }
                    
                    html += '</table>';
                    html += '<p class="click-hint">Clique em uma linha para ver como o sorteio foi calculado</p>';
                    html += '</div>';
                    
                    $('#historyTableContainer').html(html);
                    $('#historyContainer').show();
                    $('#verificationPanel').removeClass('show').html('');
                    
                    $('.history-row').bind('click', function() {
                        var index = parseInt($(this).attr('data-index'));
                        showVerificationDetails(index);
                        $('.history-row').removeClass('selected');
                        $(this).addClass('selected');
                    });
                    
                    $('#closeHistoryBtn').bind('click', function() {
                        $('#historyContainer').hide();
                        $('#verificationPanel').removeClass('show').html('');
                    });
                } else {
                    alert('Nenhum historico de rolls encontrado.');
                }
            },
            error: function() {
                alert('Erro ao carregar historico.');
            }
        });
    }
    
    function showVerificationDetails(index) {
        if(index < 0 || index >= historyData.length) return;
        
        var h = historyData[index];
        var isSuccess = h.sucesso == 1;
        var resultClass = isSuccess ? 'success' : 'failed';
        var resultText = isSuccess ? 'SUCESSO!' : 'FALHOU!';
        
        var combinedString = h.server_seed + ':' + h.client_seed + ':' + h.nonce;
        var hexFirst8 = h.hash.substring(0, 8);
        var decimalValue = parseInt(hexFirst8, 16);
        var finalNumber = decimalValue % 100;
        
        var html = '<div class="verification-header">';
        html += '<span class="verification-title">Verificacao do Sorteio #' + h.id + '</span>';
        html += '<button class="verification-close" id="closeVerification">X</button>';
        html += '</div>';
        
        html += '<div class="verification-step">';
        html += '<div class="verification-step-header"><span class="step-number">1</span><span class="step-title">Seeds Utilizadas</span></div>';
        html += '<div class="step-content">';
        html += '<div><b>Server Seed:</b> <span class="step-value">' + h.server_seed + '</span></div>';
        html += '<div style="margin-top:5px;"><b>Client Seed:</b> <span class="step-value">' + h.client_seed + '</span></div>';
        html += '<div style="margin-top:5px;"><b>Nonce:</b> <span class="step-value">' + h.nonce + '</span></div>';
        html += '</div></div>';
        
        html += '<div class="verification-step">';
        html += '<div class="verification-step-header"><span class="step-number">2</span><span class="step-title">Combinacao das Seeds</span></div>';
        html += '<div class="step-content">';
        html += '<div style="color:#888;">Formula: <span style="color:#fff;">server_seed : client_seed : nonce</span></div>';
        html += '<div style="margin-top:8px;padding:8px;background:#0a0a0a;border-radius:4px;word-break:break-all;">';
        html += '<span class="step-value hash">' + combinedString + '</span>';
        html += '</div></div></div>';
        
        html += '<div class="verification-step">';
        html += '<div class="verification-step-header"><span class="step-number">3</span><span class="step-title">Hash SHA-256</span></div>';
        html += '<div class="step-content">';
        html += '<div style="color:#888;">Resultado do SHA-256 da string combinada:</div>';
        html += '<div style="margin-top:8px;padding:8px;background:#0a0a0a;border-radius:4px;word-break:break-all;">';
        html += '<span class="step-value hash">' + h.hash + '</span>';
        html += '</div></div></div>';
        
        html += '<div class="verification-step">';
        html += '<div class="verification-step-header"><span class="step-number">4</span><span class="step-title">Conversao para Numero</span></div>';
        html += '<div class="step-content">';
        html += '<div class="calculation-box">';
        html += '<div class="calculation-line">Primeiros 8 caracteres do hash: <span style="color:#ff6600;">' + hexFirst8 + '</span></div>';
        html += '<div class="calculation-line">Converter hex para decimal: <span style="color:#2ecc71;">' + decimalValue + '</span></div>';
        html += '<div class="calculation-line">Aplicar modulo 100: ' + decimalValue + ' % 100 = <span style="color:#fff;font-weight:bold;font-size:14px;">' + finalNumber + '</span></div>';
        html += '</div></div></div>';
        
        html += '<div class="verification-step">';
        html += '<div class="verification-step-header"><span class="step-number">5</span><span class="step-title">Resultado Final</span></div>';
        html += '<div class="step-content">';
        html += '<div style="color:#888;">Chance de sucesso: <span style="color:#fff;font-weight:bold;">' + h.chance_percent + '%</span></div>';
        html += '<div style="color:#888;margin-top:5px;">Numero gerado: <span class="step-value ' + resultClass + '" style="font-size:16px;">' + h.numero_gerado + '</span></div>';
        html += '<div style="color:#888;margin-top:5px;">Regra: Se numero &lt; ' + h.chance_percent + ' = Sucesso</div>';
        html += '<div style="margin-top:10px;font-size:12px;">';
        if(isSuccess) {
            html += '<span style="color:#2ecc71;">&#10004; ' + h.numero_gerado + ' &lt; ' + h.chance_percent + ' = <b>SUCESSO</b></span>';
        } else {
            html += '<span style="color:#e74c3c;">&#10008; ' + h.numero_gerado + ' &ge; ' + h.chance_percent + ' = <b>FALHOU</b></span>';
        }
        html += '</div></div></div>';
        
        html += '<div class="verification-result ' + resultClass + '">';
        html += '<div class="verification-result-text" style="color:' + (isSuccess ? '#2ecc71' : '#e74c3c') + ';">' + resultText + '</div>';
        html += '<div class="verification-result-explain">O resultado e verificavel e justo!</div>';
        html += '</div>';
        
        html += '<div style="text-align:center;margin-top:15px;">';
        html += '<a href="https://emn178.github.io/online-tools/sha256.html" target="_blank" class="external-verify-link">Verificar SHA-256 Externamente</a>';
        html += '</div>';
        
        $('#verificationPanel').html(html).addClass('show');
        
        $('#closeVerification').bind('click', function() {
            $('#verificationPanel').removeClass('show');
            $('.history-row').removeClass('selected');
        });
    }
    
    var animationInterval = null;
    var sparkleCount = 0;
    
    function startUpgradeAnimation() {
        var overlay = $('#loadingOverlay');
        overlay.find('.loading-content').html(
            '<div class="upgrade-animation">' +
                '<div class="forge-container">' +
                    '<div class="forge-glow"></div>' +
                    '<div class="hammer">&#9876;</div>' +
                    '<div class="anvil">&#9881;</div>' +
                    '<div class="sparkles" id="sparkles"></div>' +
                '</div>' +
                '<h3 class="forge-text">Forjando equipamento...</h3>' +
                '<p class="forge-subtext">O ferreiro trabalha com precisao</p>' +
            '</div>'
        );
        
        animationInterval = setInterval(function() {
            var sparkles = $('#sparkles');
            for(var i = 0; i < 3; i++) {
                var left = 30 + Math.random() * 40;
                var delay = Math.random() * 0.5;
                var sparkle = $('<div class="sparkle">&#10022;</div>');
                sparkle.css({
                    left: left + '%',
                    animationDelay: delay + 's'
                });
                sparkles.append(sparkle);
                
                (function(el) {
                    setTimeout(function() { el.remove(); }, 1500);
                })(sparkle);
            }
        }, 900);
    }
    
    function stopUpgradeAnimation() {
        if(animationInterval) {
            clearInterval(animationInterval);
            animationInterval = null;
        }
    }
    
    function showUpgradeResult(success, newLevel) {
        var overlay = $('#loadingOverlay');
        var resultClass = success ? 'success' : 'failed';
        var resultIcon = success ? '&#10004;' : '&#10008;';
        var resultText = success ? 'SUCESSO!' : 'FALHOU!';
        var resultSubtext = success ? 'Equipamento agora e nivel +' + newLevel + '!' : 'O aprimoramento nao foi bem sucedido';
        var resultColor = success ? '#2ecc71' : '#e74c3c';
        
        overlay.find('.loading-content').html(
            '<div class="upgrade-result ' + resultClass + '">' +
                '<div class="result-icon" style="color:' + resultColor + ';">' + resultIcon + '</div>' +
                '<h2 class="result-text" style="color:' + resultColor + ';">' + resultText + '</h2>' +
                '<p class="result-subtext">' + resultSubtext + '</p>' +
            '</div>'
        );
    }
    
    $(document).ready(function() {
        loadInventory(currentCategory);
        loadServerSeedHash();
        
        $('#clientSeed').val(generateRandomSeed());
        
        $('.ferreiro-tab').bind('click', function() {
            $('.ferreiro-tab').removeClass('active');
            $(this).addClass('active');
            currentCategory = $(this).attr('data-category');
            loadInventory(currentCategory);
        });
        
        $('#aprimorarBtn').bind('click', function() {
            performUpgrade();
        });
        
        $('#historyBtn').bind('click', function() {
            loadHistory();
        });
        
        setupDropZones();
        
        // Fragmentos
        loadFragments();
        loadFragServerSeedHash();
        $('#fragClientSeed').val(generateRandomSeed());
        
        $('#fragForjarBtn').bind('click', function() {
            performFragmentCombine();
        });
    });
    
    // ======== SISTEMA DE FRAGMENTOS ========
    var selectedFragment = null;
    
    function loadFragServerSeedHash() {
        $.ajax({
            url: '_inc/ajax_blacksmith.php',
            type: 'GET',
            data: { action: 'get_server_seed_hash' },
            dataType: 'json',
            success: function(r) {
                if(r.success) $('#fragServerSeedHash').val(r.server_seed_hash);
            }
        });
    }
    
    function loadFragments() {
        $.ajax({
            url: '_inc/ajax_blacksmith.php',
            type: 'GET',
            data: { action: 'get_fragments' },
            dataType: 'json',
            success: function(response) {
                var container = $('#fragmentList');
                container.empty();
                if(!response.success || response.fragments.length === 0) {
                    container.html('<div style="text-align:center;width:100%;padding:10px;color:#666;">Você não possui fragmentos. Conclua Missões de Clã para obtê-los!</div>');
                    return;
                }
                response.fragments.forEach(function(f) {
                    var precisa = f.precisa || 5;
                    var enough  = f.quantidade >= precisa;
                    var isCrystal = (f.tipo === 'crystal');
                    var imgBase   = f.img_base || '_img/equipamentos/';
                    var imgFile = f.imagem || 'default.png';
                    var lastSlash = imgFile.lastIndexOf('/');
                    var lastDot   = imgFile.lastIndexOf('.');
                    if(lastDot <= lastSlash) { imgFile += '.png'; }
                    var imgPath = imgBase + imgFile;

                    // Cristal: borda roxa; Equipamento: borda dourada
                    var borderEnough = isCrystal ? '#cf6ecf' : '#8B6914';
                    var borderColor = enough ? borderEnough : '#444';
                    var bgColor     = isCrystal ? '#1a001a' : '#2a2200';
                    var opacity = enough ? '1' : '0.5';
                    var cursor = enough ? 'pointer' : 'default';
                    var div = $('<div></div>').css({
                        width: '70px',
                        background: bgColor,
                        border: '2px solid ' + borderColor,
                        padding: '5px',
                        textAlign: 'center',
                        cursor: cursor,
                        opacity: opacity,
                        position: 'relative'
                    });
                    var badgeBg = isCrystal ? '#5a005a' : '#8B6914';
                    var badgeFg = isCrystal ? '#cf6ecf' : '#FFD700';
                    var badge = $('<div></div>').css({
                        position: 'absolute', top: '2px', right: '2px',
                        background: enough ? badgeBg : '#555',
                        color: enough ? badgeFg : '#aaa',
                        fontSize: '10px', fontWeight: 'bold',
                        padding: '1px 4px', borderRadius: '3px', border: '1px solid #000',
                        zIndex: '3'
                    }).text(f.quantidade + '/' + precisa);
                    var imgWrapper = $('<div></div>').css({
                        position: 'relative', width: '50px', height: '50px',
                        display: 'inline-block', margin: '0 auto'
                    });
                    // Se o admin subiu imagem própria do fragmento (cristal de craft),
                    // a imagem já tem a aparência desejada — sem filtro cinza nem overlay
                    // de rachadura. Caso contrário, aplica o visual padrão de "fragmento".
                    var hasOwnFragImg = (isCrystal && (f.has_frag_img == 1 || f.has_frag_img === '1'));
                    var img = $('<img>').attr('src', imgPath)
                        .error(function(){ $(this).attr('src', '_img/equipamentos/default.png'); })
                        .css({ width: '50px', height: '50px', objectFit: 'contain', display: 'block',
                               filter: hasOwnFragImg ? 'none' : 'grayscale(100%) brightness(0.75) contrast(1.1)' });
                    imgWrapper.append(img);
                    if (!hasOwnFragImg) {
                        var crackOverlay = $('<img>').attr('src', '_img/ferreiro/crack.svg').css({
                            position: 'absolute', top: '0', left: '0',
                            width: '50px', height: '50px', pointerEvents: 'none', zIndex: '2'
                        });
                        imgWrapper.append(crackOverlay);
                    }
                    // Tag tipo: CRISTAL ou (sem tag para equipamento)
                    if(isCrystal) {
                        var typeTag = $('<div></div>').css({
                            position: 'absolute', bottom: '22px', left: '2px',
                            background: '#5a005a', color: '#fff',
                            fontSize: '8px', fontWeight: 'bold',
                            padding: '0 3px', borderRadius: '2px', border: '1px solid #cf6ecf',
                            zIndex: '3'
                        }).text('CRISTAL');
                        div.append(typeTag);
                    }
                    var nameColor = isCrystal ? '#cf6ecf' : '#aaa';
                    var nome = $('<div></div>').css({ fontSize: '9px', color: nameColor, marginTop: '2px', lineHeight: '1.1', maxHeight: '22px', overflow: 'hidden' }).text(f.nome);
                    div.append(badge).append(imgWrapper).append(nome);
                    div.data('fragment', f);
                    if(enough) {
                        div.click(function() {
                            selectFragment($(this).data('fragment'));
                            var sel = $(this);
                            $('#fragmentList .frag-selected').removeClass('frag-selected').each(function(){
                                var fd = $(this).data('fragment');
                                var bc = (fd && fd.tipo === 'crystal') ? '#cf6ecf' : '#8B6914';
                                $(this).css('border-color', bc);
                            });
                            sel.addClass('frag-selected').css('border-color', '#FFD700');
                        });
                    }
                    container.append(div);
                });
            },
            error: function() {
                $('#fragmentList').html('<span style="color:#e74c3c;">Erro ao carregar fragmentos.</span>');
            }
        });
    }
    
    function selectFragment(f) {
        selectedFragment = f;
        var precisa  = f.precisa || 5;
        var isCrystal = (f.tipo === 'crystal');
        var imgBase   = f.img_base || '_img/equipamentos/';
        var imgFile = f.imagem || 'default.png';
        var lastSlash = imgFile.lastIndexOf('/');
        var lastDot   = imgFile.lastIndexOf('.');
        if(lastDot <= lastSlash) { imgFile += '.png'; }
        var imgPath = imgBase + imgFile;
        $('#fragSelImg').attr('src', imgPath).error(function(){ $(this).attr('src', '_img/equipamentos/default.png'); });

        if(isCrystal) {
            // Cristal: forja garantida (100%), sem provably fair
            $('#fragSelNome').text('Fragmento de ' + f.nome).css('color', '#cf6ecf');
            var restC = f.quantidade - precisa;
            $('#fragSelQty').html('Você tem <b>' + f.quantidade + '/' + precisa + '</b> fragmentos<br/>' +
                                  '<span style="color:#90EE90;">→ Vai formar 1× <b>' + $('<div>').text(f.nome).html() + '</b></span><br/>' +
                                  '<span style="color:#aaa;font-size:10px;">(restarão ' + (restC < 0 ? 0 : restC) + ' fragmentos após a combinação)</span>');
            $('#fragmentSelected').css({ background: '#1a001a', 'border-color': '#cf6ecf' });
            $('#fragPFSection').hide(); // sem provably fair em cristais
            $('#fragForjarBtn').show().removeAttr('disabled')
                .text('💎 Combinar 5 Fragmentos → Cristal Completo')
                .css({ 'border-color':'#cf6ecf', color:'#cf6ecf' });
        } else {
            // Equipamento: fluxo padrão com provably fair 20%
            $('#fragSelNome').text('Fragmento de ' + f.nome).css('color', '#FFD700');
            var restantes = f.quantidade - precisa;
            $('#fragSelQty').text('Você tem ' + f.quantidade + ' fragmentos (restarão ' + (restantes < 0 ? 0 : restantes) + ' após a tentativa)');
            $('#fragmentSelected').css({ background: '#1a1a00', 'border-color': '#8B6914' });
            $('#fragPFSection').show();
            $('#fragForjarBtn').show().removeAttr('disabled')
                .text('🔥 Forjar (5 Fragmentos)')
                .css({ 'border-color':'#8B6914', color:'#FFD700' });
            loadFragServerSeedHash();
        }

        $('#fragmentSelected').show();
        $('#fragResult').hide();
        $('#fragPFResult').hide();
    }
    
    // ===== ANIMAÇÃO DA FORJA DE FRAGMENTOS =====

    var fragCycleInterval = null;
    var fragDoujutsus = [
        '_img/Forja/byakugan.png',
        '_img/Forja/rinnegan.png',
        '_img/Forja/sharigan.png'
    ];

    function stopFragCycle() {
        if(fragCycleInterval) {
            clearInterval(fragCycleInterval);
            fragCycleInterval = null;
        }
    }

    function startFragForgeAnimation(itemNome) {
        stopFragCycle();

        // 3 orbit images, each starting with a different doujutsu, spaced 120° apart (2s total, delay staggered)
        var orbitHtml = '';
        for(var i = 0; i < 3; i++) {
            var delay = (i * 5 / 3).toFixed(2) + 's';
            orbitHtml += '<img class="frag-orbit-img" data-cidx="' + i + '" src="' + fragDoujutsus[i] + '"' +
                         ' style="animation-duration:5s;animation-delay:' + delay + ';" />';
        }

        $('#fragLoadingContent').html(
            '<div class="frag-forge-anim">' +
                '<div class="frag-forge-circle">' +
                    '<div class="frag-forge-ring"></div>' +
                    orbitHtml +
                    '<img class="frag-forge-center-img" src="_img/Forja/doujutsu.png" />' +
                '</div>' +
                '<div class="frag-forge-title">Forjando fragmentos...</div>' +
                '<div class="frag-forge-subtitle">' + $('<div>').text(itemNome).html() + ' — 20% de chance</div>' +
            '</div>'
        );
        $('#fragLoadingOverlay').addClass('show');

        // Cycle each orbit image through byakugan → rinnegan → sharigan every 2000ms
        var cycleStep = 0;
        fragCycleInterval = setInterval(function() {
            cycleStep++;
            $('.frag-orbit-img').each(function() {
                var baseIdx = parseInt($(this).attr('data-cidx'), 10);
                var newIdx  = (baseIdx + cycleStep) % fragDoujutsus.length;
                $(this).attr('src', fragDoujutsus[newIdx]);
            });
        }, 2000);
    }

    function showFragForgeResult(success, itemNome, itemImg, pfData, onDone) {
        stopFragCycle();

        var color    = success ? '#FFD700' : '#e74c3c';
        var titleTxt = success ? 'FORJA BEM-SUCEDIDA!' : 'FORJA FALHOU!';
        var subTxt   = success
            ? ($('<div>').text(itemNome).html() + ' foi adicionado ao inventário!')
            : 'Os 5 fragmentos foram destruídos.';

        var raysHtml = '';
        if(success) {
            for(var i = 0; i < 8; i++) {
                raysHtml += '<div class="frag-result-ray" style="background:' + color + ';--angle:' + (i * 45) + 'deg;"></div>';
            }
        }

        // Centro: imagem do item (sucesso) ou ícone de falha
        var centerHtml;
        if(success && itemImg) {
            centerHtml = '<img src="' + itemImg + '" style="' +
                'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);' +
                'width:80px;height:80px;object-fit:contain;' +
                'filter:drop-shadow(0 0 18px #FFD700) drop-shadow(0 0 35px rgba(255,215,0,0.6));' +
                'animation:fragIconPop 0.5s ease-out;border-radius:4px;" />';
        } else {
            centerHtml = '<div class="frag-result-icon" style="color:' + color + ';">💔</div>';
        }

        $('#fragLoadingContent').html(
            '<div class="frag-forge-result">' +
                '<div class="frag-result-burst" id="fragBurst">' +
                    raysHtml +
                    centerHtml +
                '</div>' +
                '<div class="frag-result-text" style="color:' + color + ';">' + titleTxt + '</div>' +
                '<div class="frag-result-item">' + subTxt + '</div>' +
                '<button class="frag-result-close" id="fragResultCloseBtn">✔ OK</button>' +
            '</div>'
        );

        // Partículas douradas no sucesso
        if(success) {
            for(var j = 0; j < 16; j++) {
                (function() {
                    var tx  = (Math.random() * 280 - 140) + 'px';
                    var ty  = (Math.random() * 240 - 170) + 'px';
                    var del = (Math.random() * 0.9).toFixed(2);
                    var p   = $('<div class="frag-gold-particle" style="--tx:' + tx + ';--ty:' + ty + ';animation-delay:' + del + 's;">★</div>');
                    $('#fragBurst').append(p);
                })();
            }
        }

        var closed = false;
        function closeOverlay() {
            if(closed) return;
            closed = true;
            $('#fragLoadingOverlay').removeClass('show');
            if(onDone) onDone(pfData);
        }

        $('#fragResultCloseBtn').bind('click', closeOverlay);
        // Sem auto-close — jogador deve clicar OK
    }

    function performFragmentCombine() {
        if(!selectedFragment) return;
        var isCrystal  = (selectedFragment.tipo === 'crystal');
        var clientSeed = $('#fragClientSeed').val().trim();
        if(!clientSeed) clientSeed = generateRandomSeed();

        $('#fragForjarBtn').attr('disabled', 'disabled').text(isCrystal ? 'Combinando...' : 'Forjando...');
        $('#fragResult').hide();
        $('#fragPFResult').hide();

        var itemNome = selectedFragment.nome || 'Item';
        var rawImg   = selectedFragment.imagem || '';
        // Ensure valid path
        if(rawImg && rawImg.indexOf('.') === -1) rawImg += '.png';
        var imgBase  = selectedFragment.img_base || '_img/equipamentos/';
        var itemImg  = rawImg ? imgBase + rawImg : '';

        startFragForgeAnimation(itemNome);

        $.ajax({
            url: '_inc/ajax_blacksmith.php',
            type: 'POST',
            data: {
                action: 'combine_fragments',
                item_id: selectedFragment.itemid,
                tipo: isCrystal ? 'crystal' : 'equipment',
                client_seed: clientSeed
            },
            dataType: 'json',
            success: function(r) {
                var pf = r.provably_fair || {};

                // Aguarda animação antes de mostrar o resultado
                setTimeout(function() {
                    showFragForgeResult(!!r.success, itemNome, itemImg, pf, function(pfData) {
                        // Após fechar o overlay, mostra resultado inline e dados PF
                        if(r.success && isCrystal) {
                            // Cristal: combinação garantida
                            $('#fragResult').css({ background: '#2a0a2a', border: '1px solid #cf6ecf', color: '#cf6ecf', borderRadius: '5px' })
                                .html('<div style="font-size:28px;">💎</div><b>Cristal formado!</b><br/>' + r.message).show();
                        } else if(r.success) {
                            $('#fragResult').css({ background: '#1a3d1a', border: '1px solid #2ecc71', color: '#2ecc71', borderRadius: '5px' })
                                .html('<div style="font-size:28px;">✅</div><b>Forja bem-sucedida!</b><br/>' + r.message).show();
                            $('#fragResultTitle').text('SUCESSO').removeClass('failed').addClass('success');
                        } else if(r.failed) {
                            $('#fragResult').css({ background: '#3d1a1a', border: '1px solid #e74c3c', color: '#e74c3c', borderRadius: '5px' })
                                .html('<div style="font-size:28px;">❌</div><b>Forja falhou!</b><br/>' + r.message).show();
                            $('#fragResultTitle').text('FALHOU').removeClass('success').addClass('failed');
                        } else {
                            $('#fragResult').css({ background: '#3d3d1a', border: '1px solid #f1c40f', color: '#f1c40f', borderRadius: '5px' })
                                .html(r.message || 'Erro desconhecido').show();
                        }

                        // Provably Fair só aplica a equipamentos
                        if(!isCrystal && pfData && pfData.server_seed) {
                            var numClass = r.success ? 'success' : 'failed';
                            $('#fragResultServerSeed').text(pfData.server_seed);
                            $('#fragResultClientSeed').text(pfData.client_seed);
                            $('#fragResultNonce').text(pfData.nonce);
                            $('#fragResultHash').text(pfData.hash);
                            $('#fragResultNumber').text(pfData.number).removeClass('success failed').addClass(numClass);
                            $('#fragResultFinal').text(pfData.result);
                            $('#fragPFResult').show();
                        }

                        selectedFragment = null;
                        $('#fragmentSelected').hide();
                        $('#fragPFSection').hide();
                        $('#fragForjarBtn').hide().attr('disabled', 'disabled').text('🔥 Forjar (5 Fragmentos)');
                        loadFragments();
                    });
                }, isCrystal ? 2000 : 5000);
            },
            error: function() {
                $('#fragLoadingOverlay').removeClass('show');
                $('#fragResult').css({ background: '#3d1a1a', border: '1px solid #e74c3c', color: '#e74c3c', borderRadius: '5px' })
                    .html('Erro de comunicação. Tente novamente.').show();
                $('#fragForjarBtn').removeAttr('disabled').text('🔥 Forjar (5 Fragmentos)');
            }
        });
    }
</script>
