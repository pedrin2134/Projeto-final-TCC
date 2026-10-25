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
$documentosEnviados = array_values(array_filter($documentos, static function (array $documento): bool {
    return !empty($documento['id_documento'])
        && is_string($documento['caminho_arquivo'] ?? null)
        && preg_match('#^uploads/documentos/[a-f0-9]{32}\.(pdf|jpg|png)$#', $documento['caminho_arquivo'])
        && is_file(__DIR__ . '/../../' . $documento['caminho_arquivo']);
}));
$documentosCompletos = count($documentos) > 0;
foreach ($documentos as $documento) {
    if (empty($documento['caminho_arquivo']) || ($documento['status'] ?? '') === 'Indeferido') {
        $documentosCompletos = false;
        break;
    }
}

$status = $preMatricula['status'] ?? null;
$correcaoSolicitada = $status === 'Pendente de Documentacao';
$totalDocumentosRejeitados = count(array_filter($documentosEnviados, static function (array $documento): bool {
    return ($documento['status'] ?? '') === 'Indeferido';
}));
$totalDocumentosAprovados = count(array_filter($documentos, static function (array $documento): bool {
    return ($documento['status'] ?? '') === 'Deferido';
}));
$mensagens = [
    'Em Analise' => 'Sua pré-matrícula está em análise pela secretaria.',
    'Pendente de Documentacao' => 'A secretaria solicitou correções nos documentos para continuar a análise da sua pré-matrícula.',
    'Indeferido' => 'Sua pré-matrícula foi indeferida. Você pode enviar uma nova solicitação.'
];
$rotulosStatusDocumento = [
    'Pendente' => 'Pendente',
    'Em Validacao' => 'Em validação',
    'Deferido' => 'Aprovado',
    'Indeferido' => 'Rejeitado'
];
$rotulosStatus = [
    'Em Analise' => 'Em análise',
    'Pendente de Documentacao' => 'Correção solicitada',
    'Indeferido' => 'Indeferida'
];
$classesStatus = [
    'Em Analise' => 'is-analysis',
    'Pendente de Documentacao' => 'is-pending',
    'Indeferido' => 'is-rejected'
];
$dataInscricao = !empty($preMatricula['data_inscricao'])
    ? date('d/m/Y H:i', strtotime($preMatricula['data_inscricao']))
    : null;
$dataParecer = !empty($preMatricula['data_analise'])
    ? date('d/m/Y', strtotime($preMatricula['data_analise']))
    : null;
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
<body class="workflow-page student-workflow signup-flow-page<?= $correcaoSolicitada ? ' correction-mode' : '' ?>">
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
                <p class="eyebrow"><?= $correcaoSolicitada ? 'AJUSTES NECESSÁRIOS' : 'NOVA INSCRIÇÃO' ?></p>
                <h1 data-inscricao-titulo><?= $correcaoSolicitada ? 'Corrija seus<br>documentos.' : ($mostrarDocumentos ? 'Envio de<br>Documentação.' : ($mostrarCurso ? 'Escolha seu<br>curso.' : 'Acompanhe sua<br>inscrição.')) ?></h1>
                <p data-inscricao-descricao><?= $correcaoSolicitada ? 'A secretaria pediu ajustes. Consulte os motivos e reenvie os arquivos corrigidos.' : ($mostrarDocumentos ? 'Anexe os arquivos necessários para a secretaria analisar sua pré-matrícula.' : ($mostrarCurso ? 'Selecione uma formação para enviar sua pré-matrícula à secretaria.' : 'Consulte o andamento da sua pré-matrícula e as orientações da secretaria.')) ?></p>
            </div>
            <div class="step complete" data-inscricao-etapa="dados"><span>1</span><div><strong>Dados pessoais</strong><small>Cadastro realizado</small></div></div>
            <div class="step <?= $mostrarDocumentos ? ($correcaoSolicitada ? 'active needs-correction' : 'active') : ($documentosCompletos ? 'complete' : '') ?>" data-inscricao-etapa="documentos"><span><?= $correcaoSolicitada ? '!' : '2' ?></span><div><strong>Documentos</strong><small><?= $correcaoSolicitada ? 'Correção solicitada' : 'Envie os arquivos obrigatórios' ?></small></div></div>
            <div class="step <?= $mostrarCurso ? 'active' : ($preMatricula ? 'complete' : '') ?>"><span>3</span><div><strong>Curso</strong><small><?= $correcaoSolicitada ? htmlspecialchars($preMatricula['curso_nome'] ?? 'Curso selecionado', ENT_QUOTES, 'UTF-8') : 'Escolha sua formação' ?></small></div></div>
            <div class="step <?= $preMatricula && !$correcaoSolicitada ? 'active' : '' ?>"><span>4</span><div><strong>Avaliação</strong><small><?= $correcaoSolicitada ? 'Aguardando reenvio' : 'Aguarde a secretaria' ?></small></div></div>
        </aside>

        <section class="workflow-content signup-workflow-content">

        <?php if ($correcaoSolicitada): ?>
            <section class="correction-request-banner" aria-labelledby="correction-request-title">
                <span class="correction-request-icon" aria-hidden="true">!</span>
                <div class="correction-request-copy">
                    <h2 id="correction-request-title">Parecer da Secretaria Escolar</h2>
                    <p><?= !empty($preMatricula['observacao'])
                        ? '“' . htmlspecialchars($preMatricula['observacao'], ENT_QUOTES, 'UTF-8') . '”'
                        : 'A Secretaria solicitou a correção dos documentos destacados abaixo. Envie somente os arquivos recusados.' ?></p>
                </div>
                <?php if ($dataParecer): ?>
                    <time class="correction-request-date" datetime="<?= htmlspecialchars(date('Y-m-d', strtotime($preMatricula['data_analise'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($dataParecer, ENT_QUOTES, 'UTF-8') ?></time>
                <?php endif; ?>
            </section>
        <?php endif; ?>

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

        <?php if ($status && !$correcaoSolicitada): ?>
            <div class="card status-card application-status-card<?= $correcaoSolicitada ? ' is-correction-requested' : '' ?>">
                <div class="application-status-heading">
                    <div>
                        <span class="application-status-kicker">ACOMPANHAMENTO DA INSCRIÇÃO</span>
                        <h2>Status da pré-matrícula</h2>
                    </div>
                    <span class="application-status-badge <?= htmlspecialchars($classesStatus[$status] ?? 'is-analysis') ?>">
                        <?= htmlspecialchars($rotulosStatus[$status] ?? $status) ?>
                    </span>
                </div>
                <p class="application-status-message">
                    <?= htmlspecialchars($mensagens[$status] ?? 'Aguarde a análise da secretaria.') ?>
                </p>
                <dl class="application-status-details">
                    <?php if (!empty($preMatricula['curso_nome'])): ?>
                        <div><dt>Curso escolhido</dt><dd><?= htmlspecialchars($preMatricula['curso_nome']) ?></dd></div>
                    <?php endif; ?>
                    <?php if ($dataInscricao): ?>
                        <div><dt>Data da inscrição</dt><dd><?= htmlspecialchars($dataInscricao) ?></dd></div>
                    <?php endif; ?>
                </dl>
                <?php if (!empty($preMatricula['observacao'])): ?>
                    <div class="application-status-note">
                        <strong>Orientação da secretaria</strong>
                        <p><?= nl2br(htmlspecialchars($preMatricula['observacao'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$correcaoSolicitada && !$mostrarDocumentos && !$mostrarCurso && $documentosEnviados): ?>
            <section class="card uploaded-documents-card" aria-labelledby="uploaded-documents-title">
                <div class="section-heading">
                    <span class="section-icon" aria-hidden="true"><?= $correcaoSolicitada ? '!' : '02' ?></span>
                    <div>
                        <h3 id="uploaded-documents-title"><?= $correcaoSolicitada ? 'Documentos analisados pela secretaria' : 'Documentos enviados' ?></h3>
                        <p><?= $correcaoSolicitada ? 'Veja quais precisam de correção e confira o motivo informado pela secretaria.' : 'Confira os arquivos que você encaminhou para análise.' ?></p>
                    </div>
                </div>
                <div class="uploaded-documents-list">
                    <?php foreach ($documentosEnviados as $documento): ?>
                        <?php
                        $statusDocumento = (string)($documento['status'] ?? 'Pendente');
                        $classeDocumento = $statusDocumento === 'Indeferido'
                            ? 'is-rejected'
                            : ($statusDocumento === 'Deferido' ? 'is-approved' : 'is-waiting');
                        ?>
                        <article class="uploaded-document-row <?= $classeDocumento ?>">
                            <div class="uploaded-document-info">
                                <div class="uploaded-document-copy">
                                    <div class="uploaded-document-title">
                                        <strong><?= htmlspecialchars($documento['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $statusDocumento)) ?>">
                                            <?= htmlspecialchars($rotulosStatusDocumento[$statusDocumento] ?? $statusDocumento, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($documento['justificativa_recusa'])): ?>
                                        <p class="uploaded-document-note"><strong>Orientação:</strong> <?= htmlspecialchars($documento['justificativa_recusa'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php elseif ($statusDocumento === 'Deferido'): ?>
                                        <p class="uploaded-document-note">Documento validado pela secretaria.</p>
                                    <?php else: ?>
                                        <p class="uploaded-document-note">Aguardando avaliação da secretaria.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a class="uploaded-document-link"
                               href="../../controller/UsuarioController.php?acao=visualizar_documento&amp;id=<?= (int)$documento['id_documento'] ?>"
                               target="_blank" rel="noopener noreferrer">
                                Visualizar arquivo <span aria-hidden="true">↗</span>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
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
            <div class="card documents-card" id="<?= $correcaoSolicitada ? 'correcao-documentos' : 'documentos-inscricao' ?>" data-documents-stage data-correction-requested="<?= $correcaoSolicitada ? 'true' : 'false' ?>">
                <div class="section-heading">
                    <span class="section-icon"><?= $correcaoSolicitada ? '!' : '02' ?></span>
                    <div>
                        <h3><?= $correcaoSolicitada ? 'Status da documentação submetida' : 'Documentos obrigatórios' ?></h3>
                        <p><?= $correcaoSolicitada
                            ? 'Os documentos aprovados foram preservados. Substitua apenas os arquivos recusados.'
                            : 'Envie os documentos solicitados. Depois, você poderá escolher um curso.' ?></p>
                    </div>
                    <?php if ($correcaoSolicitada): ?>
                        <span class="correction-approved-count"><?= $totalDocumentosAprovados ?> de <?= count($documentos) ?> aprovados</span>
                    <?php endif; ?>
                </div>
                <form action="../../controller/UsuarioController.php" method="POST" enctype="multipart/form-data">
                    <?php foreach ($documentos as $documento): ?>
                        <?php
                        $statusAtualDocumento = (string)($documento['status'] ?? 'Pendente');
                        $documentoAprovado = $statusAtualDocumento === 'Deferido';
                        $documentoPrecisaEnvio = !$documentoAprovado
                            && (empty($documento['caminho_arquivo']) || $statusAtualDocumento === 'Indeferido');
                        ?>
                        <div class="document-row <?= $statusAtualDocumento === 'Indeferido' ? 'is-rejected-document' : ($documentoAprovado && $correcaoSolicitada ? 'is-preserved-document' : '') ?>">
                            <?php if ($correcaoSolicitada): ?>
                                <div class="correction-document-heading">
                                    <p><strong><?= htmlspecialchars($documento['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                                    <p class="correction-document-detail"><?= $documentoAprovado
                                        ? 'Documento legível e verificado pela secretaria.'
                                        : ($statusAtualDocumento === 'Indeferido'
                                            ? htmlspecialchars($documento['justificativa_recusa'] ?: 'Correção solicitada pela secretaria.', ENT_QUOTES, 'UTF-8')
                                            : (!empty($documento['caminho_arquivo'])
                                                ? 'Documento enviado e preservado durante esta correção.'
                                                : 'Documento ainda não enviado.')) ?></p>
                                </div>
                                <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $statusAtualDocumento)) ?>">
                                    <?= htmlspecialchars($documentoAprovado ? 'Aprovado' : ($statusAtualDocumento === 'Indeferido' ? 'Correção solicitada' : (!empty($documento['caminho_arquivo']) ? 'Enviado' : ($rotulosStatusDocumento[$statusAtualDocumento] ?? $statusAtualDocumento))), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php else: ?>
                                <p><strong><?= htmlspecialchars($documento['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                                <p class="document-status">Status: <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $statusAtualDocumento)) ?>"><?= htmlspecialchars($rotulosStatusDocumento[$statusAtualDocumento] ?? $statusAtualDocumento, ENT_QUOTES, 'UTF-8') ?></span></p>
                            <?php endif; ?>
                            <?php if (!empty($documento['justificativa_recusa'])): ?>
                                <?php if (!$correcaoSolicitada): ?>
                                    <p>Observação da secretaria: <?= htmlspecialchars($documento['justificativa_recusa']) ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($documento['caminho_arquivo']) && !$correcaoSolicitada): ?>
                                <?php if (!empty($documento['id_documento'])
                                    && preg_match('#^uploads/documentos/[a-f0-9]{32}\.(pdf|jpg|png)$#', $documento['caminho_arquivo'])
                                    && is_file(__DIR__ . '/../../' . $documento['caminho_arquivo'])): ?>
                                    <p class="document-file-uploaded">
                                        <strong>Arquivo enviado:</strong>
                                        <a href="../../controller/UsuarioController.php?acao=visualizar_documento&amp;id=<?= (int)$documento['id_documento'] ?>"
                                           target="_blank" rel="noopener noreferrer">Visualizar arquivo ↗</a>
                                    </p>
                                <?php else: ?>
                                    <p class="document-file-uploaded"><strong>Arquivo enviado:</strong> arquivo indisponível para visualização.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($documento['tipo_nome'] === 'Foto 3x4' && $documentoPrecisaEnvio): ?>
                                <p class="document-help">Foto vertical, proporção 3:4, mínimo de 300×400 px; JPG ou PNG até 5 MB.</p>
                            <?php endif; ?>
                            <?php if ($documentoPrecisaEnvio): ?>
                                <label class="document-upload-dropzone">
                                    <input type="file" name="documentos[<?= (int)$documento['id_tipo_documento'] ?>]" accept="<?= $documento['tipo_nome'] === 'Foto 3x4' ? 'image/jpeg,image/png' : '.pdf,.jpg,.jpeg,.png' ?>" required>
                                    <span class="document-upload-icon" aria-hidden="true">▱</span>
                                    <strong data-upload-title>Clique para selecionar <?= $documento['tipo_nome'] === 'Foto 3x4' ? 'uma nova foto 3x4' : 'o documento corrigido' ?></strong>
                                    <small>ou arraste o arquivo até aqui (<?= $documento['tipo_nome'] === 'Foto 3x4' ? 'JPG ou PNG' : 'JPG, PNG ou PDF' ?> · Máx. 5 MB)</small>
                                </label>
                                <p class="document-file-feedback" data-file-feedback role="status" aria-live="polite" hidden></p>
                            <?php elseif ($correcaoSolicitada && $documentoAprovado): ?>
                                <span class="correction-document-preserved">Documento aprovado — não precisa reenviar</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="workflow-form-actions">
                        <?php if (!$correcaoSolicitada): ?>
                            <button type="button" class="workflow-back-button" data-mostrar-dados>← Voltar</button>
                        <?php else: ?>
                            <span class="correction-resend-help">Após o reenvio, a secretaria analisará novamente em até 24 horas.</span>
                        <?php endif; ?>
                        <button type="submit" name="btn_enviar_documentos" class="btn btn-primary"><?= $correcaoSolicitada ? 'Reenviar documentos para análise →' : 'Avançar para Cursos →' ?></button>
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
