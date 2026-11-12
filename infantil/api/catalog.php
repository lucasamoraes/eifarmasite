<?php
require dirname(__DIR__).'/lib/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$products=$pdo->query('SELECT id,sku,name,presentation AS size,category,age_group AS age,price,old_price AS oldPrice,image FROM kids_products WHERE active=1 ORDER BY sort_order,name')->fetchAll();
echo json_encode(['products'=>$products,'settings'=>['name'=>setting('store_name','EiFarma Kids'),'whatsapp'=>setting('whatsapp'),'logo'=>setting('logo','assets/logo-eifarma.png')]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

