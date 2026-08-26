<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: home/index.php');
    exit;
}

$perfilId = $_SESSION['usuario_perfil_id'] ?? 2;

if ($perfilId == 1) {
    header('Location: admin_aprovacao.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel | Secretaria Escolar</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body {
            margin: 0;
            background: #f4f7fb;
            color: #1f2937;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            padding: 30px;
        }
        h1 { margin-bottom: 10px; color: #123a8a; }
        p { margin: 10px 0; color: #475569; }
        .btn {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background: #1748a8;
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bem-vindo ao painel</h1>
        <p>Usuário autenticado: <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong></p>
        <p>E-mail: <strong><?= htmlspecialchars($_SESSION['usuario_email']) ?></strong></p>
        <p>Perfil: <strong><?= $perfilId == 1 ? 'Administrador' : 'Aluno' ?></strong></p>

        <?php if ($perfilId == 1): ?>
            <a href="admin_aprovacao.php" class="btn">Aprovar cadastros</a>
        <?php else: ?>
            <a href="index.php" class="btn">Ir para o sistema do aluno</a>
        <?php endif; ?>

        <a href="home/index.php" class="btn">Voltar para a home</a>
    </div>
</body>
</html>
