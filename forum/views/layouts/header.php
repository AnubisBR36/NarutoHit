<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fórum - <?php echo nome_servidor(); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: url('/_img/background.jpg') repeat;
            color: #BBBBBB;
            font-family: Arial, sans-serif;
        }
        
        .forum-container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            padding: 10px;
            min-height: 100vh;
        }
        
        .box_top {
            background: linear-gradient(to bottom, #3a3a3a 0%, #2a2a2a 50%, #1a1a1a 100%);
            border: 2px solid #ff6600;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            height: 35px;
            padding-left: 15px;
            line-height: 32px;
            font-weight: bold;
            color: #ff6600;
            font-size: 14px;
            text-shadow: 1px 1px 2px #000;
        }
        
        .box_middle {
            background: linear-gradient(to bottom, #252525 0%, #1a1a1a 100%);
            border-left: 2px solid #ff6600;
            border-right: 2px solid #ff6600;
            padding: 15px;
        }
        
        .box_bottom {
            background: linear-gradient(to bottom, #1a1a1a 0%, #0a0a0a 100%);
            border: 2px solid #ff6600;
            border-top: none;
            border-radius: 0 0 8px 8px;
            height: 12px;
        }
        
        .box2_top {
            background: linear-gradient(to bottom, #2d2d2d 0%, #1d1d1d 50%, #151515 100%);
            border: 2px solid #cc0000;
            border-bottom: none;
            border-radius: 6px 6px 0 0;
            height: 32px;
            padding-left: 15px;
            line-height: 28px;
            font-weight: bold;
            color: #ff6600;
            font-size: 13px;
            text-shadow: 1px 1px 2px #000;
        }
        
        .box2_middle {
            background: linear-gradient(to right, #1a1a1a 0%, #222222 50%, #1a1a1a 100%);
            border-left: 2px solid #cc0000;
            border-right: 2px solid #cc0000;
            padding: 12px 15px;
        }
        
        .box2_bottom {
            background: linear-gradient(to bottom, #151515 0%, #0a0a0a 100%);
            border: 2px solid #cc0000;
            border-top: none;
            border-radius: 0 0 6px 6px;
            height: 10px;
        }
        
        .forum-content {
            padding: 10px 0;
        }
        
        .forum-search {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .forum-search input[type="text"] {
            flex: 1;
            padding: 8px;
            border: 1px solid #444;
            background: #1a1a1a;
            color: #fff;
        }
        
        .forum-search button {
            padding: 8px 20px;
            background: url('/_img/fundo_botao.jpg') repeat-x center;
            border: 1px solid #555;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
        }
        
        .forum-categoria {
            margin-bottom: 0;
        }
        
        .forum-categoria-header {
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .forum-categoria-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid #ff0000;
            box-shadow: 0 0 0 2px #000;
        }
        
        .forum-categoria-info {
            flex: 1;
        }
        
        .forum-categoria-nome {
            font-size: 16px;
            font-weight: bold;
            color: #ff6600;
            margin-bottom: 3px;
        }
        
        .forum-categoria-desc {
            color: #aaa;
            font-size: 12px;
        }
        
        .forum-categoria-stats {
            text-align: right;
            color: #888;
            font-size: 11px;
        }
        
        .forum-topico {
            background: #1a1a1a;
            padding: 10px 15px;
            margin-bottom: 5px;
            border: 2px solid #ff6600;
            transition: all 0.2s;
        }
        
        .forum-topico.lido {
            border-color: #cc0000;
        }
        
        .forum-topico-fixado {
            border-color: #ffd700 !important;
            background: #2d2d1a;
        }
        
        .forum-topico-titulo {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 3px;
        }
        
        .forum-topico-info {
            font-size: 11px;
            color: #888;
        }
        
        .forum-postagem {
            background: #1a1a1a;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            gap: 15px;
            border: 2px solid #ff6600;
        }
        
        .forum-postagem.lido {
            border-color: #cc0000;
        }
        
        .forum-postagem-autor {
            width: 120px;
            text-align: center;
            border-right: 1px solid #444;
            padding-right: 15px;
        }
        
        .forum-postagem-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 8px;
            border: 3px solid #ff6600;
        }
        
        .forum-postagem-conteudo {
            flex: 1;
        }
        
        .forum-btn {
            padding: 8px 15px;
            background: url('/_img/fundo_botao.jpg') repeat-x center;
            border: 1px solid #555;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            font-weight: bold;
        }
        
        .forum-btn:hover {
            filter: brightness(1.2);
            border-color: #ff6600;
        }
        
        .forum-btn-secondary {
            background: url('/_img/fundo_botao.jpg') repeat-x center;
            filter: brightness(0.7);
        }
        
        .forum-btn-secondary:hover {
            filter: brightness(1);
        }
        
        .forum-reacao-btn {
            background: none;
            border: 1px solid #ff6600;
            color: #ff6600;
            padding: 5px 10px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .forum-reacao-btn:hover {
            background: #ff6600;
            color: #fff;
            transform: scale(1.1);
        }
        
        .forum-reacao-btn.ativo {
            background: #ff6600;
            color: #fff;
            font-weight: bold;
        }
        
        .forum-form textarea {
            width: 100%;
            min-height: 150px;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #444;
            color: #fff;
            resize: vertical;
        }
        
        .forum-form input[type="text"] {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #444;
            color: #fff;
        }
        
        .forum-paginacao {
            text-align: center;
            padding: 15px;
        }
        
        .forum-paginacao a {
            padding: 6px 10px;
            background: #444;
            color: #fff;
            text-decoration: none;
            margin: 0 3px;
        }
        
        .forum-paginacao a.ativo {
            background: #ff6600;
        }
        
        .forum-user-vila {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }
        
        .forum-user-vila-icon {
            width: 18px;
            height: 18px;
            vertical-align: middle;
            border-radius: 50%;
            margin-right: 3px;
        }
        
        .player-info-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .player-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid #ff0000;
            box-shadow: 0 0 0 2px #000;
        }
        
        .player-details {
            flex: 1;
        }
        
        .player-name {
            font-size: 18px;
            font-weight: bold;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #ff0000, #cc0000);
            color: #fff;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #000;
        }
        
        .player-vila {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }
        
        .player-vila img {
            width: 28px;
            height: 28px;
            border-radius: 4px;
        }
        
        .sep {
            background: url('/_img/sep.jpg') repeat-x;
            height: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="forum-container">
    <div class="forum-content">
