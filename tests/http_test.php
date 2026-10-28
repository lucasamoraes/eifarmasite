<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$access=file_get_contents($root.'/storage/acesso-local.txt');
preg_match('/Senha: ([a-f0-9]+)/',$access,$match);$password=$match[1];
$cookie=tempnam($root.'/storage','http-test-');
function request(string $url,?array $post=null):array{
    global $cookie;$ch=curl_init('http://localhost/eifarma/'.$url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>30]);
    if($post!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
    $body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);return [$code,$body];
}
function expect(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('FAIL: '.$name);echo 'PASS: '.$name.PHP_EOL;}
function token(string $html):string{preg_match('/name="csrf" value="([a-f0-9]+)"/',$html,$matches);return $matches[1]??'';}
try{
    [$status,$html]=request('admin.php');expect($status===200&&str_contains($html,'Bem-vindo de volta'),'login page served');
    [$status,$html]=request('admin.php',['csrf'=>token($html),'action'=>'login','email'=>'gerente@eifarma.local','password'=>$password]);
    expect($status===200&&str_contains($html,'Importar produtos'),'authenticated dashboard');$csrf=token($html);
    expect(str_contains($html,'279 medicamentos no sistema')&&str_contains($html,'Cadastrado, não publicado'),'pending medicines listed in authenticated manager dashboard');
    expect(str_contains($html,'Aprovar e publicar')&&str_contains($html,'Retirar da vitrine'),'manual publication controls available');
    [$denied]=request('admin.php',['action'=>'medicine_visibility','csrf'=>'invalid','ean'=>'7896331702545','visible'=>'1']);expect($denied===403,'medicine approval requires valid CSRF');
    [$status,$previewHtml]=request('mips-preview.php');
    expect($status===200&&str_contains($previewHtml,'1438102930031')&&str_contains($previewHtml,'Prévia privada'),'authenticated medicine preview contains completed card');
    [$status,$html]=request('admin.php',['csrf'=>$csrf,'action'=>'preview','mode'=>'replace','sheet'=>new CURLFile($root.'/PRODUTOS EIFARMA.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','produtos.xlsx')]);
    expect($status===200&&str_contains($html,'504 EANs elegíveis')&&str_contains($html,'2693 excluídos'),'authenticated XLSX upload and preview');
    expect(!str_contains($html,'14.6318'),'purchase cost not rendered');
    [$status,$body]=request('admin.php',['action'=>'apply','csrf'=>'invalid']);expect($status===403,'mutation with invalid CSRF rejected');
    request('admin.php',['action'=>'cancel','csrf'=>$csrf]);
    request('admin.php',['action'=>'logout','csrf'=>$csrf]);
    [$status,$html]=request('admin.php');expect(str_contains($html,'Bem-vindo de volta'),'logout removes access');
    [$status]=request('mips-preview.php');expect($status===403,'medicine preview not accessible without authentication');
    foreach(['data/sample.json','PRODUTOS%20EIFARMA.xlsx','config.local.php','storage/acesso-local.txt'] as $path){[$code]=request($path);expect($code===403,'private file blocked: '.$path);}
    [$status,$html]=request('');expect($status===200&&str_contains($html,'5565999728431')&&str_contains($html,'1438102930031')&&str_contains($html,'SE PERSISTIREM OS SINTOMAS'),'storefront and WhatsApp configured');
}finally{unlink($cookie);}
