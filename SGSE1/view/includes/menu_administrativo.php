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
$contadoresMenu = [];
$erroContadoresMenu = false;
if ($perfilMenu === 1) {
    try {
        $contadoresMenu = (new UsuarioDAO())->obterContadoresMenuAdministrador();
    } catch (RuntimeException $e) {
        error_log('Falha ao mostrar contadores no menu administrativo: ' . $e->getMessage());
        $erroContadoresMenu = true;
    }
}
$formatarContadorMenu = static function ($valor): string {
    $quantidade = max(0, (int)$valor);
    return $quantidade >= 1000
        ? rtrim(rtrim(number_format($quantidade / 1000, 1, '.', ''), '0'), '.') . 'k'
        : (string)$quantidade;
};
?>
<aside class="admin-sidebar" aria-label="Menu administrativo">
    <div class="admin-sidebar-brand">
        <a class="admin-sidebar-brand-home" href="<?= $baseMenu ?><?= $destinoMarcaMenu ?>" aria-label="SGSE - Gestão Escolar">
            <span class="admin-sidebar-logo"><img src="<?= $baseMenu ?>assets/img/sua_logo.png" alt=""></span>
            <span>SGSE<small>GESTÃO ESCOLAR</small></span>
        </a>
        <button type="button" class="admin-sidebar-toggle" data-toggle-sidebar aria-label="Recolher menu" aria-expanded="true" title="Recolher menu">
            <span aria-hidden="true">‹</span>
        </button>
    </div>

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
        <?php if ($perfilMenu === 1): ?>
            <span class="admin-sidebar-label">PRINCIPAL</span>
            <?php if ($permissoesMenu['pre_matriculas']): ?>
                <a class="<?= $secaoMenu === 'painel' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/admin/home_admin.php" aria-label="Dashboard" title="Dashboard" <?= $secaoMenu === 'painel' ? 'aria-current="page"' : '' ?>>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 13.5A8.5 8.5 0 1 0 11.5 5v8.5z"/><path d="M13.5 3v8.5H22A8.5 8.5 0 0 0 13.5 3z"/></svg>
                    <span class="admin-sidebar-link-label">Dashboard</span>
                </a>
            <?php endif; ?>

            <?php if ($permissoesMenu['usuarios'] || $permissoesMenu['cursos']): ?>
                <span class="admin-sidebar-label">GESTÃO ACADÊMICA</span>
                <?php if ($permissoesMenu['usuarios']): ?>
                    <a class="<?= $secaoMenu === 'usuarios' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/admin/gerenciar_usuarios.php" aria-label="Usuários" title="Usuários" <?= $secaoMenu === 'usuarios' ? 'aria-current="page"' : '' ?>>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M16 20v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V20z"/><circle cx="9" cy="7" r="3.5"/><path d="M16 11a3.5 3.5 0 1 0-1.2-6.8M18 14.5a4 4 0 0 1 4 4V20h-3"/></svg>
                        <span class="admin-sidebar-link-label">Usuários</span>
                        <?php if (!$erroContadoresMenu): ?><span class="admin-sidebar-count"><?= htmlspecialchars($formatarContadorMenu($contadoresMenu['usuarios'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
                <?php if ($permissoesMenu['cursos']): ?>
                    <a class="<?= $secaoMenu === 'cursos.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/cursos.php" aria-label="Cursos Técnico" title="Cursos Técnico" <?= $secaoMenu === 'cursos.php' ? 'aria-current="page"' : '' ?>>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5M22 9v6"/></svg>
                        <span class="admin-sidebar-link-label">Cursos Técnico</span>
                        <?php if (!$erroContadoresMenu): ?><span class="admin-sidebar-count"><?= htmlspecialchars($formatarContadorMenu($contadoresMenu['cursos'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($permissoesMenu['usuarios'] || $permissoesMenu['solicitacoes'] || $permissoesMenu['pre_matriculas']): ?>
                <span class="admin-sidebar-label">ATENDIMENTO</span>
                <?php if ($permissoesMenu['pre_matriculas']): ?>
                    <a class="<?= $secaoMenu === 'pre_matriculas.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/secretaria/pre_matriculas.php" aria-label="Pré-matrículas" title="Pré-matrículas" <?= $secaoMenu === 'pre_matriculas.php' ? 'aria-current="page"' : '' ?>>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5M22 9v6"/></svg>
                        <span class="admin-sidebar-link-label">Pré-matrículas</span>
                    </a>
                <?php endif; ?>
                <?php if ($permissoesMenu['usuarios']): ?>
                    <a class="<?= $secaoMenu === 'secretarias' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/admin/secretarias.php" aria-label="Secretárias" title="Secretárias" <?= $secaoMenu === 'secretarias' ? 'aria-current="page"' : '' ?>>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="2.5" y="5" width="19" height="14" rx="2"/><circle cx="8" cy="11" r="2"/><path d="M5 16c.7-2 5.3-2 6 0M14 10h4M14 14h4"/></svg>
                        <span class="admin-sidebar-link-label">Secretárias</span>
                    </a>
                <?php endif; ?>
                <?php if ($permissoesMenu['solicitacoes']): ?>
                    <a class="<?= $secaoMenu === 'solicitacoes.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/secretaria/solicitacoes.php" aria-label="Solicitações de documentos" title="Solicitações de documentos" <?= $secaoMenu === 'solicitacoes.php' ? 'aria-current="page"' : '' ?>>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M13 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8M13 3v5h5M13 3l5 5M7 13h5M7 17h3"/><path d="m16 16 4-4 2 2-4 4-3 1z"/></svg>
                        <span class="admin-sidebar-link-label">Solicitações Doc.</span>
                        <?php if (!$erroContadoresMenu): ?><span class="admin-sidebar-count admin-sidebar-count-alert"><?= htmlspecialchars($formatarContadorMenu($contadoresMenu['solicitacoes'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <span class="admin-sidebar-label"><?= $perfilMenu === 2 ? 'ATENDIMENTO' : 'PRINCIPAL' ?></span>
        <?php if ($perfilMenu === 2 && $permissoesMenu['pre_matriculas']): ?>
            <a class="<?= $secaoMenu === 'pre_matriculas.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/secretaria/pre_matriculas.php" aria-label="Pré-matrículas" title="Pré-matrículas" <?= $secaoMenu === 'pre_matriculas.php' ? 'aria-current="page"' : '' ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5M22 9v6"/></svg><span class="admin-sidebar-link-label">Pré-matrículas</span>
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 2 && $permissoesMenu['solicitacoes']): ?>
            <a class="<?= $secaoMenu === 'solicitacoes.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/secretaria/solicitacoes.php" aria-label="Solicitações" title="Solicitações" <?= $secaoMenu === 'solicitacoes.php' ? 'aria-current="page"' : '' ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M13 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8M13 3v5h5M13 3l5 5M7 13h5M7 17h3"/><path d="m16 16 4-4 2 2-4 4-3 1z"/></svg><span class="admin-sidebar-link-label">Solicitações</span>
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 3 && $permissoesMenu['perfil']): ?>
            <a class="<?= $secaoMenu === 'home_usuario.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/usuario/home_usuario.php" aria-label="Minha área" title="Minha área" <?= $secaoMenu === 'home_usuario.php' ? 'aria-current="page"' : '' ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 13.5A8.5 8.5 0 1 0 11.5 5v8.5z"/><path d="M13.5 3v8.5H22A8.5 8.5 0 0 0 13.5 3z"/></svg><span class="admin-sidebar-link-label">Minha área</span>
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 3 && $permissoesMenu['solicitacoes']): ?>
            <a class="<?= $secaoMenu === 'solicitar_documentos.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/usuario/solicitar_documentos.php" aria-label="Solicitar documentos" title="Solicitar documentos" <?= $secaoMenu === 'solicitar_documentos.php' ? 'aria-current="page"' : '' ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M13 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8M13 3v5h5M13 3l5 5M7 13h5M7 17h3"/><path d="m16 16 4-4 2 2-4 4-3 1z"/></svg><span class="admin-sidebar-link-label">Solicitar documentos</span>
            </a>
        <?php endif; ?>
        <?php if ($perfilMenu === 2 && $permissoesMenu['cursos']): ?>
            <a class="<?= $secaoMenu === 'cursos.php' ? 'active' : '' ?>" href="<?= $baseMenu ?>view/cursos.php" aria-label="Cursos" title="Cursos" <?= $secaoMenu === 'cursos.php' ? 'aria-current="page"' : '' ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5M22 9v6"/></svg><span class="admin-sidebar-link-label">Cursos</span>
            </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <a class="admin-sidebar-logout" href="<?= $baseMenu ?>controller/UsuarioController.php?acao=logout" aria-label="Sair do sistema" title="Sair do sistema">
        <span aria-hidden="true">↪</span><span class="admin-sidebar-link-label">Sair do sistema</span>
    </a>
</aside>
