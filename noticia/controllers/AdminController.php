<?php
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class AdminNoticiasController {
    private $repository;
    private $usuario;
    
    public function __construct($repository, $usuario) {
        $this->repository = $repository;
        $this->usuario = $usuario;
    }
    
    // Verificar se usuário é admin
    private function verificarAdmin() {
        if (!isset($this->usuario['adm']) || ($this->usuario['adm'] != 1 && $this->usuario['adm'] != 2)) {
            header('Location: index.php?p=main');
            exit;
        }
    }
    
    // Listar todas as notícias (admin)
    public function listar($page = 1) {
        $this->verificarAdmin();
        
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $noticias = $this->repository->fetchPage($perPage, $offset);
        $total = $this->repository->count();
        $totalPages = ceil($total / $perPage);
        
        require __DIR__ . '/../views/admin/list.php';
    }
    
    // Exibir formulário de criação
    public function exibirFormulario($id = null) {
        $this->verificarAdmin();
        
        $noticia = null;
        $acao = 'criar';
        
        if ($id) {
            $noticia = $this->repository->findById($id);
            $acao = 'editar';
            if (!$noticia) {
                $_SESSION['erro_noticia'] = "Notícia não encontrada!";
                header('Location: index.php?p=admin_noticias');
                exit;
            }
        }
        
        require __DIR__ . '/../views/admin/form.php';
    }
    
    // Salvar notícia (criar ou editar)
    public function salvar() {
        $this->verificarAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?p=admin_noticias');
            exit;
        }
        
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['erro_noticia'] = "Token de segurança inválido!";
            header('Location: index.php?p=admin_noticias');
            exit;
        }
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');
        $usar_cores = isset($_POST['usar_cores']) ? 1 : 0;
        
        // Data de expiração
        $data_expiracao = null;
        if (!empty($_POST['dias_expiracao'])) {
            $dias = (int)$_POST['dias_expiracao'];
            if ($dias > 0) {
                $data_expiracao = date('Y-m-d H:i:s', strtotime("+$dias days"));
            }
        }
        
        // Validação
        if (empty($titulo) || empty($conteudo)) {
            $_SESSION['erro_noticia'] = "Título e conteúdo são obrigatórios!";
            header('Location: ' . ($id ? "?p=admin_noticias&acao=editar&id=$id" : "?p=admin_noticias&acao=nova"));
            exit;
        }
        
        if ($id) {
            // Atualizar
            $sucesso = $this->repository->update($id, $titulo, $conteudo, $data_expiracao, $usar_cores);
            $mensagem = $sucesso ? "Notícia atualizada com sucesso!" : "Erro ao atualizar notícia!";
        } else {
            // Criar
            $autor = $this->usuario['login'] ?? 'Admin';
            $sucesso = $this->repository->create($titulo, $conteudo, $autor, $data_expiracao, $usar_cores);
            $mensagem = $sucesso ? "Notícia criada com sucesso!" : "Erro ao criar notícia!";
        }
        
        $_SESSION[$sucesso ? 'sucesso_noticia' : 'erro_noticia'] = $mensagem;
        header('Location: index.php?p=admin_noticias');
        exit;
    }
    
    // Deletar notícia
    public function deletar() {
        $this->verificarAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?p=admin_noticias');
            exit;
        }
        
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['erro_noticia'] = "Token de segurança inválido!";
            header('Location: index.php?p=admin_noticias');
            exit;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $sucesso = $this->repository->delete($id);
            $_SESSION[$sucesso ? 'sucesso_noticia' : 'erro_noticia'] = 
                $sucesso ? "Notícia deletada com sucesso!" : "Erro ao deletar notícia!";
        }
        
        header('Location: index.php?p=admin_noticias');
        exit;
    }
}
