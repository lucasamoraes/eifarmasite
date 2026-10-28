<?php
require dirname(__DIR__).'/app/bootstrap.php';
if(($config['db']['name']??'')!=='eifarma_local')exit("Somente banco local.\n");
$pdo=new PDO('mysql:host=127.0.0.1;dbname=eifarma_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);echo 'PASS: '.$message.PHP_EOL;}
$ddl=$pdo->query('SHOW CREATE TABLE products')->fetch(PDO::FETCH_NUM)[1];
$pdo->exec(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl));
$p=pending_mips()[0];
$pdo->prepare('INSERT INTO products(ean,name,category,brand,active) VALUES(?,?,?,?,0)')->execute([$p['ean'],$p['name'],'Medicamentos sem receita',$p['brand']]);
$read=fn()=>visible_products($pdo->query('SELECT * FROM products')->fetchAll());
check(count($read())===0,'pending medicine hidden');
set_medicine_visibility($pdo,$p['ean'],true);
check(count($read())===1&&$read()[0]['medicine']['registration']===$p['registration'],'approval exposes exact product and registration');
set_medicine_visibility($pdo,$p['ean'],true);
check(count($read())===1,'repeated approval is idempotent');
set_medicine_visibility($pdo,$p['ean'],false);
check(count($read())===0&&(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn()===1,'withdrawal hides product and preserves record');
try{set_medicine_visibility($pdo,'123',true);throw new RuntimeException('accepted unknown EAN');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'não encontrado'),'unknown EAN rejected');}
echo "Temporary test table only; live approvals unchanged.\n";
