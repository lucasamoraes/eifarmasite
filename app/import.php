<?php
declare(strict_types=1);
function valid_ean(string $ean): bool {
    if (!preg_match('/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/D',$ean)) return false;
    $sum=0; $weight=3;
    for($i=strlen($ean)-2;$i>=0;$i--){$sum+=(int)$ean[$i]*$weight;$weight=4-$weight;}
    return (10-$sum%10)%10===(int)substr($ean,-1);
}
function read_sheet(string $path,string $extension): array {
    if ($extension==='csv') {
        $file=fopen($path,'rb');$first=fgets($file);rewind($file);
        $delimiter=substr_count($first?:'',';')>substr_count($first?:'',',')?';':',';
        $rows=[];
        while(($row=fgetcsv($file,0,$delimiter))!==false){$rows[]=$row;if(count($rows)>15001)throw new RuntimeException('Limite de 15.000 linhas por importação.');}
        fclose($file);return $rows;
    }
    if($extension!=='xlsx')throw new RuntimeException('Use um arquivo XLSX ou CSV.');
    $zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('Arquivo Excel inválido.');
    $total=0;for($i=0;$i<$zip->numFiles;$i++){$stat=$zip->statIndex($i);$total+=$stat['size'];if($total>60*1024*1024){$zip->close();throw new RuntimeException('Planilha descompactada excede 60 MB.');}}
    $parse=function(string $text){if(stripos($text,'<!DOCTYPE')!==false||stripos($text,'<!ENTITY')!==false)throw new RuntimeException('Estrutura XML não permitida.');$xml=simplexml_load_string($text,SimpleXMLElement::class,LIBXML_NONET);if(!$xml)throw new RuntimeException('Planilha com XML inválido.');return $xml;};
    libxml_use_internal_errors(true);$strings=[];
    $shared=$zip->getFromName('xl/sharedStrings.xml');
    if($shared!==false){$xml=$parse($shared);foreach($xml->si as $si){$text='';foreach($si->xpath('.//*[local-name()="t"]') as $t)$text.=(string)$t;$strings[]=$text;}}
    // Resolve first worksheet through workbook relationships rather than assuming sheet1.
    $workbook=$parse($zip->getFromName('xl/workbook.xml')?:'');
    $sheet=$workbook->sheets->sheet[0];
    if(!$sheet)throw new RuntimeException('A planilha não contém abas.');
    $rid=(string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
    $rels=$parse($zip->getFromName('xl/_rels/workbook.xml.rels')?:'');$target='';
    foreach($rels->Relationship as $rel)if((string)$rel['Id']===$rid)$target=(string)$rel['Target'];
    if(!$target||str_contains($target,'..')||str_contains($target,':'))throw new RuntimeException('Referência de aba inválida.');
    $sheetPath=str_starts_with($target,'/')?ltrim($target,'/'):'xl/'.$target;
    $xml=$parse($zip->getFromName($sheetPath)?:'');$rows=[];
    foreach($xml->sheetData->row as $row){
        $values=[];
        foreach($row->c as $cell){
            preg_match('/^([A-Z]+)\d+$/',(string)$cell['r'],$match);$column=0;
            foreach(str_split($match[1]??'A') as $letter)$column=$column*26+ord($letter)-64;
            if($column>100)continue;
            $type=(string)$cell['t'];$value=(string)$cell->v;
            if($type==='s')$value=$strings[(int)$value]??'';
            if($type==='inlineStr'){$value='';foreach($cell->xpath('.//*[local-name()="t"]') as $t)$value.=(string)$t;}
            $values[$column-1]=$value;
        }
        $rows[]=$values;if(count($rows)>15001)throw new RuntimeException('Limite de 15.000 linhas por importação.');
    }
    $zip->close();return $rows;
}
function analyze_import(array $rows): array {
    if(count($rows)<2)throw new RuntimeException('A planilha está vazia.');
    $headers=array_map(fn($v)=>strtolower(trim(str_replace("\xEF\xBB\xBF",'',(string)$v))),array_shift($rows));
    $eanIndex=array_search('ean',$headers,true);$nameIndex=array_search('descricao',$headers,true);
    if($nameIndex===false)$nameIndex=array_search('descrição',$headers,true);
    if($eanIndex===false||$nameIndex===false)throw new RuntimeException('As colunas EAN e Descricao são obrigatórias.');
    $allowed=array_column(approved_products(),null,'ean');$seen=[];$accepted=[];$excluded=[];$duplicates=0;$total=0;
    foreach($rows as $index=>$row){
        $ean=trim((string)($row[$eanIndex]??''));$name=trim((string)($row[$nameIndex]??''));
        if($ean===''&&$name==='')continue;$total++;
        if(!valid_ean($ean)){$excluded[]=['row'=>$index+2,'name'=>mb_substr($name,0,150),'reason'=>'EAN inválido ou código interno'];continue;}
        if(isset($seen[$ean])){$duplicates++;continue;}$seen[$ean]=true;
        if(!isset($allowed[$ean])){$excluded[]=['row'=>$index+2,'name'=>mb_substr($name,0,150),'reason'=>'Fora da seleção de higiene, beleza e fraldas: não publicado'];continue;}
        $accepted[]=$allowed[$ean];
    }
    if(!$total)throw new RuntimeException('A planilha não possui produtos.');
    return ['products'=>$accepted,'total'=>$total,'duplicates'=>$duplicates,'excluded'=>$excluded];
}
function apply_import(PDO $pdo,array $preview,string $mode,int $userId): array {
    if(!in_array($mode,['add','replace'],true)||empty($preview['products']))throw new RuntimeException('Nenhum produto elegível. O catálogo foi preservado.');
    $inserted=0;$duplicates=(int)$preview['duplicates'];
    $pdo->beginTransaction();
    try {
        // Serializes competing imports, including the replacement operation.
        $pdo->query('SELECT id FROM users ORDER BY id FOR UPDATE')->fetchAll();
        if($mode==='replace')$pdo->exec('UPDATE products SET active=0');
        $find=$pdo->prepare('SELECT id FROM products WHERE ean=?');
        $insert=$pdo->prepare('INSERT INTO products(ean,name,category,brand,image,image_source,active) VALUES(?,?,?,?,?,?,1)');
        $activate=$pdo->prepare('UPDATE products SET active=1 WHERE ean=?');
        foreach($preview['products'] as $p){
            $find->execute([$p['ean']]);
            if($find->fetch()){$duplicates++;if($mode==='replace')$activate->execute([$p['ean']]);continue;}
            $insert->execute([$p['ean'],$p['name'],$p['category'],$p['brand'],$p['image'],$p['image_source']]);$inserted++;
        }
        $pdo->prepare('INSERT INTO imports(user_id,mode,total_rows,accepted,duplicates,excluded) VALUES(?,?,?,?,?,?)')->execute([$userId,$mode,$preview['total'],count($preview['products']),$duplicates,count($preview['excluded'])]);
        $pdo->commit();return ['inserted'=>$inserted,'duplicates'=>$duplicates];
    } catch(Throwable $error){$pdo->rollBack();throw $error;}
}
