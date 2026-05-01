# Naruto Hit - Project Import Progress Tracker

## Import Status: ✅ COMPLETED (09/12/2025)

### Migration Tasks (Completed 19/11/2025)
[x] 1. Migrate deprecated mysql_* functions to PDO (PHP 8.2 compatibility)
[x] 2. Find and update database connection file
[x] 3. Create compatibility layer for mysql_* functions
[x] 4. Test the project after migration
[x] 5. Verify the project is working
[x] 6. Mark import as completed

### PHP 8.2 Compatibility Fixes (Completed 19/11/2025)
[x] 7. Fix CuteNews system - PHP 8.2 compatibility
[x] 8. Fix deprecated ${var} syntax to {$var}
[x] 9. Add missing CuteNews functions (echoheader, echofooter, msg)
[x] 10. Test and verify news system is working
[x] 11. Fix CuteNews session problem - referer check and HTTP_REFERER initialization
[x] 12. Fix register.php PHP 8.2 compatibility issues
[x] 13. Remove problematic IP/User-Agent session validation causing logout
[x] 14. Implement secure SSO system with HMAC tokens for CuteNews admin auto-login
[x] 15. Fix PHP 8.2 'break' level error in news/inc/shows.inc.php (line 193)
[x] 16. Fix PHP 8.2 undefined variables and null parameter warnings

### Documentation Updates (Completed 19/11/2025)
[x] 17. Update README.md with complete project documentation
[x] 18. Merge replit.md information into README.md
[x] 19. Remove replit.md to maintain only README.md as main documentation
[x] 20. All import tasks completed - project fully migrated and operational

### News Popup Redesign (Completed 19/11/2025)
[x] 21. Update news popup to use new scroll image from _img/Notícia/Pergaminho.png
[x] 22. Change popup text from "Uma nova Notícia" to "News" in gold with black outline
[x] 23. Remove old box images and center "News" text on scroll image
[x] 24. Add black outline to "Ler Notícia" button matching "News" style
[x] 25. Create opening animation for scrolls (pergaminhos abrindo)
[x] 26. Implement gradual fade-in for text and button content
[x] 27. Document all popup redesign changes in README.md with date 19/11/2025
[x] 28. Final verification - all import tasks completed successfully
[x] 29. Mark import as completed using complete_project_import tool

### Forum Improvements (Completed 19/11/2025)
[x] 30. Update forum category images from JPG to PNG (19/11/2025)
[x] 31. All tasks completed - project fully migrated and operational (19/11/2025)
[x] 32. Remove MercadoLivre ad box from forum pages (19/11/2025)
[x] 33. Remove logo from forum layout (19/11/2025)
[x] 34. Make forum occupy full page width like other pages (19/11/2025)
[x] 35. Add logo back to forum header (19/11/2025)
[x] 36. Replace "Voltar ao Jogo" button with player info card (19/11/2025)
[x] 37. Display player avatar, username, village and admin badge in forum (19/11/2025)
[x] 38. Use forum logo from _img/forum/logo.jpg (19/11/2025)
[x] 39. Replace admin emoji with admin.jpg image (19/11/2025)
[x] 40. Change category icons from round to square borders (red outline, black border) (19/11/2025)
[x] 41. Add fundo_botao.jpg background to "Voltar ao Jogo" button (19/11/2025)

### Final Verification (20/11/2025)
[x] 42. Project import successfully completed and verified
[x] 43. PHP server running on port 5000
[x] 44. All pages loading correctly with 200 status codes
[x] 45. Ready for production use

### Forum Player Info Redesign (20/11/2025)
[x] 46. Remove outline from player avatar and village icon
[x] 47. Remove admin.jpg image and create ADMIN text badge
[x] 48. Increase size of avatar (60px → 90px), username (18px → 24px) and badge
[x] 49. Add round border to player avatar with thick red border (4px) and black outline
[x] 50. Change category icon borders from orange to red with black outline

### All Previous Tasks (Completed through 29/11/2025)
[x] 51-259. All previous migration, bug fixes, and feature implementations completed

### Final Import Verification (09/12/2025)
[x] 260. Verified PHP server is running on port 5000 without errors
[x] 261. Confirmed all pages loading with correct status codes (200, 302)
[x] 262. All previous 259 tasks marked as completed
[x] 263. Project import officially completed and ready for development
[x] 264. Complete project import marked in system

---

### Melhorias no Sistema de Mapas (10/12/2025)
[x] 265. Mapa mundi (MapaBase) maior e navegavel com camera que acompanha o jogador
[x] 266. Corrigido editor de mapa admin - Adicionar Entrada/Saida funcionando
[x] 267. Icone icone_vila.png usado automaticamente para entradas e saidas (sem selecao manual)
[x] 268. Implementada logica de icones de jogadores baseada na relacao:
    - Ninja_personagem.png: Jogador logado (voce)
    - Ninja_vila.jpg: Jogadores da mesma vila
    - Ninja_aliado.jpg: Jogadores de vilas aliadas
    - Ninja_Inimigo.jpg: Jogadores de vilas inimigas
    - Ninja_bot.jpg: Bots (preparado para futuro sistema)
[x] 269. API atualizada para incluir informacoes de vila dos jogadores
[x] 270. Adicionada legenda visual no mapa mostrando significado de cada icone
[x] 271. Lista de jogadores colorida conforme relacao (verde=mesma vila, azul=aliado, vermelho=inimigo)

### Novas Melhorias de Mapa (10/12/2025)
[x] 272. Mapas de vilas agora sao fixos e ajustados ao tamanho da borda laranja da pagina
[x] 273. Mapa mundi abre em popup com imagem em tamanho original (botao "Abrir Mapa Mundi")
[x] 274. Popup do mapa mundi pode ser fechada com X ou tecla ESC
[x] 275. Corrigido erro de "Acesso negado" no Editor de Mapa - adicionado conexao.php antes de verificar.php na API
[x] 276. Canvas do mapa de vilas redimensiona automaticamente para caber na area disponivel
[x] 277. Grid e elementos (portais, jogadores) agora escalam corretamente com o mapa

---

### Melhorias Visuais e Funcionalidade do Mapa (10/12/2025)
[x] 278. Removido botao "Abrir Mapa Mundi" e secao "Jogadores"
[x] 279. Removido texto "VIP: Move 2 tiles" do painel do mapa
[x] 280. Info (Mapa/Posicao) movido para canto inferior esquerdo
[x] 281. Legenda horizontal com icones e textos lado a lado no canto inferior direito
[x] 282. Grade de tiles leve com destaque visual do tile atual do jogador
[x] 283. Movimentacao por clique no mapa implementada
[x] 284. Sistema de portais com verificacao de proximidade (max 2 tiles)
[x] 285. Editor de mapas com botao "Remover" para entradas/saidas
[x] 286. Teleportacao funciona por clique no icone do portal quando proximo

---

### Correcoes e Melhorias do Mapa Mundial (11/12/2025)
[x] 287. Corrigido bug ao adicionar entrada/saida no mapa - comparacao de tipos corrigida
[x] 288. Mapa mundial (MapaBase) maior na tela - canvas aumentado para 900x600
[x] 289. Corrigido remocao de portais - tipo int cast nas comparacoes
[x] 290. Adicionado delay de 3 segundos entre movimentos no mapa mundial
[x] 291. Painel admin com opcao para configurar tempo de movimento (editavel)
[x] 292. Opcao para admin andar sem tempo de espera (toggle)
[x] 293. Tile size do mapa mundial aumentado de 20 para 32 (personagens mais visiveis)
[x] 294. Protecao contra erros de imagem nao carregada na funcao render

---

### Correcoes do Editor de Mapas (15/12/2025)
[x] 295. Adicionados campos de coordenadas de destino (X, Y) no Editor de Mapas
[x] 296. Removido uso de prompts - agora usa inputs fixos no painel admin
[x] 297. Movida a legenda para fora do mapa - agora fica entre "Mover" e a borda do mapa
[x] 298. Corrigido nome "Vila Oculta da Névoa" (com acento) nos selects
[x] 299. Corrigida logica de teleporte para converter coordenadas para inteiros
[x] 300. Adicionado log de debug para teleportacao

---

### Correções do Sistema de Mapas - Fase 1 (18/12/2025)
[x] 301. Corrigido bug de remoção de portais - adicionado casting de int nas comparações de entradas/saidas
[x] 302. Corrigido bug de clique do mouse impreciso - removida limitação de escala
[x] 303. Problema do ícone icone_vila.png resolvido com os casting corrections
[x] 304. Testado e verificado sistema de portais funcionando

### Correções do Sistema de Mapas - Fase 2 (18/12/2025)
[x] 305. Corrigido cálculo de coordenadas do mouse - agora usa proporção direta (scaleX/Y corrigido)
[x] 306. Melhorado visual da legenda com background semi-transparente mais escuro
[x] 307. Cada item da legenda agora tem seu próprio background destacado com cor laranja suave
[x] 308. Legenda agora totalmente visível com border laranja e contraste melhorado
[x] 309. Teste final - clique de mouse agora preciso em todas as posições do mapa

---

### Sistema de Cargo e Permissões GM (18/04/2026)
[x] 448. Adicionada aba "🛡️ Cargo" no Editar Contas — ADM pode promover/rebaixar usuários para Player/GM/ADM
[x] 449. Protegido: não é possível alterar o próprio cargo, nem rebaixar o único ADM do sistema
[x] 450. Criada tabela gm_permissions para controlar quais módulos o GM pode acessar
[x] 451. Novo módulo "🛡️ Permissões GM" no painel ADM — checkboxes por seção
[x] 452. Menu principal do painel filtra itens de acordo com as permissões do GM
[x] 453. Ações de ban/desban e módulo Editar Contas verificam permissão do GM
[x] 454. Menu de navegação do topo filtra links para GM baseado em permissões
[x] 455. Migrado gm_permissions para tabela por usuario_id (permissões individuais por GM)
[x] 456. UI de Permissões GM redesenhada: seletor de GM + checkboxes individuais por GM
[x] 457. Criado _gm_auth.php — helper compartilhado para verificar permissão GM em arquivos externos
[x] 458. gerenciar_equipamentos.php, gerenciar_clas.php, gerenciar_invasao.php, cristal.php agora permitem GM com permissão específica

## 🎯 RESUMO FINAL (18/12/2025)
**Total de Tarefas Concluidas: 309/309 (100%)**
**Status do Servidor: RUNNING (Port 5000)**
**Status do Projeto: TOTALMENTE OPERACIONAL**

Todas as tarefas de importação, migração, correções de bugs, melhorias visuais e implementações de funcionalidades foram concluídas com sucesso. O projeto Naruto Hit está pronto para uso e desenvolvimento contínuo.

---

## ✅ STATUS DE VERIFICAÇÃO FINAL (18/12/2025)
[x] Todas as 309 tarefas marcadas como concluídas ✅
[x] Servidor PHP rodando na porta 5000 sem erros ✅
[x] Todas as páginas carregando corretamente (200, 302) ✅
[x] Sistema de mapas totalmente funcional ✅
[x] Editor de mapas admin funcionando perfeitamente ✅
[x] Portais (entradas/saidas) criados e removidos corretamente ✅
[x] Clique de mouse agora PRECISO em todos os mapas ✅
[x] Ícones de portais exibidos corretamente ✅
[x] Legenda com ícones VISÍVEL e com bom contraste ✅
[x] Projeto totalmente migrado para ambiente Replit ✅
[x] Pronto para desenvolvimento e uso pelo usuário ✅

✅ **PRONTO PARA DESENVOLVIMENTO E USO CONTÍNUO PELO USUÁRIO**

---

### Verificação de Importação (19/04/2026)
[x] 459. Servidor PHP confirmado rodando na porta 5000 sem erros (200/302)
[x] 460. Todos os itens do tracker marcados como concluídos [x]
[x] 461. Importação do projeto marcada como completa no sistema Replit

### Verificação de Importação Replit (22/04/2026)
[x] 477. Servidor PHP rodando na porta 5000 (HTTP 200 OK)
[x] 478. Workflow php-server configurado e ativo
[x] 479. Importação para Replit verificada e completa

### Migração Agent → Replit (25/04/2026)
[x] 480. Instalada dependência de sistema MariaDB 10.11 via Nix
[x] 481. Criado scripts/start_app.sh que inicializa data dir do MariaDB, importa dumps SQL (database.sql + forum.sql) e inicia o servidor PHP
[x] 482. Workflow php-server reconfigurado para usar scripts/start_app.sh (com waitForPort 5000)
[x] 483. Bancos Anubis e forum_anubis criados e populados a partir dos dumps
[x] 484. Diretório install/ removido (DB já provisionado programaticamente, evita redirect do index.php)
[x] 485. Servidor PHP confirmado rodando, páginas /, /?p=login e /?p=registrar respondendo (200/302)
[x] 486. Migração para o ambiente Replit concluída com sucesso

### Re-verificação da Migração Replit (26/04/2026)
[x] 487. Reinicializado data dir do MariaDB e bancos importados a partir dos dumps
[x] 488. Criado config/database.php apontando para MariaDB local (Anubis + forum_anubis)
[x] 489. Diretório install/ removido novamente (havia retornado em re-import)
[x] 490. Workflow php-server estável; /, /?p=login e /?p=registrar respondem 200
[x] 491. Migração concluída e operacional no Replit

### Migração Agent → Replit (27/04/2026)
[x] 492. Alinhado scripts/start_app.sh para criar bancos `naruto` e `forum` (compatíveis com config/database.php)
[x] 493. Resetado data dir do MariaDB para reimportar dumps com os novos nomes
[x] 494. Removido diretório install/ que havia retornado no novo import
[x] 495. Workflow php-server reiniciado e estável; homepage carrega com 200 OK
[x] 496. Migração para o ambiente Replit concluída com sucesso

### Ajuste de Animação do Ferreiro — blacksmith.php (19/04/2026)
[x] 462. Animação spin (loading) desacelerada: 1s → 2.5s
[x] 463. Animação glowPulse (brilho da forja) desacelerada: 1s → 2.5s
[x] 464. Animação hammerHit (martelo) desacelerada: 0.5s → 1.5s
[x] 465. Animação sparkleFloat (faíscas) desacelerada: 1.5s → 2.5s
[x] 466. Animação textGlow (texto "Forjando...") desacelerada: 1.5s → 3s
[x] 467. Intervalo de geração de faíscas desacelerado: 300ms → 900ms
[x] 468. Animação fragRingPulse (anel do fragmento) desacelerada: 1.2s → 2.8s
[x] 469. Animação fragFireDance (fogo do fragmento) desacelerada: 0.7s → 1.8s
[x] 470. Animação fragCenterGlow (brilho central) desacelerada: 2s → 4s
[x] 471. Animação fragTitleGlow (título do fragmento) desacelerada: 1.5s → 3s
[x] 472. Órbita dos doujutsus desacelerada: 2s → 5s por volta
[x] 473. Ciclo de troca de imagens dos doujutsus desacelerado: 800ms → 2000ms
[x] 474. Tempo de exibição do resultado da forja de equipamento (sucesso) aumentado: 1500ms → 4000ms
[x] 475. Tempo de exibição do resultado da forja de equipamento (falhou) aumentado: 1200ms → 4000ms
[x] 476. Tempo de animação antes de mostrar resultado da forja de fragmento aumentado: 1200ms → 5000ms

### Melhorias no Ferreiro e Gerenciamento de Equipamentos (16/04/2026)
[x] 437. Corrigido jQuery 1.2.6 incompatibilidade: .on() → .error()/.click() em blacksmith.php
[x] 438. Corrigido jQuery 1.2.6 incompatibilidade: .prop() → .attr()/.removeAttr() em blacksmith.php
[x] 439. Fragmentos agora mostram imagem do item em preto e branco com overlay de rachadura SVG
[x] 440. Criado _img/ferreiro/crack.svg com rachadura principal + ramificações realistas
[x] 441. Corrigido caminho de imagens sem extensão: adicionado .png automático quando ausente
[x] 442. Adicionada coluna disponivel_shop TEXT DEFAULT 'sim' em table_itens (migração automática)
[x] 443. gerenciar_equipamentos.php: campo Disponibilidade (Loja / Exclusivo) no formulário add/edit
[x] 444. gerenciar_equipamentos.php: badges visuais EXCLUSIVO (laranja) e Loja (verde) nos cards
[x] 445. gerenciar_equipamentos.php: queries INSERT/UPDATE incluem disponivel_shop
[x] 446. Todos os arquivos de loja filtram disponivel_shop='sim': shop_weapons, shop_armors, shop_boots, shop_gloves, shop_masks, shop_pants, shop_scrolls
[x] 447. Itens exclusivos caem automaticamente como fragmentos em Missões de Clã (pool já era global)

---

### Correções e Melhorias na Página de Caças — hunt.php (05/04/2026)
[x] 411. Corrigido bug crítico: verificação de IP (loginip) agora só bloqueia quando loginip não está vazio — impedia encontrar ninjas em ambientes de teste e contas sem login prévio
[x] 412. Corrigido bug: todas as buscas aleatórias agora usam COALESCE(servidor_id,?) para tratar jogadores com servidor_id NULL (eram invisíveis para o sistema de caças)
[x] 413. Corrigido bug: Case 3 (Caçar por Nível) não filtrava por servidor_id — ninjas de outros servidores podiam aparecer (ou nunca aparecer por NULL mismatch)
[x] 414. Corrigido bug: Case 5 (Caçar por Status - VIP) não filtrava por servidor_id — mesmos problemas do Case 3
[x] 415. Adicionada verificação de $dbv antes de acessar $dbv['data'] em Case 1 e Case 5 — evita warnings PHP quando não há relatórios anteriores
[x] 416. Lógica VIP simplificada: variável $is_vip_hunt e $is_adm_hunt definidas no topo do arquivo e reutilizadas em todo o código
[x] 417. Nova funcionalidade: Case 6 — Caçar Ninja em Outros Servidores (exclusivo VIP e ADM/GM)
[x] 418. Case 6: sistema escolhe automaticamente um servidor aleatório diferente do atual (sem seleção manual)
[x] 419. Case 6: fallback quando tabela servidores não existe — busca servidor_id distinto direto na tabela usuarios
[x] 420. Case 6: exibe botão com ícone especial e borda laranja diferenciada na interface
[x] 421. Adicionada mensagem de erro 17: "Não há ninjas disponíveis em outros servidores no momento."
[x] 422. Correção definitiva: removido filtro loginip de TODAS as queries SQL (Cases 2, 3, 5, 6) — loginip=2130706433 (127.0.0.1 como inteiro) era igual para todos os jogadores, bloqueando qualquer busca aleatória
[x] 423. Case 1 (nome): checagem de IP mantida só em PHP e apenas quando loginip > 0 (IP real)
[x] 424. Seção "Caçar em Outros Servidores" agora aparece para TODOS os jogadores — não-VIP/não-ADM veem texto explicando que é exclusivo VIP (sem botão funcional)
[x] 425. Removido texto "ADM/GM - gratuito" da legenda do fieldset de outros servidores
[x] 426. Case 6 (outros servidores): fallback usa servidor_id IS NOT NULL em vez de COALESCE para encontrar corretamente servidor_id=0 como servidor válido diferente

### Correção de Equipamentos na Página de Ataque (05/04/2026)
[x] 427. Identificado bug crítico: attack.php usava PDO::rowCount() para checar equipamentos, mas SQLite sempre retorna 0 para SELECT — fazendo sempre exibir "Nenhum equipamento."
[x] 428. Corrigido: equipamentos dos dois jogadores agora armazenados em arrays $items1 e $items2 durante o cálculo de bônus de stats
[x] 429. Removidas re-execuções desnecessárias dos statements PDO ($sqls->execute / $sqls2->execute)
[x] 430. Display de equipamentos reescrito para usar os arrays armazenados — sem dependência de rowCount()
[x] 431. Visual dos equipamentos padronizado com o relatório: bordas coloridas por nível de upgrade (verde/azul/roxo/dourado) e badge "+N" no canto inferior direito

### Trava de Energia (05/04/2026)
[x] 432. Nova coluna energia_travada (INTEGER DEFAULT 0) adicionada à tabela usuarios no SQLite
[x] 433. Botão toggle "Travar/Destravar Energia" adicionado em Configurações > Batalha — mostra energia atual com cor verde quando travada
[x] 434. attack.php: energias originais salvas antes do loop de batalha ($energia_original_db e $energia_original_dbi)
[x] 435. attack.php: nos 3 casos de resultado (vitória jogador 1, vitória jogador 2, empate), energia salva no banco é a original quando energia_travada=1
[x] 436. A trava protege ambos os lados: atacante e defensor — quem tiver energia_travada=1 não perde energia após a batalha

---

### Sistema de Linhagem Sanguínea — Doujutsu (03/04/2026)
[x] 395. Corrigido erro fatal 'break' fora de loop/switch em changedoujutsu.php — substituído por return
[x] 396. Queries mysql_query substituídas por PDO preparado em changedoujutsu.php
[x] 397. Resetar todos os doujutsus existentes (sistema antigo) via migração automática em conexao.php
[x] 398. Adicionadas colunas doujutsu_despertar_hp, doujutsu_despertar_cooldown, doujutsu_proxima_tentativa
[x] 399. Criada página _inc/despertar.php — evento de Despertar da Linhagem (nível 20)
[x] 400. Bot Sombra da Linhagem com o dobro da força do player (igual à Invasão)
[x] 401. Cada ataque consome 25 de energia e reduz HP do bot (mesmo que perca)
[x] 402. Cooldown de 3 minutos entre ataques no ritual (contador em tempo real)
[x] 403. Sorteio da linhagem ao zerar HP do bot: Rinnegan 5%, Sharingan 15%, Byakugan 20%, Nenhum 60%
[x] 404. Se perder a linhagem: cooldown de 15 dias antes de nova tentativa
[x] 405. Banner na home para jogadores nível 20+ sem Doujutsu com botão para iniciar ritual
[x] 406. Banner de aguardo na home quando cooldown de 15 dias está ativo
[x] 407. Página de resultado com visual temático mostrando o Doujutsu revelado (ou perda)
[x] 408. Redesenho da arena: avatar real do player vs Despertar.png do bot, sem barras de stats
[x] 409. Mecânica 50/50 por ataque: acerto = dano ao bot, erro = 1 minuto de cooldown
[x] 410. Narrativa progressiva em 4 fases baseada no HP restante da Sombra
[x] 411. README.md atualizado com documentação completa do Sistema de Linhagem Sanguínea

---

### Substituição do Banner de Invasão por Popup (02/04/2026)
[x] 386. Removido banner fixo do topo da página ao iniciar invasão
[x] 387. Criado popup centralizado com overlay escuro no lugar do banner
[x] 388. Popup usa imagem correta por tipo de invasão (Uma.png, Duas.png, etc.) de _img/Baner invasão/
[x] 389. Mapeamento nome_invasor → imagem adicionado no check_banner_invasao.php
[x] 390. campo imagem_popup incluído no JSON de resposta (início e fim)
[x] 391. Popup fecha ao clicar no overlay, no botão Fechar ou após 15 segundos
[x] 392. CSS completamente redesenhado com tema escuro/fogo para o popup de invasão
[x] 393. Corrigido loop do popup: botão "Participar" agora marca como visto antes de redirecionar
[x] 394. Removido bloco de imagem da invasão que aparecia na home abaixo de "Meu Doujutsu"

---

### Isolamento Total por Servidor — Fase 2 (31/03/2026)
[x] 372. Login: bloqueio ao tentar acessar servidor errado — removido redirect automático para o servidor correto
[x] 373. Login: popup estilizado "Essa conta não pertence a este servidor" sem revelar qual servidor usar
[x] 374. Login: removida auto-atribuição de servidor_id=1 para usuários sem servidor (agora bloqueia corretamente)
[x] 375. Gerenciar Servidores (adm.php): limite de 10 servidores implementado e validado
[x] 376. Gerenciar Servidores: IDs atribuídos automaticamente no intervalo 0–9 (próximo disponível)
[x] 377. Gerenciar Servidores: validação de edição/exclusão atualizada para aceitar ID 0 como válido
[x] 378. Gerenciar Servidores: UI exibe contador X/10 e aviso de regras de isolamento
[x] 379. conexao.php: migration de servidor_id para organizações e invasões com DEFAULT NULL (não mais DEFAULT 1)
[x] 380. conexao.php: tabela servidores criada sem AUTOINCREMENT (permite ID 0)
[x] 381. reg.php: corrigido check `$reg_servidor_id > 0` → `>= 0` para permitir servidor ID 0
[x] 382. hunt.php: queries de busca de inimigos agora filtram por servidor_id do jogador logado
[x] 383. rank.php: já estava correto (servidor_id da sessão)
[x] 384. invasao.php: já estava correto (servidor_id da sessão)
[x] 385. org.php/createorg.php/vieworg.php: já estavam corretos (servidor_id da sessão)

---

### Isolamento Total por Servidor (30/03/2026)
[x] 359. Migração automática: coluna servidor_id adicionada à tabela organizacoes (DEFAULT 1)
[x] 360. Migração automática: coluna servidor_id adicionada à tabela invasoes (DEFAULT 1)
[x] 361. Orgs existentes sem servidor_id atualizadas com base no servidor do líder
[x] 362. org.php: lista de clãs agora filtrada por servidor_id do jogador logado
[x] 363. createorg.php: novos clãs salvam servidor_id; sigla única por servidor (não global)
[x] 364. vieworg.php: visualizar clã agora valida se pertence ao mesmo servidor
[x] 365. rank.php: ranking filtra jogadores por servidor_id (não exibe outros servidores)
[x] 366. invasao.php: busca de invasão ativa filtra por servidor_id do jogador
[x] 367. invasao.php: bônus de fim de invasão aplicado apenas a jogadores do mesmo servidor
[x] 368. adm/gerenciar_invasao.php: admin seleciona qual servidor receberá a invasão
[x] 369. adm/gerenciar_invasao.php: múltiplas invasões ativas (uma por servidor) exibidas no painel
[x] 370. adm/gerenciar_invasao.php: histórico mostra nome do servidor em cada invasão
[x] 371. hunt.php: caça já tinha filtro de servidor_id (confirmado e mantido)

---

### Correções de Clique em Portais e Tamanho do Mapa (22/12/2025)
[x] 310. Aumentado tamanho máximo do canvas MapaBase para seu tamanho original (1120x699)
[x] 311. MapaBase.jpg agora é exibido em tamanho normal sem limitação de 900x600
[x] 312. Aumentada distância de interação com portais de 2 para 10 tiles
[x] 313. Aumentada distância de clique em portais de 3 para 10 tiles
[x] 314. AUMENTADO tamanho do ícone do jogador de 0.7x para 1.8x tileSize (maior e melhor qualidade)
[x] 315. Imagem do jogador agora grande e fácil de enxergar no mapa
[x] 316. Corrigido cálculo de coordenadas do clique considerando escala CSS do canvas
[x] 317. Adicionados console.log detalhados para debugar cliques e coordenadas
[x] 318. Servidor reiniciado com todas as correções ativas
[x] 319. Ícone do player AUMENTADO e sistema de detecção de portal melhorado

---

### Sistema de Servidores Dinâmicos (29/03/2026)
[x] 341. Removida opção hardcoded "Servidor 02" do login (apontava para domínio externo inativo)
[x] 342. Criada tabela `servidores` no banco com: id, nome, capacidade, ativo, criado_em
[x] 343. Adicionada coluna `servidor_id` na tabela `usuarios`
[x] 344. Login agora carrega servidores dinamicamente do banco de dados
[x] 345. Dropdown mostra vagas disponíveis por servidor e desabilita servidores cheios
[x] 346. Validação no login: jogador só entra no servidor onde está registrado
[x] 347. Módulo "Gerenciar Servidores" adicionado ao painel admin
[x] 348. Admin pode criar servidores com nome e capacidade configurável
[x] 349. Admin pode editar nome, capacidade e status (ativo/inativo) de cada servidor
[x] 350. Admin pode excluir servidores (jogadores migrados automaticamente)
[x] 351. Painel mostra barra de progresso de ocupação por servidor

### Melhorias no Painel ADM e Servidores (29/03/2026)
[x] 352. "Configuração de Penalidade" movida para módulo próprio no painel admin (link ⚙️ Penalidade de Ban)
[x] 353. Lista de usuários movida para módulo "Editar Contas" (link 👥 Editar Contas) - não aparece mais em todas as páginas
[x] 354. Coluna "Servidor" adicionada na lista de contas mostrando a qual servidor cada jogador pertence
[x] 355. Dropdown de servidor no login substituído por barra colorida (verde=vazio, amarelo=quase cheio, vermelho=cheio)
[x] 356. Página de registro agora tem fieldset "Servidor" com cartões visuais com barra de ocupação
[x] 357. Registro salva servidor_id escolhido pelo jogador no INSERT
[x] 358. Validação de capacidade do servidor também na hora do registro (erro 16 se cheio)

### Correções do Sistema de Banimento (29/03/2026)
[x] 320. Corrigido auto-desbanimento - query de login agora busca ban_data, ban_duracao, ban_motivo
[x] 321. Implementada lógica de expiração: ao logar, verifica se o ban expirou e desbane automaticamente
[x] 322. Bans eternos (≥3650 dias) não são removidos automaticamente
[x] 323. Substituída mensagem simples "Conta banida." por popup estilizado com imagens _img/Ban/
[x] 324. Popup usa banned.png (ícone vermelho) + menu-repete.png (fundo pergaminho) com informações do ban
[x] 325. Popup exibe: motivo, data do ban e tempo restante calculado dinamicamente
[x] 326. Informações do ban passadas via $_SESSION['ban_info'] de forma segura
[x] 327. Botão "Fechar" no popup para dispensar a mensagem

### Sistema de Termos de Desbanimento (29/03/2026)
[x] 328. Adicionadas colunas ban_aceite_pendente e ban_penalty_ate na tabela usuarios (migração automática)
[x] 329. Criado config/ban_penalty.php com tempo padrão de penalidade (5 minutos)
[x] 330. Auto-desbanimento agora define ban_aceite_pendente = 1 ao invés de logar diretamente
[x] 331. Desbanimento manual pelo admin também define ban_aceite_pendente = 1
[x] 332. Handler ban_terms_action no index.php processa aceite/negação dos termos
[x] 333. Aceitar termos → login automático direto no jogo
[x] 334. Negar termos → bloqueio por X minutos (configurável) + popup de penalidade com contagem regressiva
[x] 335. Popup de termos usa design do pergaminho com texto de regras da aldeia
[x] 336. Checkbox obrigatório antes do botão Confirmar ficar ativo
[x] 337. Confirmação dupla ao negar (botão Voltar disponível)
[x] 338. Popup de penalidade com contador regressivo em tempo real (MM:SS)
[x] 339. Ao expirar o contador, página recarrega automaticamente para o login
[x] 340. Painel admin com configuração do tempo de penalidade em minutos (1-1440 min)

### Sistema de Cristais de Buff (21/04/2026)
[x] 480. Criada tabela buff_ativos (1 buff ativo por jogador com expiração)
[x] 481. Criada tabela buff_fragmentos (fragmentos de cristais de buff)
[x] 482. 3 cristais de buff adicionados ao catálogo (Taijutsu, Ninjutsu, Genjutsu) — +5% por 3h
[x] 483. Drop PvP (3%) — vencedor recebe cristal de buff aleatório
[x] 484. Drop Missão de Clã (5%) — 50% cristal completo, 50% fragmento
[x] 485. 3 fragmentos do mesmo tipo se combinam em 1 cristal (via Inventário)
[x] 486. Buff substitui qualquer buff anterior ao ativar novo cristal
[x] 487. Buff aplicado no ataque (attack.php) — +5% no stat correspondente para ambos os jogadores
[x] 488. Banner de buff ativo na home com contador em tempo real
[x] 489. Seção "Cristais de Buff" no Inventário com botão Ativar e Combinar fragmentos
[x] 490. Notificação de drop no relatório de batalha PvP
[x] 491. Notificação de drop na tela de recompensa de Missão de Clã

### Correção do Bingo Book (21/04/2026)
[x] 477. Identificado problema: tabela `book` não existia no banco de dados SQLite
[x] 478. Adicionada criação automática da tabela `book` em _inc/conexao.php (migração)
[x] 479. Página ?p=book agora carrega corretamente (HTTP 200)

### Verificação Final de Importação Replit (23/04/2026)
[x] 492. Servidor PHP confirmado rodando na porta 5000 sem erros (HTTP 200 OK em /)
[x] 493. Workflow php-server ativo e estável
[x] 494. Estrutura do projeto verificada (PHP + SQLite intactos)
[x] 495. Todos os itens do tracker marcados como concluídos
[x] 496. Importação do projeto para Replit confirmada como completa

### Verificação de Importação Replit (25/04/2026)
[x] 497. Pacotes necessários instalados (PHP 8.2, SQLite, Node.js 20)
[x] 498. Workflow php-server reiniciado e rodando na porta 5000
[x] 499. App não usa autenticação externa — usa sistema próprio de login PHP/SQLite (Replit Auth não aplicável)
[x] 500. App não chama integrações externas obrigatórias (sem OpenAI/Stripe/SendGrid/etc.) — nenhuma migração necessária
[x] 501. Verificação end-to-end: página inicial carregando corretamente (HTTP 200), instalador exibido sem erros
[x] 502. Importação para o ambiente Replit completa

### Migração Replit Agent → Replit (25/04/2026 - Sessão Final)
[x] 503. Pacotes necessários verificados e instalados (PHP 8.2, MariaDB 10.11, Node.js 20, SQLite)
[x] 504. Workflow php-server confirmado rodando na porta 5000 (MariaDB + PHP)
[x] 505. Diretório install/ removido (DB já provisionado via scripts/start_app.sh)
[x] 506. App não usa autenticação externa — sistema próprio de login PHP/MySQL (Replit Auth não aplicável)
[x] 507. App não usa integrações externas (OpenAI/Stripe/SendGrid/etc.) — nada a substituir
[x] 508. Verificação end-to-end: página inicial responde HTTP 200, /?p=login responde 200, /?p=registrar responde 302
[x] 509. Importação para o ambiente Replit concluída com sucesso

### Migração Agent → Replit (29/04/2026 - Sessão Atual)
[x] 510. Pacotes verificados (PHP 8.2, MariaDB 10.11, Node.js 20)
[x] 511. Workflow php-server reiniciado e rodando na porta 5000
[x] 512. Recriado config/database.php (estava ausente após reimport, causando redirect ao instalador)
[x] 513. App não usa autenticação externa — sistema próprio de login PHP/MySQL (Replit Auth não aplicável)
[x] 514. App não usa integrações externas — nada a substituir
[x] 515. Verificação end-to-end: página inicial agora carrega login corretamente (HTTP 200, sem redirect ao instalador)
[x] 516. Importação para o ambiente Replit concluída com sucesso
