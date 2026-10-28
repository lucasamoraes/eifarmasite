<?php
declare(strict_types=1);
date_default_timezone_set('America/Sao_Paulo');
define('ROOT', dirname(__DIR__));
$config = ['db' => null, 'whatsapp' => '5565999728431', 'cosmos_token' => '', 'cosmos_user_agent' => ''];
if (is_file(ROOT . '/config.local.php')) $config = array_replace($config, require ROOT . '/config.local.php');
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function db(): ?PDO {
    global $config;
    static $pdo;
    if (!$config['db']) return null;
    if (!$pdo) {
        $c = $config['db'];
        $pdo = new PDO('mysql:host='.$c['host'].';dbname='.$c['name'].';charset=utf8mb4', $c['user'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
        $pdo->exec("SET time_zone = '-03:00'");
    }
    return $pdo;
}
function sample(): array { return json_decode(file_get_contents(ROOT.'/data/sample.json'), true, 512, JSON_THROW_ON_ERROR); }
function prepared_mips(): array { return json_decode(file_get_contents(ROOT.'/data/mips-ready.json'), true, 512, JSON_THROW_ON_ERROR); }
function pending_mips(): array { return json_decode(file_get_contents(ROOT.'/data/mips-pending.json'), true, 512, JSON_THROW_ON_ERROR); }
function approved_products(): array { return array_merge(json_decode(file_get_contents(ROOT.'/data/catalog.json'), true, 512, JSON_THROW_ON_ERROR),prepared_mips()); }
function visible_products(array $rows): array {
    $allowed=array_column(approved_products(),null,'ean');
    $pending=array_column(pending_mips(),null,'ean');$result=[];
    foreach($rows as $p){
        if(isset($p['active'])&&!(int)$p['active'])continue;
        if(isset($allowed[$p['ean']])){$result[]=isset($allowed[$p['ean']]['medicine'])?$allowed[$p['ean']]:$p;continue;}
        if(isset($pending[$p['ean']])){
            $m=$pending[$p['ean']];
            $p['medicine']=['ingredient'=>$m['ingredient'],'registration'=>$m['registration'],'manufacturer'=>$m['brand']];
            $result[]=$p;
        }
    }
    return $result;
}
function set_medicine_visibility(PDO $pdo,string $ean,bool $active): void {
    $medicines=array_column(array_merge(prepared_mips(),pending_mips()),null,'ean');
    if(!isset($medicines[$ean]))throw new RuntimeException('Medicamento não encontrado na seleção cadastrada.');
    $pdo->beginTransaction();
    try{
        $pdo->query('SELECT id FROM users ORDER BY id FOR UPDATE')->fetchAll();
        $find=$pdo->prepare("SELECT id FROM products WHERE ean=? AND category='Medicamentos sem receita' FOR UPDATE");$find->execute([$ean]);
        if(!$find->fetch())throw new RuntimeException('Medicamento não encontrado no banco.');
        $pdo->prepare('UPDATE products SET active=? WHERE ean=?')->execute([(int)$active,$ean]);
        $pdo->commit();
    }catch(Throwable $e){$pdo->rollBack();throw $e;}
}
function catalog(bool $medicinePreview=false): array {
    $db = db();
    if (!$db) return $medicinePreview ? prepared_mips() : approved_products();
    if ($medicinePreview) return prepared_mips();
    return visible_products($db->query('SELECT ean,name,category,brand,image,image_source FROM products WHERE active=1 ORDER BY sort_order,id')->fetchAll());
}
function banners(): array {
    require_once ROOT.'/app/banner-upload.php';
    $custom=array_values(array_filter(custom_banners(),fn($b)=>!empty($b['active'])));
    if($custom)return array_map(fn($b)=>['title'=>$b['title'],'image'=>'banner.php?id='.$b['id'],'mobile'=>$b['mobile']?'banner.php?id='.$b['id'].'&size=mobile':'','link'=>$b['link']],$custom);
    $defaults = [
        ['title'=>'Seu cuidado começa com um oi.', 'subtitle'=>'Escolha seus produtos favoritos. A EI! FARMA recebe seu pedido pelo WhatsApp.', 'eyebrow'=>'PERTINHO DE VOCÊ, TODOS OS DIAS', 'category'=>'', 'theme'=>'blue', 'cta'=>'Explorar produtos'],
        ['title'=>'Um momento só seu.', 'subtitle'=>'Encontre os essenciais de corpo e banho para a sua rotina de cuidado.', 'eyebrow'=>'CUIDADOS QUE FAZEM PARTE DA ROTINA', 'category'=>'Corpo e banho', 'theme'=>'rose', 'cta'=>'Ver corpo e banho'],
        ['title'=>'Cuidado da raiz às pontas.', 'subtitle'=>'Shampoos e condicionadores para acompanhar o seu dia a dia.', 'eyebrow'=>'ESPECIAIS PARA SEUS CABELOS', 'category'=>'Cabelos', 'theme'=>'blue', 'cta'=>'Ver produtos para cabelos'],
    ];
    $db = db();
    if (!$db) return $defaults;
    $rows=$db->query('SELECT template FROM campaigns WHERE active=1 AND (starts_on IS NULL OR starts_on<=CURRENT_DATE) AND (ends_on IS NULL OR ends_on>=CURRENT_DATE) ORDER BY id')->fetchAll();
    return $rows ? array_map(fn($r)=>$defaults[(int)$r['template']],$rows) : [$defaults[0]];
}
function start_session(): void {
    if (session_status()===PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Strict']);
        ini_set('session.use_strict_mode','1'); session_start();
    }
    if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
}
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Sessão expirada. Recarregue a página.'); }
}
function icon(string $name): string {
    if($name==='chat')return '<img class="whatsapp-icon" src="images/whats.jfif" alt="" aria-hidden="true">';
    $paths = [
        'bag'=>'<path d="M2 3h3l3 13h11l3-9H6"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'chat'=>'<path d="M21 11.5a9 9 0 0 1-13 8L3 21l1.5-5A9 9 0 1 1 21 11.5Z"/><path d="M8 9c1 4 3 6 7 7l1-3-3-1-1 1-2-2 1-1-1-3-2 2Z"/>',
        'arrow'=>'<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'heart'=>'<path d="M20.5 4.5a5 5 0 0 0-7 0L12 6l-1.5-1.5a5 5 0 0 0-7 7L12 20l8.5-8.5a5 5 0 0 0 0-7Z"/>',
        'bottle'=>'<path d="M9 3h6v4l3 3v11H6V10l3-3V3Z"/><path d="M9 3h8M6 14h12"/>',
        'drop'=>'<path d="M12 2S4 11 4 15a8 8 0 0 0 16 0c0-4-8-13-8-13Z"/><path d="M8 15a4 4 0 0 0 4 4"/>',
        'sun'=>'<circle cx="12" cy="12" r="4"/><path d="M12 1v3m0 16v3M1 12h3m16 0h3M4 4l2 2m12 12 2 2M4 20l2-2M18 6l2-2"/>',
        'smile'=>'<circle cx="12" cy="12" r="9"/><path d="M8 14s1 3 4 3 4-3 4-3M8 8h.01M16 8h.01"/>',
        'grid'=>'<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'close'=>'<path d="m6 6 12 12M6 18 18 6"/>',
        'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 22v-2a8 8 0 0 1 16 0v2"/>',
    ];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round">'.($paths[$name]??$paths['heart']).'</svg>';
}
