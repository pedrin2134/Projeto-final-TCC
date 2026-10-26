<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || !in_array((int)($_SESSION['perfil_id'] ?? 0), [1, 2], true)
    || !Autorizacao::usuarioTemPermissao('GERENCIAR_PRE_MATRICULAS')) {
    http_response_code(403);
    exit('Acesso não autorizado.');
}

require_once __DIR__ . '/../../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../../controller/UsuarioController.php';

$controller = new UsuarioController();
$preMatriculas = $controller->listarPreMatriculas();
$idPreMatriculaAberta = filter_var($_GET['abrir'] ?? null, FILTER_VALIDATE_INT);
$totalPreMatriculas = count($preMatriculas);
$totalAguardandoAnalise = count(array_filter($preMatriculas, static function (array $preMatricula): bool {
    return ($preMatricula['status'] ?? '') === 'Em Analise';
}));
$totalCorrecaoSolicitada = count(array_filter($preMatriculas, static function (array $preMatricula): bool {
    return ($preMatricula['status'] ?? '') === 'Pendente de Documentacao';
}));
$totalAprovadas = count(array_filter($preMatriculas, static function (array $preMatricula): bool {
    return ($preMatricula['status'] ?? '') === 'Aprovado';
}));
$exibeMenuAdministrativo = true;
$classeCorpo = 'workflow-page secretary-workflow';
include __DIR__ . '/../includes/topo.php';
?>
<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
    <div class="main-content workflow-shell wide-shell">
        <header class="page-header workflow-header">
            <h1>Análise de pré-matrículas</h1>
            <p>Revise as inscrições e decida os próximos passos de cada aluno.</p>
        </header>

        <?php if (($_GET['erro'] ?? '') === 'documentos'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">A pré-matrícula só pode ser aprovada depois que todos os documentos obrigatórios forem deferidos.</p>
        <?php elseif (($_GET['erro'] ?? '') === 'matricula'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Não foi possível efetivar a matrícula. Verifique se o aluno já possui matrícula neste período letivo e tente novamente.</p>
        <?php elseif (($_GET['erro'] ?? '') === 'analise'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Não foi possível registrar a análise da pré-matrícula. Tente novamente.</p>
        <?php elseif (($_GET['erro'] ?? '') === 'avaliacao'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Não foi possível salvar a avaliação dos documentos. Confira as observações dos documentos indeferidos e tente novamente.</p>
        <?php endif; ?>
        <?php if (($_GET['resultado'] ?? '') === 'ok'): ?>
            <p class="workflow-feedback workflow-feedback-success" role="status">Avaliação do documento registrada com sucesso.</p>
        <?php elseif (($_GET['resultado'] ?? '') === 'erro'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Não foi possível registrar a avaliação do documento.</p>
        <?php endif; ?>

        <?php if ($preMatriculas): ?>
            <section class="secretary-application-stats" aria-label="Resumo das pré-matrículas">
                <article class="secretary-stat-card">
                    <span>Total de inscritos</span>
                    <strong><?= $totalPreMatriculas ?></strong>
                </article>
                <article class="secretary-stat-card is-waiting">
                    <span>Aguardando análise</span>
                    <strong><?= $totalAguardandoAnalise ?></strong>
                </article>
                <article class="secretary-stat-card is-correction">
                    <span>Correção solicitada</span>
                    <strong><?= $totalCorrecaoSolicitada ?></strong>
                </article>
                <article class="secretary-stat-card is-approved">
                    <span>Aprovadas</span>
                    <strong><?= $totalAprovadas ?></strong>
                </article>
            </section>

            <section class="secretary-applications-list" aria-labelledby="secretary-applications-title">
                <div class="secretary-applications-heading">
                    <h2 id="secretary-applications-title">Candidatos cadastrados</h2>
                    <span><?= $totalPreMatriculas ?> <?= $totalPreMatriculas === 1 ? 'inscrição' : 'inscrições' ?></span>
                </div>
                <div class="secretary-applications-columns" aria-hidden="true">
                    <span>Candidato</span>
                    <span>Curso pretendido</span>
                    <span>Data de inscrição</span>
                    <span>Status</span>
                    <span>Documentos</span>
                    <span>Ação</span>
                </div>
            <?php foreach ($preMatriculas as $preMatricula): ?>
                <?php
                $documentos = $controller->listarDocumentosAluno((int)$preMatricula['id_usuario']);
                $totalDocumentosEnviados = count(array_filter($documentos, static function (array $documento): bool {
                    return !empty($documento['id_documento']);
                }));
                $totalDocumentosPendentes = count(array_filter($documentos, static function (array $documento): bool {
                    return empty($documento['id_documento'])
                        || in_array($documento['status'] ?? 'Pendente', ['Pendente', 'Em Validacao'], true);
                }));
                $statusPreMatricula = (string)$preMatricula['status'];
                $statusPreMatriculaFormatado = [
                    'Em Analise' => 'Em análise',
                    'Pendente de Documentacao' => 'Correção solicitada',
                    'Aprovado' => 'Aprovada',
                    'Indeferido' => 'Indeferida'
                ][$statusPreMatricula] ?? $statusPreMatricula;
                $dataInscricao = !empty($preMatricula['data_inscricao'])
                    ? date('d/m/Y H:i', strtotime($preMatricula['data_inscricao']))
                    : 'Não informada';
                $preMatriculaAprovada = $statusPreMatricula === 'Aprovado';
                ?>
                <article class="card applicant-card applicant-review-card <?= $statusPreMatricula === 'Pendente de Documentacao' ? 'is-correction' : ($statusPreMatricula === 'Aprovado' ? 'is-approved' : ($statusPreMatricula === 'Indeferido' ? 'is-rejected' : 'is-waiting')) ?>"
                         id="pre-matricula-<?= (int)$preMatricula['id_pre_matricula'] ?>">
                    <button type="button" class="applicant-review-summary"
                            data-open-review="analise-<?= (int)$preMatricula['id_pre_matricula'] ?>"
                            aria-haspopup="dialog">
                        <span class="applicant-summary-student-group">
                            <?php if (!empty($preMatricula['aluno_foto'])): ?>
                                <img class="student-photo" src="../../<?= htmlspecialchars($preMatricula['aluno_foto'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else: ?>
                                <span class="avatar-badge" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr((string)$preMatricula['aluno_nome'], 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <span class="applicant-summary-student">
                                <strong><?= htmlspecialchars($preMatricula['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($preMatricula['aluno_email'], ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                        </span>
                        <span class="applicant-summary-course"><?= htmlspecialchars($preMatricula['curso_nome'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="applicant-summary-date"><?= htmlspecialchars($dataInscricao, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="applicant-summary-status status-pill status-<?= strtolower(str_replace(' ', '-', $statusPreMatricula)) ?>">
                            <?= htmlspecialchars($statusPreMatriculaFormatado, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="applicant-document-count <?= $totalDocumentosPendentes > 0 ? 'has-pending' : '' ?>">
                            <?= $totalDocumentosEnviados ?>/<?= count($documentos) ?> documentos
                        </span>
                        <span class="applicant-open-action"><?= $preMatriculaAprovada ? 'Ver detalhes' : 'Analisar' ?></span>
                    </button>

                    <dialog class="applicant-review-dialog"
                            id="analise-<?= (int)$preMatricula['id_pre_matricula'] ?>"
                            aria-labelledby="titulo-analise-<?= (int)$preMatricula['id_pre_matricula'] ?>"
                            <?= $idPreMatriculaAberta === (int)$preMatricula['id_pre_matricula'] ? 'data-open-on-load' : '' ?>>
                        <?php if ($preMatriculaAprovada): ?>
                        <div class="review-dialog-form review-dialog-readonly">
                        <?php else: ?>
                        <form action="../../controller/UsuarioController.php" method="POST" class="review-dialog-form" data-review-form>
                            <input type="hidden" name="id_pre_matricula" value="<?= (int)$preMatricula['id_pre_matricula'] ?>">
                        <?php endif; ?>
                            <header class="review-dialog-header">
                                <div>
                                    <h2 id="titulo-analise-<?= (int)$preMatricula['id_pre_matricula'] ?>"><?= $preMatriculaAprovada ? 'Detalhes da pré-matrícula' : 'Análise de pré-matrícula' ?> - <?= htmlspecialchars($preMatricula['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p><?= htmlspecialchars($preMatricula['curso_nome'], ENT_QUOTES, 'UTF-8') ?> <span class="review-registration-code">#<?= (int)$preMatricula['id_pre_matricula'] ?></span></p>
                                </div>
                                <button type="button" class="review-dialog-close" data-close-review aria-label="<?= $preMatriculaAprovada ? 'Fechar detalhes' : 'Fechar análise' ?>">×</button>
                            </header>

                            <div class="review-dialog-body">
                                <div class="review-student-facts">
                                    <div><strong>CPF</strong><span><?= htmlspecialchars($preMatricula['aluno_cpf'] ?: 'Não informado', ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div><strong>DATA DE NASCIMENTO</strong><span><?= htmlspecialchars($preMatricula['aluno_data_nascimento'] ?: 'Não informado', ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div><strong>TELEFONE / WHATSAPP</strong><span><?= htmlspecialchars($preMatricula['aluno_telefone'] ?: 'Não informado', ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div><strong>E-MAIL</strong><span><?= htmlspecialchars($preMatricula['aluno_email'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                </div>

                                <?php if ($preMatriculaAprovada): ?>
                                    <section class="approved-enrollment-details" aria-label="Dados da matrícula efetivada">
                                        <h3>Matrícula efetivada</h3>
                                        <div class="approved-enrollment-grid">
                                            <div><strong>Status</strong><span><?= htmlspecialchars($preMatricula['status_matricula'] ?? 'Aprovada', ENT_QUOTES, 'UTF-8') ?></span></div>
                                            <div><strong>Código de matrícula</strong><span><?= htmlspecialchars($preMatricula['codigo_matricula'] ?? 'Não localizado', ENT_QUOTES, 'UTF-8') ?></span></div>
                                            <div><strong>Curso</strong><span><?= htmlspecialchars($preMatricula['curso_nome'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                            <div><strong>Período letivo</strong><span><?= htmlspecialchars($preMatricula['periodo_letivo'] ?? 'Não informado', ENT_QUOTES, 'UTF-8') ?></span></div>
                                            <div><strong>Data da aprovação</strong><span><?= !empty($preMatricula['data_analise']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($preMatricula['data_analise'])), ENT_QUOTES, 'UTF-8') : 'Não informada' ?></span></div>
                                            <div><strong>Data da matrícula</strong><span><?= !empty($preMatricula['data_matricula']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($preMatricula['data_matricula'])), ENT_QUOTES, 'UTF-8') : 'Não informada' ?></span></div>
                                        </div>
                                        <?php if (!empty($preMatricula['observacao'])): ?>
                                            <p><strong>Parecer da Secretaria:</strong> <?= nl2br(htmlspecialchars($preMatricula['observacao'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        <?php endif; ?>
                                    </section>
                                <?php endif; ?>

                                <section class="application-documents review-dialog-documents" aria-labelledby="documentos_<?= (int)$preMatricula['id_pre_matricula'] ?>">
                                    <div class="application-documents-heading">
                                        <div>
                                            <h3 id="documentos_<?= (int)$preMatricula['id_pre_matricula'] ?>">Conferência de documentos</h3>
                                            <p><?= $preMatriculaAprovada ? 'Documentos e avaliações registrados nesta inscrição.' : 'Verifique individualmente cada arquivo enviado.' ?></p>
                                        </div>
                                        <span class="application-documents-count"><?= $totalDocumentosEnviados ?> de <?= count($documentos) ?> enviados</span>
                                    </div>
                                    <div class="review-table-scroll">
                                        <table class="review-documents-table">
                                            <thead>
                                                <tr><th>Documento</th><th>Arquivo</th><th><?= $preMatriculaAprovada ? 'Resultado' : 'Avaliação' ?></th><th>Observação / inconsistência</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($documentos as $indiceDocumento => $documento): ?>
                                                    <?php
                                                    $statusDocumento = (string)($documento['status'] ?? 'Pendente');
                                                    $statusDocumentoFormatado = [
                                                        'Pendente' => 'Pendente',
                                                        'Em Validacao' => 'Em validação',
                                                        'Deferido' => 'Deferido',
                                                        'Indeferido' => 'Indeferido'
                                                    ][$statusDocumento] ?? $statusDocumento;
                                                    $arquivoDocumentoValido = !empty($documento['id_documento'])
                                                        && is_string($documento['caminho_arquivo'] ?? null)
                                                        && preg_match('#^uploads/documentos/[a-f0-9]{32}\.(pdf|jpg|png)$#', $documento['caminho_arquivo'])
                                                        && is_file(__DIR__ . '/../../' . $documento['caminho_arquivo']);
                                                    $idDocumento = (int)($documento['id_documento'] ?? 0);
                                                    ?>
                                                    <tr class="<?= $statusDocumento === 'Indeferido' ? 'is-rejected' : ($statusDocumento === 'Deferido' ? 'is-approved' : '') ?>">
                                                        <td><strong><?= ($indiceDocumento + 1) . '. ' . htmlspecialchars($documento['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                                        <td>
                                                            <?php if ($arquivoDocumentoValido): ?>
                                                                <a class="file-link" href="../../<?= htmlspecialchars($documento['caminho_arquivo'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Visualizar ↗</a>
                                                            <?php else: ?>
                                                                <span class="missing-file"><?= !empty($documento['id_documento']) ? 'Arquivo indisponível' : 'Não enviado' ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($arquivoDocumentoValido): ?>
                                                                <?php if ($preMatriculaAprovada): ?>
                                                                    <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $statusDocumento)) ?>"><?= htmlspecialchars($statusDocumentoFormatado, ENT_QUOTES, 'UTF-8') ?></span>
                                                                <?php else: ?>
                                                                    <label class="visually-hidden" for="avaliacao_<?= $idDocumento ?>">Avaliação de <?= htmlspecialchars($documento['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></label>
                                                                    <select id="avaliacao_<?= $idDocumento ?>" name="avaliacoes[<?= $idDocumento ?>]" required data-document-status>
                                                                        <option value="" <?= !in_array($statusDocumento, ['Deferido', 'Indeferido'], true) ? 'selected' : '' ?>>Selecione</option>
                                                                        <option value="Deferido" <?= $statusDocumento === 'Deferido' ? 'selected' : '' ?>>Deferido</option>
                                                                        <option value="Indeferido" <?= $statusDocumento === 'Indeferido' ? 'selected' : '' ?>>Indeferido</option>
                                                                    </select>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="status-pill status-pendente"><?= htmlspecialchars($statusDocumentoFormatado, ENT_QUOTES, 'UTF-8') ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($arquivoDocumentoValido): ?>
                                                                <?php if ($preMatriculaAprovada): ?>
                                                                    <?= !empty($documento['justificativa_recusa']) ? nl2br(htmlspecialchars($documento['justificativa_recusa'], ENT_QUOTES, 'UTF-8')) : '—' ?>
                                                                <?php else: ?>
                                                                    <label class="visually-hidden" for="motivo_<?= $idDocumento ?>">Observação sobre <?= htmlspecialchars($documento['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></label>
                                                                    <input type="text" id="motivo_<?= $idDocumento ?>" name="justificativas[<?= $idDocumento ?>]" value="<?= htmlspecialchars($documento['justificativa_recusa'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Observação ou motivo da inconsistência" data-document-note>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="review-table-placeholder">Envie este documento para análise.</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </section>

                                <?php if (!$preMatriculaAprovada): ?>
                                <section class="review-final-decision" aria-label="Decisão final da Secretaria">
                                    <div class="review-final-heading">
                                        <h3>Decisão final da Secretaria</h3>
                                        <span data-review-recommendation>Status recomendado: aguarde a análise dos documentos</span>
                                    </div>
                                    <div class="review-final-fields">
                                        <div>
                                            <label for="decisao_<?= (int)$preMatricula['id_pre_matricula'] ?>">Resultado da inscrição</label>
                                            <select id="decisao_<?= (int)$preMatricula['id_pre_matricula'] ?>" name="status" required data-final-status>
                                                <option value="">Selecione a decisão</option>
                                                <option value="Aprovado">Aprovar matrícula</option>
                                                <option value="Pendente de Documentacao">Solicitar correção de documentos</option>
                                                <option value="Indeferido">Indeferir inscrição</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="parecer_<?= (int)$preMatricula['id_pre_matricula'] ?>">Parecer / mensagem enviada ao aluno</label>
                                            <textarea id="parecer_<?= (int)$preMatricula['id_pre_matricula'] ?>" name="observacao" rows="2" placeholder="Explique a decisão e as correções necessárias"></textarea>
                                        </div>
                                    </div>
                                </section>
                                <?php endif; ?>
                            </div>

                        <?php if ($preMatriculaAprovada): ?>
                            <footer class="review-dialog-actions">
                                <button type="button" class="review-cancel-button" data-close-review>Fechar detalhes</button>
                            </footer>
                        </div>
                        <?php else: ?>
                            <footer class="review-dialog-actions">
                                <button type="button" class="review-cancel-button" data-close-review>Cancelar</button>
                                <button type="submit" name="btn_registrar_analise_completa" class="btn btn-primary">Registrar e enviar avaliação</button>
                            </footer>
                        </form>
                        <?php endif; ?>
                    </dialog>
                </article>
            <?php endforeach; ?>
            </section>
        <?php else: ?>
            <p>Nenhuma pré-matrícula foi enviada.</p>
        <?php endif; ?>

    </div>
    </div>
</div>
</main>
</body>
</html>
