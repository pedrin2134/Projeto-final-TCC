<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
</head>
<body>

    <h2>Cadastrar Usuário</h2>

    <form action="../controller/UsuarioController.php" method="POST">
           <input type="text" name="nome" placeholder="Nome" required><br><br>
        <input type="email" name="email" placeholder="E-mail" required><br><br>
        <input type="password" name="senha" placeholder="Senha" required><br><br>

        <select name="perfil_id">
            <option value="1">Administrador</option>
            <option value="2" selected>Cliente</option>
        </select><br><br>

        <button type="submit" name="btn_cadastrar">Cadastrar</button>
    </form>
    <a href="../index.php">
        <button type="button">Voltar ao início</button>
    </a>
</body>
</html>