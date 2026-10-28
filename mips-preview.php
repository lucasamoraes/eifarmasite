<?php
require __DIR__.'/app/bootstrap.php';
start_session();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
if(empty($_SESSION['user']) || time()-($_SESSION['last_seen']??0)>1800){http_response_code(403);exit('Entre na área do gerente para visualizar esta prévia.');}
$medicinePreview=true;
require __DIR__.'/index.php';
