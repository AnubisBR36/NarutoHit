var PopupNoticia = {
    noticiaAtual: null,
    
    init: function() {
        this.verificarNovaNoticia();
    },
    
    verificarNovaNoticia: function() {
        $.ajax({
            url: '_inc/check_noticia_nova.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.tem_nova && response.noticia) {
                    PopupNoticia.noticiaAtual = response.noticia;
                    PopupNoticia.mostrarPopup();
                }
            },
            error: function() {
                console.log('Erro ao verificar notícia nova');
            }
        });
    },
    
    mostrarPopup: function() {
        if (!this.noticiaAtual) return;
        
        $('#popup-noticia-overlay').fadeIn(300);
        $('#popup-noticia').fadeIn(300);
    },
    
    fecharPopup: function() {
        if (!this.noticiaAtual) return;
        
        var noticiaId = this.noticiaAtual.id;
        
        $.ajax({
            url: '_inc/marcar_noticia_lida.php',
            type: 'POST',
            data: { noticia_id: noticiaId },
            dataType: 'json',
            async: false,
            success: function(response) {
                window.location.href = '?p=news#noticia-' + noticiaId;
            },
            error: function() {
                window.location.href = '?p=news#noticia-' + noticiaId;
            }
        });
    }
};

$(document).ready(function() {
    PopupNoticia.init();
    
    $('#popup-noticia-botao').click(function() {
        PopupNoticia.fecharPopup();
    });
});
