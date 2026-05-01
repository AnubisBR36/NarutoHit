    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function reagirPostagem(postagemId, tipoReacao) {
    $.ajax({
        url: '?p=forum_reacao',
        method: 'POST',
        data: { 
            postagem_id: postagemId,
            tipo: tipoReacao
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message || 'Erro ao processar reação!');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro:', error);
            alert('Erro ao processar reação!');
        }
    });
}
</script>
</body>
</html>
