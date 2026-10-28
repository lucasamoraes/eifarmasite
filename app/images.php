<?php
declare(strict_types=1);
function lookup_image(string $ean): ?array {
    if(!preg_match('/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/D',$ean))return null;
    $url='https://cdn-cosmos.bluesoft.com.br/products/'.$ean;
    $ch=curl_init($url);
    $body='';
    curl_setopt_array($ch,[CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk)use(&$body):int{if(strlen($body)+strlen($chunk)>5*1024*1024)return 0;$body.=$chunk;return strlen($chunk);}]);
    $result=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    // The CDN may omit Content-Type; inspect actual image bytes instead.
    if($result===false||$code!==200||@getimagesizefromstring($body)===false)return null;
    return ['image'=>$url,'image_source'=>'https://cosmos.bluesoft.com.br/produtos/'.$ean];
}
