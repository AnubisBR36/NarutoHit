<?php
// Controlador para visualização pública de notícias
class PublicNoticiasController {
    private $repository;
    
    public function __construct($repository) {
        $this->repository = $repository;
    }
    
    // Exibir notícias para os jogadores (apenas ativas, não expiradas)
    public function renderList($page = 1, $perPage = 5) {
        $offset = ($page - 1) * $perPage;
        
        $noticias = $this->repository->fetchActiveNews($perPage, $offset);
        $total = $this->repository->count();
        $totalPages = ceil($total / $perPage);
        
        require __DIR__ . '/../views/public/list.php';
    }
}
