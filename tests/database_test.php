<?php
require dirname(__DIR__).'/app/bootstrap.php';require ROOT.'/app/import.php';
if(($config['db']['name']??'')!=='eifarma_local')exit("Teste permitido somente no banco local do projeto.\n");
$pdo=new PDO('mysql:host=127.0.0.1;dbname=eifarma_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function expect(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('FAIL: '.$name);echo 'PASS: '.$name.PHP_EOL;}
// Temporary tables shadow the real tables for this connection only.
$productsDDL=$pdo->query('SHOW CREATE TABLE products')->fetch(PDO::FETCH_NUM)[1];
$importsDDL=$pdo->query('SHOW CREATE TABLE imports')->fetch(PDO::FETCH_NUM)[1];
$importsDDL=preg_replace('/,\s*CONSTRAINT[^\n]+/','',$importsDDL);
$pdo->exec(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$productsDDL));
$pdo->exec(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$importsDDL));
$seed=sample();$preview=['products'=>[$seed[0],$seed[1]],'total'=>2,'duplicates'=>0,'excluded'=>[]];
$result=apply_import($pdo,$preview,'add',1);expect($result['inserted']===2,'add inserts selected products');
$result=apply_import($pdo,$preview,'add',1);expect($result['inserted']===0&&$result['duplicates']===2,'existing EANs ignored');
$preview['products']=[$seed[1]];apply_import($pdo,$preview,'replace',1);
expect((int)$pdo->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn()===1,'replace inactivates missing products');
expect((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn()===2,'replace preserves stored records');
$preview['products']=[];
try{apply_import($pdo,$preview,'replace',1);throw new RuntimeException('Empty replacement accepted');}catch(RuntimeException $error){expect(str_contains($error->getMessage(),'preservado'),'empty replacement refused');}
expect((int)$pdo->query('SELECT COUNT(*) FROM imports')->fetchColumn()===3,'history records successful imports only');
echo "Temporary tables discarded when connection closes; live catalog untouched.\n";
