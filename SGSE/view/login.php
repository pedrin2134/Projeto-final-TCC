<?php
session_start();

require_once __DIR__.'/../model/dao/Conexao.php';
require_once __DIR__.'/../model/dao/UsuarioDAO.php';

$cursos = (new UsuarioDAO())->listarCursosAtivos();
$slidesBanner = [[
    'imagem' => '../assets/img/portal-banner.avif',
    'alt' => 'Profissionais trabalhando em um ambiente de tecnologia',
    'titulo' => 'Conecte-se ao seu',
    'destaque' => 'Futuro Profissional.',
    'texto' => 'Descubra novas possibilidades, escolha sua formação e construa o futuro que você deseja.',
    'rotulo' => 'FORMAÇÃO PARA O FUTURO'
]];
$hashesBanner = [hash_file('sha256', __DIR__ . '/../assets/img/portal-banner.avif')];

foreach ($cursos as $curso) {
    $imagemCurso = (string)($curso['imagem'] ?? '');
    if (count($slidesBanner) >= 4) {
        break;
    }
    if (!preg_match('#^uploads/cursos/[a-f0-9]{32}\.(jpg|png|webp)$#', $imagemCurso)
        || !is_file(__DIR__ . '/../' . $imagemCurso)) {
        continue;
    }
    $hashImagem = hash_file('sha256', __DIR__ . '/../' . $imagemCurso);
    if ($hashImagem === false || in_array($hashImagem, $hashesBanner, true)) {
        continue;
    }

    $nomeCurso = (string)$curso['nome'];
    $hashesBanner[] = $hashImagem;
    $slidesBanner[] = [
        'imagem' => '../' . $imagemCurso,
        'alt' => 'Imagem do curso ' . $nomeCurso,
        'titulo' => 'Conecte-se ao seu',
        'destaque' => 'Futuro Profissional.',
        'texto' => 'Conheça ' . $nomeCurso . ' e dê o próximo passo na sua trajetória profissional.',
        'rotulo' => 'SEU FUTURO COMEÇA COM UMA ESCOLHA'
    ];
}

if (count($slidesBanner) < 3) {
    foreach (glob(__DIR__ . '/../uploads/cursos/*') ?: [] as $arquivoCurso) {
        if (count($slidesBanner) >= 4) {
            break;
        }
        $imagemCurso = str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            substr($arquivoCurso, strlen(__DIR__ . '/../'))
        );
        if (!preg_match('#^uploads/cursos/[a-f0-9]{32}\.(jpg|png|webp)$#', $imagemCurso)) {
            continue;
        }
        $hashImagem = hash_file('sha256', $arquivoCurso);
        if ($hashImagem === false || in_array($hashImagem, $hashesBanner, true)) {
            continue;
        }

        $hashesBanner[] = $hashImagem;
        $slidesBanner[] = [
            'imagem' => '../' . $imagemCurso,
            'alt' => 'Ambiente de aprendizagem e formação profissional',
            'titulo' => 'Conecte-se ao seu',
            'destaque' => 'Futuro Profissional.',
            'texto' => 'Conheça os cursos disponíveis e dê o próximo passo na sua trajetória profissional.',
            'rotulo' => 'SEU FUTURO COMEÇA COM UMA ESCOLHA'
        ];
    }
}

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
    <link rel="stylesheet" href="../assets/css/estilo_banner.css?v=<?php echo filemtime(__DIR__.'/../assets/css/estilo_banner.css'); ?>">
    <script src="../assets/js/tema.js?v=<?php echo filemtime(__DIR__.'/../assets/js/tema.js'); ?>" defer></script>
    <script src="../assets/js/login-banner.js?v=<?php echo filemtime(__DIR__.'/../assets/js/login-banner.js'); ?>" defer></script>
    <script src="../assets/js/login-password.js?v=<?php echo filemtime(__DIR__.'/../assets/js/login-password.js'); ?>" defer></script>
</head>
<body class="login-page">
    <header class="site-header">
        <a class="brand" href="login.php">
            <img class="brand-logo" src="../assets/img/sua_logo.png" alt="Logo SGSE - Sistema de Gestão de Secretaria Escolar">
            <span>SGSE<br><small>SECRETARIA ESCOLAR</small></span>
        </a>
        <nav class="site-nav" aria-label="Navegação principal">
            <a href="#cursos">CURSOS</a>
            <a href="#atendimento">SUPORTE</a>
            <a class="nav-cta" href="cadastrar_usuario.php">INSCREVA-SE</a>
            <button type="button" class="theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
        </nav>
    </header>

    <main class="login-layout">
        <section class="login-visual">
            <div class="login-banner" data-login-banner aria-label="Conheça as oportunidades da Escola Técnica de Ceilândia">
                <div class="banner-slides">
                    <?php foreach ($slidesBanner as $indice => $slide): ?>
                        <article class="banner-slide<?= $indice === 0 ? ' is-active' : '' ?>"
                                 data-banner-slide
                                 aria-hidden="<?= $indice === 0 ? 'false' : 'true' ?>">
                            <img src="<?= htmlspecialchars($slide['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($slide['alt'], ENT_QUOTES, 'UTF-8') ?>"
                                 <?= $indice === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                            <div class="banner-shade"></div>
                            <div class="banner-content">
                                <span class="banner-kicker"><?= htmlspecialchars($slide['rotulo'], ENT_QUOTES, 'UTF-8') ?></span>
                                <h1><?= htmlspecialchars($slide['titulo'], ENT_QUOTES, 'UTF-8') ?><br><span class="banner-title-highlight"><?= htmlspecialchars($slide['destaque'], ENT_QUOTES, 'UTF-8') ?></span></h1>
                                <p><?= htmlspecialchars($slide['texto'], ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="banner-actions">
                                    <a class="banner-cta" href="cadastrar_usuario.php">INICIAR PRÉ-MATRÍCULA <span aria-hidden="true">→</span></a>
                                    <a class="banner-cta banner-cta-secondary" href="#cursos">EXPLORAR CURSOS</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if (count($slidesBanner) > 1): ?>
                    <div class="banner-controls" aria-label="Controles do banner">
                        <button type="button" class="banner-arrow" data-banner-previous aria-label="Imagem anterior">‹</button>
                        <div class="banner-dots">
                            <?php foreach ($slidesBanner as $indice => $slide): ?>
                                <button type="button"
                                        class="banner-dot<?= $indice === 0 ? ' is-active' : '' ?>"
                                        data-banner-go="<?= $indice ?>"
                                        aria-label="Mostrar imagem <?= $indice + 1 ?>"
                                        aria-current="<?= $indice === 0 ? 'true' : 'false' ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="banner-arrow" data-banner-next aria-label="Próxima imagem">›</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <div class="login-container login-card" id="suporte">
            <div class="card-kicker">ACESSO RÁPIDO</div>
            <h2>Portal Acadêmico</h2>
            <p class="card-intro">Entre para acompanhar sua inscrição e seus dados acadêmicos.</p>

        <!-- Tratamento das mensagens de erro -->
        <?php if (isset($_GET['erro'])) { ?>
            <?php if ($_GET['erro'] === 'inativo') { ?>
                <div class="alert-error">
                    Sua conta está desativada. Entre em contato com <strong>admin@gmail.com</strong> para reativá-la.
                </div>
            <?php } elseif ($_GET['erro'] == '1') { ?>
                <div class="alert-error">
                    E-mail ou senha inválidos!
                </div>
            <?php } ?>
        <?php } ?>

        <form action="../controller/UsuarioController.php?acao=autenticar" method="POST">
            <div class="input-group">
                <label for="email">E-MAIL INSTITUCIONAL</label>
                <div class="login-input-control">
                    <svg class="login-input-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 6.5h17v11h-17z"></path><path d="m4 7 8 6 8-6"></path></svg>
                    <input type="email" id="email" name="email" required placeholder="Digite seu e-mail" autocomplete="username">
                </div>
            </div>

            <div class="input-group">
                <label for="senha">SENHA</label>
                <div class="password-field">
                    <svg class="login-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path></svg>
                    <input type="password" id="senha" name="senha" required placeholder="Digite sua senha" autocomplete="current-password">
                    <button type="button" class="password-toggle" data-toggle-password aria-label="Mostrar senha" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.2 12s3.4-6 9.8-6 9.8 6 9.8 6-3.4 6-9.8 6-9.8-6-9.8-6Z"></path><circle cx="12" cy="12" r="2.8"></circle><path class="eye-slash" d="m4 4 16 16"></path></svg>
                    </button>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary login-submit">
                    <span>Entrar no Sistema</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5h5v14h-5"></path><path d="M10 8l4 4-4 4"></path><path d="M14 12H3"></path></svg>
                </button>
            </div>
        </form>
            <p class="register-link">Ainda não tem cadastro? <a href="cadastrar_usuario.php">Criar conta de aluno</a></p>
        </div>
    </main>

    <section class="course-section" id="cursos" aria-labelledby="course-title">
        <div class="course-heading">
            <div>
                <p class="eyebrow">ESCOLHA SEU CAMINHO</p>
                <h2 id="course-title">Encontre o curso ideal para você</h2>
            </div>
            <p>Escolha uma opção para começar sua inscrição.</p>
        </div>
        <div class="course-grid">
            <?php foreach ($cursos as $curso) { ?>
                <a class="course-tile" href="cadastrar_usuario.php">
                    <?php if (!empty($curso['imagem'])) { ?>
                        <img class="course-photo"
                             src="../<?php echo htmlspecialchars($curso['imagem'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="Foto do curso <?php echo htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php } else { ?>
                        <span class="course-photo-placeholder" aria-hidden="true"><?php echo htmlspecialchars($curso['sigla'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php } ?>
                    <span class="course-code"><?php echo htmlspecialchars($curso['sigla'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="course-tile-content">
                        <strong><?php echo htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span class="course-action">Começar inscrição <span aria-hidden="true">→</span></span>
                    </span>
                </a>
            <?php } ?>
        </div>
    </section>

    <footer class="login-footer" aria-label="Informações e suporte">
        <div class="login-footer-main">
            <div class="footer-about">
                <a class="footer-brand" href="login.php" aria-label="SGSE - página inicial">
                    <img src="../assets/img/sua_logo.png" alt="">
                    <strong>SGSE</strong>
                </a>
                <p>Sistema de Gestão de Secretaria Escolar integrado, seguro e ágil para instituições de ensino.</p>
            </div>

            <nav class="footer-links" aria-label="Links rápidos">
                <h2>Links Rápidos</h2>
                <a href="#cursos">Nossos Cursos</a>
                <a href="#suporte">Área Restrita</a>
                <a href="cadastrar_usuario.php">Pré-Matrícula Online</a>
            </nav>

            <nav class="footer-courses" aria-label="Cursos">
                <h2>Cursos</h2>
                <?php foreach ($cursos as $curso): ?>
                    <a href="#cursos"><?= htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="footer-support" id="atendimento">
                <h2>Secretaria Atendimento</h2>
                <p>Segunda a Sexta: 08h às 21h</p>
                <p>Sábados: 08h às 12h</p>
                <a class="footer-phone" href="tel:0800592800">
                    <span aria-hidden="true">☎</span> 0800 592-800
                </a>
            </div>
        </div>
        <div class="login-footer-bottom">
            © <?= date('Y') ?> SGSE • Secretaria Escolar Digital. Todos os direitos reservados.
        </div>
    </footer>

</body>
</html>