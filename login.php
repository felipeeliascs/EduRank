<?php
/*
 * EduRank - autenticação administrativa mínima.
 * A senha é verificada apenas no servidor (password_verify).
 */
session_start();

$configFile = __DIR__ . '/config.php';
if (is_file($configFile)) {
    require $configFile;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$senha = isset($_POST['password']) ? (string) $_POST['password'] : '';
$hash = isset($PASSWORD_HASH) ? (string) $PASSWORD_HASH : '';

if ($hash !== '' && $hash !== 'COLE_AQUI_O_HASH_GERADO' && password_verify($senha, $hash)) {
    session_regenerate_id(true);
    $_SESSION['edurank_auth'] = true;
    header('Location: edit.php');
    exit;
}

// Pequena pausa para dificultar tentativas repetidas.
usleep(400000);
header('Location: index.html?erro=1');
exit;
