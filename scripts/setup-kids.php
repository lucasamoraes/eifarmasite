<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$pdo=db();
if(!$pdo){fwrite(STDERR,"Banco não configurado.\n");exit(1);}
$sql=file_get_contents(dirname(__DIR__).'/infantil/database.sql');
if($sql===false){fwrite(STDERR,"Arquivo database.sql não encontrado.\n");exit(1);}
$pdo->exec($sql);
fwrite(STDOUT,"Catálogo infantil instalado/atualizado.\n");
