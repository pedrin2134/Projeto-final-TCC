<?php
// view/editar_usuario.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Trava de Segurança
if (!isset($_SESSION['usuario_id'])) {
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
    $senha = $_POST['senha']; // Se preenchida, o controller gerará o hash
    $perfil_id = (int)$_POST['perfil_id'];

    $sucesso = $controller->atualizar($id, $nome, $email, $senha, $perfil_id);

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
include_once __DIR__ . '/includes/topo.php';
?>

<link rel="stylesheet" href="../assets/css/estilo.css?v=<?php echo filemtime('../assets/css/estilo.css'); ?>">

<div class="admin-workspace">
    <?php include __DIR__ . '/includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
<div class="main-content" style="max-width: 600px; margin: 30px auto; padding: 0 20px;">
    <h2>Editar Usuário</h2>
    <?= $msg ?>

    <form method="POST">
        <!-- Campo oculto com o ID -->
        <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id']) ?>">

        <label>Nome:</label><br>
        <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required><br><br>

        <label>E-mail:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required><br><br>

        <label>Nova Senha (deixe em branco para manter a atual):</label><br>
        <input type="password" name="senha" placeholder="Preencha apenas para alterar"><br><br>

        <label>Perfil:</label><br>
        <select name="perfil_id">
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