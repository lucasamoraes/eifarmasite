<?php
require dirname(__DIR__).'/app/bootstrap.php';require ROOT.'/app/install.php';
if(($config['db']['name']??'')!=='eifarma_local')exit('Somente banco local.');
$pdo=new PDO('mysql:host=127.0.0.1;dbname=eifarma_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$ddl=$pdo->query('SHOW CREATE TABLE users')->fetch(PDO::FETCH_NUM)[1];$pdo->exec(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl));
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);echo 'PASS: '.$message.PHP_EOL;}
$key=bin2hex(random_bytes(32));$password=bin2hex(random_bytes(16));
foreach([['wrong',$password,$password],[$key,'short','short'],[$key,$password,'different']] as [$provided,$pass,$confirm]){
 try{create_first_manager($pdo,$key,$provided,'test@example.com',$pass,$confirm);throw new LogicException('Invalid request accepted');}catch(RuntimeException $e){}
 check((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()===0,'invalid credentials leave database empty');
}
create_first_manager($pdo,$key,$key,'test@example.com',$password,$password);
$user=$pdo->query('SELECT * FROM users')->fetch();check(password_verify($password,$user['password_hash']),'password stored as verifiable hash');
try{create_first_manager($pdo,$key,$key,'other@example.com',$password,$password);throw new LogicException('Second manager accepted');}catch(RuntimeException $e){}
check((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()===1,'installer blocks second account');
echo "Only temporary table used; actual managers unchanged.\n";
