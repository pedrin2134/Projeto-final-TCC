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

$exibeMenuAdministrativo = true;
include_once __DIR__ . '/../includes/topo.php';
?>

<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <div class="main-content">
            <header class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h1>Gerenciamento de Usuários</h1>
                <a href="../cadastrar_usuario.php" class="btn btn-ativar">+ Novo Usuário</a>
            </header>

            <h2>Lista de Usuários</h2>

            <?php if (!empty($usuarios)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Perfil ID</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['nome'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['perfil_id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['status'] ?? 'Ativo') ?></td>

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
                                        <button type="submit" name="btn_excluir" class="btn btn-excluir" onclick="return confirm('ATENÇÃO: Tem certeza que deseja excluir este usuário permanentemente?');">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum usuário encontrado.</p>
            <?php endif; ?>
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