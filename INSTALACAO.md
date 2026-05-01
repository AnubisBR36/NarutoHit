# Guia de Instalação — Anubis Serve

Este é um guia genérico para colocar o jogo em pé. Funciona em **XAMPP local**
(Windows / Linux / macOS) e na maioria das **hospedagens compartilhadas** que
oferecem PHP + MySQL (Hostinger, Locaweb, KingHost, HostGator, Hostoo, cPanel
em geral, painel ISPConfig, painel DirectAdmin, etc.) e também em VPS com
Apache/Nginx.

> Resumo: copie os arquivos, crie um banco MySQL vazio, abra
> `seu-site.com/install/` no navegador e siga 5 passos. O instalador faz tudo
> sozinho.

---

## 1. Requisitos mínimos

| Item | Mínimo | Recomendado |
|---|---|---|
| **PHP** | 8.0 | 8.2+ |
| **Extensões PHP** | `pdo_mysql`, `mbstring`, `json`, `session` | + `gd`, `openssl`, `zip` |
| **MySQL / MariaDB** | MySQL 5.7 ou MariaDB 10.3 | MySQL 8.0+ ou MariaDB 10.6+ |
| **Espaço em disco** | 80 MB | 200 MB (sobra para uploads/avatares) |
| **PHP `memory_limit`** | 64M | 128M |
| **PHP `upload_max_filesize`** | 8M | 16M |

A primeira tela do instalador (`/install/`) faz o **diagnóstico automático** e
mostra o que está faltando antes de você prosseguir.

---

## 2. Opção A — Instalação local com XAMPP

### 2.1. Baixar e instalar o XAMPP

1. Baixe em <https://www.apachefriends.org/pt_br/> e instale com as opções
   padrão. Marque pelo menos **Apache + MySQL + PHP**.
2. Abra o **XAMPP Control Panel** e clique em **Start** ao lado de **Apache**
   e **MySQL**.

### 2.2. Copiar os arquivos do jogo

1. Copie a pasta inteira do jogo (este projeto) para dentro de
   `C:\xampp\htdocs\` (Windows) ou `/opt/lampp/htdocs/` (Linux).
2. Renomeie para algo curto como `naruto`. Ficará:
   `C:\xampp\htdocs\naruto\`.

### 2.3. Criar o banco vazio

> **Não importa nada manualmente.** O instalador cuida disso.

1. Abra <http://localhost/phpmyadmin/>.
2. Clique em **Novo** → digite o nome `naruto` → **Criar**.
3. Pode fechar o phpMyAdmin.

### 2.4. Rodar o instalador

1. Abra <http://localhost/naruto/install/>.
2. Siga os 5 passos. No **Passo 2 (MySQL)** use:
   - **Host:** `127.0.0.1`
   - **Porta:** `3306`
   - **Usuário:** `root`
   - **Senha:** *(vazio — padrão do XAMPP)*
   - **Banco principal:** `naruto`
   - **Banco do fórum:** *(vazio — usa o mesmo)*
3. No **Passo 4 (Conta ADM)** crie seu admin e **escolha o personagem** dele
   (qualquer um do catálogo, incluindo VIP).
4. No **Passo 5** clique em **Confirmar e instalar**.
5. Quando aparecer **Instalação concluída**, clique em **Apagar pasta install/
   e ir para o jogo**.
6. Acesse <http://localhost/naruto/>. Faça login com a conta ADM criada.

---

## 3. Opção B — Hospedagem compartilhada (cPanel, Hostinger, Locaweb, KingHost, HostGator, etc.)

O passo-a-passo é o mesmo em qualquer painel — só muda o nome dos botões.

### 3.1. Subir os arquivos

Você tem 3 caminhos. Use o que preferir:

**Por FTP** (FileZilla, WinSCP):
1. Conecte usando os dados do painel (host, usuário FTP, senha).
2. Envie tudo para a pasta pública do site
   (`public_html/`, `htdocs/`, `www/` ou `httpdocs/` — depende do painel).

**Por Gerenciador de Arquivos do painel:**
1. Compacte o jogo todo em `.zip` localmente.
2. Painel → **Gerenciador de Arquivos** → entre em `public_html` →
   **Upload** → envie o zip → **Extrair**.

**Por Git** (se a hospedagem aceitar):
1. `git clone` direto dentro de `public_html`.

### 3.2. Criar o banco MySQL

1. No painel da hospedagem, procure **MySQL** ou **Bancos de Dados MySQL**.
2. Crie:
   - Um **banco** novo (ex.: `usuario_naruto`).
   - Um **usuário** novo, com senha forte.
   - **Adicione** o usuário ao banco com **TODOS os privilégios**.
3. Anote: **nome do banco**, **usuário**, **senha**, e o **host**
   (geralmente `localhost`, mas alguns painéis usam `mysql.seusite.com.br` ou
   um IP — está escrito na tela do painel).

> **Hostinger:** o host normalmente é `localhost`. **KingHost** usa
> `mysql.SEUDOMINIO.com.br`. **Locaweb** mostra o host na aba do banco.
> **cPanel padrão (HostGator/Hostoo):** `localhost`.

### 3.3. Rodar o instalador

1. Abra `https://seudominio.com/install/`.
2. **Passo 2 (MySQL):** preencha com os dados anotados acima. Clique em
   **Testar & Criar**. Se der erro de conexão, reveja host/usuário/senha.
3. **Passo 3:** quantos servidores (mundos) o jogo terá. Pode deixar 1.
4. **Passo 4 (Conta ADM):** seu login + email + senha + **personagem**
   (escolha qualquer um do catálogo — o ADM começa no nível 99 então pode
   usar VIP também).
5. **Passo 5:** revise tudo, baixe o resumo (`Download summary`) e clique em
   **Confirmar e instalar**.
6. Quando concluir, clique em **Apagar pasta install/ e ir para o jogo**.
   *(Se a hospedagem bloquear a remoção via PHP, apague a pasta `install/`
   manualmente pelo gerenciador de arquivos do painel.)*

---

## 4. Opção C — VPS Linux (Apache ou Nginx)

Resumo dos comandos:

```bash
# Pacotes (Ubuntu/Debian)
sudo apt update && sudo apt install -y \
  apache2 mysql-server php php-mysql php-mbstring php-gd php-zip unzip git

# Pasta do site
sudo mkdir -p /var/www/naruto
sudo chown -R www-data:www-data /var/www/naruto

# Subir os arquivos do jogo para /var/www/naruto

# Banco
sudo mysql -e "CREATE DATABASE naruto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'naruto'@'localhost' IDENTIFIED BY 'troque_essa_senha';"
sudo mysql -e "GRANT ALL ON naruto.* TO 'naruto'@'localhost';"

# Rodar o instalador via navegador: http://SEU_IP/install/
```

Configure um VirtualHost apontando para `/var/www/naruto`. Para HTTPS use
Certbot/Let's Encrypt.

---

## 5. Estrutura de pastas e permissões

| Pasta | O que é | Permissão |
|---|---|---|
| `_img/avatares/` | Avatares enviados pelos jogadores | escrita (755) |
| `forum/avatars/` | Avatares do fórum | escrita (755) |
| `cache/` | Cache opcional | escrita (755) |
| `config/database.php` | Conexão (gerada pelo instalador) | leitura |
| `install/` | Pasta do instalador | **deve ser apagada após instalar** |

A primeira tela do instalador roda um **diagnóstico de pastas** que mostra
exatamente quais precisam de escrita. Itens marcados como `[CRÍTICO]`
precisam estar OK antes de continuar.

---

## 6. Pós-instalação

### 6.1. Apagar a pasta `install/`

Por segurança. Se o último botão do instalador não conseguiu apagar
(permissão), apague pelo FTP / gerenciador de arquivos.

### 6.2. Conferir `config/database.php`

O instalador cria esse arquivo automaticamente. Para trocar a senha do banco
mais tarde, basta editar este arquivo:

```php
return [
    'driver' => 'mysql',
    'mysql'  => [
        'host'    => '127.0.0.1',
        'port'    => '3306',
        'dbname'  => 'naruto',
        'user'    => 'naruto',
        'pass'    => 'sua_senha',
        'charset' => 'utf8mb4',
    ],
];
```

### 6.3. Personagens — como funciona

- **4 iniciais** (Naruto / Sasuke / Sakura / Kakashi) já vêm liberados.
- **29 desbloqueáveis** (Iruka, Asuma, Neji, Lee, Gaara, Itachi, etc.) são
  liberados **automaticamente por nível** (ex.: Iruka no nível 5, Itachi no
  nível 60).
- **Os 7 últimos** do catálogo (Sakon, Kidoumaru, Tayuya, Jiroubo, Kimimaro,
  Kabuto, Itachi) são marcados como **VIP** e exigem assinatura ativa para
  ficar selecionáveis.
- O jogador troca o personagem ativo em **Configurações → Personagem**.

### 6.4. Painel ADM

Depois de logar com a conta criada no Passo 4, vá em
`https://seudominio.com/?p=adm` para entrar no painel administrativo
(gerenciar jogadores, dar VIP, banir, gerenciar servidores, tickets, etc.).

---

## 7. Atualizar o jogo (subir nova versão)

1. Faça **backup do banco** pelo phpMyAdmin (Exportar → SQL).
2. Faça **backup das pastas** `_img/avatares/`, `forum/avatars/` e
   `config/database.php`.
3. Substitua os arquivos do site pela nova versão.
4. Restaure as 3 pastas/arquivo do passo 2 por cima.
5. Acesse o site uma vez logado como ADM — o sistema roda **migrações
   automáticas** do MySQL (em `_inc/mysql_compat.php`) para ajustar colunas
   novas que tenham sido adicionadas.

---

## 8. Solução de problemas comuns

**"SQLSTATE[HY000] [2002] Connection refused"**
→ Host/porta do MySQL errados. Confirme no painel da hospedagem.

**"Access denied for user 'xxx'@'localhost'"**
→ Usuário ou senha do banco errados. No painel, redefina a senha do usuário
do banco e atualize `config/database.php`.

**"Specified key was too long; max key length is 767 bytes"**
→ MariaDB muito antigo (< 10.3). Atualize o MariaDB ou peça ao suporte da
hospedagem.

**Imagens dos personagens não aparecem**
→ Faltam arquivos em `_img/personagens/`. Confirme que o upload incluiu
todas as pastas, inclusive subpastas.

**Não consigo apagar a pasta `install/` pelo botão final**
→ Permissão. Apague manualmente via FTP / gerenciador de arquivos.

**Tela em branco após instalar**
→ Veja `error_log` (Apache) ou ative `display_errors = On` em `php.ini`
temporariamente para ver o erro real.

---

## 9. Onde pedir ajuda

- Abra uma issue no repositório do projeto descrevendo o erro,
  PHP/MySQL versão, e qual painel/hospedagem você usa.
- Inclua o **log do instalador** (botão `⬇ Download full log` na última tela)
  — ele tem todos os passos do que rodou.

---

Boa partida! 🍥
