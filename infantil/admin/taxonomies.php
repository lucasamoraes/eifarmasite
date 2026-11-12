<?php
require dirname(__DIR__).'/lib/bootstrap.php';require_admin();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$type=$_POST['type']??'';$name=trim((string)($_POST['name']??''));
    try{
        if($name===''||mb_strlen($name)>120)throw new RuntimeException('Informe um nome válido.');
        $table=$type==='category'?'kids_categories':($type==='presentation'?'kids_presentations':'');
        if(!$table)throw new RuntimeException('Tipo inválido.');
        $s=$pdo->prepare("INSERT INTO $table(name,sort_order) VALUES(?,99) ON DUPLICATE KEY UPDATE active=1");$s->execute([$name]);
        $_SESSION['flash']='Padrão cadastrado com sucesso.';header('Location: taxonomies.php');exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
$notice=$_SESSION['flash']??'';unset($_SESSION['flash']);$categories=kids_categories();$presentations=kids_presentations();
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Categorias e apresentações</title><link rel="stylesheet" href="<?=e(base_url('assets/admin.css'))?>"></head><body><header class="admin-head"><div><b>EiFarma Kids</b><span>Padronização do catálogo</span></div><nav><a href="index.php">← Voltar ao painel</a></nav></header><main class="admin-wrap"><div class="title"><div><span>Organização</span><h1>Categorias e apresentações</h1></div></div><?php if($notice):?><div class="alert success"><?=e($notice)?></div><?php endif?><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif?><div class="import-grid"><section class="card"><h2>Categorias</h2><p>Usadas nos filtros e no cadastro de produtos.</p><form method="post" class="settings" style="grid-template-columns:1fr auto"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="type" value="category"><label>Nova categoria<input name="name" required placeholder="Ex.: Alimentação do Bebê"></label><button>Cadastrar</button></form><ul><?php foreach($categories as $item):?><li><?=e($item)?></li><?php endforeach?></ul></section><section class="card"><h2>Apresentações</h2><p>Opções padronizadas exibidas nos produtos.</p><form method="post" class="settings" style="grid-template-columns:1fr auto"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="type" value="presentation"><label>Nova apresentação<input name="name" required placeholder="Ex.: 200 ml"></label><button>Cadastrar</button></form><ul><?php foreach($presentations as $item):?><li><?=e($item)?></li><?php endforeach?></ul></section></div><section class="card"><h2>Faixas etárias</h2><p>As idades continuam padronizadas e não precisam ser cadastradas manualmente.</p><ul><li>Todas as idades</li><li>0 a 6 meses</li><li>6 a 12 meses</li><li>1 a 3 anos</li><li>3+ anos</li></ul></section></main></body></html>
