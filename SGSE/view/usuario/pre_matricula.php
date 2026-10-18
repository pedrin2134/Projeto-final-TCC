<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['inscricao_usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

if (isset($_SESSION['usuario_id'])
    && ((int)($_SESSION['perfil_id'] ?? 0) !== 3
        || !Autorizacao::usuarioTemPermissao('GERENCIAR_PRE_MATRICULAS'))) {
    header('Location: ../../index.php');
    exit();
}

if (!isset($_SESSION['usuario_id'])
    && !Autorizacao::usuarioTemPermissao(
        'GERENCIAR_PRE_MATRICULAS',
        (int)$_SESSION['inscricao_usuario_id']
    )) {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../../controller/UsuarioController.php';

$controller = new UsuarioController();
$idAluno = (int)($_SESSION['usuario_id'] ?? $_SESSION['inscricao_usuario_id']);
$preMatricula = $controller->buscarPreMatricula($idAluno);

if ($preMatricula && $preMatricula['status'] === 'Aprovado') {
    header('Location: home_usuario.php');
    exit();
}

$cursos = $controller->listarCursosAtivos();
$documentos = $controller->listarDocumentosAluno($idAluno);
$documentosCompletos = count($documentos) > 0;
foreach ($documentos as $documento) {
    if (empty($documento['caminho_arquivo']) || $documento['status'] === 'Indeferido') {
        $documentosCompletos = false;
        break;
    }
}

$status = $preMatricula['status'] ?? null;
$mensagens = [
    'Em Analise' => 'Sua pré-matrícula está em análise pela secretaria.',
    'Pendente de Documentacao' => 'A secretaria solicitou documentação para continuar a análise.',
    'Indeferido' => 'Sua pré-matrícula foi indeferida. Você pode enviar uma nova solicitação.'
];
$novaInscricao = !$preMatricula || $status === 'Indeferido';
$mostrarDocumentos = (!$preMatricula && !$documentosCompletos)
    || ($status === 'Pendente de Documentacao')
    || ($status === 'Indeferido' && !$documentosCompletos);
$mostrarCurso = $novaInscricao && $documentosCompletos;
$dadosPessoais = $mostrarDocumentos
    ? $controller->buscarDadosPessoaisInscricao($idAluno)
    : null;
if ($mostrarDocumentos && !$dadosPessoais) {
    http_response_code(404);
    exit('Não foi possível carregar os dados pessoais desta inscrição.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-matrícula</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo.css'); ?>">
    <link rel="stylesheet" href="../../assets/css/estilo_home.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo_home.css'); ?>">
    <link rel="stylesheet" href="../../assets/css/estilo_fluxo_inscricao.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo_fluxo_inscricao.css'); ?>">
    <script src="../../assets/js/tema.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/tema.js'); ?>" defer></script>
    <script src="../../assets/js/arquivo-selecionado.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/arquivo-selecionado.js'); ?>" defer></script>
    <script src="../../assets/js/dados-pessoais-inscricao.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/dados-pessoais-inscricao.js'); ?>" defer></script>
</head>
<body class="workflow-page student-workflow signup-flow-page">
    <header class="signup-topbar">
        <a class="brand" href="../login.php">
            <img src="../../assets/img/sua_logo.png" alt="Logo da Escola Técnica de Ceilândia">
            <span>SGSE<br><small>SISTEMA DE GESTÃO DE SECRETARIA ESCOLAR</small></span>
        </a>
        <a class="back-link" href="../login.php" aria-label="Voltar para o acesso"><span class="back-link-full">← Voltar para o acesso</span><span class="back-link-short" aria-hidden="true">← Voltar</span></a>
        <button type="button" class="theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
    </header>

    <main class="main-content workflow-shell workflow-layout signup-workflow-layout">
        <aside class="steps workflow-sidebar" aria-label="Etapas da pré-matrícula">
            <div class="signup-intro">
                <p class="eyebrow">NOVA INSCRIÇÃO</p>
                <h1 data-inscricao-titulo><?= $mostrarDocumentos ? 'Envio de<br>Documentação.' : ($mostrarCurso ? 'Escolha seu<br>curso.' : 'Acompanhe sua<br>inscrição.') ?></h1>
                <p data-inscricao-descricao><?= $mostrarDocumentos ? 'Anexe os arquivos necessários para a secretaria analisar sua pré-matrícula.' : ($mostrarCurso ? 'Selecione uma formação para enviar sua pré-matrícula à secretaria.' : 'Consulte o andamento da sua pré-matrícula e as orientações da secretaria.') ?></p>
            </div>
            <div class="step complete" data-inscricao-etapa="dados"><span>✓</span><div><strong>Dados pessoais</strong><small>Cadastro realizado</small></div></div>
            <div class="step <?= $mostrarDocumentos ? 'active' : ($documentosCompletos ? 'complete' : '') ?>" data-inscricao-etapa="documentos"><span>2</span><div><strong>Documentos</strong><small>Envie os arquivos obrigatórios</small></div></div>
            <div class="step <?= $mostrarCurso ? 'active' : ($preMatricula ? 'complete' : '') ?>"><span>3</span><div><strong>Curso</strong><small>Escolha sua formação</small></div></div>
            <div class="step <?= $preMatricula ? 'active' : '' ?>"><span>4</span><div><strong>Avaliação</strong><small>Aguarde a secretaria</small></div></div>
        </aside>

        <section class="workflow-content signup-workflow-content">

        <?php if (($_GET['documento'] ?? '') === 'ok'): ?>
            <p class="workflow-feedback workflow-feedback-success" role="status">
                <?php if ($preMatricula): ?>
                    Documentos enviados para nova análise da secretaria.
                <?php else: ?>
                    Documentos enviados. Agora você pode escolher um curso.
                <?php endif; ?>
            </p>
        <?php elseif (($_GET['documento'] ?? '') === 'faltante'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Envie todos os documentos obrigatórios pendentes para continuar.</p>
        <?php elseif (($_GET['documento'] ?? '') === 'arquivo_invalido'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Há um arquivo inválido. Envie PDF, JPG ou PNG de até 5 MB; a Foto 3x4 deve ser JPG ou PNG.</p>
        <?php elseif (($_GET['documento'] ?? '') === 'foto_invalida'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">A Foto 3×4 deve ser uma imagem vertical JPG ou PNG, com proporção 3:4 (tolerância de 2%) e resolução mínima de 300×400 pixels. O limite é 5 MB.</p>
        <?php elseif (($_GET['documento'] ?? '') === 'erro'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Não foi possível armazenar os documentos. Tente novamente.</p>
        <?php endif; ?>

        <?php if (($_GET['erro'] ?? '') === 'curso'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Envie todos os documentos obrigatórios antes de escolher um curso.</p>
        <?php endif; ?>

        <?php if ($status): ?>
            <div class="card status-card">
                <h2>Status da pré-matrícula</h2>
                <h3><?= htmlspecialchars($status) ?></h3>
                <p><?= htmlspecialchars($mensagens[$status] ?? 'Aguarde a análise da secretaria.') ?></p>
                <?php if (!empty($preMatricula['curso_nome'])): ?>
                    <p><strong>Curso:</strong> <?= htmlspecialchars($preMatricula['curso_nome']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($mostrarDocumentos): ?>
            <div class="card personal-data-card" data-personal-data-stage hidden>
                <div class="section-heading">
                    <span class="section-icon">01</span>
                    <div><h3>Dados pessoais</h3><p>Estas são as informações salvas no seu cadastro.</p></div>
                </div>
                <dl class="personal-data-list">
                    <div><dt>Nome completo</dt><dd><?= htmlspecialchars($dadosPessoais['nome'] ?? '') ?></dd></div>
                    <div><dt>CPF</dt><dd><?= htmlspecialchars($dadosPessoais['cpf'] ?? '') ?></dd></div>
                    <div><dt>Telefone</dt><dd><?= htmlspecialchars($dadosPessoais['telefone'] ?? '') ?></dd></div>
                    <div><dt>Data de nascimento</dt><dd><?= !empty($dadosPessoais['data_nascimento']) ? date('d/m/Y', strtotime($dadosPessoais['data_nascimento'])) : '' ?></dd></div>
                    <div><dt>E-mail</dt><dd><?= htmlspecialchars($dadosPessoais['email'] ?? '') ?></dd></div>
                </dl>
                <p class="personal-data-note">Por segurança, a senha não é exibida nesta tela.</p>
                <div class="workflow-form-actions">
                    <button type="button" class="workflow-back-button" data-mostrar-documentos>← Voltar para documentos</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mostrarCurso): ?>
            <div class="card course-card">
                <div class="section-heading">
                    <span class="section-icon">03</span>
                    <div><h3>Escolha seu curso</h3><p>Seus documentos foram enviados. Selecione uma formação para encaminhar a pré-matrícula.</p></div>
                </div>
                <?php if ($cursos): ?>
                    <form action="../../controller/UsuarioController.php" method="POST">
                        <div class="workflow-course-grid">
                            <?php foreach ($cursos as $curso): ?>
                                <label class="workflow-course-option">
                                    <input type="radio" name="id_curso" value="<?= (int)$curso['id_curso'] ?>" required>
                                    <span class="workflow-course-content">
                                        <?php if (!empty($curso['imagem'])): ?>
                                            <img class="workflow-course-photo"
                                                 src="../../<?= htmlspecialchars($curso['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                                 alt="Foto do curso <?= htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?php else: ?>
                                            <span class="workflow-course-photo-placeholder" aria-hidden="true"><?= htmlspecialchars($curso['sigla'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <small><?= htmlspecialchars($curso['sigla']) ?></small>
                                        <strong><?= htmlspecialchars($curso['nome']) ?></strong>
                                        <em>Selecionar curso</em>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" name="btn_pre_matricula" class="btn btn-primary">Enviar pré-matrícula para análise</button>
                    </form>
                <?php else: ?>
                    <p>Não há cursos ativos disponíveis no momento.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($mostrarDocumentos): ?>
            <div class="card documents-card" data-documents-stage>
                <div class="section-heading">
                    <span class="section-icon">02</span>
                    <div><h3>Documentos obrigatórios</h3><p>Envie os documentos solicitados. Depois, você poderá escolher um curso.</p></div>
                </div>
                <form action="../../controller/UsuarioController.php" method="POST" enctype="multipart/form-data">
                    <?php foreach ($documentos as $documento): ?>
                        <div class="document-row">
                            <p><strong><?= htmlspecialchars($documento['tipo_nome']) ?></strong></p>
                            <p class="document-status">Status: <span class="status-pill status-<?= strtolower($documento['status'] ?? 'pendente') ?>"><?= htmlspecialchars($documento['status'] ?? 'Pendente') ?></span></p>
                            <?php if (!empty($documento['justificativa_recusa'])): ?>
                                <p>Observação da secretaria: <?= htmlspecialchars($documento['justificativa_recusa']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($documento['caminho_arquivo'])): ?>
                                <p class="document-file-uploaded"><strong>Arquivo enviado:</strong> <?= htmlspecialchars(basename($documento['caminho_arquivo'])) ?></p>
                            <?php endif; ?>
                            <?php if ($documento['tipo_nome'] === 'Foto 3x4'): ?>
                                <p class="document-help">Foto vertical, proporção 3:4, mínimo de 300×400 px; JPG ou PNG até 5 MB.</p>
                            <?php endif; ?>
                            <input type="file" name="documentos[<?= (int)$documento['id_tipo_documento'] ?>]" accept="<?= $documento['tipo_nome'] === 'Foto 3x4' ? 'image/jpeg,image/png' : '.pdf,.jpg,.jpeg,.png' ?>" <?= (empty($documento['caminho_arquivo']) || $documento['status'] === 'Indeferido') ? 'required' : '' ?>>
                            <p class="document-file-feedback" data-file-feedback role="status" aria-live="polite" hidden></p>
                        </div>
                    <?php endforeach; ?>
                    <div class="workflow-form-actions">
                        <button type="button" class="workflow-back-button" data-mostrar-dados>← Voltar</button>
                        <button type="submit" name="btn_enviar_documentos" class="btn btn-primary">Avançar para Cursos →</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <p><a href="../../controller/UsuarioController.php?acao=logout">Sair</a></p>
        <?php endif; ?>
        </section>
    </main>
</body>
</html>
