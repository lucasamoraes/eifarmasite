<?php
function custom_banners():array {
    $path=ROOT.'/storage/banners.json';
    if(!is_file($path))return [];
    $f=fopen($path,'r');if(!$f)throw new RuntimeException('Não foi possível ler banners.');
    flock($f,LOCK_SH);try{return json_decode(stream_get_contents($f)?:'[]',true,512,JSON_THROW_ON_ERROR);}finally{flock($f,LOCK_UN);fclose($f);}
}
function update_custom_banners(callable $update):void {
    $f=fopen(ROOT.'/storage/banners.json','c+');if(!$f)throw new RuntimeException('A pasta storage precisa permitir gravação.');
    flock($f,LOCK_EX);
    try{$rows=json_decode(stream_get_contents($f)?:'[]',true,512,JSON_THROW_ON_ERROR);$rows=$update($rows);$json=json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);rewind($f);ftruncate($f,0);fwrite($f,$json);fflush($f);}finally{flock($f,LOCK_UN);fclose($f);}
}
function validate_banner_file(array $file):void {
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Selecione uma imagem WebP válida (até 5 MB).');
    if(($file['size']??0)>5*1024*1024)throw new RuntimeException('Cada imagem deve ter no máximo 5 MB.');
    $info=@getimagesize($file['tmp_name']);
    if(!$info||($info['mime']??'')!=='image/webp')throw new RuntimeException('O arquivo precisa ser uma imagem WebP real.');
    if($info[0]>4096||$info[1]>4096||$info[0]<200||$info[1]<100)throw new RuntimeException('Dimensões permitidas: de 200 × 100 até 4096 × 4096 pixels.');
}
function upload_custom_banner(array $post,array $files):void {
    $title=trim((string)($post['banner_title']??''));
    if($title===''||mb_strlen($title)>150)throw new RuntimeException('Informe um título de até 150 caracteres.');
    $link=trim((string)($post['banner_link']??''));
    if($link!==''&&(!filter_var($link,FILTER_VALIDATE_URL)||parse_url($link,PHP_URL_SCHEME)!=='https'))throw new RuntimeException('O link deve começar com https://.');
    $desktop=$files['banner_desktop']??[];$mobile=$files['banner_mobile']??[];
    validate_banner_file($desktop);$hasMobile=($mobile['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
    if($hasMobile)validate_banner_file($mobile);
    $dir=ROOT.'/storage/banners';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Não foi possível criar a pasta de banners.');
    $id=bin2hex(random_bytes(16));$saved=[];
    try{
        foreach(['desktop'=>$desktop]+($hasMobile?['mobile'=>$mobile]:[]) as $kind=>$file){$path=$dir.'/'.$id.'-'.$kind.'.webp';if(!is_uploaded_file($file['tmp_name'])||!move_uploaded_file($file['tmp_name'],$path))throw new RuntimeException('Não foi possível salvar a imagem.');$saved[]=$path;}
        update_custom_banners(function($rows)use($id,$title,$link,$hasMobile){if(count($rows)>=30)throw new RuntimeException('Limite de 30 banners cadastrados.');$rows[]=['id'=>$id,'title'=>$title,'link'=>$link,'mobile'=>$hasMobile,'active'=>true];return $rows;});
    }catch(Throwable $e){foreach($saved as $path)unlink($path);throw $e;}
}
