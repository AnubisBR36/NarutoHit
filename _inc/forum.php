<?php
// Garantir conexão com banco principal está disponível
require_once '_inc/conexao.php';

// Incluir controllers necessários
require_once 'forum/controllers/ForumController.php';
require_once 'forum/controllers/TopicoController.php';
require_once 'forum/controllers/PostagemController.php';
require_once 'forum/controllers/CurtidaController.php';
require_once 'forum/controllers/ReacaoController.php';
require_once 'forum/controllers/ModeracaoController.php';
require_once 'forum/controllers/BuscaController.php';

// Iniciar sessão se ainda não estiver
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Roteamento
$page = isset($_GET['p']) ? $_GET['p'] : 'forum';

// Rotas AJAX - processar e sair antes de qualquer HTML
if ($page === 'forum_reacao' || $page === 'forum_curtir') {
    if ($page === 'forum_reacao') {
        $controller = new ReacaoController();
        $controller->reagir();
    } elseif ($page === 'forum_curtir') {
        $controller = new CurtidaController();
        $controller->curtir();
    }
    exit;
}

switch ($page) {
    case 'forum':
        $controller = new ForumController();
        $controller->index();
        break;
        
    case 'forum_topicos':
        $controller = new TopicoController();
        $categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
        $controller->listar($categoria_id);
        break;
        
    case 'forum_topico':
        $controller = new TopicoController();
        $topico_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $controller->visualizar($topico_id);
        break;
        
    case 'forum_criar_topico':
        $controller = new TopicoController();
        $controller->criar();
        break;
        
    case 'forum_criar_postagem':
        $controller = new PostagemController();
        $controller->criar();
        break;
        
    case 'forum_deletar_postagem':
        $controller = new PostagemController();
        $controller->deletar();
        break;
        
    case 'forum_curtir':
        $controller = new CurtidaController();
        $controller->curtir();
        break;
        
    case 'forum_reacao':
        $controller = new ReacaoController();
        $controller->reagir();
        break;
        
    case 'forum_deletar_topico':
        $controller = new ModeracaoController();
        $controller->deletarTopico();
        break;
        
    case 'forum_fixar':
        $controller = new ModeracaoController();
        $controller->fixarTopico();
        break;
        
    case 'forum_fechar':
        $controller = new ModeracaoController();
        $controller->fecharTopico();
        break;
        
    case 'forum_busca':
        $controller = new BuscaController();
        $controller->buscar();
        break;
        
    default:
        $controller = new ForumController();
        $controller->index();
        break;
}
