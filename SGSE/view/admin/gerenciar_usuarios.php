<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['perfil_id'] ?? 0) !== 1
    || !Autorizacao::usuarioTemPermissao('GERENCIAR_USUARIOS')) {
    http_response_code(403);
    exit('Acesso não autorizado.');
}

require_once __DIR__ . '/../../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../../controller/UsuarioController.php';

$usuarioController = new UsuarioController();
$usuarios = $usuarioController->listar();
$buscaUsuarios = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$perfilFiltro = filter_var($_GET['perfil'] ?? 0, FILTER_VALIDATE_INT);
$perfilFiltro = in_array($perfilFiltro, [1, 2, 3], true) ? $perfilFiltro : 0;
$usuariosExibidos = array_values(array_filter($usuarios, static function (array $usuario) use ($buscaUsuarios, $perfilFiltro): bool {
    $correspondePerfil = $perfilFiltro === 0 || (int)($usuario['perfil_id'] ?? 0) === $perfilFiltro;
    if (!$correspondePerfil || $buscaUsuarios === '') {
        return $correspondePerfil;
    }

    return mb_stripos((string)($usuario['nome'] ?? ''), $buscaUsuarios, 0, 'UTF-8') !== false
        || mb_stripos((string)($usuario['email'] ?? ''), $buscaUsuarios, 0, 'UTF-8') !== false;
}));
$rotulosPerfil = [1 => 'Administrador', 2 => 'Secretaria', 3 => 'Aluno'];
$mensagensExclusao = [
    'ok' => ['success', 'Usuário e registros vinculados excluídos com sucesso.'],
    'ok_arquivos_pendentes' => ['error', 'Usuário e registros vinculados foram excluídos, mas alguns arquivos não puderam ser removidos. Consulte o log do servidor.'],
    'vinculado' => ['error', 'A exclusão foi cancelada porque ainda há um vínculo no banco de dados. Nenhum dado foi apagado; consulte o log do servidor.'],
    'nao_encontrado' => ['error', 'O usuário não foi encontrado. Atualize a lista e tente novamente.'],
    'dados_invalidos' => ['error', 'Não foi possível excluir: dados inválidos.'],
    'erro' => ['error', 'Não foi possível excluir o usuário. Tente novamente.']
];
$resultadoExclusao = is_string($_GET['resultado'] ?? null) ? $_GET['resultado'] : '';

$exibeMenuAdministrativo = true;
$classeCorpo = 'admin-users-page';
include_once __DIR__ . '/../includes/topo.php';
?>

<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <div class="main-content admin-list-page admin-users-page">
            <header class="admin-page-header">
                <div>
                    <h1>Gerenciamento de Usuários</h1>
                    <p>Visualize, crie, edite ou remova usuários do sistema SGSE.</p>
                </div>
                <a href="../cadastrar_usuario.php" class="btn admin-primary-button">+ Novo Usuário</a>
            </header>

            <?php if (isset($mensagensExclusao[$resultadoExclusao])): ?>
                <p class="secretary-alert <?= $mensagensExclusao[$resultadoExclusao][0] ?>" role="status">
                    <?= htmlspecialchars($mensagensExclusao[$resultadoExclusao][1], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <section class="admin-list-panel">
                <form class="admin-list-filters" action="gerenciar_usuarios.php" method="GET" role="search">
                    <label class="admin-search-field">
                        <span class="visually-hidden">Buscar por nome ou e-mail</span>
                        <input type="search" name="busca" placeholder="Buscar por nome ou e-mail..." value="<?= htmlspecialchars($buscaUsuarios, ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span class="visually-hidden">Filtrar por perfil</span>
                        <select name="perfil">
                            <option value="0">Todos os perfis</option>
                            <?php foreach ($rotulosPerfil as $idPerfil => $rotuloPerfil): ?>
                                <option value="<?= $idPerfil ?>" <?= $perfilFiltro === $idPerfil ? 'selected' : '' ?>><?= htmlspecialchars($rotuloPerfil, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="admin-primary-button">Filtrar</button>
                    <?php if ($buscaUsuarios !== '' || $perfilFiltro !== 0): ?>
                        <a class="admin-filter-reset" href="gerenciar_usuarios.php">Limpar</a>
                    <?php endif; ?>
                    <span class="admin-list-count">Exibindo <?= count($usuariosExibidos) ?> usuários</span>
                </form>

                <?php if ($usuariosExibidos): ?>
                    <div class="admin-table-wrap">
                    <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuariosExibidos as $u): ?>
                            <?php
                            $fotoPerfilUsuario = is_string($u['foto_perfil'] ?? null)
                                && preg_match('#^uploads/perfis/[a-f0-9]{32}\.(jpg|png|webp)$#', $u['foto_perfil'])
                                && is_file(__DIR__ . '/../../' . $u['foto_perfil'])
                                ? $u['foto_perfil']
                                : null;
                            $nomeUsuario = (string)($u['nome'] ?? '');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($u['id'] ?? '') ?></td>
                                <td>
                                    <?php if ($fotoPerfilUsuario !== null): ?>
                                        <img class="admin-user-avatar" src="../../<?= htmlspecialchars($fotoPerfilUsuario, ENT_QUOTES, 'UTF-8') ?>"
                                             alt="Foto de <?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php else: ?>
                                        <span class="admin-user-avatar admin-user-avatar-placeholder" aria-label="Sem foto de perfil">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($nomeUsuario, 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($rotulosPerfil[(int)($u['perfil_id'] ?? 0)] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="admin-status-badge <?= strtoupper($u['status'] ?? '') === 'INATIVO' ? 'is-inactive' : 'is-active' ?>"><?= htmlspecialchars($u['status'] ?? 'Ativo', ENT_QUOTES, 'UTF-8') ?></span></td>

                                <td class="acoes-container">
                                    <a href="../editar_usuario.php?id=<?= (int)$u['id'] ?>" class="btn btn-editar">Editar</a>

                                    <?php if (strtoupper($u['status'] ?? '') === 'INATIVO'): ?>
                                        <a href="../../controller/UsuarioController.php?acao=ativar&amp;id=<?= (int)$u['id'] ?>"
                                           class="btn btn-ativar"
                                           onclick="return confirm('Tem certeza que deseja ativar este usuário?');">
                                            Ativar
                                        </a>
                                    <?php else: ?>
                                        <a href="../../controller/UsuarioController.php?acao=desativar&amp;id=<?= (int)$u['id'] ?>"
                                           class="btn btn-desativar"
                                           onclick="return confirm('Tem certeza que deseja desativar este usuário?');">
                                            Desativar
                                        </a>
                                    <?php endif; ?>

                                    <form action="../../controller/UsuarioController.php" method="POST" class="form-excluir" style="display: inline;">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" name="btn_excluir" class="btn btn-excluir" onclick="return confirm('ATENÇÃO: Esta ação apagará permanentemente o usuário e os registros vinculados, incluindo documentos, matrículas, solicitações e declarações. Não pode ser desfeita. Deseja continuar?');">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    </table>
                    </div>
            <?php else: ?>
                <p class="admin-empty-state"><?= $buscaUsuarios !== '' || $perfilFiltro !== 0 ? 'Nenhum usuário corresponde aos filtros selecionados.' : 'Nenhum usuário encontrado.' ?></p>
            <?php endif; ?>
            </section>
        </div>
    </div>
</div>
<?php
if (file_exists(__DIR__ . '/../includes/rodape.php')) {
    include_once __DIR__ . '/../includes/rodape.php';
}
?>
</main>
</body>
</html>