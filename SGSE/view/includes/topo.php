<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$caminhoScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$posicaoView = strpos($caminhoScript, '/view/');
$classesCorpo = preg_split('/\s+/', trim($classeCorpo ?? ''));
$carregarEstilosHome = (bool)array_intersect(
    ['admin-dashboard-page', 'user-home-page', 'service-requests-page', 'profile-page'],
    $classesCorpo ?: []
);
$carregarEstilosPreMatricula = (bool)array_intersect(
    ['admin-dashboard-page', 'secretary-workflow'],
    $classesCorpo ?: []
);

$baseDir = $posicaoView !== false
    ? substr($caminhoScript, 0, $posicaoView + 1)
    : '/';
$paginaAtual = basename($caminhoScript);
$rotulosPagina = [
    'home_admin.php' => 'Painel administrativo',
    'gerenciar_usuarios.php' => 'Gerenciar usuários',
    'secretarias.php' => 'Gerenciar secretárias',
    'cadastrar_secretaria.php' => 'Cadastrar secretária',
    'editar_secretaria.php' => 'Gerenciar secretárias',
    'solicitacoes.php' => 'Solicitações de documentos',
    'cursos.php' => 'Gerenciar cursos',
    'pre_matriculas.php' => 'Pré-matrículas',
    'perfil.php' => 'Meu perfil'
];
$rotuloPagina = $rotulosPagina[$paginaAtual] ?? 'Gestão escolar';
$diasSemana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$meses = [
    1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
    5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
    9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
];
$dataCabecalho = $diasSemana[(int)date('w')] . ', ' . date('j') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');
$notificacoesAdmin = [];
$erroNotificacoesAdmin = false;
if (!empty($exibeMenuAdministrativo) && (int)($_SESSION['perfil_id'] ?? 0) === 1) {
    require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
    try {
        $notificacoesAdmin = (new UsuarioDAO())->listarNotificacoesAdministrador();
    } catch (RuntimeException $e) {
        error_log('Falha ao exibir notificações administrativas: ' . $e->getMessage());
        $erroNotificacoesAdmin = true;
    }
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
    <?php if (!empty($exibeMenuAdministrativo)): ?>
        <script src="<?= $baseDir ?>assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../../assets/js/tema.js') ?>" defer></script>
    <?php endif; ?>
    <?php if ($carregarEstilosHome): ?>
        <link rel="stylesheet" href="<?= $baseDir ?>assets/css/estilo_home.css?v=<?= time() ?>">
    <?php endif; ?>
    <?php if ($carregarEstilosPreMatricula): ?>
        <link rel="stylesheet" href="<?= $baseDir ?>assets/css/estilo_matricula.css?v=<?= time() ?>">
    <?php endif; ?>
    <?php if (!empty($exibeMenuAdministrativo)): ?>
        <link rel="stylesheet" href="<?= $baseDir ?>assets/css/estilo_tema_sistema.css?v=<?= filemtime(__DIR__ . '/../../assets/css/estilo_tema_sistema.css') ?>">
        <?php if ((int)($_SESSION['perfil_id'] ?? 0) === 1): ?>
            <script src="<?= $baseDir ?>assets/js/notificacoes_admin.js?v=<?= filemtime(__DIR__ . '/../../assets/js/notificacoes_admin.js') ?>" defer></script>
        <?php endif; ?>
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

        <?php if (!empty($exibeMenuAdministrativo)): ?>
            <div class="top-bar-breadcrumb" aria-label="Localização atual">
                <span>Escola Técnica de Ceilândia</span>
                <span aria-hidden="true">›</span>
                <strong><?= htmlspecialchars($rotuloPagina, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="top-bar-tools">
                <button type="button" class="theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
                <?php if ((int)($_SESSION['perfil_id'] ?? 0) === 1): ?>
                    <div class="admin-notifications" data-notifications data-user-id="<?= (int)($_SESSION['usuario_id'] ?? 0) ?>">
                        <button type="button" class="admin-notifications-toggle" data-notifications-toggle
                                aria-label="Abrir notificações" aria-expanded="false" aria-controls="admin-notifications-panel">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                            <span class="admin-notifications-count" data-notifications-count hidden></span>
                        </button>
                        <section class="admin-notifications-panel" id="admin-notifications-panel" data-notifications-panel aria-label="Notificações da Escola" hidden>
                            <header class="admin-notifications-heading">
                                <h2>Notificações da Escola</h2>
                                <button type="button" data-notifications-clear>Limpar todas</button>
                            </header>
                            <?php if ($erroNotificacoesAdmin): ?>
                                <p class="admin-notifications-empty" role="status">Não foi possível carregar as notificações. Atualize a página para tentar novamente.</p>
                            <?php elseif ($notificacoesAdmin): ?>
                                <ul class="admin-notifications-list">
                                    <?php foreach ($notificacoesAdmin as $notificacao): ?>
                                        <?php
                                        $tipoNotificacao = $notificacao['tipo'] === 'solicitacao' ? 'solicitacao' : 'pre-matricula';
                                        $destinoNotificacao = $notificacao['tipo'] === 'solicitacao'
                                            ? $baseDir . 'view/secretaria/solicitacoes.php'
                                            : $baseDir . 'view/admin/home_admin.php#pre-matriculas';
                                        $timestampNotificacao = strtotime((string)$notificacao['criado_em']);
                                        ?>
                                        <li class="admin-notification-item" data-notification-id="<?= htmlspecialchars((string)$notificacao['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <a href="<?= htmlspecialchars($destinoNotificacao, ENT_QUOTES, 'UTF-8') ?>" class="admin-notification-link">
                                                <span class="admin-notification-icon <?= $tipoNotificacao ?>" aria-hidden="true">
                                                    <?= $tipoNotificacao === 'solicitacao' ? '▣' : '♙' ?>
                                                </span>
                                                <span class="admin-notification-copy">
                                                    <strong><?= htmlspecialchars((string)$notificacao['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <span><?= htmlspecialchars((string)$notificacao['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <time datetime="<?= htmlspecialchars(date(DATE_ATOM, $timestampNotificacao ?: time()), ENT_QUOTES, 'UTF-8') ?>"
                                                          data-notification-time="<?= $timestampNotificacao ?: 0 ?>">
                                                        <?= $timestampNotificacao ? date('d/m/Y H:i', $timestampNotificacao) : '—' ?>
                                                    </time>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="admin-notifications-empty" data-notifications-empty hidden>Não há notificações novas.</p>
                            <?php else: ?>
                                <p class="admin-notifications-empty" data-notifications-empty>Ainda não há notificações.</p>
                            <?php endif; ?>
                        </section>
                    </div>
                <?php endif; ?>
                <span class="top-bar-date"><span aria-hidden="true">▣</span><?= htmlspecialchars($dataCabecalho, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

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