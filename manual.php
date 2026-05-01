<?php
session_start();
include("_inc/conexao.php");
include("_inc/funcoes.php");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Completo :: <?php echo nome_servidor(); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #ff6b35;
            --secondary: #004643;
            --accent: #f9bc60;
            --background: #0d1117;
            --surface: #161b22;
            --surface-light: #21262d;
            --text-primary: #ffffff;
            --text-secondary: #8b949e;
            --text-muted: #6e7681;
            --border: #30363d;
            --shadow: rgba(0, 0, 0, 0.3);
            --gradient: linear-gradient(135deg, var(--primary), var(--accent));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(600px circle at 0% 0%, rgba(255, 107, 53, 0.1), transparent 40%),
                radial-gradient(600px circle at 100% 100%, rgba(249, 188, 96, 0.1), transparent 40%);
            z-index: -1;
        }

        /* Header */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .logo img {
            height: 40px;
            width: auto;
            border-radius: 8px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .breadcrumb a:hover {
            color: var(--primary);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .hero-image {
            width: 120px;
            height: auto;
            border-radius: 12px;
            border: 3px solid var(--primary);
            margin: 0 1rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* Navigation */
        .nav-container {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .nav-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .nav-item {
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-item:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px var(--shadow);
        }

        .nav-item i {
            font-size: 1.5rem;
            width: 24px;
            text-align: center;
        }

        /* Content Sections */
        .section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .section-header i {
            background: var(--gradient);
            padding: 0.5rem;
            border-radius: 8px;
            color: white;
            font-size: 1.2rem;
        }

        .section-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .section-content {
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .section-content p {
            margin-bottom: 1rem;
        }

        .section-content strong {
            color: var(--text-primary);
        }

        /* Images */
        .manual-image {
            max-width: 120px;
            height: auto;
            border-radius: 8px;
            border: 2px solid var(--border);
            transition: all 0.3s ease;
        }

        .manual-image:hover {
            transform: scale(1.05);
            border-color: var(--primary);
        }

        .manual-image-large {
            max-width: 250px;
            height: auto;
            border-radius: 12px;
            border: 2px solid var(--border);
            margin: 1rem 0;
        }

        .manual-image-small {
            max-width: 80px;
            height: auto;
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        /* Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .card {
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .card-icon {
            background: var(--gradient);
            padding: 0.5rem;
            border-radius: 6px;
            color: white;
        }

        .card h3 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .card img {
            width: 100%;
            max-width: 100px;
            height: auto;
            border-radius: 6px;
            margin-top: 1rem;
        }

        /* Vila Cards */
        .vila-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .vila-card {
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .vila-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow);
        }

        .vila-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            margin-bottom: 0.5rem;
        }

        /* Character Grid */
        .character-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .character-item {
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .character-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow);
        }

        .character-item img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            margin-bottom: 0.5rem;
        }

        /* Tables */
        .table-container {
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            margin: 2rem 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--surface);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
            vertical-align: middle;
        }

        .table tr:hover {
            background: rgba(255, 107, 53, 0.05);
        }

        .table img {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            margin-right: 0.5rem;
            vertical-align: middle;
        }

        /* Info Boxes */
        .info-box {
            background: rgba(249, 188, 96, 0.1);
            border: 1px solid var(--accent);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            position: relative;
        }

        .info-box::before {
            content: '\f05a';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            color: var(--accent);
            font-size: 1.2rem;
        }

        .warning-box {
            background: rgba(255, 107, 53, 0.1);
            border-color: var(--primary);
        }

        .warning-box::before {
            content: '\f071';
            color: var(--primary);
        }

        /* Lists */
        .feature-list {
            list-style: none;
            margin: 1.5rem 0;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list li::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: var(--primary);
            background: rgba(255, 107, 53, 0.1);
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        /* Element Icons */
        .element-icons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .element-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--border);
            transition: all 0.3s ease;
        }

        .element-icon:hover {
            transform: scale(1.1);
            border-color: var(--primary);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 3rem 0;
            border-top: 1px solid var(--border);
            margin-top: 4rem;
        }

        .home-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gradient);
            color: white;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .home-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow);
        }

        /* Image Gallery */
        .image-gallery {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero-image {
                width: 80px;
            }

            .nav-grid {
                grid-template-columns: 1fr;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

            .character-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }

            .vila-cards-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .header-content {
                padding: 0 1rem;
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="_img/Manual/logo_manual.jpg" alt="Manual <?php echo nome_servidor(); ?>">
                Manual <?php echo nome_servidor(); ?>
            </div>
            <nav class="breadcrumb">
                <a href="index.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <span>Manual</span>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1>Manual Completo do <?php echo nome_servidor(); ?></h1>
            <p>Domine todos os aspectos do mundo ninja e torne-se uma lenda no universo de Naruto</p>
            <div class="image-gallery">
                <img src="_img/Manual/naruto_exemplo.jpg" alt="Naruto" class="hero-image">
                <img src="_img/Manual/sasuke_exemplo.jpg" alt="Sasuke" class="hero-image">
                <img src="_img/Manual/sakura_exemplo.jpg" alt="Sakura" class="hero-image">
                <img src="_img/Manual/kakashi_exemplo.jpg" alt="Kakashi" class="hero-image">
            </div>
        </section>

        <!-- Navigation -->
        <section class="nav-container">
            <div class="nav-title">
                <i class="fas fa-compass"></i>
                Navegação Rápida
            </div>
            <div class="nav-grid">
                <a href="#introducao" class="nav-item">
                    <i class="fas fa-play-circle"></i>
                    <span>Introdução ao Jogo</span>
                </a>
                <a href="#personagens" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Personagens Disponíveis</span>
                </a>
                <a href="#vilas" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Vilas Ninjas</span>
                </a>
                <a href="#atributos" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Sistema de Atributos</span>
                </a>
                <a href="#treino" class="nav-item">
                    <i class="fas fa-dumbbell"></i>
                    <span>Treinamentos</span>
                </a>
                <a href="#jutsus" class="nav-item">
                    <i class="fas fa-fire"></i>
                    <span>Jutsus e Elementos</span>
                </a>
                <a href="#equipamentos" class="nav-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Equipamentos</span>
                </a>
                <a href="#missoes" class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Sistema de Missões</span>
                </a>
                <a href="#combate" class="nav-item">
                    <i class="fas fa-fist-raised"></i>
                    <span>Sistema de Combate</span>
                </a>
                <a href="#doujutsus" class="nav-item">
                    <i class="fas fa-eye"></i>
                    <span>Doujutsus</span>
                </a>
                <a href="#economia" class="nav-item">
                    <i class="fas fa-coins"></i>
                    <span>Sistema Econômico</span>
                </a>
                <a href="#dicas" class="nav-item">
                    <i class="fas fa-lightbulb"></i>
                    <span>Dicas Avançadas</span>
                </a>
            </div>
        </section>

        <!-- Introdução -->
        <section id="introducao" class="section">
            <div class="section-header">
                <i class="fas fa-play-circle"></i>
                <h2>Introdução ao <?php echo nome_servidor(); ?></h2>
            </div>
            <div class="section-content">
                <p>O <strong><?php echo nome_servidor(); ?></strong> é um RPG online baseado no universo de Naruto, onde você assume o papel de um ninja em formação. O jogo oferece uma experiência completa de imersão no mundo ninja, com sistemas complexos de desenvolvimento de personagem, combate estratégico e economia player-driven.</p>
                
                <div class="info-box">
                    <strong>Bem-vindo ao Mundo Ninja!</strong> Este é um jogo de estratégia e paciência. O desenvolvimento do seu ninja acontece ao longo do tempo, mesmo quando você está offline.
                </div>

                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3>Criação de Personagem</h3>
                        </div>
                        <p>Escolha entre mais de 20 personagens únicos do anime, cada um com habilidades especiais, histórias próprias e caminhos de desenvolvimento específicos.</p>
                        <img src="_img/Manual/naruto_exemplo.jpg" alt="Exemplo Naruto" class="manual-image">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <h3>Mundo Persistente</h3>
                        </div>
                        <p>Explore um mundo que continua evoluindo mesmo quando você não está jogando. Missões progridem, economia flutua e eventos especiais acontecem.</p>
                        <img src="_img/Manual/mapa_cidade.jpg" alt="Mapa da Cidade" class="manual-image">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h3>Progressão Realista</h3>
                        </div>
                        <p>Avance através dos ranks ninjas: Genin, Chuunin, Jounin e potencialmente Kage, cada um desbloqueando novas possibilidades.</p>
                        <img src="_img/Manual/rank_genin.png" alt="Rank Genin" class="manual-image">
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Características Principais:</h3>
                <ul class="feature-list">
                    <li><strong>Sistema de Tempo Real:</strong> O jogo continua progredindo mesmo offline</li>
                    <li><strong>Economia Player-Driven:</strong> Preços flutuam baseado na oferta e demanda dos jogadores</li>
                    <li><strong>PvP Estratégico:</strong> Combates baseados em estratégia, não apenas poder bruto</li>
                    <li><strong>Organizações:</strong> Forme ou junte-se a grupos de ninjas para missões especiais</li>
                    <li><strong>Eventos Sazonais:</strong> Participe de invasões e eventos especiais do universo Naruto</li>
                </ul>
            </div>
        </section>

        <!-- Personagens -->
        <section id="personagens" class="section">
            <div class="section-header">
                <i class="fas fa-users"></i>
                <h2>Personagens Disponíveis</h2>
            </div>
            <div class="section-content">
                <p>No <?php echo nome_servidor(); ?>, você pode escolher entre uma vasta gama de personagens do universo Naruto. Cada personagem possui características únicas, habilidades especiais e diferentes potenciais de crescimento. A escolha do seu personagem influenciará diretamente seu estilo de jogo e estratégias.</p>
                
                <div class="warning-box">
                    <strong>Escolha Importante:</strong> A seleção do personagem é permanente e não pode ser alterada depois. Considere cuidadosamente as características de cada um.
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Personagens Iniciais (Disponíveis no Registro):</h3>
                <div class="character-grid">
                    <div class="character-item">
                        <img src="_img/Manual/naruto_exemplo.jpg" alt="Naruto">
                        <h4>Naruto Uzumaki</h4>
                        <p><strong>Especialidade:</strong> Ninjutsu e Resistência</p>
                        <p><small>Alto chakra, excelente para jutsus poderosos</small></p>
                    </div>
                    <div class="character-item">
                        <img src="_img/Manual/sasuke_exemplo.jpg" alt="Sasuke">
                        <h4>Sasuke Uchiha</h4>
                        <p><strong>Especialidade:</strong> Agilidade e Precisão</p>
                        <p><small>Potencial para despertar o Sharingan</small></p>
                    </div>
                    <div class="character-item">
                        <img src="_img/Manual/sakura_exemplo.jpg" alt="Sakura">
                        <h4>Sakura Haruno</h4>
                        <p><strong>Especialidade:</strong> Força e Cura</p>
                        <p><small>Força física excepcional e habilidades médicas</small></p>
                    </div>
                    <div class="character-item">
                        <img src="_img/Manual/kakashi_exemplo.jpg" alt="Kakashi">
                        <h4>Kakashi Hatake</h4>
                        <p><strong>Especialidade:</strong> Versatilidade</p>
                        <p><small>Copy Ninja - pode aprender diversos jutsus</small></p>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Personagens Desbloqueáveis:</h3>
                <div class="character-grid">
                    <div class="character-item">
                        <img src="_img/Manual/hinata_exemplo.jpg" alt="Hinata">
                        <h4>Hinata Hyuuga</h4>
                        <p><strong>Especialidade:</strong> Byakugan e Defesa</p>
                        <p><small>Visão de 360° e técnicas do Punho Suave</small></p>
                    </div>
                    <div class="character-item">
                        <img src="_img/Manual/shikamaru_exemplo.jpg" alt="Shikamaru">
                        <h4>Shikamaru Nara</h4>
                        <p><strong>Especialidade:</strong> Estratégia e Controle</p>
                        <p><small>Técnicas de manipulação de sombras</small></p>
                    </div>
                    <div class="character-item">
                        <img src="_img/Manual/gaara_exemplo.jpg" alt="Gaara">
                        <h4>Gaara do Deserto</h4>
                        <p><strong>Especialidade:</strong> Defesa Absoluta</p>
                        <p><small>Controle total sobre a areia</small></p>
                    </div>
                    <div class="character-item">
                        <img src="_img/Manual/lee_exemplo.jpg" alt="Lee">
                        <h4>Rock Lee</h4>
                        <p><strong>Especialidade:</strong> Taijutsu Puro</p>
                        <p><small>Mestre das artes marciais ninja</small></p>
                    </div>
                </div>

                <div class="info-box">
                    <strong>Sistema de Desbloqueio:</strong> Personagens especiais podem ser desbloqueados através de conquistas específicas, eventos especiais ou progressão no jogo. Alguns exigem itens raros ou cumprimento de missões específicas.
                </div>
            </div>
        </section>

        <!-- Vilas -->
        <section id="vilas" class="section">
            <div class="section-header">
                <i class="fas fa-home"></i>
                <h2>Vilas Ninjas</h2>
            </div>
            <div class="section-content">
                <p>As vilas são o coração da experiência ninja. Cada vila possui características únicas, especializações, vantagens estratégicas e culturas distintas. Sua escolha de vila influenciará drasticamente sua jornada ninja, desde os jutsus disponíveis até as oportunidades de missões.</p>
                
                <div class="info-box">
                    <strong>Impacto da Escolha:</strong> Sua vila determina seus aliados naturais, inimigos potenciais, acesso a certos jutsus e tipos de missões disponíveis.
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Vilas Principais:</h3>
                <div class="vila-cards-grid">
                    <div class="vila-card">
                        <img src="_img/Manual/vila_folha.jpg" alt="Vila da Folha">
                        <h4>Vila da Folha (Konoha)</h4>
                        <p><strong>Especialidade:</strong> Desenvolvimento Balanceado</p>
                        <p><small>• Maior variedade de jutsus<br>• Economia estável<br>• Alianças fortes</small></p>
                    </div>
                    <div class="vila-card">
                        <img src="_img/Manual/vila_areia.jpg" alt="Vila da Areia">
                        <h4>Vila da Areia (Suna)</h4>
                        <p><strong>Especialidade:</strong> Defesa e Controle</p>
                        <p><small>• Técnicas de areia únicas<br>• Defesas superiores<br>• Resistência ao clima</small></p>
                    </div>
                    <div class="vila-card">
                        <img src="_img/Manual/vila_nevoa.jpg" alt="Vila da Névoa">
                        <h4>Vila da Névoa (Kiri)</h4>
                        <p><strong>Especialidade:</strong> Assassinato Silencioso</p>
                        <p><small>• Técnicas furtivas<br>• Ataques críticos<br>• Especialistas em espadas</small></p>
                    </div>
                    <div class="vila-card">
                        <img src="_img/Manual/vila_nuvem.jpg" alt="Vila da Nuvem">
                        <h4>Vila da Nuvem (Kumo)</h4>
                        <p><strong>Especialidade:</strong> Técnicas de Raio</p>
                        <p><small>• Velocidade extrema<br>• Jutsus de raio<br>• Força física superior</small></p>
                    </div>
                    <div class="vila-card">
                        <img src="_img/Manual/vila_pedra.jpg" alt="Vila da Pedra">
                        <h4>Vila da Pedra (Iwa)</h4>
                        <p><strong>Especialidade:</strong> Técnicas de Terra</p>
                        <p><small>• Defesa rochosa<br>• Resistência extrema<br>• Jutsus de terra</small></p>
                    </div>
                    <div class="vila-card">
                        <img src="_img/Manual/vila_som.jpg" alt="Vila do Som">
                        <h4>Vila do Som (Oto)</h4>
                        <p><strong>Especialidade:</strong> Experimentos</p>
                        <p><small>• Técnicas experimentais<br>• Poder bruto<br>• Habilidades únicas</small></p>
                    </div>
                    <div class="vila-card">
                        <img src="_img/Manual/vila_chuva.jpg" alt="Vila da Chuva">
                        <h4>Vila da Chuva (Ame)</h4>
                        <p><strong>Especialidade:</strong> Técnicas Aquáticas</p>
                        <p><small>• Controle da chuva<br>• Detecção avançada<br>• Jutsus de água</small></p>
                    </div>
                    <div class="vila-card">
                        <img src="_img/Manual/akatsuki_folha.jpg" alt="Akatsuki">
                        <h4>Akatsuki</h4>
                        <p><strong>Especialidade:</strong> Poder Absoluto</p>
                        <p><small>• Jutsus proibidos<br>• Poder extremo<br>• Organização criminosa</small></p>
                    </div>
                </div>

                <div class="warning-box">
                    <strong>Atenção:</strong> Algumas vilas como Akatsuki possuem requisitos especiais para entrada e podem afetar suas relações com outras vilas.
                </div>
            </div>
        </section>

        <!-- Atributos -->
        <section id="atributos" class="section">
            <div class="section-header">
                <i class="fas fa-chart-bar"></i>
                <h2>Sistema de Atributos</h2>
            </div>
            <div class="section-content">
                <p>O sistema de atributos é o core do desenvolvimento do seu ninja. Cada atributo influencia diferentes aspectos do combate, exploração e interação social. O desenvolvimento balanceado versus especialização é uma das decisões estratégicas mais importantes do jogo.</p>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Atributo</th>
                                <th>Função Principal</th>
                                <th>Impacto no Combate</th>
                                <th>Desenvolvimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong style="color: var(--primary);">Força</strong></td>
                                <td>Dano físico e capacidade de carga</td>
                                <td>Determina dano de Taijutsu e armas</td>
                                <td>Treino de força e uso de equipamentos pesados</td>
                            </tr>
                            <tr>
                                <td><strong style="color: var(--primary);">Agilidade</strong></td>
                                <td>Velocidade de ataque e esquiva</td>
                                <td>Chance de esquiva e ordem de ação</td>
                                <td>Treino de velocidade e práticas de mobilidade</td>
                            </tr>
                            <tr>
                                <td><strong style="color: var(--primary);">Resistência</strong></td>
                                <td>Pontos de vida e defesa física</td>
                                <td>HP total e redução de dano físico</td>
                                <td>Treino de resistência e combates frequentes</td>
                            </tr>
                            <tr>
                                <td><strong style="color: var(--primary);">Chakra</strong></td>
                                <td>Energia espiritual para jutsus</td>
                                <td>MP total e poder dos jutsus</td>
                                <td>Meditação e uso frequente de técnicas</td>
                            </tr>
                            <tr>
                                <td><strong style="color: var(--primary);">Inteligência</strong></td>
                                <td>Aprendizado e estratégia</td>
                                <td>Eficiência dos jutsus e resistência mental</td>
                                <td>Estudo de pergaminhos e resolução de problemas</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Estratégias de Desenvolvimento:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fist-raised"></i>
                            </div>
                            <h3>Build Taijutsu</h3>
                        </div>
                        <p><strong>Foco:</strong> Força + Agilidade + Resistência</p>
                        <p>Especialista em combate corpo a corpo, alta durabilidade e dano físico consistente.</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h3>Build Ninjutsu</h3>
                        </div>
                        <p><strong>Foco:</strong> Chakra + Inteligência</p>
                        <p>Mestre dos jutsus elementais, capaz de causar dano massivo à distância.</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <h3>Build Balanceado</h3>
                        </div>
                        <p><strong>Foco:</strong> Desenvolvimento equilibrado</p>
                        <p>Versátil em todas as situações, mas sem especialização extrema.</p>
                    </div>
                </div>

                <div class="info-box">
                    <strong>Dica Avançada:</strong> Considere seu personagem escolhido ao planejar atributos. Alguns personagens têm afinidades naturais que tornam certas builds mais eficientes.
                </div>
            </div>
        </section>

        <!-- Treino -->
        <section id="treino" class="section">
            <div class="section-header">
                <i class="fas fa-dumbbell"></i>
                <h2>Sistema de Treinamentos</h2>
            </div>
            <div class="section-content">
                <p>O sistema de treino é o mecanismo principal de progressão no <?php echo nome_servidor(); ?>. Diferente de muitos jogos, o treino continua mesmo quando você está offline, tornando-o ideal para jogadores que não podem ficar online constantemente. Cada tipo de treino desenvolve aspectos específicos do seu ninja.</p>
                
                <div style="text-align: center; margin: 2rem 0;">
                    <img src="_img/Manual/treino_chakra.jpg" alt="Treino de Chakra" class="manual-image-large">
                </div>

                <div class="warning-box">
                    <strong>Gestão de Energia:</strong> O treino consome energia ao longo do tempo. Planeje bem seus treinos para maximizar a eficiência!
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Tipos de Treinamento:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-dumbbell"></i>
                            </div>
                            <h3>Treino de Força</h3>
                        </div>
                        <p><strong>Desenvolvimento:</strong> Aumenta poder físico e capacidade de carga</p>
                        <p><strong>Duração:</strong> 1-24 horas dependendo da intensidade</p>
                        <p><strong>Custo:</strong> Baixo em yens, alto em energia</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-wind"></i>
                            </div>
                            <h3>Treino de Agilidade</h3>
                        </div>
                        <p><strong>Desenvolvimento:</strong> Melhora velocidade e precisão</p>
                        <p><strong>Duração:</strong> 1-24 horas dependendo da intensidade</p>
                        <p><strong>Custo:</strong> Médio em yens, médio em energia</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Treino de Resistência</h3>
                        </div>
                        <p><strong>Desenvolvimento:</strong> Aumenta HP e defesa física</p>
                        <p><strong>Duração:</strong> 2-48 horas (treino mais longo)</p>
                        <p><strong>Custo:</strong> Baixo em yens, muito alto em energia</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h3>Treino de Chakra</h3>
                        </div>
                        <p><strong>Desenvolvimento:</strong> Expande reservas de energia espiritual</p>
                        <p><strong>Duração:</strong> 1-36 horas</p>
                        <p><strong>Custo:</strong> Alto em yens (pergaminhos), médio em energia</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <h3>Estudo de Pergaminhos</h3>
                        </div>
                        <p><strong>Desenvolvimento:</strong> Aumenta inteligência e conhecimento</p>
                        <p><strong>Duração:</strong> 30 minutos - 12 horas</p>
                        <p><strong>Custo:</strong> Muito alto em yens, baixo em energia</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h3>Treino de Doujutsu</h3>
                        </div>
                        <p><strong>Desenvolvimento:</strong> Desenvolve habilidades oculares especiais</p>
                        <p><strong>Duração:</strong> 6-72 horas (apenas para usuários específicos)</p>
                        <p><strong>Custo:</strong> Extremamente alto em recursos</p>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Estratégias de Otimização:</h3>
                <ul class="feature-list">
                    <li><strong>Treino Overnight:</strong> Configure treinos longos antes de dormir</li>
                    <li><strong>Ciclo de Energia:</strong> Alterne entre treinos de alta e baixa energia</li>
                    <li><strong>Planejamento Semanal:</strong> Use treinos curtos nos dias ativos, longos nos fins de semana</li>
                    <li><strong>Economia de Recursos:</strong> Invista em treinos baratos quando os yens estão escassos</li>
                    <li><strong>Especialização Temporária:</strong> Foque um atributo por vez para ganhos mais visíveis</li>
                </ul>
            </div>
        </section>

        <!-- Jutsus -->
        <section id="jutsus" class="section">
            <div class="section-header">
                <i class="fas fa-fire"></i>
                <h2>Jutsus e Sistema Elemental</h2>
            </div>
            <div class="section-content">
                <p>Os jutsus são a essência do poder ninja. O <?php echo nome_servidor(); ?> possui um sistema elemental complexo baseado no anime, onde diferentes elementos interagem entre si criando vantagens e desvantagens estratégicas. Dominar este sistema é fundamental para o combate avançado.</p>
                
                <div style="text-align: center; margin: 2rem 0;">
                    <img src="_img/Manual/jutsu_exemplo.jpg" alt="Exemplo de Jutsu" class="manual-image-large">
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Elementos Básicos:</h3>
                <div class="element-icons">
                    <img src="_img/Manual/elemento_fogo.png" alt="Fogo" class="element-icon">
                    <img src="_img/Manual/elemento_agua.png" alt="Água" class="element-icon">
                    <img src="_img/Manual/elemento_terra.png" alt="Terra" class="element-icon">
                    <img src="_img/Manual/elemento_raio.png" alt="Raio" class="element-icon">
                    <img src="_img/Manual/elemento_vento.png" alt="Vento" class="element-icon">
                </div>

                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h3>Katon (Fogo)</h3>
                        </div>
                        <p><strong>Forte contra:</strong> Vento</p>
                        <p><strong>Fraco contra:</strong> Água</p>
                        <p><strong>Características:</strong> Alto dano direto, efeitos de queimadura, ataques em área</p>
                        <img src="_img/Manual/elemento_fogo.png" alt="Fogo" class="manual-image-small">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-tint"></i>
                            </div>
                            <h3>Suiton (Água)</h3>
                        </div>
                        <p><strong>Forte contra:</strong> Fogo</p>
                        <p><strong>Fraco contra:</strong> Terra</p>
                        <p><strong>Características:</strong> Versatilidade, controle de campo, técnicas defensivas</p>
                        <img src="_img/Manual/elemento_agua.png" alt="Água" class="manual-image-small">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-mountain"></i>
                            </div>
                            <h3>Doton (Terra)</h3>
                        </div>
                        <p><strong>Forte contra:</strong> Água</p>
                        <p><strong>Fraco contra:</strong> Raio</p>
                        <p><strong>Características:</strong> Defesa superior, técnicas de contenção, durabilidade</p>
                        <img src="_img/Manual/elemento_terra.png" alt="Terra" class="manual-image-small">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <h3>Raiton (Raio)</h3>
                        </div>
                        <p><strong>Forte contra:</strong> Terra</p>
                        <p><strong>Fraco contra:</strong> Vento</p>
                        <p><strong>Características:</strong> Velocidade extrema, penetração de defesas, paralisia</p>
                        <img src="_img/Manual/elemento_raio.png" alt="Raio" class="manual-image-small">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-wind"></i>
                            </div>
                            <h3>Fuuton (Vento)</h3>
                        </div>
                        <p><strong>Forte contra:</strong> Raio</p>
                        <p><strong>Fraco contra:</strong> Fogo</p>
                        <p><strong>Características:</strong> Alcance longo, cortes precisos, mobilidade</p>
                        <img src="_img/Manual/elemento_vento.png" alt="Vento" class="manual-image-small">
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Categorias de Jutsus:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h3>Ninjutsu</h3>
                        </div>
                        <p>Técnicas que manipulam chakra e elementos. Requer alta inteligência e chakra para máxima eficiência.</p>
                        <p><strong>Exemplos:</strong> Katon: Goukakyuu, Suiton: Suiryuudan, Chidori</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fist-raised"></i>
                            </div>
                            <h3>Taijutsu</h3>
                        </div>
                        <p>Arte marcial pura que usa apenas o corpo físico. Depende de força, agilidade e resistência.</p>
                        <p><strong>Exemplos:</strong> Konoha Senpuu, Hachimon Tonkou, Juuken</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <h3>Genjutsu</h3>
                        </div>
                        <p>Técnicas de ilusão que afetam a mente do oponente. Baseado em inteligência e controle de chakra.</p>
                        <p><strong>Exemplos:</strong> Kanashibari, Tsukuyomi, Kokuangyou</p>
                    </div>
                </div>

                <div class="info-box">
                    <strong>Aprendizado de Jutsus:</strong> Jutsus são aprendidos através de pergaminhos, mentores especiais, missões específicas ou como recompensas de eventos. Alguns jutsus são exclusivos de certas vilas ou personagens.
                </div>
            </div>
        </section>

        <!-- Equipamentos -->
        <section id="equipamentos" class="section">
            <div class="section-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Sistema de Equipamentos</h2>
            </div>
            <div class="section-content">
                <p>O sistema de equipamentos no <?php echo nome_servidor(); ?> é profundo e estratégico. Cada peça de equipamento não apenas melhora seus atributos, mas também pode desbloquear habilidades especiais, modificar a aparência do seu ninja e influenciar suas interações sociais.</p>
                
                <div class="image-gallery">
                    <img src="_img/Manual/equipamento_exemplo.jpg" alt="Equipamento" class="manual-image">
                    <img src="_img/Manual/roupa_exemplo.jpg" alt="Roupa" class="manual-image">
                    <img src="_img/Manual/arma_exemplo.jpg" alt="Arma" class="manual-image">
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Categorias de Equipamentos:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-sword"></i>
                            </div>
                            <h3>Armas Principais</h3>
                        </div>
                        <p><strong>Tipos:</strong> Kunais, Katanas, Shurikens, Armas Especiais</p>
                        <p><strong>Efeito:</strong> Aumenta poder de ataque e pode ter habilidades únicas</p>
                        <p><strong>Exemplos:</strong> Kunai do Yondaime, Samehada, Fuuma Shuriken</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <h3>Roupas e Armaduras</h3>
                        </div>
                        <p><strong>Tipos:</strong> Roupas Básicas, Uniformes de Vila, Mantos Especiais</p>
                        <p><strong>Efeito:</strong> Melhora defesa e pode fornecer resistências elementais</p>
                        <p><strong>Exemplos:</strong> Manto Akatsuki, Roupa ANBU, Uniforme Jounin</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-running"></i>
                            </div>
                            <h3>Calçados</h3>
                        </div>
                        <p><strong>Tipos:</strong> Sandálias Simples, Botas de Proteção, Calçados Especiais</p>
                        <p><strong>Efeito:</strong> Aumenta agilidade e pode melhorar velocidade de movimento</p>
                        <p><strong>Exemplos:</strong> Sandálias de Madeira, Botas de Proteção</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-scroll"></i>
                            </div>
                            <h3>Pergaminhos</h3>
                        </div>
                        <p><strong>Tipos:</strong> Pergaminhos de Jutsu, Pergaminhos de Invocação</p>
                        <p><strong>Efeito:</strong> Permitem usar jutsus especiais ou invocar criaturas</p>
                        <p><strong>Exemplos:</strong> Pergaminho do Céu, Pergaminho da Terra</p>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Sistema de Qualidade:</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Qualidade</th>
                                <th>Cor</th>
                                <th>Bônus de Atributos</th>
                                <th>Características Especiais</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Comum</strong></td>
                                <td style="color: #ffffff;">Branco</td>
                                <td>+1 a +5</td>
                                <td>Sem habilidades especiais</td>
                            </tr>
                            <tr>
                                <td><strong>Incomum</strong></td>
                                <td style="color: #1eff00;">Verde</td>
                                <td>+6 a +15</td>
                                <td>Uma habilidade menor</td>
                            </tr>
                            <tr>
                                <td><strong>Raro</strong></td>
                                <td style="color: #0099ff;">Azul</td>
                                <td>+16 a +30</td>
                                <td>Habilidade significativa</td>
                            </tr>
                            <tr>
                                <td><strong>Épico</strong></td>
                                <td style="color: #cc00ff;">Roxo</td>
                                <td>+31 a +50</td>
                                <td>Múltiplas habilidades</td>
                            </tr>
                            <tr>
                                <td><strong>Lendário</strong></td>
                                <td style="color: #ff8000;">Laranja</td>
                                <td>+51 a +100</td>
                                <td>Habilidades únicas poderosas</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="warning-box">
                    <strong>Equipamentos Únicos:</strong> Alguns equipamentos são únicos no servidor e podem ser perdidos se o personagem for morto em combate PvP específico.
                </div>
            </div>
        </section>

        <!-- Missões -->
        <section id="missoes" class="section">
            <div class="section-header">
                <i class="fas fa-tasks"></i>
                <h2>Sistema de Missões</h2>
            </div>
            <div class="section-content">
                <p>As missões são o coração da progressão passiva no <?php echo nome_servidor(); ?>. Este sistema permite que seu ninja continue ganhando experiência, yens e itens mesmo quando você está offline, tornando o jogo ideal para pessoas com rotinas ocupadas.</p>
                
                <div class="info-box">
                    <strong>Progressão Offline:</strong> As missões são a principal forma de progresso quando você não pode ficar online. Sempre deixe seu ninja em missão!
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Ranks de Missões:</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Dificuldade</th>
                                <th>Duração</th>
                                <th>Recompensas</th>
                                <th>Risco</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><img src="_img/Manual/missao_d.png" alt="Rank D"><strong>Rank D</strong></td>
                                <td>Muito Fácil</td>
                                <td>30min - 2h</td>
                                <td>Baixas mas seguras</td>
                                <td>Nenhum</td>
                            </tr>
                            <tr>
                                <td><img src="_img/Manual/missao_c.png" alt="Rank C"><strong>Rank C</strong></td>
                                <td>Fácil</td>
                                <td>1h - 4h</td>
                                <td>Médias e estáveis</td>
                                <td>Muito baixo</td>
                            </tr>
                            <tr>
                                <td><img src="_img/Manual/missao_b.png" alt="Rank B"><strong>Rank B</strong></td>
                                <td>Média</td>
                                <td>2h - 8h</td>
                                <td>Boas com chance de itens</td>
                                <td>Baixo</td>
                            </tr>
                            <tr>
                                <td><img src="_img/Manual/missao_a.png" alt="Rank A"><strong>Rank A</strong></td>
                                <td>Difícil</td>
                                <td>4h - 16h</td>
                                <td>Altas com itens raros</td>
                                <td>Médio</td>
                            </tr>
                            <tr>
                                <td><img src="_img/Manual/missao_s.png" alt="Rank S"><strong>Rank S</strong></td>
                                <td>Extrema</td>
                                <td>8h - 24h</td>
                                <td>Excelentes com itens únicos</td>
                                <td>Alto</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Tipos de Missões:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <h3>Missões de Vila</h3>
                        </div>
                        <p>Missões internas da sua vila, geralmente seguras e com recompensas garantidas.</p>
                        <p><strong>Exemplos:</strong> Patrulhamento, Entrega de mensagens, Treinamento de novatos</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-map"></i>
                            </div>
                            <h3>Missões de Exploração</h3>
                        </div>
                        <p>Exploração de territórios desconhecidos com chance de descobrir segredos.</p>
                        <p><strong>Exemplos:</strong> Mapeamento, Coleta de recursos, Investigação</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fist-raised"></i>
                            </div>
                            <h3>Missões de Combate</h3>
                        </div>
                        <p>Enfrentamento direto com inimigos, alto risco mas recompensas valiosas.</p>
                        <p><strong>Exemplos:</strong> Eliminação de bandidos, Resgate de reféns, Sabotagem</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Missões de Grupo</h3>
                        </div>
                        <p>Missões especiais que requerem múltiplos ninjas para serem completadas.</p>
                        <p><strong>Exemplos:</strong> Invasões, Missões diplomáticas, Operações especiais</p>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Estratégias de Missões:</h3>
                <ul class="feature-list">
                    <li><strong>Rotação de Dificuldade:</strong> Alterne entre missões fáceis e difíceis baseado na sua condição</li>
                    <li><strong>Missões Noturnas:</strong> Configure missões longas antes de dormir</li>
                    <li><strong>Planejamento de Fim de Semana:</strong> Reserve missões mais arriscadas para quando pode monitorar</li>
                    <li><strong>Gestão de Recursos:</strong> Use missões fáceis quando está com poucos itens de cura</li>
                    <li><strong>Especialização:</strong> Foque em tipos de missão que complementam seu build</li>
                </ul>

                <div class="warning-box">
                    <strong>Riscos das Missões:</strong> Missões de rank alto podem resultar em ferimentos, perda de equipamentos ou até mesmo morte do personagem em casos extremos.
                </div>
            </div>
        </section>

        <!-- Combate -->
        <section id="combate" class="section">
            <div class="section-header">
                <i class="fas fa-fist-raised"></i>
                <h2>Sistema de Combate</h2>
            </div>
            <div class="section-content">
                <p>O combate no <?php echo nome_servidor(); ?> é estratégico e baseado em turnos, onde conhecimento, timing e preparação são mais importantes que poder bruto. Cada decisão pode determinar o resultado da batalha.</p>
                
                <div class="info-box">
                    <strong>Combate Estratégico:</strong> Analise bem seu oponente antes de atacar. Cada ninja tem pontos fortes e fracos específicos.
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Mecânicas de Combate:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fist-raised"></i>
                            </div>
                            <h3>Ataques Básicos</h3>
                        </div>
                        <p><strong>Custo:</strong> Sem chakra</p>
                        <p><strong>Dano:</strong> Baseado em Força + Arma</p>
                        <p>Ataques físicos simples mas confiáveis, ideais para conservar chakra.</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h3>Jutsus</h3>
                        </div>
                        <p><strong>Custo:</strong> Chakra variável</p>
                        <p><strong>Dano:</strong> Baseado em Inteligência + Elemento</p>
                        <p>Técnicas poderosas com efeitos especiais, mas consomem recursos.</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Defesa</h3>
                        </div>
                        <p><strong>Custo:</strong> Turno</p>
                        <p><strong>Efeito:</strong> Reduz dano em 50-75%</p>
                        <p>Reduz drasticamente o dano recebido, útil para recuperação.</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-medkit"></i>
                            </div>
                            <h3>Uso de Itens</h3>
                        </div>
                        <p><strong>Custo:</strong> Item + Turno</p>
                        <p><strong>Efeito:</strong> Variável</p>
                        <p>Permite usar poções, pergaminhos e outros consumíveis.</p>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Fatores de Combate:</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fator</th>
                                <th>Influência</th>
                                <th>Como Melhorar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Ordem de Ação</strong></td>
                                <td>Baseada em Agilidade</td>
                                <td>Treinar agilidade, equipamentos leves</td>
                            </tr>
                            <tr>
                                <td><strong>Chance de Crítico</strong></td>
                                <td>Baseada em Agilidade + Sorte</td>
                                <td>Alto nível, equipamentos especiais</td>
                            </tr>
                            <tr>
                                <td><strong>Chance de Esquiva</strong></td>
                                <td>Baseada em Agilidade vs Precisão do oponente</td>
                                <td>Treinar agilidade, técnicas especiais</td>
                            </tr>
                            <tr>
                                <td><strong>Resistência Elemental</strong></td>
                                <td>Baseada em equipamentos e habilidades</td>
                                <td>Equipamentos específicos, doujutsus</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Estratégias Avançadas:</h3>
                <ul class="feature-list">
                    <li><strong>Análise Pré-Combate:</strong> Estude o perfil do oponente antes de atacar</li>
                    <li><strong>Gestão de Chakra:</strong> Use jutsus no momento certo, não no primeiro turno</li>
                    <li><strong>Exploração Elemental:</strong> Identifique fraquezas elementais do oponente</li>
                    <li><strong>Timing de Itens:</strong> Use poções no momento mais eficiente</li>
                    <li><strong>Combos:</strong> Combine diferentes tipos de ataque para máxima eficiência</li>
                    <li><strong>Fuga Estratégica:</strong> Saber quando fugir é parte da estratégia</li>
                </ul>

                <div class="warning-box">
                    <strong>Consequências PvP:</strong> Combates contra outros jogadores podem resultar em perda de itens, yens ou até mesmo equipamentos únicos.
                </div>
            </div>
        </section>

        <!-- Doujutsus -->
        <section id="doujutsus" class="section">
            <div class="section-header">
                <i class="fas fa-eye"></i>
                <h2>Doujutsus - Habilidades Oculares</h2>
            </div>
            <div class="section-content">
                <p>Os Doujutsus são habilidades oculares raríssimas e extremamente poderosas que apenas alguns ninjas especiais podem despertar. Cada doujutsu oferece habilidades únicas que podem mudar completamente o estilo de jogo do seu ninja.</p>
                
                <div class="warning-box">
                    <strong>Raridade Extrema:</strong> Doujutsus são extremamente raros. Apenas certos personagens têm a possibilidade de despertá-los, e mesmo assim não é garantido.
                </div>

                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h3>Sharingan</h3>
                        </div>
                        <p><strong>Clã:</strong> Uchiha</p>
                        <p><strong>Habilidades:</strong></p>
                        <ul style="font-size: 0.85rem; margin-top: 0.5rem;">
                            <li>• Prever movimentos do oponente</li>
                            <li>• Copiar jutsus observados</li>
                            <li>• Resistência a genjutsus</li>
                            <li>• Chance de evoluir para Mangekyou</li>
                        </ul>
                        <img src="_img/Manual/doujutsu_sharingan.jpg" alt="Sharingan" class="manual-image">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>Byakugan</h3>
                        </div>
                        <p><strong>Clã:</strong> Hyuuga</p>
                        <p><strong>Habilidades:</strong></p>
                        <ul style="font-size: 0.85rem; margin-top: 0.5rem;">
                            <li>• Visão de 360 graus</li>
                            <li>• Ver pontos de chakra</li>
                            <li>• Detectar inimigos ocultos</li>
                            <li>• Bônus para técnicas Juuken</li>
                        </ul>
                        <img src="_img/Manual/doujutsu_byakugan.jpg" alt="Byakugan" class="manual-image">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-circle"></i>
                            </div>
                            <h3>Rinnegan</h3>
                        </div>
                        <p><strong>Raridade:</strong> Lendária</p>
                        <p><strong>Habilidades:</strong></p>
                        <ul style="font-size: 0.85rem; margin-top: 0.5rem;">
                            <li>• Controle sobre vida e morte</li>
                            <li>• Manipulação gravitacional</li>
                            <li>• Absorção de chakra</li>
                            <li>• Habilidades únicas devastadoras</li>
                        </ul>
                        <p style="color: var(--primary); font-size: 0.8rem; margin-top: 0.5rem;"><strong>Obtível apenas em eventos especiais</strong></p>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Processo de Despertar:</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Doujutsu</th>
                                <th>Requisitos</th>
                                <th>Processo</th>
                                <th>Chance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Sharingan</strong></td>
                                <td>Personagem Uchiha, Alto estresse emocional</td>
                                <td>Treino específico + Evento traumático</td>
                                <td>15-25%</td>
                            </tr>
                            <tr>
                                <td><strong>Byakugan</strong></td>
                                <td>Personagem Hyuuga, Meditação profunda</td>
                                <td>Treino de concentração + Rituals especiais</td>
                                <td>20-30%</td>
                            </tr>
                            <tr>
                                <td><strong>Rinnegan</strong></td>
                                <td>Evento especial ou conquista impossível</td>
                                <td>Apenas em eventos específicos do servidor</td>
                                <td>0.1%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="info-box">
                    <strong>Desenvolvimento dos Doujutsus:</strong> Uma vez despertado, o doujutsu pode ser treinado e evoluído através de treinos específicos e uso em combate.
                </div>
            </div>
        </section>

        <!-- Economia -->
        <section id="economia" class="section">
            <div class="section-header">
                <i class="fas fa-coins"></i>
                <h2>Sistema Econômico</h2>
            </div>
            <div class="section-content">
                <p>O <?php echo nome_servidor(); ?> possui uma economia complexa e dinâmica onde os preços flutuam baseado na oferta e demanda dos jogadores. Compreender a economia é essencial para o sucesso a longo prazo.</p>
                
                <div style="text-align: center; margin: 2rem 0;">
                    <img src="_img/Manual/yens_icon.png" alt="Yens" class="manual-image">
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Moedas do Jogo:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <h3>Yens</h3>
                        </div>
                        <p><strong>Obtenção:</strong> Missões, Vendas, Trabalhos</p>
                        <p><strong>Uso:</strong> Equipamentos, Treinos, Consumíveis</p>
                        <p>Moeda principal do jogo, obtida através de atividades normais.</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-gem"></i>
                            </div>
                            <h3>Pontos Premium</h3>
                        </div>
                        <p><strong>Obtenção:</strong> Compra com dinheiro real, Eventos</p>
                        <p><strong>Uso:</strong> Itens exclusivos, Aceleração</p>
                        <p>Moeda premium para itens especiais e conveniências.</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3>Pontos de Evento</h3>
                        </div>
                        <p><strong>Obtenção:</strong> Participação em eventos especiais</p>
                        <p><strong>Uso:</strong> Itens únicos de evento</p>
                        <p>Moeda especial obtida apenas durante eventos.</p>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Mercado e Comércio:</h3>
                <ul class="feature-list">
                    <li><strong>Sistema de Leilões:</strong> Itens raros são vendidos através de leilões competitivos</li>
                    <li><strong>Mercado Global:</strong> Compre e venda itens com outros jogadores</li>
                    <li><strong>Flutuação de Preços:</strong> Preços mudam baseado na oferta e demanda</li>
                    <li><strong>Investimentos:</strong> Compre itens baratos para vender quando o preço subir</li>
                    <li><strong>Especialização:</strong> Torne-se especialista em um tipo de item</li>
                </ul>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Dicas de Gestão Financeira:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                            <h3>Economia de Recursos</h3>
                        </div>
                        <p>• Sempre mantenha uma reserva de emergência<br>
                        • Não gaste tudo em upgrades imediatos<br>
                        • Priorize investimentos de longo prazo</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Investimentos Inteligentes</h3>
                        </div>
                        <p>• Observe tendências do mercado<br>
                        • Compre durante eventos especiais<br>
                        • Invista em itens que valorizam com o tempo</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <h3>Comércio Social</h3>
                        </div>
                        <p>• Construa relacionamentos comerciais<br>
                        • Participe de guildas mercantes<br>
                        • Negocie preços justos</p>
                    </div>
                </div>

                <div class="info-box">
                    <strong>Economia Player-Driven:</strong> Ao contrário de muitos jogos, os preços no <?php echo nome_servidor(); ?> são determinados pelos próprios jogadores, criando uma economia realista e dinâmica.
                </div>
            </div>
        </section>

        <!-- Dicas Avançadas -->
        <section id="dicas" class="section">
            <div class="section-header">
                <i class="fas fa-lightbulb"></i>
                <h2>Dicas Avançadas e Estratégias</h2>
            </div>
            <div class="section-content">
                <p>Estas são dicas avançadas coletadas de jogadores veteranos que dominaram os aspectos mais complexos do <?php echo nome_servidor(); ?>. Aplicar essas estratégias pode ser a diferença entre um ninja comum e uma lenda.</p>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Estratégias de Longo Prazo:</h3>
                <ul class="feature-list">
                    <li><strong>Planejamento de 30 Dias:</strong> Sempre tenha metas mensais claras para desenvolvimento</li>
                    <li><strong>Diversificação:</strong> Não coloque todos os recursos em uma única estratégia</li>
                    <li><strong>Networking:</strong> Construa relacionamentos sólidos com outros jogadores</li>
                    <li><strong>Adaptabilidade:</strong> Esteja pronto para mudar estratégias conforme o meta evolui</li>
                    <li><strong>Paciência:</strong> Os melhores resultados vêm com tempo e consistência</li>
                </ul>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Otimização Avançada:</h3>
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3>Gestão de Tempo</h3>
                        </div>
                        <p>• Configure treinos longos antes de períodos offline<br>
                        • Use alertas para não perder oportunidades<br>
                        • Sincronize atividades com seu horário real</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <h3>Meta-Game</h3>
                        </div>
                        <p>• Estude as tendências dos outros jogadores<br>
                        • Antecipe mudanças no equilíbrio do jogo<br>
                        • Mantenha-se atualizado com as novidades</p>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Aspecto Social</h3>
                        </div>
                        <p>• Participe ativamente da comunidade<br>
                        • Forme alianças estratégicas<br>
                        • Compartilhe conhecimento para receber ajuda</p>
                    </div>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Erros Comuns a Evitar:</h3>
                <div class="warning-box">
                    <strong>Armadilhas para Iniciantes:</strong>
                    <ul style="margin-top: 1rem;">
                        <li>• Gastar todos os yens imediatamente</li>
                        <li>• Focar apenas em um atributo</li>
                        <li>• Ignorar o aspecto social do jogo</li>
                        <li>• Não planejar a longo prazo</li>
                        <li>• Desistir após as primeiras dificuldades</li>
                    </ul>
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Recursos Essenciais:</h3>
                <ul class="feature-list">
                    <li><strong>Documentação:</strong> Leia todas as atualizações e novidades</li>
                    <li><strong>Comunidade:</strong> Participe de fóruns e grupos de discussão</li>
                    <li><strong>Mentoria:</strong> Encontre jogadores experientes para orientação</li>
                    <li><strong>Experimentação:</strong> Teste diferentes estratégias em personagens alternativos</li>
                    <li><strong>Feedback:</strong> Sempre avalie e ajuste suas estratégias</li>
                </ul>

                <div class="info-box">
                    <strong>Lembre-se:</strong> O <?php echo nome_servidor(); ?> é um jogo de longo prazo. O sucesso vem da consistência, paciência e aplicação inteligente de estratégias ao longo do tempo.
                </div>

                <h3 style="color: var(--text-primary); margin: 2rem 0 1rem;">Considerações Finais:</h3>
                <p>O <?php echo nome_servidor(); ?> oferece uma experiência profunda e recompensadora para aqueles que se dedicam a compreender seus sistemas. Este manual cobre os aspectos fundamentais, mas o verdadeiro aprendizado vem da experiência prática. <strong>Não desista nos primeiros obstáculos</strong> - cada ninja lendário começou como um simples estudante da academia!</p>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <a href="index.php" class="home-btn">
            <i class="fas fa-home"></i>
            Voltar ao Jogo
        </a>
    </footer>

    <script>
        // Smooth scroll para navegação
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Adicionar classe ativa na navegação
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('.section');
            const navItems = document.querySelectorAll('.nav-item');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });

            navItems.forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('href') === `#${current}`) {
                    item.classList.add('active');
                }
            });
        });

        // Animação nas imagens ao passar o mouse
        document.querySelectorAll('.manual-image, .manual-image-large').forEach(img => {
            img.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            
            img.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
