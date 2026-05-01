
// Sistema de Popup de Invasão
var BannerInvasao = {
    popupAtivo: false,

    verificarBanners: function() {
        if (this.popupAtivo) return;

        fetch('_inc/check_banner_invasao.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.mostrar_banner) {
                    BannerInvasao.exibirPopup(data);
                }
            })
            .catch(function(error) {
                console.log('Erro ao verificar invasão:', error);
            });
    },

    exibirPopup: function(data) {
        if (this.popupAtivo) return;
        this.popupAtivo = true;

        var overlay = document.createElement('div');
        overlay.id = 'invasao-overlay';

        var imgPath = '_img/Baner invas\u00e3o/' + (data.imagem_popup || 'Uma.png');

        var botaoAcao = '';
        var titulo = '';
        var descricao = '';

        if (data.tipo === 'inicio') {
            titulo = 'INVASÃO COMEÇOU!';
            descricao = '<strong>' + data.nome_invasor + '</strong> está atacando a vila!<br>'
                      + 'Prêmio: <span class="inv-premio">' + data.premio_yens + ' Yens</span> &nbsp;|&nbsp; '
                      + 'Bônus: <span class="inv-bonus">+' + data.bonus_vila + '%</span>';
            botaoAcao = '<button class="inv-btn-participar" onclick="BannerInvasao.irParaInvasao(' + data.invasao_id + ', \'' + data.tipo + '\')">⚔ PARTICIPAR</button>';
        } else if (data.tipo === 'fim') {
            titulo = 'INVASÃO DERROTADA!';
            descricao = '<strong>' + data.vencedor_nome + '</strong> derrotou <strong>' + data.nome_invasor + '</strong>!<br>'
                      + 'Vila <strong>' + data.vila_vencedora + '</strong> ganhou <span class="inv-bonus">+' + data.bonus_vila + '%</span> em todos os status!';
            botaoAcao = '<button class="inv-btn-participar" onclick="BannerInvasao.irParaInvasao(' + data.invasao_id + ', \'' + data.tipo + '\')">👁 VER RESULTADO</button>';
        }

        overlay.innerHTML =
            '<div id="invasao-popup">' +
                '<div id="invasao-popup-imagem">' +
                    '<img src="' + imgPath + '" alt="' + data.nome_invasor + '" id="invasao-img">' +
                '</div>' +
                '<div id="invasao-popup-corpo">' +
                    '<div id="invasao-titulo">' + titulo + '</div>' +
                    '<div id="invasao-descricao">' + descricao + '</div>' +
                    '<div id="invasao-botoes">' +
                        botaoAcao +
                        '<button class="inv-btn-fechar" onclick="BannerInvasao.fecharPopup(' + data.invasao_id + ', \'' + data.tipo + '\')">✕ FECHAR</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        document.body.appendChild(overlay);

        setTimeout(function() {
            overlay.classList.add('invasao-visivel');
        }, 50);

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                BannerInvasao.fecharPopup(data.invasao_id, data.tipo);
            }
        });

        setTimeout(function() {
            BannerInvasao.fecharPopup(data.invasao_id, data.tipo);
        }, 15000);
    },

    irParaInvasao: function(invasaoId, tipo) {
        var overlay = document.getElementById('invasao-overlay');
        if (overlay) {
            overlay.classList.remove('invasao-visivel');
            overlay.classList.add('invasao-saindo');
        }
        BannerInvasao.popupAtivo = false;
        BannerInvasao.marcarComoVisualizado(invasaoId, tipo);
        setTimeout(function() {
            window.location.href = '?p=invasao';
        }, 300);
    },

    fecharPopup: function(invasaoId, tipo) {
        var overlay = document.getElementById('invasao-overlay');
        if (overlay) {
            overlay.classList.remove('invasao-visivel');
            overlay.classList.add('invasao-saindo');
            setTimeout(function() {
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                BannerInvasao.popupAtivo = false;
            }, 500);
            BannerInvasao.marcarComoVisualizado(invasaoId, tipo);
        }
    },

    marcarComoVisualizado: function(invasaoId, tipo) {
        fetch('_inc/marcar_banner_visualizado.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invasao_id: invasaoId, tipo: tipo })
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    BannerInvasao.verificarBanners();
});

setInterval(function() {
    BannerInvasao.verificarBanners();
}, 30000);
