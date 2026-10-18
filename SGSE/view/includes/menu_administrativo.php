<?php
require_once __DIR__ . '/../../controller/Autorizacao.php';

$perfilMenu = (int)($_SESSION['perfil_id'] ?? 0);
$permissoesMenu = [
    'perfil' => Autorizacao::usuarioTemPermissao('GERENCIAR_PERFIL'),
    'usuarios' => Autorizacao::usuarioTemPermissao('GERENCIAR_USUARIOS'),
    'cursos' => Autorizacao::usuarioTemPermissao('GERENCIAR_CURSOS'),
    'pre_matriculas' => Autorizacao::usuarioTemPermissao('GERENCIAR_PRE_MATRICULAS'),
    'solicitacoes' => Autorizacao::usuarioTemPermissao('GERENCIAR_SOLICITACOES')
];
$scriptMenu = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$posicaoViewMenu = strpos($scriptMenu, '/view/');
$baseMenu = $posicaoViewMenu !== false
    ? substr($scriptMenu, 0, $posicaoViewMenu + 1)
    : '/';
$paginaMenu = basename($scriptMenu);
$secaoMenu = in_array($paginaMenu, ['gerenciar_usuarios.php', 'editar_usuario.php'], true)
    ? 'usuarios'
    : (in_array($paginaMenu, ['secretarias.php', 'cadastrar_secretaria.php', 'editar_secretaria.php'], true)
        ? 'secretarias'
    : ($paginaMenu === 'home_admin.php' ? 'painel' : ($paginaMenu === 'perfil.php' ? 'perfil' : $paginaMenu)));
$nomePerfilMenu = $perfilMenu === 1 ? 'Administrador' : 'Secretaria';
$nomePerfilMenu = $perfilMenu === 3 ? 'Aluno' : $nomePerfilMenu;
$nomeUsuarioMenu = (string)($_SESSION['usuario_nome'] ?? $nomePerfilMenu);
$fotoPerfilMenu = $_SESSION['foto_perfil'] ?? null;
$destinoMarcaMenu = $perfilMenu === 1
    ? 'view/admin/home_admin.php'
    : ($perfilMenu === 2 ? 'view/secretaria/pre_matriculas.php' : 'view/usuario/home_usuario.php');
?>
<aside class="admin-sidebar" aria-label="Menu administrativo">
    <a class="admin-sidebar-brand" href="<?= $baseMenu ?><?= $destinoMarcaMenu ?>">
        <img src="<?= $baseMenu ?>assets/img/sua_logo.png" alt="">
        <span>SGSE<small>Sistema de Gestão Escolar</small></span>
    </a>

    <?php if ($permissoesMenu['perfil']): ?>
    <a class="admin-sidebar-user" href="<?= $baseMenu ?>view/perfil.php" aria-label="Editar meu perfil">
        <?php if ($fotoPerfilMenu && preg_match('#^uploads/perfis/[a-f0-9]{32}\.(jpg|png|webp)$#', $fotoPerfilMenu)): ?>
            <img class="admin-sidebar-avatar admin-sidebar-avatar-image" src="<?= $baseMenu . htmlspecialchars($fotoPerfilMenu, ENT_QUOTES, 'UTF-8') ?>" alt="Foto do perfil de <?= htmlspecialchars($nomeUsuarioMenu, ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
            <span class="admin-sidebar-avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($nomeUsuarioMenu, 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
        <?php endif; ?>
        <span><strong><?= htmlspecialchars($nomeUsuarioMenu, ENT_QUOTES, 'UTF-8') ?></strong><small><?= $nomePerfilMenu ?> · Editar perfil</small></span>
    </a>
    <?php endif; ?>

    <nav class="admin-sidebar-nav">
        <span class="admin-sidebar-label">MENU PRINCIPAL</span>
        <?php if ($perfilMenu === 1 && $permissoesMenu['pre_matriculas']): ?>
            <a class="<?= $secaoMenu === 'painel' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/admin/home_admin.php" <?= $secaoMenu === 'painel' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">⌂</span>Painel administrativo
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 1 && $permissoesMenu['usuarios']): ?>
            <a class="<?= $secaoMenu === 'usuarios' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/admin/gerenciar_usuarios.php" <?= $secaoMenu === 'usuarios' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">▤</span>Gerenciar usuários
            </a>
            <a class="<?= $secaoMenu === 'secretarias' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/admin/secretarias.php" <?= $secaoMenu === 'secretarias' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">♙</span>Gerenciar secretárias
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 1 && $permissoesMenu['solicitacoes']): ?>
            <a class="<?= $secaoMenu === 'solicitacoes.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/secretaria/solicitacoes.php" <?= $secaoMenu === 'solicitacoes.php' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">▧</span>Solicitações
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 2 && $permissoesMenu['pre_matriculas']): ?>
            <a class="<?= $secaoMenu === 'pre_matriculas.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/secretaria/pre_matriculas.php" <?= $secaoMenu === 'pre_matriculas.php' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">▣</span>Pré-matrículas
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 2 && $permissoesMenu['solicitacoes']): ?>
            <a class="<?= $secaoMenu === 'solicitacoes.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/secretaria/solicitacoes.php" <?= $secaoMenu === 'solicitacoes.php' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">▧</span>Solicitações
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 3 && $permissoesMenu['perfil']): ?>
            <a class="<?= $secaoMenu === 'home_usuario.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/usuario/home_usuario.php" <?= $secaoMenu === 'home_usuario.php' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">⌂</span>Minha área
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 3 && $permissoesMenu['solicitacoes']): ?>
            <a class="<?= $secaoMenu === 'solicitar_documentos.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/usuario/solicitar_documentos.php" <?= $secaoMenu === 'solicitar_documentos.php' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">▧</span>Solicitar documentos
            </a>
        <?php endif; ?>
        <?php if (in_array($perfilMenu, [1, 2], true) && $permissoesMenu['cursos']): ?>
            <a class="<?= $secaoMenu === 'cursos.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/cursos.php" <?= $secaoMenu === 'cursos.php' ? 'aria-current="page"' : '' ?>>
                <span aria-hidden="true">▦</span>Cursos
            </a>
        <?php endif; ?>
    </nav>

    <a class="admin-sidebar-logout" href="<?= $baseMenu ?>controller/UsuarioController.php?acao=logout">
        <span aria-hidden="true">↪</span>Sair do sistema
    </a>
</aside>
