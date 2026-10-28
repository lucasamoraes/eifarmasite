<?php
require __DIR__.'/app/bootstrap.php';require_once ROOT.'/app/banner-upload.php';
$id=(string)($_GET['id']??'');$size=(string)($_GET['size']??'desktop');
if(!preg_match('/^[a-f0-9]{32}$/D',$id)||!in_array($size,['desktop','mobile'],true)){http_response_code(404);exit;}
$known=array_column(custom_banners(),null,'id');if(!isset($known[$id])){http_response_code(404);exit;}
$file=ROOT.'/storage/banners/'.$id.'-'.$size.'.webp';if(!is_file($file)){http_response_code(404);exit;}
header('Content-Type: image/webp');header('X-Content-Type-Options: nosniff');header('Cache-Control: public, max-age=86400');readfile($file);
