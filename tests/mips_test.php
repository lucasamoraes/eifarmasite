<?php
require dirname(__DIR__).'/app/bootstrap.php';
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);echo 'PASS: '.$message.PHP_EOL;}
$items=prepared_mips();
check(count($items)===1,'one completed medicine card');
check($items[0]['ean']==='7896523201887','exact presentation EAN');
check($items[0]['medicine']['registration']==='1438102930031','exact CMED registration');
foreach($items as $p){foreach(['ingredient','registration','manufacturer','indication','usage','warning','symptoms_warning','leaflet','registration_status'] as $key)check(!empty($p['medicine'][$key]),'medicine field '.$key);}
check(count(approved_products())===504,'503 care products plus one completed medicine');
check(in_array($items[0]['ean'],array_column(approved_products(),'ean'),true),'completed medicine accepted for import');
