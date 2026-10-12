<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Descobre o caminho base até a pasta do projeto (ex: /CRUD_LOGIN_PCC/ ou /noturno/CRUD_LOGIN_PCC/)
$caminhoScript = $_SERVER['SCRIPT_NAME'];
$posicaoProjeto = strpos($caminhoScript, 'CRUD_LOGIN_PCC');
$classesCorpo = preg_split('/\s+/', trim($classeCorpo ?? ''));
$carregarEstilosHome = (bool)array_intersect(
    ['admin-dashboard-page', 'user-home-page', 'service-requests-page', 'profile-page'],
    $classesCorpo ?: []
);

if ($posicaoProjeto !== false) {
    // Pega do início da URL até o final do nome da pasta do projeto
    $baseDir = substr($caminhoScript, 0, $posicaoProjeto + strlen('CRUD_LOGIN_PCC')) . '/';
} else {
    // Fallback padrão para a raiz do servidor
    $baseDir = '/CRUD_LOGIN_PCC/';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestão</title>

    <!-- CSS com caminho dinâmico -->
    <link rel="stylesheet" href="<?= $baseDir ?>assets/css/estilo.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $baseDir ?>assets/css/estilo_topo.css?v=<?= time(); ?>">
    <?php if ($carregarEstilosHome): ?>
        <link rel="stylesheet" href="<?= $baseDir ?>assets/css/estilo_home.css?v=<?= time() ?>">
    <?php endif; ?>
</head>
<body class="<?= !empty($exibeMenuAdministrativo) ? 'manager-body ' : '' ?><?= htmlspecialchars($classeCorpo ?? '', ENT_QUOTES, 'UTF-8') ?>">

<header class="top-bar">
    <div class="top-bar-container">
        
        <!-- Logo / Marca -->
        <div class="top-bar-brand">
            <img src="<?= $baseDir ?>assets/img/sua_logo.png" alt="Logo" class="top-bar-logo">
            <a href="<?= $baseDir ?>index.php" class="top-bar-title">Sistema de Gestão</a>
        </div>

        <!-- Botões de Navegação -->
        <nav class="top-bar-nav">
            <?php if (isset($_SESSION['usuario_id']) && (int)($_SESSION['perfil_id'] ?? 0) === 3): ?>
                <a href="<?= $baseDir ?>view/usuario/home_usuario.php" class="top-bar-btn">Área Usuário</a>
            <?php endif; ?>
        </nav>

        <!-- Informações do Usuário -->
        <div class="top-bar-user">
            <?php if (isset($_SESSION['usuario_nome'])): ?>
                <span>Olá, <strong><?= htmlspecialchars($_SESSION['usuario_nome']); ?></strong></span>
                <?php if (!in_array((int)($_SESSION['perfil_id'] ?? 0), [1, 2], true)): ?>
                    <a href="<?= $baseDir ?>controller/UsuarioController.php?acao=logout" class="btn-logout">Sair</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= $baseDir ?>view/login.php" class="top-bar-btn">Entrar</a>
            <?php endif; ?>
        </div>

    </div>
</header>

<main class="conteudo-principal">