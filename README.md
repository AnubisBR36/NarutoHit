# AnubisServe

Jogo de navegador baseado no universo de Naruto.

## Descrição

AnubisServe é um RPG online por navegador ambientado no mundo de Naruto, onde os jogadores criam seus próprios personagens ninja, treinam, batalham e evoluem suas habilidades. O jogo oferece uma experiência completa com sistema de combate PvP e PvE, clãs, missões, treinamento, marketplace e eventos dinâmicos.

## Requisitos do Sistema

- PHP 8.2 ou superior
- Servidor Web (Apache, Nginx, ou PHP Development Server)
- SQLite
- Navegador web moderno

## Instalação

1. Clone ou baixe este repositório
2. Configure o servidor web para apontar para a pasta raiz do projeto
3. Configure as chaves do Google reCAPTCHA conforme as instruções abaixo
4. Acesse o projeto pelo navegador

## Configuração do Google reCAPTCHA

O sistema de login utiliza Google reCAPTCHA para proteção contra bots. Para configurar:

1. Localize o arquivo de exemplo de configuração na pasta de configurações
2. Faça uma cópia removendo a extensão de exemplo
3. Obtenha suas chaves em: https://www.google.com/recaptcha/admin
4. Preencha os campos de chave pública e chave privada
5. Salve o arquivo

## Principais Funcionalidades

### Sistema de Combate
- **PvP (Jogador vs Jogador)**: Batalhas estratégicas entre jogadores com sistema anti-bot visual
- **PvE (Jogador vs Ambiente)**: Missões e treinamento para evolução de personagem
- **Relatórios de Batalha**: Sistema completo de registro e visualização de histórico de combates
- **Sistema Anti-Bot**: Verificação visual com imagens de vilas antes de cada ataque

### Sistema de Clãs
- Criação e gerenciamento de clãs
- Sistema de ranking entre clãs
- Cooperação entre membros da mesma vila

### Sistema de Invasões
- Eventos dinâmicos de invasão de vilas
- Notificações em tempo real via popup
- Sistema de recompensas automáticas para defensores vitoriosos
- Bloqueio de funcionalidades durante invasões

### Sistema de Selos Amaldiçoados
- **4 Tipos de Selos**: Selo do Céu (Ninjutsu), Selo da Terra (Taijutsu), Selo da Lua (Genjutsu) e Selo do Sol (Balanceado)
- **3 Níveis de Evolução** por selo
- Sistema de progressão baseado em vitórias PvP
- Bônus estratégicos de atributos

### Sistema de Doujutsu
- Linhagem sanguínea adquirida em ritual exclusivo a partir do nível 20
- Sharingan, Byakugan e Rinnegan, com bônus e penalidades próprias

### Sistema de Mensagens
- Comunicação entre jogadores
- Resposta com citação automática
- Badge visual para mensagens de administradores
- Preservação de formatação e acentuação

### Sistema de Notícias
- Painel administrativo completo para criar, editar e deletar notícias
- Visualização pública com paginação e formatação
- Suporte a tags de formatação e cores personalizadas
- Expiração automática de notícias
- Proteção com validação de permissões

### Sistema de Banimento
- Popup visual estilizado ao tentar acessar conta banida
- Exibição de motivo, data e tempo restante do banimento
- Auto-desbanimento automático ao expirar o prazo
- Fluxo de aceite de termos obrigatório após desbanimento
- Penalidade configurável para quem rejeitar os termos

### Marketplace (Comércio entre Players)
- Economia in-game para compra e venda de itens entre jogadores
- Sistema de yens (moeda do jogo) e cristais como moedas alternativas
- VIP obrigatório para anunciar/vender itens; todos os jogadores podem visitar lojas e comprar
- Itens pendentes de recebimento ficam em reserva até o vendedor sacar manualmente
- Suporte a múltiplas moedas simultâneas na mesma loja

### Mapas Diversos
- Representação das diferentes vilas ninja (Folha, Areia, Nuvem, Névoa, Chuva, Rocha)
- Sistema de movimentação entre vilas

### Sistema Multi-Servidor
- Suporte a até 10 servidores totalmente isolados
- Jogadores de servidores diferentes não se veem em ranking, invasões, clãs ou batalhas
- Cada servidor com capacidade configurável e barra de lotação no login
- Administradores podem acessar qualquer servidor

### Fórum
- Categoria pública (Vila Neutra), uma categoria por vila e categoria exclusiva da Akatsuki
- Acesso restrito por vila (admins veem todas)
- Tags ADM e GM exibidas abaixo do nome em cada postagem

## Tecnologias Utilizadas

- **Backend**: PHP 8.2
- **Banco de Dados**: SQLite com PDO e prepared statements
- **Frontend**: HTML, CSS, JavaScript
- **Servidor**: PHP Development Server (porta 5000)

## Segurança

- Senhas armazenadas com hash criptográfico
- Proteção contra bots no login via Google reCAPTCHA
- Validação de sessões em todas as páginas protegidas
- Prepared statements em todas as consultas ao banco de dados
- Validação de permissões em ações administrativas
- Sistema de tokens para operações sensíveis
- Modo de manutenção com bloqueio automático para não-administradores

---

## Atualizações Recentes

---

### Novembro de 2025

- **Sistema de Manutenção** — Interface redesenhada com tema escuro e redirecionamento automático; administradores mantêm acesso normal
- **Sistema de Mensagens** — Citação automática ao responder e badge visual para mensagens da administração
- **Sistema de Selos Amaldiçoados** — 4 tipos de selo com bônus estratégicos e 3 níveis de evolução; cards animados com cores temáticas; apenas 1 selo ativo por vez
- **Interface de Batalhas** — Exibição do Selo Amaldiçoado ativo de cada jogador durante o combate, com breakdown completo dos bônus
- **Anti-Bot Visual com Imagens de Vilas** — 5 botões com imagens aleatórias de 8 vilas, validação no servidor com token criptográfico e penalidades por erros repetidos
- **Painel Administrativo** — Visual unificado com o tema do site (botões, formulários e tabelas)
- **Relatórios de Batalha** — Todas as batalhas registradas automaticamente; opção de apagar relatório individual
- **Notícias** — Arquitetura em camadas, painel com criação, edição, exclusão e paginação; tags de cores e expiração automática por dias
- **Popup de Notícias** — Pergaminho com animação de abertura, texto dourado contornado e aparecimento gradual
- **Limpeza de Itens** — Ferramenta administrativa para remoção seletiva de equipamentos e consumíveis com confirmação dupla
- **Página do Ferreiro** — Inventário com 7 categorias em abas, drag-and-drop para equipamento e cristal com feedback visual e botão "Aprimorar" condicional

---

### Março de 2026

#### Sistema de Ban Visual e Termos de Desbanimento (29/03/2026)
- Popup visual com imagens temáticas substituiu a mensagem simples; exibe motivo, data e tempo restante (bans eternos aparecem como "Permanente")
- Auto-desbanimento ao expirar o prazo; bans permanentes (≥ 10 anos) são isentos
- Após desbanimento, o jogador deve aceitar os termos antes de entrar; pergaminho estilizado com checkbox obrigatório
- Penalidade configurável (1 min – 24 h) para quem rejeitar, com contador regressivo

#### Sistema Multi-Servidor — Isolamento Total (29–31/03/2026)
- Até 10 servidores isolados; jogadores não se veem entre servidores em ranking, invasões, clãs ou batalhas
- Login carrega servidores dinamicamente e desabilita os cheios; admins acessam qualquer servidor
- Painel administrativo para criar, editar e excluir servidores (limite 10), com barra de progresso de ocupação
- Bingo Book com cards individuais (avatar, borda por nível de ameaça, badge de classificação, recompensa acumulada, placar e cooldown)

---

### Abril de 2026

#### Popup de Invasão com Imagem do Biju (02/04/2026)
- Banner fixo no topo substituído por popup centralizado com a imagem correta do Biju conforme a invasão (Uma a Dez Caudas)
- Fecha ao clicar no overlay, no botão "Fechar" ou após 15 segundos
- Bug do botão "Participar" (loop) corrigido

#### Ícones Dinâmicos na Seção de Atualizações (02/04/2026)
- O ícone genérico foi substituído por ícones específicos: imagem real do jutsu aprendido, gif do selo evoluído, ícone de nível ao subir, símbolo da Akatsuki, etc.
- Tamanho fixo 32×32 com bordas arredondadas

#### Estatísticas Filtradas por Servidor (03/04/2026)
- A caixinha "Estatísticas" do menu lateral exibe apenas dados do servidor atual e mostra o nome do servidor no título (ex.: **Estatísticas — Konoha**)

#### Toggle no "Meu Inventário" (03/04/2026)
- Botão de colapso (▲/▼) no título, com estado lembrado entre sessões

#### Sistema de Linhagem Sanguínea — Doujutsu (03/04/2026)
- Substitui o sistema antigo de atribuição automática
- **Ritual de Despertar** ao atingir nível 20: arena com avatar real do jogador contra a Sombra da Linhagem
- Cada ataque tem **10% de chance de acertar**; errar dá cooldown de 30 segundos
- Sorteio ao vencer: Rinnegan 5% · Sharingan 15% · Byakugan 20% · Sem linhagem 60%
- Sorteio "sem herança" aplica cooldown de 15 dias antes de nova tentativa

#### Atributos com Bônus Dourado e Tooltip de Detalhamento (07/04/2026)
- Total de cada atributo soma corretamente Base + Equipamentos + Doujutsu + Invasão
- Quando há bônus ativo, badge `+X` em dourado com contorno preto aparece ao lado do total
- Tooltip ao passar o mouse mostra a origem detalhada de cada bônus (apenas as fontes maiores que zero)

#### Fórum — Categorias e Tags ADM/GM (08/04/2026)
- 9 categorias padrão: Vila Neutra (pública), uma por vila e Akatsuki (exclusiva de renegados)
- Acesso restrito por vila; admins veem todas
- Tags ADM (vermelho/dourado) e GM (azul) abaixo do nome do autor em cada postagem

#### Correções de Imagens de Jutsus e Novo Jutsu Susano (14/04/2026)
- Imagem do **Bunshin no Jutsu** corrigida (exibia incorretamente a do Susano)
- **Susano** adicionado como novo jutsu (nível 25, força 300, requer Sharingan nível 3, custo 2.000 ryous)
- Removidos jutsus sem imagens e que ninguém possuía

#### Correção do Sistema de Treinamento (14/04/2026)
- Bug que redirecionava o jogador de volta à escola ao iniciar treino corrigido
- Fluxo escolher → treinar (com contador regressivo) → receber experiência funcionando

#### Melhoria na Visibilidade do Grid do Mapa (14/04/2026)
- Linhas de grade mais visíveis sobre qualquer mapa
- Highlight de hover ao passar o mouse sobre um tile (preenchimento branco semitransparente e borda destacada)
- Tile do jogador com borda laranja ligeiramente mais intensa

#### Tooltip Rico de Atributos e Penalidade de Doujutsu (15/04/2026)
- Painel flutuante estilizado abre ao passar o mouse no ícone de status
- Título colorido por atributo: azul (Taijutsu), roxo (Ninjutsu), verde (Genjutsu)
- Linhas individuais com ícones para Base, Equipamentos, Doujutsu (com a imagem real) e Invasão
- Penalidade de Doujutsu exibida em vermelho com contorno preto
- Penalidades reais aplicadas ao total e ao combate: Sharingan e Rinnegan reduzem Taijutsu, Byakugan reduz Ninjutsu (1% por nível)

#### Sincronização dos Atributos com o Combate (15/04/2026)
- Multiplicador do Selo Amaldiçoado, antes ignorado em "Meus Atributos", agora é aplicado e exibido no tooltip em verde (bônus) ou vermelho (penalidade)
- Ícone do atributo aparece ao lado do nome no painel flutuante

#### Redesign Visual Completo do Painel Administrativo (17/04/2026)
- Todos os módulos administrativos convertidos para o tema escuro/laranja do site
- Eliminados fundos brancos, imagens de box antigas e botões fora do padrão
- Tabelas, formulários, botões e modais padronizados; alertas em cores temáticas
- Removidos botões de "Voltar" duplicados (a barra de navegação já tem o link)

#### Sistema de Permissões por GM e Logs Administrativos (18/04/2026)
- Cargos: Player, GM (Moderador) e ADM
- ADM pode promover/rebaixar usuários; proteção contra remover o último ADM
- Permissões individuais por GM, configuráveis por módulo
- GMs editam Players e outros GMs, mas nunca contas ADM (invisíveis para eles)
- Tags visuais nas listas: nome do ADM em amarelo `[ADM]`, GM em azul `[GM]`
- Logs administrativos paginados com filtros por ação, autor e alvo, com cores por tipo de ação

#### Sistema de Comércio entre Players — Cristais e Correções (19/04/2026)
- Ao anunciar, o jogador VIP escolhe a moeda: Yens, Cristal de Chakra Refinado, Cristal de Chakra Bruto ou Chakra Forjado
- Yens ficam pendentes até o saque; cristais são transferidos diretamente do comprador para o vendedor
- Permissões: anunciar e gerenciar loja são exclusivas VIP; visitar e comprar são abertas a todos os jogadores; vendedor não compra os próprios itens
- Compra com cristal e com yens agora atômica e à prova de falhas (mensagens de erro distintas)

#### Animações do Ferreiro — Forja de Equipamentos e Fragmentos (19/04/2026)
- **Forja de Equipamentos** — overlay com martelo batendo, brilho laranja pulsante, faíscas e texto "Forjando equipamento..."; resultado em verde (sucesso) ou vermelho (falha) com fechamento automático
- **Forja de Fragmentos** — overlay tela cheia com imagem de doujutsu pulsante, três imagens orbitando (Byakugan, Rinnegan e Sharingan se alternando), anel dourado pulsante e texto animado
- Sucesso na forja de fragmentos: imagem real do equipamento forjado com raios e partículas douradas explodindo
- Falha: ícone 💔 com mensagem sobre os fragmentos destruídos
- Velocidades das animações ajustadas para que cada elemento seja visto com clareza

#### Sistema de Cristais de Buff — Reorganização Completa (22/04/2026)

**Organização e navegação**
- Cristais de Buff ganharam uma área própria, separada do inventário comum
- Navegação por abas unificada entre Itens, Cristais de Refinamento e Cristais de Buff
- "Cristais de Refinamento" passou a ser o título oficial (antes "Pergaminhos")

**Padronização visual**
- Botões padronizados com o mesmo fundo do restante do site
- Linhas de tabela com cores consistentes e hover sutil
- Removidos gradientes inline e emojis decorativos dos botões de ação

**Administração de Cristais de Buff**
- Nova área no painel administrativo para enviar Cristais de Buff a jogadores, com listagem por jogador e validação no envio

**Inventário da home — Cristais como abas reais**
- Cristais de Refinamento e Cristais de Buff agora são abas inline no inventário, carregando dinamicamente como as demais categorias
- Cada cristal aparece como card com imagem, nome e selo dourado de quantidade
- Fragmentos de Buff têm aparência acinzentada e marcador "FRAG" para diferenciá-los dos cristais completos
- **Clique em Cristal de Refinamento** abre o Ferreiro
- **Clique em Cristal de Buff completo** pede confirmação e ativa o buff imediatamente
- **Clique em Fragmento de Buff** abre a área de Cristais de Buff para combinação

**Forja**
- A aba de Cristais do Ferreiro mostra apenas Cristais de Refinamento; Cristais de Buff não aparecem por lá

**Indicador de Buff Ativo (limpo, sem banner)**
- Removido o banner grande de Buff Ativo que ocupava espaço e sobrepunha conteúdo importante
- Pequeno ícone de Buff aparece ao lado do ícone de status apenas na linha do atributo afetado, com brilho dourado, sinalizando o buff ativo
- O bônus entra no contador verde de "Meus Atributos" ao lado do total
- Ao passar o mouse no ícone de status, o painel detalhado mostra uma nova linha "Cristal de Buff" com o valor, a porcentagem e a contagem regressiva até a expiração
- Fórmula idêntica à utilizada em combate, aplicada após os demais bônus

#### Sistema de Tickets de Suporte — Fase 1 (22/04/2026)

**Substituição do LiveZilla (descontinuado) por sistema interno de tickets.**

**Para o jogador (`?p=support`)**
- Aba "Meus Tickets" lista os tickets do jogador com status colorido (Aberto / Em atendimento / Resolvido / Fechado), indicador de mensagens não lidas, e badge VIP quando aplicável
- Aba "Abrir Novo Ticket" com seleção de categoria (Bug, Conta, Pagamento/VIP, Denúncia, Sugestão, Outros), assunto e mensagem
- Visualização do ticket em formato de conversa (jogador à esquerda, equipe à direita)
- Possibilidade de responder ao ticket ou fechá-lo manualmente
- VIPs entram com prioridade automática (campo `prioridade`)

**Para o staff (Painel ADM → 🎫 Suporte / Tickets)**
- Listagem com filtros por status (Ativos / Aberto / Atendimento / Resolvido / Fechado / Todos) e por categoria
- Tickets pendentes destacados; ordenação por prioridade VIP, depois não-lidos pelo staff, depois data de atualização
- Detalhe do ticket mostra dados do jogador automaticamente: nome, ID, nível, vila, servidor, status VIP, último IP, atendente atribuído
- Resposta do staff muda status automaticamente de "Aberto" para "Em atendimento" e atribui o atendente
- Staff pode mudar status manualmente (Aberto / Em atendimento / Resolvido / Fechado)
- Badge vermelho com contador no menu admin mostrando tickets pendentes (não lidos pelo staff e ainda ativos)
- Permissão "🎫 Suporte / Tickets" integrada ao sistema de Permissões GM existente

**Banco de dados (migração automática em `_inc/conexao.php`)**
- `tickets` (id, usuario_id, servidor_id, categoria, assunto, status, prioridade, atendente_id, criado_em, atualizado_em, nao_lido_player, nao_lido_staff)
- `ticket_mensagens` (id, ticket_id, autor_id, autor_tipo, mensagem, criado_em)
- Índices em `usuario_id`, `status` e `ticket_id`

**Ajustes pós-lançamento**
- Identidade do staff oculta do jogador: respostas aparecem como "🛡️ Equipe de Suporte", sem expor o nome do ADM/GM
- Status badge "Em atendimento" encurtado para "🔵 Atendendo" (com `white-space:nowrap`) para caber na coluna da listagem do jogador
- No painel admin, o seletor de status virou um form independente que **muda automaticamente ao selecionar** (sem precisar enviar resposta)
- Notificação no menu lateral do jogador: ao receber resposta ou mudança no ticket, aparece `_img/important.png` com animação de pulso e texto "X ticket(s) atualizado(s)!" — segue a mesma base das notificações de mensagens novas e leva direto para `?p=support`

#### Badge VIP — Substituição do contorno amarelo (22/04/2026)

- Removido o contorno amarelo dourado dos avatares VIP em todo o site (menu lateral, ranking e página de visualização de jogador `?p=view&view=...`)
- Removida a faixa "★ VIP ★" gradient abaixo do avatar no menu lateral
- Adicionado novo badge `_img/vip.png` (32x32) sobreposto no canto superior esquerdo do avatar, discreto mas visível, com sombrinha sutil (drop-shadow) para destaque sobre fundos claros
- Tamanho do badge ajustado por contexto: 26px no menu lateral e na página de visualização, 18px no ranking
- Tooltip "VIP" mantido ao passar o mouse sobre o badge

---

#### Correção página Jutsus — não exibia jutsus aprendidos (22/04/2026)
- A página `?p=jutsus` exibia "Nenhum jutsu aprendido" mesmo para contas com jutsus.
- Causa raiz: o `try` agrupava a consulta de jutsus e a consulta da tabela legada `natureza` (que não existe no SQLite). A `PDOException` da segunda query caía no `catch` e zerava `$dbj`/`$jutsus_results`.
- Correção em `_inc/jutsus.php`: separadas as duas consultas em `try/catch` independentes; loop reescrito com `fetchAll` + `foreach` (mesmo padrão de `attack.php`), com `htmlspecialchars` nos nomes de jutsu para segurança.

#### Limpeza e organização do repositório (22/04/2026)
Foi feita uma faxina geral pra reduzir lixo acumulado e arquivos legados.

**Fase 1 — Lixo confirmado (deletado):**
- `_support/` inteiro (LiveZilla antigo, já substituído pelo novo sistema de tickets) — ~1.9 MB
- 9 scripts one-shot do root: `setup_database.php`, `setup_mapas.php`, `setup_colunas_mundo.php`, `limpar_icones_mapas.php`, `limpar_referencias_obsoletas.php`, `resetar_posicoes_mapas.php`, `test_inventory.php`, `test_inventory2.php`, `teste_sessao.php`
- Gateway de pagamento legado: `pagseguro.php`, `pagseguro2.php`, `pgs.php`
- `_notes/` (1 arquivo `.mno` antigo)
- Resíduos de chats antigos em `attached_assets/` (`Pasted-*.txt`, `Chat_*.txt`, `forum_*.txt`, `Modelo_*.php`, `blacksmith_*.php`, `Sem título_*.png`) — preservada subpasta `generated_images/`

**Fase 2 — PHPs órfãos (deletado, validado por análise estática):**
- Root: `ajax_outros_jogadores.php`, `cron_parceria_diario.php`, `parceria.php`, `search_atk.php`, `server.php`, `smtp.class.php`
- `_inc/`: `batalha.php`, `check_nivel.php`, `cron_energia.php`, `cron_manutencao.php` (continha **credenciais MySQL hardcoded** — remoção também por segurança), `cron_missoes.php`, `cron_otimize.php`, `cron_otimizar.php`, `funcoes_mapa.php`, `historico_bans.php`, `include.php`, `messages_main.php`, `missions_e.php`, `pagseguro.php`, `pie3D.php`, `placesorg.php`, `psteste.php`, `retorno.php`, `search_newmsg.php`, `serial.php`, `teste.php`, `twitter.php`, `update.php`, `upload.php`, `upload_imagem.php`, `verificar_fair.php`
- *(Nota: `_inc/reports1.php` e `_inc/reports2.php` foram inicialmente removidos por engano — eram carregados dinamicamente por `_inc/reports.php` via `require_once('reports'.$type.'.php')` e foram restaurados.)*
- `adm/`: `criar_contas_teste.php`, `criar_contas_teste_mapa.php`, `database_admin.php`
- `_nh/` inteiro (versão antiga do site, único reference era do `cron_manutencao.php` também removido)

Total: **287 → 231 PHPs (-56 arquivos)**.

**Fase 3 — Imagens órfãs (deletado):**
- 15 imagens no root `_img/` sem nenhuma referência no código: `anuncie.jpg`, `ball_off.png`, `ball_on.png`, `button.jpg`, `contact.jpg`, `fundo_captcha.jpg`, `help.png`, `kageicon.jpg`, `logo1.jpg`, `mdn.jpg`, `pontos.jpg`, `preparing.jpg`, `twitter.png`, `v2.jpg`, `v2_soon.jpg`
- `_img/uploads/` (referenciada apenas por `_inc/upload.php` e `_inc/upload_imagem.php`, ambos removidos)
- `_img/Mapa Clan/` (sem referência)

Total: **1956 → 1933 imagens (33 MB → 32 MB)**.

**Pastas de imagens preservadas integralmente** porque o código as referencia dinamicamente por id/nome (ex.: `_img/jutsus/{id}.jpg`, `_img/personagens/{nome}/{n}.jpg`, `_img/equipamentos/{tipo}/{nome}.png`, etc.). Renomear ou reorganizar essas pastas exigiria uma migração coordenada com o banco de dados — não foi feito por segurança.

### Segurança (23/04/2026) — Correção de SQL Injection

Auditoria do shim `_inc/mysql_compat.php` (camada de compatibilidade que faz `mysql_*` antigos rodarem sobre PDO). Embora o shim já use `prepare()` internamente, **123 chamadas `mysql_*`** no código continuavam concatenando variáveis direto na string SQL — o `prepare` posterior não ajuda nesse caso.

Foram identificadas e corrigidas **6 vulnerabilidades críticas** com input externo (`$_GET`/`$_POST`) concatenado em consultas:

| Arquivo | Tipo de injection |
|---------|-------------------|
| `poll.php` | numérica em `WHERE id=$_GET[id]` |
| `pollresult.php` | numérica em `WHERE id=$_GET[id]` |
| `_inc/quests.php` (3 queries) | numérica em `id=$_GET[start]` e nos UPDATEs |
| `_inc/recover.php` | string em `usuario='$_POST[rec_usuario]' AND email='$_POST[rec_email]'` |
| `_inc/polls.php` | **column-name injection** em `SET resp_$_POST[enq_resposta]=...` (mais grave — permitiria sobrescrever qualquer coluna) |

Todos os casos foram migrados para **PDO prepared statements** com parâmetros vinculados (`?` + `execute([...])`). No `polls.php` adicionei whitelist `['a','b','c','d','e']` para o nome de coluna, que não pode ser parametrizado em SQL.

As demais ~117 chamadas `mysql_*` continuam usando o shim — funcionam corretamente e não recebem input externo concatenado, então não são vetor de SQL injection. Migração completa para PDO nativo pode ser feita gradualmente no futuro.

#### Sistema de Denúncias de Apresentação (23/04/2026)

Sistema completo de moderação para denúncias da apresentação (`config_apresentacao`) dos perfis dos jogadores.

**Correções e regras (`_inc/spam.php`):**
- Corrigido erro fatal `'break' not in the 'loop' or 'switch' context` (PHP 8.2) — `break` substituído por `return` (arquivo é incluído via `index.php`).
- Múltiplas denúncias contra o mesmo alvo permitidas, **desde que sejam de informantes diferentes**.
- Mesma pessoa só pode denunciar o mesmo jogador novamente após **24 horas** — alerta mostra horas/minutos restantes.

**Novo módulo `🚨 Denúncias` no painel admin (`adm/adm.php`):**
- Link na seção "Ferramentas de Administração" com contador vermelho (total de denúncias abertas).
- **Visão Agrupada por Alvo** (padrão): jogador denunciado, total de denúncias, denunciantes únicos, data da última.
- **Visão Lista Completa**: todas as denúncias com mensagem reportada (preview renderizado + código HTML/texto em `<details>`).
- Filtro por nome de alvo ou informante e paginação.

**Ações disponíveis em cada linha (com `confirm()`):**
- 🔍 **Perfil** — abre o perfil do denunciado em nova aba.
- 🧹 **Apagar Aps.** — limpa `config_apresentacao` do alvo.
- ✉️ **Avisar** — envia mensagem privada de aviso ao denunciado.
- 📨 **Penalizado** — resposta automática em massa para todos os denunciantes do alvo: *"Obrigado pela denúncia, o jogador foi penalizado..."*
- 📨 **Sem irregular.** — resposta automática em massa: *"Obrigado pela denúncia, ao olhar o perfil não achamos nada que quebre as regras..."*
- 🗑️ **Excluir / Limpar denúncias** — apaga uma denúncia específica ou todas contra um alvo.
- Todas as ações registradas em `admin_logs`.

**Mensagens automáticas impessoais:**
- Enviadas com `origem=0` (sistema) — sem nome de ADM/GM exposto.
- Assunto prefixado com `[Automática]` e corpo iniciado com aviso *"⚠️ Esta é uma mensagem automática do sistema, não é necessário responder."*
- Botão **Responder** automaticamente desabilitado pelo `messages_view.php` para `origem=0`.

**Visual destacado das mensagens da Administração (`_inc/messages_r.php` e `_inc/messages_view.php`):**
- Remetente exibido como `_img/minilogo.jpg` + texto **ADMINISTRAÇÃO** em vermelho com contorno preto.
- Assunto em vermelho com contorno preto para maior visibilidade.
- Aplicado tanto na lista da caixa de entrada quanto na visualização individual da mensagem.

#### Integração com X (Twitter) e YouTube nos Perfis (23/04/2026)

Modernização das redes sociais exibidas nos perfis dos jogadores e nova vitrine de criadores na home.

**Twitter/X (`_inc/view_twitter.php`):**
- Substituído o widget legado `widgets.twimg.com/j/2/widget.js` (descontinuado pelo X em 2013) pelo embed atual `platform.twitter.com/widgets.js`.
- Tema escuro, altura fixa de 500px, sem cabeçalho/rodapé.
- Sanitização do nome de usuário (apenas `A-Z`, `0-9`, `_`) protegendo contra XSS.
- Botão "@usuario ↗" sempre clicável, levando ao perfil no X.
- Aviso amigável quando o X bloqueia o embed (perfis privados, suspensos ou usuário deslogado — limitação do próprio X desde 2023).

**YouTube — nova integração:**
- Novas colunas `config_youtube` e `config_okyoutube` em `usuarios` (migração automática em `_inc/conexao.php`).
- Nova seção **YouTube** em **Configurações → Conexões** (`_inc/config_conn.php`):
  - Campo aceita URL completa do canal, `@handle` ou Channel ID puro.
  - Parser extrai automaticamente o identificador de URLs (`/channel/`, `/user/`, `/c/`, `/@`).
  - Checkbox de autorização independente.
- Novo arquivo `_inc/view_youtube.php` exibe os últimos vídeos no perfil:
  - Embed responsivo 16:9 da playlist de uploads do canal (Channel ID `UC...` → playlist `UU...`).
  - Usa `youtube-nocookie.com` para mais privacidade.
  - Botão vermelho "▶ Visitar canal ↗" sempre disponível.
  - Lazy loading do iframe.
- Bloco incluído em `_inc/view.php` apenas quando o jogador autorizou.

**▶ Top Criadores de Conteúdo (home — `_inc/home.php`):**
- Novo bloco logo após o nLink, listando os **3 melhores criadores** marcados como Criador no painel admin.
- Ordenado por nível, desempate por yens faturados.
- Pódio com medalhas 🥇🥈🥉, link para o perfil do jogador e botão direto para visitar o canal no YouTube.
- Bloco **só aparece se houver pelo menos 1 criador** cadastrado.
- **Recolhível** (mesmo padrão de "Meu Inventário" / "Meus Equipamentos") via `nhToggle('bloco-criadores')`.

**Cartão e galeria de vídeos no perfil (`_inc/view_youtube.php`):**
- Cartão estilizado acima do player com nome real do canal e thumbnail do último vídeo.
- Dados obtidos via **feed RSS público** do YouTube (sem necessidade de chave de API), com cache de 1 hora em `_cache/yt_*.json`.
- Galeria **📺 Vídeos Recentes** com os 5 últimos vídeos como miniaturas clicáveis (grid responsivo, hover vermelho, lazy loading).
- Iframe player usa **YouTube IFrame API** com `onError` para esconder o player quando o vídeo está indisponível (evita a tela "Este vídeo não está disponível").

#### Sistema de Criadores de Conteúdo e Parcerias (23/04/2026)

Programa oficial de Criadores com badge no perfil, vitrine na home e sistema de presentes administrado por ADM.

**Banco de dados:**
- Nova coluna `criador_conteudo INTEGER DEFAULT 0` em `usuarios` (migração automática em `_inc/conexao.php`).

**Badge no perfil (`_inc/view.php`):**
- Quando `criador_conteudo = 1`, aparece logo abaixo do avatar uma faixa em gradiente vermelho com glow:
  **🎬 CRIADOR DE CONTEÚDO**.
- Bordas arredondadas, contorno e sombra para chamar atenção.

**Filtro do Top Criadores:**
- O bloco da home agora exige `criador_conteudo = 1` **além** de ter YouTube autorizado — apenas criadores oficiais aparecem no pódio.

**Novo módulo `🎬 Criadores` no painel admin (`adm/adm.php`):**
- Link na seção "Ferramentas de Administração" com contador de criadores ativos (badge vermelho).
- **Apenas administradores (ADM=1)** têm acesso.
- Duas seções principais:

**1. Gerenciar Criadores:**
- Busca por nome para listar jogadores e ver status atual.
- Botão **➕ Tornar Criador** ou **➖ Remover** (com confirmação).
- Listagem dos criadores ativos com status do canal do YouTube.
- Toda ação de promoção/remoção dispara mensagem automática (`origem=0`) ao jogador.

**2. Enviar Presente de Parceria:**
- **Select de destinatário** — apenas criadores ativos aparecem.
- **Select de tipo** com optgroups:
  - 💰 **Yens** (atualiza `usuarios.yens` direto).
  - 💎 **Cristais de Buff** — Tai/Nin/Gen (categoria `cristal_buff`).
  - ⚒️ **Cristais de Aprimoramento** — Refinado, Bruto, Forjado (categoria `cristal`).
- **Quantidade** (1 a 1000) — itens são inseridos em `usaveis` um por um.
- Lista de itens carregada dinamicamente de `table_usaveis` (futuras categorias adicionadas automaticamente).
- Mensagem automática `🎁 Presente de Parceria recebido!` enviada ao destinatário descrevendo o presente.
- Todas as ações (promoção, remoção, presente) registradas em `admin_logs`.

#### Sistema de Criadores — Links de Referência, Logs e Presentes em Massa (23/04/2026)

Expansão do programa de Criadores de Conteúdo: links personalizados, logs de cadastros, envio em lote e UI revisada no painel admin.

**Banco de dados (migrações automáticas em `_inc/conexao.php`):**
- Nova coluna `ref_link VARCHAR(60)` em `usuarios` — slug único gerado a partir do nome do criador.
- Nova tabela `criador_refs (id, criador_id, novo_usuario_id, novo_usuario_nome, data)` com índice por `criador_id`.

**Link de Referência personalizado:**
- Ao promover um jogador a Criador (ou ao abrir o módulo pela 1ª vez), o sistema gera automaticamente um slug único (`cri_gerar_ref_link()` em `adm/adm.php`) e grava em `usuarios.ref_link`.
- O bloco da home (`_inc/home.php`) mostra ao próprio criador um campo destacado com a URL `?p=reg&nlink=<ref_link>` para divulgar.
- O cadastro (`_inc/reg.php`) aceita tanto `nlink=usuario` quanto `nlink=ref_link`, dá 100 yens de bônus para o indicador e registra a referência em `criador_refs`.

**Logs de Referrals por Criador (botão 📊 Logs):**
- Tela com total de cadastros, agrupamento por dia, lista de jogadores indicados e contagem.
- Apagar log por data com regra de retenção: **só permite excluir entradas com mais de 30 dias** (mostra o limite atual e bloqueia datas recentes).
- Toda exclusão é registrada em `admin_logs` (ação `Limpar Log Refs Criador`).

**Tabela de Criadores Ativos (módulo Criadores):**
- Mostra Jogador, Nível, status do YouTube, **Link de Referência** (campo selecionável) e botões de ação:
  - 🔗 **Link** — copia a URL completa para a área de transferência.
  - 📊 **Logs** — abre o histórico de referrals daquele criador.
  - ➖ **Remover** — revoga o status (com confirmação).

**Formulário de Presente — 3 modos via abas:**
- 👤 **Criador específico** — dropdown com todos os criadores ativos.
- 🔍 **Buscar por nome** — campo livre que casa com `LOWER(usuario) LIKE`, útil para premiar quem se destacou.
- 📢 **Enviar para TODOS** — atinge todos os criadores ativos em uma só ação. Nesta aba o select único é substituído por uma **grade de checkboxes com múltiplos itens** (Yens + cada Cristal de Buff/Aprimoramento), com botões "✓ Marcar todos" / "✗ Desmarcar". Cada item selecionado é entregue para cada criador, com mensagem própria.
- Hidden field `modo_envio` controla o fluxo no handler; campos `tipos[]` (multi) e `tipo` (único) coexistem sem conflito.
- Mensagem de sucesso resume tipos × criadores × total de entregas.

**Mensagem em anexo (notificação no inbox):**
- A função `cri_entregar_presente()` envia uma mensagem detalhando a parceria + o presente recebido para a caixa de entrada do criador.
- **Bugfix (23/04/2026):** as mensagens estavam sendo inseridas com `status='nao'`, mas o restante do sistema (`menu_on.php`, `messages_r.php`, `messages_view.php`) usa `status='naolido'` como flag de "não lida". Por isso o sininho/contador não aparecia. Corrigido para `'naolido'` — agora o criador recebe a notificação visível imediatamente.

**Acessibilidade visual:**
- Todos os textos antes em vermelho (`#e74c3c`, `#FF4444`, `#FF6666`, `#ff4444`, `#ff6666`) no painel ADM foram trocados para **branco (`#fff`)**, mantendo bordas e fundos vermelhos para preservar o aspecto de aviso. Melhora a leitura sobre os fundos escuros.

**Reposicionamento do badge no perfil:**
- A faixa **🎬 CRIADOR DE CONTEÚDO** em `_inc/view.php` foi movida para baixo do avatar (antes ficava ao lado), em um wrapper centralizado de largura total.

## Aviso Legal

Este é um projeto de fã baseado no universo de Naruto. Naruto é propriedade de Masashi Kishimoto e suas respectivas empresas licenciadoras.
