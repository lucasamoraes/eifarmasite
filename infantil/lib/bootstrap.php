<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/app/bootstrap.php';
start_session();

function kids_db(): PDO {
    $pdo=db();
    if(!$pdo)throw new RuntimeException('Banco do projeto principal não configurado.');
    return $pdo;
}
function kids_base(string $path=''): string {
    $script=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/infantil'));
    $root=preg_replace('#/admin$#','',$script);
    return rtrim($root,'/').'/'.ltrim($path,'/');
}
function kids_require_admin(): void {
    if(empty($_SESSION['user'])){header('Location: '.kids_base('admin/login.php'));exit;}
    if(time()-($_SESSION['last_seen']??0)>1800){unset($_SESSION['user']);header('Location: '.kids_base('admin/login.php'));exit;}
    $_SESSION['last_seen']=time();
}
function kids_setting(string $key,string $default=''): string {
    $s=kids_db()->prepare('SELECT setting_value FROM kids_settings WHERE setting_key=?');$s->execute([$key]);
    $v=$s->fetchColumn();return $v===false?$default:(string)$v;
}
$pdo=kids_db();
function base_url(string $path=''): string { return kids_base($path); }
function require_admin(): void { kids_require_admin(); }
function setting(string $key,string $default=''): string { return kids_setting($key,$default); }
function csrf_token(): string { return (string)($_SESSION['csrf']??''); }
