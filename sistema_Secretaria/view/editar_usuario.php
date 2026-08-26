<?php
// view/editar_usuario.php

require_once __DIR__ . '/../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../controller/UsuarioController.php';

$controller = new UsuarioController();
$msg = "";

// 1. Processa a atualização via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_atualizar'])) {
    $sucesso = $controller->atualizar(
        (int)$_POST['id'],
        $_POST['nome'],
        $_POST['email'],
        $_POST['senha'],
        (int)$_POST['perfil_id']
    );

    if ($sucesso) {
        // Redireciona para a lista principal após salvar
        header("Location: ../index.php");
        exit;
    } else {
        $msg = "<p style='color:red;'>Erro ao atualizar usuário.</p>";
    }
}

// 2. Busca os dados atuais do usuário para preencher o formulário
$id = $_GET['id'] ?? null;
$usuario = $id ? $controller->buscarPorId((int)$id) : null;

if (!$usuario) {
    die("Usuário não encontrado.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
</head>
<body>

    <h2>Editar Usuário</h2>
    <?= $msg ?>

    <form method="POST">
        <!-- Campo oculto guardando o ID do usuário -->
        <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id']) ?>">

        <label>Nome:</label><br>
        <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required><br><br>

        <label>E-mail:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required><br><br>

        <label>Nova Senha (deixe em branco para não alterar):</label><br>
        <input type="password" name="senha"><br><br>

        <label>Perfil ID:</label><br>
        <select name="perfil_id">
            <option value="1" <?= $usuario['perfil_id'] == 1 ? 'selected' : '' ?>>Administrador</option>
            <option value="2" <?= $usuario['perfil_id'] == 2 ? 'selected' : '' ?>>Aluno</option>
        </select><br><br>

        <button type="submit" name="btn_atualizar">Salvar Alterações</button>
        <a href="../index.php"><button type="button">Cancelar</button></a>
    </form>

</body>
</html>