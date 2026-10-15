<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: view/login.php');
    exit();
}

$destinosPorPerfil = [
    1 => 'view/admin/home_admin.php',
    2 => 'view/secretaria/pre_matriculas.php',
    3 => 'view/usuario/home_usuario.php'
];
$perfil = (int)($_SESSION['perfil_id'] ?? 0);

if (!isset($destinosPorPerfil[$perfil])) {
    http_response_code(403);
    exit('Perfil de usuário inválido.');
}

header('Location: ' . $destinosPorPerfil[$perfil]);
exit();
