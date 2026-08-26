<?php
// index.php

// Carrega todas as dependências na ordem correta
require 'model/dto/UsuarioDTO.php';
require 'model/dao/Conexao.php';
require 'model/dao/UsuarioDAO.php';
require 'controller/UsuarioController.php';

$usuarioController = new UsuarioController();
$usuarios = $usuarioController->listar();

$msg = "";

// Processa o envio dos formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. AÇÃO: Testar Conexão
    if (isset($_POST['btn_testar'])) {
        try {
            Conexao::getConexao();
            $msg = "<p>Conexão realizada com sucesso!</p>";
        } catch (Exception $e) {
            $msg = "<p>Erro na conexão: " . $e->getMessage() . "</p>";
        }
    }

}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>

    <h2>Painel do Sistema</h2>

    <?= $msg ?>

    <form method="POST">
        <button type="submit" name="btn_testar">Testar Banco</button>
    </form>

    <hr>
    <p>
        <a href="view/cadastrar_usuario.php">
            <button type="button">Cadastrar Usuário</button>
        </a>
    </p>

    <h2>Lista de Usuários</h2>
    <?php if (!empty($usuarios)): ?>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil ID</th>
                <th>Ações</th>
                </tr>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($u['nome'] ?? '') ?></td>
                    <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($u['perfil_id'] ?? '') ?></td>
                    <td>
                        <a href="view/editar_usuario.php?id=<?= $u['id'] ?>">
                            <button type="button">Editar</button>
                        </a>
                    </td>
                    </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhum usuário encontrado.</p>
    <?php endif ?>

</body>
</html>