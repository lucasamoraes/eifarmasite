# Instalação na HostGator

## 1. Banco pelo phpMyAdmin

1. No cPanel, abra **Bancos de dados MySQL**.
2. Crie o banco e o usuário; vincule o usuário ao banco com **todos os privilégios**.
3. Abra o phpMyAdmin, selecione o banco, clique em **Importar** e envie `database.sql`.

## 2. Configuração

1. Renomeie `config.example.php` para `config.php`.
2. Preencha nome do banco, usuário e senha criados no cPanel.
3. Se publicar em `seudominio.com/catalogo`, use `'base_url' => '/catalogo'`. Na raiz, deixe vazio.

## 3. Envio dos arquivos

Envie o conteúdo desta pasta para `public_html` ou para `public_html/catalogo` pelo Gerenciador de Arquivos do cPanel. Garanta permissão de escrita (`755`) na pasta `uploads/products`.

## 4. Criar o primeiro acesso

Abra `https://seudominio.com/setup-admin.php`, crie o administrador e depois **apague `setup-admin.php`** do servidor.

## Importação rápida de produtos e imagens

- Use um SKU único por produto: `CHU001`, `FRALDA023` etc.
- Nomeie a imagem exatamente com o SKU: `CHU001.webp`.
- Na Área do Lojista, envie todas as imagens juntas.
- Importe o CSV usando o modelo `admin/modelo-produtos.csv`.
- O sistema procura a foto pelo SKU e faz o vínculo automaticamente.

Colunas do CSV:

`sku;nome;apresentacao;categoria;idade;preco;preco_antigo`

Faixas aceitas: `all`, `0-6`, `6-12`, `1-3`, `3+`.
