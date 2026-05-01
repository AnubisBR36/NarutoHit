<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Admin' : 'Painel Admin'; ?></title>
    <link rel="stylesheet" href="../_css/naruto.css">
    <style>
        body {
            margin: 0; padding: 0;
            background: url('../_img/background.jpg') repeat center top #1a1a1a;
            font-family: Arial, Verdana, sans-serif;
            font-size: 12px;
            color: #BBBBBB;
        }
        .adm-nav {
            background: url('../_img/menu.jpg') repeat-x;
            padding: 5px 10px;
            border-bottom: 1px solid #ff6600;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .adm-nav a {
            color: #FFD700;
            text-decoration: none;
            font-size: 11px;
            padding: 3px 8px;
            border: 1px solid #555;
            background: rgba(0,0,0,0.4);
        }
        .adm-nav a:hover { border-color: #ff6600; color: #ff6600; }
        .adm-nav .breadcrumb { color: #888; font-size: 11px; margin-right: 5px; }
        .adm-box3-top {
            background: #1a0a00;
            border-left: 5px solid #ff6600;
            border-bottom: 1px solid #ff6600;
            height: 35px; line-height: 35px;
            padding-left: 12px;
            font-weight: bold;
            color: #FFD700;
            font-size: 13px;
        }
        .box_top, .box2_top {
            background: #1a0a00 !important;
            border-left: 4px solid #ff6600 !important;
            border-bottom: 1px solid #444 !important;
            height: auto !important; line-height: normal !important;
            padding: 7px 10px 7px 12px !important;
            font-weight: bold;
            color: #FFD700 !important;
            font-size: 13px;
        }
        .box_middle, .box2_middle {
            background: #111111 !important;
            border-left: 1px solid #333 !important;
            border-right: 1px solid #333 !important;
            padding: 10px 12px !important;
            min-height: 20px;
        }
        .box_bottom, .box2_bottom {
            background: #1a0a00 !important;
            border-left: 1px solid #333 !important;
            border-right: 1px solid #333 !important;
            border-bottom: 2px solid #ff6600 !important;
            height: 8px !important;
        }
        .adm-page-title {
            background: #1a0a00;
            border-left: 4px solid #ff6600;
            padding: 6px 12px;
            color: #FFD700;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .alert-success {
            background: #0a2a0a url('../_img/alert.png') no-repeat 5px center;
            border: 1px dotted #4CAF50;
            color: #90EE90;
            padding: 6px 6px 6px 28px;
            margin: 6px 0;
        }
        .alert-error {
            background: #2a0a0a url('../_img/alert.png') no-repeat 5px center;
            border: 1px dotted #ff4444;
            color: #FFAAAA;
            padding: 6px 6px 6px 28px;
            margin: 6px 0;
        }
        .alert-warning {
            background: #2a1a00 url('../_img/alert.png') no-repeat 5px center;
            border: 1px dotted #ff6600;
            color: #FFD700;
            padding: 6px 6px 6px 28px;
            margin: 6px 0;
        }
        fieldset {
            border: 1px solid #ff6600;
            background: #1a1200;
            padding: 10px;
            margin: 8px 0;
        }
        legend {
            color: #FFD700;
            font-weight: bold;
            padding: 0 6px;
            font-size: 12px;
        }
        input[type=text], input[type=number], input[type=email], input[type=password],
        select, textarea {
            background: #2a2200;
            border: 1px solid #555;
            color: #FFFFFF;
            padding: 4px 6px;
            font-size: 12px;
            font-family: Arial, sans-serif;
        }
        input[type=text]:focus, input[type=number]:focus, select:focus, textarea:focus {
            border-color: #ff6600;
            outline: none;
        }
        label { color: #BBBBBB; }
        h2, h3 { color: #ff6600; margin: 8px 0 5px 0; font-size: 14px; }
        h2 { font-size: 16px; }
        table.adm-table { width: 100%; border-collapse: collapse; }
        table.adm-table th {
            background: url('../_img/menu.jpg') repeat-x;
            color: #FFD700;
            padding: 5px 8px;
            font-size: 11px;
            border-bottom: 1px solid #ff6600;
            text-align: left;
        }
        table.adm-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #333;
            color: #BBBBBB;
            font-size: 11px;
            vertical-align: middle;
        }
        table.adm-table tr:hover td { background: #2a2200; }
        .btn-danger {
            background: url('../_img/fundo_botao.jpg') left;
            border: 1px solid #cc0000;
            color: #FFFFFF;
            cursor: pointer;
            font-size: 12px;
            padding: 4px 10px;
        }
        .btn-danger:hover { border-color: #ff4444; }
        .btn-success {
            background: url('../_img/fundo_botao.jpg') left;
            border: 1px solid #4CAF50;
            color: #FFFFFF;
            cursor: pointer;
            font-size: 12px;
            padding: 4px 10px;
        }
        .btn-success:hover { border-color: #66FF66; }
        .stat-number { font-size: 24px; font-weight: bold; color: #ff6600; }
        .stats-row { display: flex; gap: 10px; flex-wrap: wrap; margin: 8px 0; }
        .stat-box {
            background: #1a1200;
            border: 1px solid #555;
            padding: 10px 15px;
            text-align: center;
            flex: 1;
            min-width: 100px;
        }
        .stat-box div:last-child { color: #888; font-size: 10px; margin-top: 3px; }
    </style>
</head>
<body>
<div align="center">
<table align="center" cellpadding="0" cellspacing="0" width="760">
    <tr>
        <td width="20" rowspan="6" style="background:url('../_img/border_left.jpg') repeat-y right;">&nbsp;</td>
        <td height="130" valign="bottom" style="background:url('../_img/logo2.jpg') no-repeat center;">&nbsp;</td>
        <td width="20" rowspan="6" style="background:url('../_img/border_right.jpg') repeat-y;">&nbsp;</td>
    </tr>
    <tr>
        <td valign="top" style="background:url('../_img/border_top.jpg') repeat-x top; height:8px;">&nbsp;</td>
    </tr>
    <tr>
        <td valign="top" bgcolor="#444444">
            <div class="adm-box3-top">⚙️ Painel Administrativo<?php if(isset($page_title)): ?> &rsaquo; <?php echo htmlspecialchars($page_title); ?><?php endif; ?></div>
            <div class="adm-nav">
                <span class="breadcrumb">Navegação:</span>
                <a href="adm.php">🏠 Painel</a>
                <a href="adm.php?modulo=equipamentos">⚔️ Equipamentos</a>
                <a href="adm.php?modulo=clas">🏯 Clãs</a>
                <a href="adm.php?modulo=invasao_completa">⚡ Invasão</a>
                <a href="adm.php?modulo=cristais">💎 Cristais</a>
                <a href="adm.php?modulo=manutencao">🔧 Manutenção</a>
                <a href="adm.php?modulo=limpar_ip">🔓 Limpar IPs</a>
            </div>
            <div style="background:#111111; padding:8px 12px; min-height:300px; border-left:1px solid #333; border-right:1px solid #333;">
