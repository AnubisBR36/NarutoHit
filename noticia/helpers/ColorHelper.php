<?php

class ColorHelper {
    
    // Renderizar cores usando tags [cor=#HEXCODE]texto[/cor]
    public static function renderColors($text) {
        // Padrão: [cor=#FF0000]texto colorido[/cor]
        $pattern = '/\[cor=(#[A-Fa-f0-9]{6}|#[A-Fa-f0-9]{3})\](.*?)\[\/cor\]/s';
        $replacement = '<span style="color: $1;">$2</span>';
        return preg_replace($pattern, $replacement, $text);
    }
    
    // Renderizar cores e permitir negrito, itálico
    public static function renderFormatting($text) {
        // Primeiro aplica cores
        $text = self::renderColors($text);
        
        // Negrito: [b]texto[/b]
        $text = preg_replace('/\[b\](.*?)\[\/b\]/s', '<strong>$1</strong>', $text);
        
        // Itálico: [i]texto[/i]
        $text = preg_replace('/\[i\](.*?)\[\/i\]/s', '<em>$1</em>', $text);
        
        // Sublinhado: [u]texto[/u]
        $text = preg_replace('/\[u\](.*?)\[\/u\]/s', '<u>$1</u>', $text);
        
        // Quebra de linha
        $text = nl2br($text);
        
        return $text;
    }
    
    // Limpar tags de formatação (para preview)
    public static function stripTags($text) {
        $text = preg_replace('/\[cor=#[A-Fa-f0-9]{3,6}\](.*?)\[\/cor\]/s', '$1', $text);
        $text = preg_replace('/\[b\](.*?)\[\/b\]/s', '$1', $text);
        $text = preg_replace('/\[i\](.*?)\[\/i\]/s', '$1', $text);
        $text = preg_replace('/\[u\](.*?)\[\/u\]/s', '$1', $text);
        return $text;
    }
}
