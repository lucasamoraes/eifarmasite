# Publicação por Git no cPanel

## Estado atual

O código está preparado para PHP 8.1+ com PDO MySQL, mbstring, ZipArchive, SimpleXML e cURL. O banco da hospedagem é independente do banco local. As aprovações feitas no painel ficam no banco e não são transportadas pelo Git.

Os arquivos versionados não incluem config.local.php, senhas de acesso local, planilha original, backups, logs ou pacotes ZIP. Os arquivos data/catalog.json, data/mips-ready.json e data/mips-pending.json são dados iniciais de produtos, sem preços de compra.

## Primeira publicação

1. Criar ou indicar o repositório remoto e enviar a branch main.
2. No Git Version Control do cPanel, clonar esse repositório preferencialmente fora da pasta pública. Para repositório privado, configurar uma chave de deploy com acesso somente de leitura.
3. Definir a pasta real do subdomínio antes de gerar .cpanel.yml. Não copiar uma configuração de deploy de outro site: o destino deve ser exclusivo da EI Farma.
4. Criar o banco MySQL e usuário pelo cPanel. Na raiz do site, criar config.local.php a partir de config.example.php com as credenciais da hospedagem. Esse arquivo fica somente no servidor.
5. Em banco novo, executar scripts/setup.php pelo terminal privado com JSON de email e password pela entrada padrão. O script cria as tabelas, o gerente, os produtos completos e os candidatos a medicamento inativos. Não executar novamente sobre uma instalação existente como rotina de deploy.
6. Ativar HTTPS e display_errors=Off. Conferir bloqueio HTTP de config.local.php, data/, storage/, app/, scripts/, .git/ e planilhas.
7. Validar login, pesquisa, carrinho, mensagem do WhatsApp, importação e ações de aprovação/retirada de medicamentos. Conferir dados e identificação da farmácia antes de disponibilizar medicamentos ao público.

## Atualizações

O deploy deve copiar apenas index.php, admin.php, mips-preview.php, .htaccess e os diretórios app/, assets/, data/ e images/. Criar storage/ e copiar seu .htaccess sem apagar arquivos existentes. Não substituir config.local.php nem limpar o banco, storage/ ou aprovações do gerente. Não executar setup.php automaticamente a cada atualização.

As atualizações do código não são backup do banco. Fazer backup do banco antes de mudanças de estrutura. Reverter um commit não desfaz importações nem alterações feitas no painel.

## Pendências de conexão

Ainda precisam ser informados: URL do repositório remoto, pasta de destino do subdomínio, forma de deploy disponível e acesso à hospedagem. Nenhum push ou deploy foi realizado apenas pela preparação deste arquivo.
