<?php
require dirname(__DIR__).'/lib/bootstrap.php';
require_admin();verify_csrf();
if(($_FILES['csv']['error']??1)!==UPLOAD_ERR_OK)exit('Falha no arquivo.');
$h=fopen($_FILES['csv']['tmp_name'],'r');
$first=fgets($h);rewind($h);
$delimiter=substr_count((string)$first,"\t")>substr_count((string)$first,';')?"\t":';';
fgetcsv($h,0,$delimiter);$count=0;
$stmt=$pdo->prepare('INSERT INTO kids_products (sku,name,presentation,category,age_group,price,old_price,image) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),presentation=VALUES(presentation),category=VALUES(category),age_group=VALUES(age_group),price=VALUES(price),old_price=VALUES(old_price),image=COALESCE(VALUES(image),image),active=1');
$categoryStmt=$pdo->prepare('INSERT IGNORE INTO kids_categories(name,sort_order) VALUES(?,99)');
$presentationStmt=$pdo->prepare('INSERT IGNORE INTO kids_presentations(name,sort_order) VALUES(?,99)');
$decimal=fn($v)=>(float)str_replace(',','.',preg_replace('/[^0-9,.-]/','',(string)$v));
while(($r=fgetcsv($h,0,$delimiter))!==false){
    if(count($r)<6)continue;
    [$sku,$name,$presentation,$category,$age,$price]=$r;$old=$r[6]??null;
    $sku=strtoupper(trim($sku));$presentation=trim($presentation);$category=trim($category);
    if($sku===''||trim($name)===''||$category==='')continue;
    $categoryStmt->execute([$category]);
    if($presentation!=='')$presentationStmt->execute([$presentation]);
    $image=null;
    foreach(['webp','jpg','jpeg','png'] as $ext){$candidate='uploads/products/'.$sku.'.'.$ext;if(is_file(dirname(__DIR__).'/'.$candidate)){$image=$candidate;break;}}
    $stmt->execute([$sku,trim($name),$presentation,$category,kids_age_value((string)$age),$decimal($price),$old!==''?$decimal($old):null,$image]);
    $count++;
}
fclose($h);
$_SESSION['flash']="$count produto(s) importado(s). Categorias, apresentações, idades e fotos foram padronizadas.";
header('Location: '.base_url('admin/index.php'));
