<?php
require dirname(__DIR__).'/lib/bootstrap.php';require_admin();verify_csrf();
$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$ok=0;$errors=[];
foreach($_FILES['images']['tmp_name']??[] as $i=>$tmp){if(($_FILES['images']['error'][$i]??1)!==UPLOAD_ERR_OK)continue;$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);if(!isset($allowed[$mime])){$errors[]=$_FILES['images']['name'][$i];continue;}$sku=preg_replace('/[^A-Za-z0-9_-]/','',pathinfo($_FILES['images']['name'][$i],PATHINFO_FILENAME));if(!$sku)continue;$filename=strtoupper($sku).'.'.$allowed[$mime];if(move_uploaded_file($tmp,dirname(__DIR__).'/uploads/kids_products/'.$filename)){$path='uploads/kids_products/'.$filename;$s=$pdo->prepare('UPDATE kids_products SET image=? WHERE UPPER(sku)=?');$s->execute([$path,strtoupper($sku)]);$ok++;}}
$_SESSION['flash']="$ok imagem(ns) enviada(s) e vinculadas pelo SKU.";header('Location: '.base_url('admin/index.php'));

