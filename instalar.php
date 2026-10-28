<?php
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/install.php';
header('Cache-Control: no-store');header('X-Frame-Options: DENY');header('X-Robots-Tag: noindex, nofollow');
$local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);
if(!$local&&(empty($_SERVER['HTTPS'])||$_SERVER['HTTPS']==='off')){http_response_code(403);exit('Abra esta página por HTTPS para instalar.');}
start_session();$error='';$success=false;$blocked=false;$ready=false;$email='eifarma@gmail.com';
try{
    $pdo=db();if(!$pdo)throw new RuntimeException('Configure a conexão com o banco em config.local.php primeiro.');
    $blocked=(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()>0;
    $ready=strlen((string)($config['setup_key']??''))>=32;
    if($_SERVER['REQUEST_METHOD']==='POST'){
        verify_csrf();
        if($blocked)throw new RuntimeException('A instalação está bloqueada: já existe um gerente.');
        $email=trim((string)($_POST['email']??''));
        create_first_manager($pdo,(string)($config['setup_key']??''),(string)($_POST['setup_key']??''),$email,(string)($_POST['password']??''),(string)($_POST['confirmation']??''));
        $success=true;$blocked=true;session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
    }
}catch(PDOException $e){error_log('EI Farma installer database failure: '.$e->getCode());$error='Não foi possível acessar as tabelas. Confira a conexão e a importação de database.sql.';}
catch(Throwable $e){$error=$e->getMessage();}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Criar primeiro acesso — EI Farma</title><link rel="stylesheet" href="assets/style.css"></head><body class="admin-body"><main class="container admin-main"><section class="panel login-panel"><span class="eyebrow">EI! FARMA</span><h1>Criar meu acesso</h1>
<?php if($error):?><p class="notice error" role="alert"><?=e($error)?></p><?php endif;?>
<?php if($success):?><p class="notice" role="status">Acesso criado. Entre com o e-mail e a senha que você escolheu. A instalação foi bloqueada.</p><a class="button" href="admin.php">Entrar no painel</a>
<?php elseif($blocked):?><p>Já existe uma conta de gerente. Esta instalação está bloqueada e não altera senhas existentes.</p><a class="button" href="admin.php">Entrar no painel</a>
<?php elseif(!$ready):?><p>Para habilitar esta tela, adicione a chave privada de instalação no campo <code>setup_key</code> de <code>config.local.php</code>. Ela deve ter pelo menos 32 caracteres.</p>
<?php else:?><p>Escolha a senha do seu painel. A chave de instalação é usada somente para criar o primeiro acesso.</p><form method="post"><?=csrf()?><label for="setup_key">Chave de instalação</label><input id="setup_key" name="setup_key" type="password" required maxlength="128" autocomplete="off"><label for="email">E-mail</label><input id="email" name="email" type="email" required maxlength="190" value="<?=e($email)?>" autocomplete="username"><label for="password">Sua senha (mínimo de 12 caracteres)</label><input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"><label for="confirmation">Repita sua senha</label><input id="confirmation" name="confirmation" type="password" required minlength="12" maxlength="72" autocomplete="new-password"><button class="button" type="submit">Criar meu acesso</button></form><?php endif;?>
</section></main></body></html>
