<?php
declare(strict_types=1);
function create_first_manager(PDO $pdo,string $configuredKey,string $key,string $email,string $password,string $confirmation):void {
    if(strlen($configuredKey)<32||!hash_equals($configuredKey,$key))throw new RuntimeException('Chave de instalação inválida ou não configurada.');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)throw new RuntimeException('Informe um e-mail válido.');
    if(strlen($password)<12||strlen($password)>72)throw new RuntimeException('Use uma senha de 12 a 72 bytes.');
    if($password!==$confirmation)throw new RuntimeException('As senhas não coincidem.');
    $lock='eifarma-install-'.substr(hash('sha256',(string)$pdo->query('SELECT DATABASE()')->fetchColumn()),0,40);
    $stmt=$pdo->prepare('SELECT GET_LOCK(?,5)');$stmt->execute([$lock]);
    if((int)$stmt->fetchColumn()!==1)throw new RuntimeException('Outra instalação está em andamento. Tente novamente.');
    try{
        $pdo->beginTransaction();
        if((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()>0)throw new RuntimeException('A instalação está bloqueada: já existe um gerente.');
        $pdo->prepare('INSERT INTO users(email,password_hash) VALUES(?,?)')->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    finally{$pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lock]);}
}
