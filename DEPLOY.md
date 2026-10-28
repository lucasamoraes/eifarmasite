# Publicação por Git no cPanel

## Estado atual

O código está preparado para PHP 8.1+ com PDO MySQL, mbstring, ZipArchive, SimpleXML e cURL. O banco da hospedagem é independente do banco local. As aprovações feitas no painel ficam no banco e não são transportadas pelo Git.

Os arquivos versionados não incluem config.local.php, senhas de acesso local, planilha original, backups, logs ou pacotes ZIP. Os arquivos data/catalog.json, data/mips-ready.json e data/mips-pending.json são dados iniciais de produtos, sem preços de compra.

## Primeira publicação

1. Repositório remoto: https://github.com/lucasamoraes/eifarmasite.git, branch main.
2. No Git Version Control do cPanel, clonar esse repositório preferencialmente fora da pasta pública. Para repositório privado, configurar uma chave de deploy com acesso somente de leitura.
3. O arquivo .cpanel.yml publica em /home1/luca6802/eifarma.amplificati.com.br. No repositório clonado, abra Manage → Pull or Deploy → Update from Remote → Deploy HEAD Commit. A clonagem pode ficar em /home1/luca6802/repositories/eifarmasite. A pasta .well-known é preservada.
4. Criar o banco MySQL e usuário pelo cPanel. Na raiz do site, criar config.local.php a partir de config.example.php com as credenciais da hospedagem. Esse arquivo fica somente no servidor.
5. Em banco novo, executar scripts/setup.php pelo terminal privado com JSON de email e password pela entrada padrão. O script cria as tabelas, o gerente, os produtos completos e os candidatos a medicamento inativos. Não executar novamente sobre uma instalação existente como rotina de deploy.
6. Ativar HTTPS e display_errors=Off. Conferir bloqueio HTTP de config.local.php, data/, storage/, app/, scripts/, .git/ e planilhas.
7. Validar login, pesquisa, carrinho, mensagem do WhatsApp, importação e ações de aprovação/retirada de medicamentos. Conferir dados e identificação da farmácia antes de disponibilizar medicamentos ao público.

## Atualizações

O deploy copia os arquivos públicos, app/, assets/, images/ e os JSONs selecionados de data/. Também disponibiliza config.example.php, database.sql e scripts/setup.php para a instalação inicial; os três ficam bloqueados por HTTP. Cria storage/ e copia seu .htaccess sem apagar arquivos existentes. Não substitui config.local.php nem limpa o banco, storage/ ou aprovações do gerente. Não executa setup.php automaticamente a cada atualização.

As atualizações do código não são backup do banco. Fazer backup do banco antes de mudanças de estrutura. Reverter um commit não desfaz importações nem alterações feitas no painel.

## Pendências de conexão

Código e configuração de deploy enviados ao GitHub. O deploy na hospedagem ainda precisa ser executado pelo cPanel; também faltam configurar o banco de produção e a conta do gerente. Os dados de acesso locais não foram enviados ao GitHub.
