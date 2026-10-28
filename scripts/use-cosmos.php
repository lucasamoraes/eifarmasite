<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
$pdo=db();if(!$pdo)exit("Sem banco configurado. A amostra já usa Cosmos.\n");
$stmt=$pdo->prepare('UPDATE products SET image=?,image_source=? WHERE ean=?');
$pdo->beginTransaction();
try{
    foreach(sample() as $p)$stmt->execute([$p['image'],$p['image_source'],$p['ean']]);
    $pdo->commit();echo "Fotos da amostra atualizadas para o CDN Cosmos.\n";
}catch(Throwable $error){$pdo->rollBack();throw $error;}
