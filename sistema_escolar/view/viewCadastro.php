<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="css/cadastro.css">
</head>
<body>
    <header>
        
        <h2>SISTEMA ESCOLAR</h2>

    </header>

    <hr>

    <main>
        <form method="post" action="cadastro.php">
            <h3>Cadastrar Usuário</h3>

            <div>
                <input type="text" name="nome" placeholder="Nome" required><br><br>
                <input type="email" name="email" placeholder="E-mail" required><br><br>
                <input type="text" name="cpf" placeholder="CPF" required><br><br>
                <input type="text" name="telefone" placeholder="Telefone" required><br><br>
                <input type="text" name="endereco" placeholder="Endereço" required><br><br>
                <input type="text" name="numero" placeholder="Número" required><br><br>
                <input type="text" name="complemento" placeholder="Complemento" required><br><br>
                <input type="text" name="bairro" placeholder="Bairro" required><br><br>
                <input type="password" name="senha" placeholder="Senha" required><br><br>
            </div>

            <button type="submit" name="btn_cadastrar">Cadastrar</button>

            <?php if ($msg !== ''): ?>
                <p><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </form>
    </main>

    <hr>

    <footer>SISTEMA ESCOLAR</footer>
</body>
</html>
