<?php
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/import.php';
function expect(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('FAIL: '.$name);echo 'PASS: '.$name.PHP_EOL;}
$rows=read_sheet(ROOT.'/PRODUTOS EIFARMA.xlsx','xlsx');
$report=analyze_import($rows);
expect($report['total']===3197,'read source workbook and sparse columns');
expect(count($report['products'])===504,'only 504 selected EANs accepted');
expect(!str_contains(json_encode($report['products']),'Valor de Compra'),'no purchase costs in catalog');
$seed=sample()[0];
$r=analyze_import([['Descricao','EAN'],['Original',$seed['ean']],['Duplicate',$seed['ean']],['Invalid','1234567890123'],['Medicamento','7896004817477']]);
expect(count($r['products'])===1&&$r['duplicates']===1&&count($r['excluded'])===2,'deduplication, checksum and unknown product exclusion');
expect($r['products'][0]['name']===$seed['name'],'import does not overwrite trusted product identity');
expect(!valid_ean('1.234567890123E+12'),'scientific notation rejected without inventing EAN');
try{analyze_import([['Produto','Codigo'],['Test','123']]);throw new RuntimeException('Missing header check failed');}catch(RuntimeException $e){expect(str_contains($e->getMessage(),'obrigatórias'),'missing headers rejected');}
echo json_encode(['rows'=>$report['total'],'accepted'=>count($report['products']),'duplicates'=>$report['duplicates'],'excluded'=>count($report['excluded'])]),PHP_EOL;
