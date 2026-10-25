<?php
// view/editar_usuario.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Trava de Segurança
require_once __DIR__ . '/../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['perfil_id'] ?? 0) !== 1
    || !Autorizacao::usuarioTemPermissao('GERENCIAR_USUARIOS')) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../controller/UsuarioController.php';

$controller = new UsuarioController();
$msg = "";

// 1. Processa a atualização via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_atualizar'])) {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $perfil_id = (int)$_POST['perfil_id'];

    $sucesso = $controller->atualizar($id, $nome, $email, $perfil_id);

    if ($sucesso) {
        header("Location: admin/gerenciar_usuarios.php");
        exit;
    } else {
        $msg = "<p style='color:red;'>Erro ao atualizar usuário. Tente novamente.</p>";
    }
}

// 2. Busca os dados atuais do usuário para preencher o formulário
$id = $_GET['id'] ?? null;
$usuario = $id ? $controller->buscarPorId((int)$id) : null;

if (!$usuario) {
    die("Usuário não encontrado.");
}

$exibeMenuAdministrativo = true;
$classeCorpo = 'admin-user-edit-page';
include_once __DIR__ . '/includes/topo.php';
?>

<div class="admin-workspace">
    <?php include __DIR__ . '/includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
<div class="main-content" style="max-width: 600px; margin: 30px auto; padding: 0 20px;">
    <h2 class="admin-user-edit-title">Editar Usuário</h2>
    <p class="admin-user-edit-note">A senha só pode ser alterada pelo próprio usuário, na página Meu perfil.</p>
    <?= $msg ?>

    <form method="POST" class="admin-user-edit-form">
        <!-- Campo oculto com o ID -->
        <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id']) ?>">

        <label for="editar-usuario-nome">Nome:</label><br>
        <input id="editar-usuario-nome" type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required><br><br>

        <label for="editar-usuario-email">E-mail:</label><br>
        <input id="editar-usuario-email" type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required><br><br>

        <label for="editar-usuario-perfil">Perfil:</label><br>
        <select id="editar-usuario-perfil" name="perfil_id">
            <option value="1" <?= $usuario['perfil_id'] == 1 ? 'selected' : '' ?>>Administrador</option>
            <option value="2" <?= $usuario['perfil_id'] == 2 ? 'selected' : '' ?>>Secretaria</option>
            <option value="3" <?= $usuario['perfil_id'] == 3 ? 'selected' : '' ?>>Aluno</option>
        </select><br><br>

        <button type="submit" name="btn_atualizar" class="btn btn-ativar">Salvar Alterações</button>
        <a href="admin/gerenciar_usuarios.php" class="btn btn-desativar" style="text-decoration: none; display: inline-block; text-align: center;">Cancelar</a>
    </form>
</div>

<?php
if (file_exists(__DIR__ . '/includes/rodape.php')) {
    include_once __DIR__ . '/includes/rodape.php';
}
?>
</div>
</div>
</main>
</body>
</html>