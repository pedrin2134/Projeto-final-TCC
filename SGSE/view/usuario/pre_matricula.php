<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['inscricao_usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

if (isset($_SESSION['usuario_id']) && (int)($_SESSION['perfil_id'] ?? 0) !== 3) {
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
$etapa = $_GET['etapa'] ?? ($mostrarCurso ? 'curso' : ($preMatricula ? 'status' : 'documentos'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-matrícula</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo.css'); ?>">
    <link rel="stylesheet" href="../../assets/css/estilo_home.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo_home.css'); ?>">
    <script src="../../assets/js/tema.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/tema.js'); ?>" defer></script>
</head>
<body class="workflow-page student-workflow">
    <div class="main-content workflow-shell workflow-layout" style="max-width: 700px; margin: 30px auto; padding: 0 20px;">
        <header class="page-header workflow-header">
            <h1>Pré-matrícula</h1>
            <p>Complete sua inscrição e acompanhe a análise da secretaria.</p>
            <button type="button" class="theme-toggle workflow-theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
        </header>

        <aside class="steps workflow-sidebar" aria-label="Etapas da pré-matrícula">
            <div class="step complete"><span>1</span><div><strong>Dados pessoais</strong><small>Cadastro realizado</small></div></div>
            <div class="step <?= $mostrarDocumentos ? 'active' : ($documentosCompletos ? 'complete' : '') ?>"><span>2</span><div><strong>Documentos</strong><small>Envie os arquivos obrigatórios</small></div></div>
            <div class="step <?= $mostrarCurso ? 'active' : ($preMatricula ? 'complete' : '') ?>"><span>3</span><div><strong>Curso</strong><small>Escolha sua formação</small></div></div>
            <div class="step <?= $preMatricula ? 'active' : '' ?>"><span>4</span><div><strong>Avaliação</strong><small>Aguarde a secretaria</small></div></div>
        </aside>

        <section class="workflow-content">

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
            <div class="card documents-card">
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
                                <p>Arquivo enviado: <?= htmlspecialchars(basename($documento['caminho_arquivo'])) ?></p>
                            <?php endif; ?>
                            <?php if ($documento['tipo_nome'] === 'Foto 3x4'): ?>
                                <p class="document-help">Foto vertical, proporção 3:4, mínimo de 300×400 px; JPG ou PNG até 5 MB.</p>
                            <?php endif; ?>
                            <input type="file" name="documentos[<?= (int)$documento['id_tipo_documento'] ?>]" accept="<?= $documento['tipo_nome'] === 'Foto 3x4' ? 'image/jpeg,image/png' : '.pdf,.jpg,.jpeg,.png' ?>" <?= (empty($documento['caminho_arquivo']) || $documento['status'] === 'Indeferido') ? 'required' : '' ?>>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="btn_enviar_documentos" class="btn btn-primary">Enviar documentos e continuar</button>
                </form>
            </div>
        <?php endif; ?>

        <p><a href="../../controller/UsuarioController.php?acao=logout">Sair</a></p>
        </section>
    </div>
</body>
</html>
