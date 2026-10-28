<?php
// Run from CLI. Credentials come from stdin, never from a public web installer.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
$pdo=db();if(!$pdo)exit("Configure config.local.php primeiro.\n");
$pdo->exec(file_get_contents(ROOT.'/database.sql'));
$input=json_decode(stream_get_contents(STDIN),true);
if(!is_array($input)||!filter_var($input['email']??'',FILTER_VALIDATE_EMAIL)||strlen($input['password']??'')<12)exit("Informe via stdin JSON com email e password de pelo menos 12 caracteres.\n");
$pdo->prepare('INSERT INTO users(email,password_hash) VALUES(?,?)')->execute([$input['email'],password_hash($input['password'],PASSWORD_DEFAULT)]);
$insert=$pdo->prepare('INSERT IGNORE INTO products(ean,name,category,brand,image,image_source,sort_order) VALUES(?,?,?,?,?,?,?)');
foreach(approved_products() as $i=>$p)$insert->execute([$p['ean'],$p['name'],$p['category'],$p['brand'],$p['image'],$p['image_source'],$i]);
$pendingInsert=$pdo->prepare('INSERT IGNORE INTO products(ean,name,category,brand,image,image_source,active) VALUES(?,?,?,?,?,?,0)');
foreach(json_decode(file_get_contents(ROOT.'/data/mips-pending.json'),true,512,JSON_THROW_ON_ERROR) as $p)$pendingInsert->execute([$p['ean'],$p['name'],'Medicamentos sem receita',$p['brand'],$p['image'],$p['image_source']]);
if(!(int)$pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn())$pdo->exec('INSERT INTO campaigns(template) VALUES(0),(1),(2)');
echo "Banco preparado, gerente criado e catálogo importado.\n";
