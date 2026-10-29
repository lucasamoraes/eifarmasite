<?php
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/import.php';
start_session();
header('Cache-Control: no-store');header('X-Frame-Options: DENY');
$error='';$notice=$_SESSION['notice']??'';unset($_SESSION['notice']);$pdo=null;
try{$pdo=db();}catch(Throwable $failure){error_log($failure->getMessage());$error='Não foi possível conectar ao banco. Confira a configuração da hospedagem.';}
if(!empty($_SESSION['user'])&&time()-($_SESSION['last_seen']??0)>1800){unset($_SESSION['user'],$_SESSION['preview']);}
if(!empty($_SESSION['user']))$_SESSION['last_seen']=time();
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $action=$_POST['action']??'';
        if($action==='logout'){$_SESSION=[];session_destroy();header('Location: admin.php');exit;}
        if(!$pdo)throw new RuntimeException('Configure o banco para habilitar a área do gerente.');
        if($action==='login'){
            $stmt=$pdo->prepare('SELECT * FROM users WHERE email=?');$stmt->execute([trim((string)($_POST['email']??''))]);$user=$stmt->fetch();
            $hash=$user['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            $valid=password_verify((string)($_POST['password']??''),$hash);
            if(!$user||!$valid||($user['locked_until']&&strtotime($user['locked_until'])>time())){
                if($user)$pdo->prepare('UPDATE users SET failed_attempts=failed_attempts+1, locked_until=CASE WHEN failed_attempts>=5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE) ELSE locked_until END WHERE id=?')->execute([$user['id']]);
                throw new RuntimeException('Acesso não autorizado. Verifique os dados ou aguarde alguns minutos.');
            }
            $pdo->prepare('UPDATE users SET failed_attempts=0,locked_until=NULL WHERE id=?')->execute([$user['id']]);
            session_regenerate_id(true);$_SESSION['user']=(int)$user['id'];$_SESSION['last_seen']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));header('Location: admin.php');exit;
        }
        if(empty($_SESSION['user']))throw new RuntimeException('Entre na sua conta para continuar.');
        if($action==='upload_banner'){
            require_once ROOT.'/app/banner-upload.php';upload_custom_banner($_POST,$_FILES);
            $_SESSION['notice']='Banner publicado no carrossel.';header('Location: admin.php#custom-banners');exit;
        }
        if($action==='toggle_banner'){
            require_once ROOT.'/app/banner-upload.php';$id=(string)($_POST['banner_id']??'');$active=($_POST['active']??'')==='1';
            update_custom_banners(function($rows)use($id,$active){foreach($rows as &$row)if($row['id']===$id)$row['active']=$active;return $rows;});
            header('Location: admin.php#custom-banners');exit;
        }
        if($action==='medicine_visibility'){
            $value=(string)($_POST['visible']??'');
            if(!in_array($value,['0','1'],true))throw new RuntimeException('Situação inválida.');
            set_medicine_visibility($pdo,(string)($_POST['ean']??''),$value==='1');
            $_SESSION['notice']=$value==='1'?'Medicamento aprovado manualmente e publicado na vitrine.':'Medicamento retirado da vitrine. O cadastro foi preservado.';
            header('Location: admin.php#medicamentos');exit;
        }
        if($action==='preview'){
            $file=$_FILES['sheet']??[];
            if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Não foi possível receber o arquivo. Confira o limite de upload da hospedagem.');
            if($file['size']>10*1024*1024)throw new RuntimeException('O limite é 10 MB por arquivo.');
            $mode=$_POST['mode']??'add';if(!in_array($mode,['add','replace'],true))throw new RuntimeException('Modo de importação inválido.');
            $extension=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            $preview=analyze_import(read_sheet($file['tmp_name'],$extension));
            $preview['mode']=$mode;$preview['created']=time();
            $preview['existing']=0;$check=$pdo->prepare('SELECT COUNT(*) FROM products WHERE ean=?');
            foreach($preview['products'] as $p){$check->execute([$p['ean']]);$preview['existing']+=(int)$check->fetchColumn();}
            $_SESSION['preview']=$preview;header('Location: admin.php#preview');exit;
        }
        if($action==='apply'){
            $preview=$_SESSION['preview']??null;
            if(!$preview||time()-$preview['created']>1800)throw new RuntimeException('Prévia expirada. Envie novamente a planilha.');
            $result=apply_import($pdo,$preview,$preview['mode'],$_SESSION['user']);unset($_SESSION['preview']);
            $_SESSION['notice']='Importação concluída. '.$result['inserted'].' novos produtos; '.$result['duplicates'].' duplicados preservados. '.($preview['mode']==='replace'?'A vitrine agora contém somente os EANs elegíveis da planilha.':'Os produtos já cadastrados foram mantidos.');header('Location: admin.php');exit;
        }
        if($action==='cancel'){unset($_SESSION['preview']);header('Location: admin.php');exit;}
        if($action==='campaign'){
            $template=filter_var($_POST['template']??null,FILTER_VALIDATE_INT);
            if(!in_array($template,[0,1,2],true))throw new RuntimeException('Modelo inválido.');
            $dates=[];foreach(['starts_on','ends_on'] as $key){$v=$_POST[$key]??'';if($v!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)||date('Y-m-d',strtotime($v))!==$v))throw new RuntimeException('Data inválida.');$dates[$key]=$v?:null;}
            if($dates['starts_on']&&$dates['ends_on']&&$dates['starts_on']>$dates['ends_on'])throw new RuntimeException('O fim deve ser igual ou posterior ao início.');
            $pdo->prepare('INSERT INTO campaigns(template,starts_on,ends_on) VALUES(?,?,?)')->execute([$template,$dates['starts_on'],$dates['ends_on']]);$_SESSION['notice']='Campanha programada.';header('Location: admin.php#campaigns');exit;
        }
        if($action==='toggle_campaign'){$pdo->prepare('UPDATE campaigns SET active=1-active WHERE id=?')->execute([(int)($_POST['id']??0)]);header('Location: admin.php#campaigns');exit;}
        if($action==='images'){
            require __DIR__.'/app/images.php';
            $rows=$pdo->query("SELECT ean FROM products WHERE active=1 AND (image IS NULL OR image='') ORDER BY id")->fetchAll();
            $offset=(int)($_SESSION['image_offset']??0);if($offset>=count($rows))$offset=0;
            $batch=array_slice($rows,$offset,3);$found=0;
            foreach($batch as $row){$image=lookup_image($row['ean']);if($image){$pdo->prepare('UPDATE products SET image=?,image_source=? WHERE ean=?')->execute([$image['image'],$image['image_source'],$row['ean']]);$found++;}}
            $_SESSION['image_offset']=max(0,$offset+count($batch)-$found);
            $_SESSION['notice']=count($batch).' EANs consultados; '.$found.' novas fotos encontradas. Produtos sem foto continuam disponíveis com imagem neutra.';header('Location: admin.php');exit;
        }
    }catch(Throwable $failure){if($failure instanceof PDOException){error_log($failure->getMessage());$error='Não foi possível concluir a operação no banco. Nenhuma importação parcial foi aplicada.';}else{$error=$failure->getMessage();}}
}
$logged=$pdo&&!empty($_SESSION['user']);$preview=$_SESSION['preview']??null;
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Gerência — EI! FARMA</title><link rel="stylesheet" href="assets/style.css"></head><body class="admin-body"><header class="admin-header"><div class="container"><a class="logo" href="./"><img src="images/Design%20sem%20nome%20(26).png" alt="EI! FARMA"></a><a class="button" href="./">Ver o site <?=icon('arrow')?></a></div></header><main class="container admin-main">
<?php if($error):?><p class="notice error" role="alert"><?=e($error)?></p><?php endif;?><?php if($notice):?><p class="notice" role="status"><?=e($notice)?></p><?php endif;?>
<?php if(!$pdo):?><div class="panel"><span class="eyebrow">PRIMEIRA CONFIGURAÇÃO</span><h1>O catálogo de teste está no ar.</h1><p>Para habilitar o acesso do gerente e salvar alterações, conecte o banco MySQL/MariaDB do cPanel e crie a conta administrativa.</p><p>A vitrine funciona com os 12 produtos da amostra enquanto o banco não está configurado.</p><a class="button" href="./">Explorar a vitrine <?=icon('arrow')?></a></div>
<?php elseif(!$logged):?><form class="panel login-panel" method="post"><?=csrf()?><input type="hidden" name="action" value="login"><span class="eyebrow">ÁREA DO GERENTE</span><h2>Bem-vindo de volta.</h2><p>Gerencie os produtos e as campanhas da EI! FARMA.</p><label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="username" required maxlength="190"><label for="password">Senha</label><input id="password" name="password" type="password" autocomplete="current-password" required><button class="button" type="submit">Entrar <?=icon('arrow')?></button></form>
<?php else:
$counts=$pdo->query("SELECT COUNT(*) total,COALESCE(SUM(active=1),0) active,COALESCE(SUM(active=1 AND (image IS NULL OR image='')),0) missing FROM products")->fetch();
$history=$pdo->query('SELECT * FROM imports ORDER BY id DESC LIMIT 10')->fetchAll();
$campaigns=$pdo->query('SELECT * FROM campaigns ORDER BY id DESC')->fetchAll();
?><div class="admin-heading"><div><span class="eyebrow">EI! FARMA · GERÊNCIA</span><h1>Seu catálogo, do seu jeito.</h1></div><form method="post"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="text-button">Sair da conta</button></form></div>
<div class="stats"><div class="stat"><b><?=e($counts['active'])?></b>produtos na vitrine</div><div class="stat"><b><?=e($counts['total'])?></b>produtos cadastrados</div><div class="stat"><b><?=e($counts['missing'])?></b>produtos sem foto</div></div>
<?php require ROOT.'/app/mips-admin.php';?><section class="panel"><h2>Importar produtos</h2><p>Envie o Excel do sistema ou um CSV com as colunas <strong>Descricao</strong> e <strong>EAN</strong>. Os custos de compra são ignorados.</p><p class="notice">A seleção atual contém produtos de higiene, beleza e fraldas e <strong>279 MIPs identificados</strong>. Os MIPs entram cadastrados; os pendentes só aparecem na vitrine depois que você clicar em <strong>Aprovar e publicar</strong>.</p><form method="post" enctype="multipart/form-data"><?=csrf()?><input type="hidden" name="action" value="preview"><label for="sheet">Planilha de produtos · XLSX ou CSV · até 10 MB</label><input type="file" id="sheet" name="sheet" accept=".xlsx,.csv" required><label><input type="radio" name="mode" value="add" checked> Adicionar — mantém o catálogo e ignora EANs já cadastrados</label><label><input type="radio" name="mode" value="replace"> Substituir vitrine — mostra apenas os produtos elegíveis desta planilha</label><button class="button">Analisar planilha <?=icon('arrow')?></button></form></section>
<?php if($preview):?><section class="panel" id="preview"><h2>Confira antes de aplicar</h2><p><?=e($preview['total'])?> linhas · <?=count($preview['products'])?> EANs elegíveis · <?=e($preview['duplicates'])?> repetições na planilha · <?=count($preview['excluded'])?> excluídos</p><p><?=e($preview['existing'])?> EANs já estão no cadastro. <?= $preview['mode']==='replace'?'Serão reativados; produtos ausentes ficarão inativos.':'Serão ignorados, preservando seus dados e seu estado atual.'?></p><div class="table-scroll"><table><thead><tr><th>EAN</th><th>Produto que será publicado</th><th>Categoria</th></tr></thead><tbody><?php foreach($preview['products'] as $p):?><tr><td><?=e($p['ean'])?></td><td><?=e($p['name'])?></td><td><?=e($p['category'])?></td></tr><?php endforeach;?></tbody></table></div><details><summary>Motivos das exclusões (primeiras 100 linhas)</summary><div class="table-scroll"><table><thead><tr><th>Linha</th><th>Produto</th><th>Motivo</th></tr></thead><tbody><?php foreach(array_slice($preview['excluded'],0,100) as $row):?><tr><td><?=e($row['row'])?></td><td><?=e($row['name'])?></td><td><?=e($row['reason'])?></td></tr><?php endforeach;?></tbody></table></div></details><div class="inline-form"><form method="post"><?=csrf()?><input type="hidden" name="action" value="apply"><button class="button" <?=empty($preview['products'])?'disabled':''?>>Confirmar <?= $preview['mode']==='replace'?'substituição da vitrine':'importação'?></button></form><form method="post"><?=csrf()?><input type="hidden" name="action" value="cancel"><button class="text-button">Descartar prévia</button></form></div></section><?php endif;?>
<section class="panel"><h2>Fotos por código de barras</h2><p>Consulte até 3 produtos sem foto por vez no CDN do Bluesoft Cosmos, usando o EAN. A consulta não altera os nomes nem libera produtos.</p><form method="post"><?=csrf()?><input type="hidden" name="action" value="images"><button class="button" <?=!(int)$counts['missing']?'disabled':''?>>Buscar próximas fotos</button></form></section>
<section class="panel" id="campaigns"><h2>Banners e campanhas</h2><p>Use os modelos da EI! FARMA. Eles não incluem medicamentos, alegações terapêuticas ou descontos fictícios.</p><form method="post" class="inline-form"><?=csrf()?><input type="hidden" name="action" value="campaign"><div><label for="template">Modelo</label><select id="template" name="template"><option value="0">Institucional · Seu cuidado começa com um oi</option><option value="1">Corpo e banho · Um momento só seu</option><option value="2">Cabelos · Da raiz às pontas</option></select></div><div><label for="starts">Início (opcional)</label><input type="date" id="starts" name="starts_on"></div><div><label for="ends">Fim (opcional)</label><input type="date" id="ends" name="ends_on"></div><button class="button">Programar</button></form><div class="table-scroll"><table><thead><tr><th>Modelo</th><th>Início</th><th>Fim</th><th>Estado</th><th>Ação</th></tr></thead><tbody><?php foreach($campaigns as $c):?><tr><td><?=e(['Institucional','Corpo e banho','Cabelos'][(int)$c['template']]??'Institucional')?></td><td><?=e($c['starts_on']?:'Imediato')?></td><td><?=e($c['ends_on']?:'Sem término')?></td><td><?=$c['active']?'Ativo':'Inativo'?></td><td><form method="post"><?=csrf()?><input type="hidden" name="action" value="toggle_campaign"><input type="hidden" name="id" value="<?=e($c['id'])?>"><button class="text-button"><?=$c['active']?'Desativar':'Ativar'?></button></form></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="panel"><h2>Últimas importações</h2><?php if(!$history):?><p>Nenhuma importação realizada pelo painel.</p><?php else:?><div class="table-scroll"><table><thead><tr><th>Data</th><th>Modo</th><th>Linhas</th><th>Elegíveis</th><th>Duplicados</th><th>Excluídos</th></tr></thead><tbody><?php foreach($history as $h):?><tr><td><?=e(date('d/m/Y H:i',strtotime($h['created_at'])))?></td><td><?=$h['mode']==='replace'?'Substituir':'Adicionar'?></td><td><?=e($h['total_rows'])?></td><td><?=e($h['accepted'])?></td><td><?=e($h['duplicates'])?></td><td><?=e($h['excluded'])?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
<?php if($logged)require ROOT.'/app/banners-admin.php';?>
<?php endif;?></main></body></html>
