<?php
// Router only for the local PHP preview; Apache uses .htaccess.
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
if(preg_match('~(?:^|/)(?:storage|data|scripts|app|tests|\.git)(?:/|$)|\.(?:xlsx|csv|sql|md|py|log)$|/config[^/]*\.php$|/\.~i',$path)){http_response_code(403);exit('Acesso negado.');}
if($path!=='/'&&is_file(__DIR__.$path))return false;
if($path==='/'||$path==='/index.php'){require __DIR__.'/index.php';return;}
http_response_code(404);echo 'Página não encontrada.';
