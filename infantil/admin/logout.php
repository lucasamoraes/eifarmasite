<?php require dirname(__DIR__).'/lib/bootstrap.php'; unset($_SESSION['user'],$_SESSION['admin_name'],$_SESSION['last_seen']); header('Location: '.base_url('admin/login.php'));
