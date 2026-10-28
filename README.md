# EI! FARMA

Catálogo em PHP 8.1+ para cPanel/Apache, com MySQL/MariaDB, carrinho de pedidos e WhatsApp **5565999728431**. Não processa pagamento, não reserva estoque e não publica preços ou custos.

## Teste local

O ambiente local já possui banco `eifarma_local`, usuário restrito e uma conta de gerente com senha aleatória. Os dados de acesso estão em `storage/acesso-local.txt`, bloqueado por HTTP. Não copie essas credenciais para a hospedagem.

Sem `config.local.php`, o site lê 503 produtos selecionados em `data/catalog.json`. Nesse modo, o painel exibe a configuração pendente e não permite alterações. Nenhuma senha padrão é criada.

No XAMPP, acesse `http://localhost/eifarma/`. Alternativa: `C:\xampp\php\php.exe -S 127.0.0.1:8088 router.php` a partir desta pasta.

## cPanel

1. Defina a raiz do subdomínio para esta pasta e ative HTTPS.
2. Crie o banco e seu usuário no cPanel (MySQL Databases), concedendo permissões apenas nesse banco.
3. Copie `config.example.php` para `config.local.php` e preencha host, nome, usuário e senha. O phpMyAdmin administra o banco; não é o banco em si.
4. Pelo terminal privado do cPanel, execute `php scripts/setup.php`, fornecendo pela entrada padrão um JSON com `email` e `password` (12+ caracteres). O script cria tabelas, conta do gerente e catálogo. Não coloque a senha na linha de comando. O mesmo `database.sql` pode ser importado pelo phpMyAdmin; a conta ainda precisa de hash gerado por `password_hash`.
5. Entre em `/admin.php`. Troque a conta criada se necessário diretamente com `password_hash`; não existe instalador público.
6. Confira se `.htaccess` está ativo: `/data/sample.json`, `/config.local.php` e a planilha XLSX devem retornar HTTP 403. Não envie a planilha original, scripts de preparação, testes, logs ou README ao servidor público se não forem necessários. Configure `display_errors=Off` em produção.

Extensões: PDO MySQL, mbstring, ZIP, SimpleXML e cURL. `upload_max_filesize` deve ser pelo menos 10M e `post_max_size` maior que 10M. A aplicação limita XLSX descompactado a 60 MB e 15.000 linhas. Faça backup do banco antes de publicar alterações de infraestrutura.

## Escopo do catálogo

- 503 EANs selecionados de higiene, beleza e fraldas da planilha fornecida; é uma lista editorial restrita, não uma classificação legal automática de todos os 3.197 itens.
- Importação XLSX/CSV com prévia; aceita apenas EANs da seleção. As colunas de custo nunca são persistidas nem enviadas ao navegador. Novos EANs não analisados são excluídos automaticamente.
- Adicionar ignora produtos já cadastrados (inclusive inativos). Substituir inativa os anteriores e reativa somente EANs elegíveis da planilha, preservando imagens. Uma importação sem elegíveis não pode apagar a vitrine. Operação transacional e histórico.
- Banners usam três modelos restritos com ativação e datas. Quando nenhum está vigente, aparece o banner institucional.
- Busca, filtro, ordenação, carrinho persistido no navegador, quantidades de 1–99 e mensagem no WhatsApp. O cliente confirma o envio no WhatsApp.
- O gerente ainda não tem editor livre de produtos/fotos nem recuperação de senha. Novos EANs dependem de ampliação da seleção analisada. Não é uma promessa de conformidade automatizada para qualquer produto.

## Imagens e licenças

O catálogo utiliza as imagens do CDN Bluesoft Cosmos no formato `https://cdn-cosmos.bluesoft.com.br/products/{EAN}`, conforme a fonte indicada pelo proprietário. Os 503 produtos usam URLs por EAN; a existência de fotos para todos os itens não é garantida. O navegador exibe um marcador neutro se a imagem falhar.

`app/images.php` verifica a imagem pelo EAN para o painel. Não é necessário token para os endereços diretos utilizados; isso não equivale a contratar a API cadastral do Cosmos nem a garantir disponibilidade permanente do CDN. Os links de origem aparecem nos produtos.

## Antes de disponibilizar ao público

O teste não foi publicado no subdomínio. Ainda é necessário conectar o banco real, cadastrar o gerente, confirmar horário de funcionamento e demais dados aplicáveis da farmácia, conferir os dados dos produtos e testar na hospedagem. O site inicial não contém medicamentos, fórmulas infantis, mamadeiras ou chupetas. A categorização do ERP e um EAN isolado não atestam regularização sanitária.

## Verificação realizada

- PHP lint em todos os arquivos e sintaxe JavaScript.
- `tests/import_test.php`: leitura do XLSX real, 3.197 linhas, 503 elegíveis, exclusão dos demais, EAN/dígito verificador, duplicados e cabeçalhos inválidos.
- `tests/database_test.php`: adição, repetição, substituição, preservação de cadastro e proteção contra catálogo vazio em tabelas temporárias. Executa somente no banco local do projeto com o usuário root do XAMPP; não altera a vitrine.
- `tests/http_test.php`: login, upload autenticado até a prévia, logout, rejeição de CSRF inválido e bloqueio de arquivos privados pelo Apache. Não aplica substituições.
- Navegador: busca Dove, adição e alteração de quantidade, persistência após recarregar, limpeza da lista e layout a 390 px sem transbordamento horizontal. Nenhuma mensagem de teste foi enviada ao WhatsApp.

## Carga de 28/10/2026

503 produtos na vitrine local (491 novos e 12 preservados). Outros 2.694 registros foram excluídos da carga, por escopo, identificação insuficiente ou EAN inválido. O relatório privado está em data/catalog-audit.json. Os nomes novos preservam as descrições abreviadas do ERP, sem inventar variantes. A seleção conservadora não substitui consulta de regularização sanitária por produto.

Razão social, CNPJ e endereço fornecidos pelo proprietário constam no rodapé. Horário ainda não confirmado. O pacote dist/eifarma-cpanel.zip não contém credenciais locais, planilha com custos ou backups. Ainda não publicado no subdomínio.
