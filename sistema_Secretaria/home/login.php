<?php
session_start();

require_once __DIR__ . '/../controller/UsuarioController.php';

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email !== '' && $senha !== '') {
        $controller = new UsuarioController();
        $usuario = $controller->login($email, $senha);

        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_perfil_id'] = $usuario['perfil_id'];

            if ($usuario['perfil_id'] == 1) {
                header('Location: ../admin_aprovacao.php');
            } else {
                header('Location: ../painel.php');
            }
            exit;
        }

        $erro = 'Seu cadastro ainda não foi aprovado pelo administrador.';
    } else {
        $erro = 'Preencha e-mail e senha.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Secretaria Escolar</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

    <div class="login-layout">
        <!-- Coluna Esquerda: Intro Visual -->
        <div class="login-intro">
            <a href="index.php" class="brand">SECRETARIA ESCOLAR</a>
            
            <div class="intro-content">
                <span class="eyebrow">Painel de Controle</span>
                <h1>Gestão Simplificada.</h1>
                <p>Acesse o sistema para gerenciar turmas, matrículas, notas e solicitações de usuários.</p>
            </div>
            
            <div class="intro-mark">●</div>
        </div>

        <!-- Coluna Direita: Formulário de Login -->
        <div class="login-panel">
            <div class="form-wrap">
                <h2>Login</h2>
                <p class="form-description">Área administrativa da secretaria</p>

                <!-- Mensagem de Erro do PHP adaptada ao novo estilo -->
                <?php if ($erro !== ''): ?>
                    <div class="message"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
                    </div>

                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                    </div>

                    <button type="submit">
                        Entrar <span>→</span>
                    </button>
                </form>

                <div class="register-link">
                    Primeiro acesso? <a href="../view/cadastrar_usuario.php">Ir para cadastro</a>
                </div>
            </div>

            <footer>
                <a href="index.php" style="color: inherit; text-decoration: none;">← Voltar para a home</a>
            </footer>
        </div>
    </div>

</body>
</html>
