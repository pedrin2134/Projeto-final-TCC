<?php
session_start();

require_once __DIR__.'/../model/dao/Conexao.php';
require_once __DIR__.'/../model/dao/UsuarioDAO.php';

$cursos = (new UsuarioDAO())->listarCursosAtivos();

// Se já estiver logado, redireciona para a página principal
if (isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Sistema</title>
    <link rel="stylesheet" href="../assets/css/estilo_login.css?v=<?php echo filemtime(__DIR__.'/../assets/css/estilo_login.css'); ?>">
    <script src="../assets/js/tema.js?v=<?php echo filemtime(__DIR__.'/../assets/js/tema.js'); ?>" defer></script>
</head>
<body class="login-page">
    <div class="contact-strip">
        <span class="contact-dot"></span>
        <strong>Secretaria Escolar</strong>
        <span>Precisa de ajuda? Entre em contato com a secretaria.</span>
    </div>

    <header class="site-header">
        <a class="brand" href="login.php">
            <img src="../assets/img/sua_logo.png" alt="Logo da Escola Técnica de Ceilândia">
            <span>SGSE<br><small>SISTEMA DE GESTÃO DE SECRETARIA ESCOLAR</small></span>
        </a>
        <nav class="site-nav" aria-label="Navegação principal">
            <a href="login.php">Acesso</a>
            <a href="cadastrar_usuario.php">Inscrição</a>
            <a href="#suporte">Suporte</a>
            <a class="nav-cta" href="cadastrar_usuario.php">INSCREVA-SE</a>
            <button type="button" class="theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
        </nav>
    </header>

    <main class="login-layout">
        <section class="login-visual">
            <p class="eyebrow">PORTAL ACADÊMICO</p>
            <h1>Seu próximo passo<br><strong>começa aqui.</strong></h1>
            <p class="visual-copy">Acesse sua jornada acadêmica, acompanhe sua pré-matrícula e envie seus documentos com segurança.</p>
            <div class="visual-mark">
                <span>ETC</span>
                <small>Educação que abre caminhos</small>
            </div>
        </section>

        <div class="login-container login-card" id="suporte">
            <div class="card-kicker">ÁREA RESTRITA</div>
            <h2>Acesse sua conta</h2>
            <p class="card-intro">Entre para acompanhar sua inscrição e seus dados acadêmicos.</p>

        <!-- Tratamento das mensagens de erro -->
        <?php if (isset($_GET['erro'])) { ?>
            <?php if ($_GET['erro'] === 'inativo') { ?>
                <div class="alert-error" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 0.9rem;">
                    Sua conta está desativada. Entre em contato com <strong>admin@gmail.com</strong> para reativá-la.
                </div>
            <?php } elseif ($_GET['erro'] == '1') { ?>
                <div class="alert-error" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 0.9rem;">
                    E-mail ou senha inválidos!
                </div>
            <?php } elseif ($_GET['erro'] === 'aguardando') { ?>
                <div class="alert-error" style="background-color: #fff3cd; color: #805c00; border: 1px solid #ffe69c; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 0.9rem;">
                    Sua inscrição ainda está em análise. O acesso será liberado após a confirmação da secretaria.
                </div>
            <?php } ?>
        <?php } ?>

        <form action="../controller/UsuarioController.php?acao=autenticar" method="POST">
            <div class="input-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="Digite seu e-mail">
            </div>

            <div class="input-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">Entrar</button>
            </div>
        </form>
            <p class="register-link">Ainda não tem cadastro? <a href="cadastrar_usuario.php">Criar conta de aluno</a></p>
        </div>
    </main>

    <section class="course-section" aria-labelledby="course-title">
        <div class="course-heading">
            <div>
                <p class="eyebrow">ESCOLHA SEU CAMINHO</p>
                <h2 id="course-title">Encontre o curso ideal para você</h2>
            </div>
            <p>Escolha uma opção para começar sua inscrição.</p>
        </div>
        <div class="course-grid">
            <?php foreach ($cursos as $curso) { ?>
                <a class="course-tile" href="cadastrar_usuario.php?id_curso=<?php echo (int) $curso['id_curso']; ?>">
                    <span class="course-code"><?php echo htmlspecialchars($curso['sigla']); ?></span>
                    <strong><?php echo htmlspecialchars($curso['nome']); ?></strong>
                    <span class="course-action">Escolher curso <span aria-hidden="true">→</span></span>
                </a>
            <?php } ?>
        </div>
    </section>

    <footer class="login-footer">© <?php echo date('Y'); ?> Escola Técnica de Ceilândia <span>•</span> Portal do aluno</footer>

</body>
</html>