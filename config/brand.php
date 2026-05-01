<?php
/**
 * Configuração da marca/identidade do servidor.
 *
 * O nome do servidor é usado em emails, mensagens automáticas, rodapés,
 * páginas de login/FAQ etc. — em substituição ao antigo "AnubisServe"
 * que estava espalhado pelo código.
 *
 * Para personalizar, basta editar a constante BRAND_NAME abaixo
 * (ou definir BRAND_NAME no PHP antes deste arquivo ser incluído).
 */

if (!defined('BRAND_NAME')) {
    define('BRAND_NAME', 'NarutoTheGame');
}

if (!function_exists('nome_servidor')) {
    /**
     * Retorna o nome do servidor configurado em config/brand.php.
     */
    function nome_servidor(): string {
        return BRAND_NAME;
    }
}

if (!function_exists('nome_servidor_safe')) {
    /**
     * Versão escapada para HTML (evita XSS quando o nome contém caracteres especiais).
     */
    function nome_servidor_safe(): string {
        return htmlspecialchars(nome_servidor(), ENT_QUOTES, 'UTF-8');
    }
}
