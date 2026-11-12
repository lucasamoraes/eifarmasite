<?php
require dirname(__DIR__).'/lib/bootstrap.php';require_admin();verify_csrf();$save=$pdo->prepare('INSERT INTO kids_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');$save->execute(['store_name',trim($_POST['store_name'])]);$save->execute(['whatsapp',preg_replace('/\D/','',$_POST['whatsapp'])]);
if(($_FILES['logo']['error']??1)===UPLOAD_ERR_OK){$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo']['tmp_name']);if(isset($allowed[$mime])){$path='uploads/logo.'.$allowed[$mime];move_uploaded_file($_FILES['logo']['tmp_name'],dirname(__DIR__).'/'.$path);$save->execute(['logo',$path]);}}
$_SESSION['flash']='Configurações atualizadas.';header('Location: '.base_url('admin/index.php'));

