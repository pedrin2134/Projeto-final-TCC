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
$documentos = $preMatricula
    ? $controller->listarDocumentosPreMatricula((int)$preMatricula['id_pre_matricula'], $idAluno)
    : [];
$status = $preMatricula['status'] ?? null;
$mensagens = [
    'Em Analise' => 'Sua pré-matrícula está em análise pela secretaria.',
    'Pendente de Documentacao' => 'A secretaria solicitou documentação para continuar a análise.',
    'Indeferido' => 'Sua pré-matrícula foi indeferida. Você pode enviar uma nova solicitação.'
];
$etapa = $_GET['etapa'] ?? ($preMatricula ? 'documentos' : 'curso');
$mostrarDocumentos = $preMatricula && ($etapa === 'documentos' || ($_GET['documento'] ?? '') === 'erro');
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
            <div class="step <?= $etapa === 'curso' ? 'active' : 'complete' ?>"><span>2</span><div><strong>Curso</strong><small>Escolha sua formação</small></div></div>
            <div class="step <?= $etapa === 'documentos' ? 'active' : ($etapa === 'status' ? 'complete' : '') ?>"><span>3</span><div><strong>Documentos</strong><small>Envie seus arquivos</small></div></div>
            <div class="step <?= $etapa === 'status' ? 'active' : '' ?>"><span>4</span><div><strong>Avaliação</strong><small>Aguarde a secretaria</small></div></div>
        </aside>

        <section class="workflow-content">

        <?php if (($_GET['documento'] ?? '') === 'ok'): ?>
            <p style="color: green;">Todos os documentos foram enviados para análise.</p>
        <?php elseif (($_GET['documento'] ?? '') === 'erro'): ?>
            <p style="color: red;">Envie todos os documentos obrigatórios usando PDF, JPG ou PNG com até 5 MB por arquivo.</p>
        <?php endif; ?>

        <?php if ($status && $etapa === 'status'): ?>
            <div class="card status-card">
                <?php if (($_GET['etapa'] ?? '') === 'status'): ?>
                    <h2>Status da pré-matrícula</h2>
                <?php endif; ?>
                <h3><?= htmlspecialchars($status) ?></h3>
                <p><?= htmlspecialchars($mensagens[$status] ?? 'Aguarde a análise da secretaria.') ?></p>
                <p><strong>Curso:</strong> <?= htmlspecialchars($preMatricula['curso_nome']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$preMatricula || $status === 'Indeferido'): ?>
            <div class="card course-card">
                <div class="section-heading">
                    <span class="section-icon">01</span>
                    <div><h3>Escolha seu curso</h3><p>Selecione uma formação para iniciar sua pré-matrícula.</p></div>
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
                        <button type="submit" name="btn_pre_matricula" class="btn btn-primary">Enviar pré-matrícula</button>
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
                    <div><h3>Documentos obrigatórios</h3><p>Envie todos os arquivos para a secretaria avaliar.</p></div>
                </div>
                <form action="../../controller/UsuarioController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_pre_matricula" value="<?= (int)$preMatricula['id_pre_matricula'] ?>">
                    <?php foreach ($documentos as $documento): ?>
                        <div class="document-row">
                            <p><strong><?= htmlspecialchars($documento['tipo_nome']) ?></strong></p>
                            <p>Status: <?= htmlspecialchars($documento['status'] ?? 'Pendente') ?></p>
                            <?php if (!empty($documento['justificativa_recusa'])): ?>
                                <p>Observação da secretaria: <?= htmlspecialchars($documento['justificativa_recusa']) ?></p>
                            <?php endif; ?>
                            <input type="file" name="documentos[<?= (int)$documento['id_tipo_documento'] ?>]" accept=".pdf,.jpg,.jpeg,.png" <?= (!$documento['id_documento'] || $documento['status'] === 'Indeferido') ? 'required' : '' ?>>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="btn_enviar_documentos" class="btn btn-primary">Enviar todos os documentos</button>
                </form>
            </div>
        <?php endif; ?>

        <p><a href="../../controller/UsuarioController.php?acao=logout">Sair</a></p>
        </section>
    </div>
</body>
</html>
