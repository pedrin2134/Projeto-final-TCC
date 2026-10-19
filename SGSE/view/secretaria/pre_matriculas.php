<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['perfil_id'] ?? 0) !== 2
    || !Autorizacao::usuarioTemPermissao('GERENCIAR_PRE_MATRICULAS')) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../../controller/UsuarioController.php';

$controller = new UsuarioController();
$preMatriculas = $controller->listarPreMatriculas();
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
            <button type="button" class="theme-toggle workflow-theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
        </header>

        <?php if (($_GET['erro'] ?? '') === 'documentos'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">A pré-matrícula só pode ser aprovada depois que todos os documentos obrigatórios forem deferidos.</p>
        <?php endif; ?>
        <?php if (($_GET['resultado'] ?? '') === 'ok'): ?>
            <p class="workflow-feedback workflow-feedback-success" role="status">Avaliação do documento registrada com sucesso.</p>
        <?php elseif (($_GET['resultado'] ?? '') === 'erro'): ?>
            <p class="workflow-feedback workflow-feedback-error" role="alert">Não foi possível registrar a avaliação do documento.</p>
        <?php endif; ?>

        <?php if ($preMatriculas): ?>
            <?php foreach ($preMatriculas as $preMatricula): ?>
                <div class="card applicant-card">
                    <div class="applicant-heading">
                        <?php if (!empty($preMatricula['aluno_foto'])): ?><img class="student-photo" src="../../<?= htmlspecialchars($preMatricula['aluno_foto']) ?>" alt="Foto de <?= htmlspecialchars($preMatricula['aluno_nome']) ?>"><?php else: ?><span class="avatar-badge"><?= strtoupper(substr($preMatricula['aluno_nome'], 0, 1)) ?></span><?php endif; ?>
                        <div><h3><?= htmlspecialchars($preMatricula['aluno_nome']) ?></h3><p><?= htmlspecialchars($preMatricula['aluno_email']) ?></p></div><span class="status-pill status-<?= strtolower(str_replace(' ', '-', $preMatricula['status'])) ?>"><?= htmlspecialchars($preMatricula['status']) ?></span>
                    </div>
                    <div class="personal-data">
                        <div><strong>Nome completo</strong><span><?= htmlspecialchars($preMatricula['aluno_nome']) ?></span></div>
                        <div><strong>CPF</strong><span><?= htmlspecialchars($preMatricula['aluno_cpf'] ?: 'Não informado') ?></span></div>
                        <div><strong>E-mail</strong><span><?= htmlspecialchars($preMatricula['aluno_email']) ?></span></div>
                        <div><strong>Telefone</strong><span><?= htmlspecialchars($preMatricula['aluno_telefone'] ?: 'Não informado') ?></span></div>
                        <div><strong>Data de nascimento</strong><span><?= htmlspecialchars($preMatricula['aluno_data_nascimento'] ?: 'Não informado') ?></span></div>
                        <div><strong>Status da conta</strong><span><?= htmlspecialchars($preMatricula['aluno_status']) ?></span></div>
                        <div><strong>Cargo</strong><span><?= htmlspecialchars($preMatricula['aluno_cargo'] ?: 'Não informado') ?></span></div>
                        <div><strong>Setor</strong><span><?= htmlspecialchars($preMatricula['aluno_setor'] ?: 'Não informado') ?></span></div>
                    </div>
                    <div class="application-summary"><span><strong>Curso</strong><?= htmlspecialchars($preMatricula['curso_nome'] . ' (' . $preMatricula['curso_sigla'] . ')') ?></span><span><strong>Inscrição</strong><?= htmlspecialchars($preMatricula['data_inscricao']) ?></span></div>

                    <section class="application-documents" aria-labelledby="documentos_<?= (int)$preMatricula['id_pre_matricula'] ?>">
                        <h4 id="documentos_<?= (int)$preMatricula['id_pre_matricula'] ?>">Documentos para análise</h4>
                        <?php $documentos = $controller->listarDocumentosAluno((int)$preMatricula['id_usuario']); ?>
                        <?php foreach ($documentos as $documento): ?>
                            <div class="review-row">
                                <div class="review-meta">
                                    <strong><?= htmlspecialchars($documento['tipo_nome']) ?></strong>
                                    <span class="status-pill status-<?= strtolower($documento['status'] ?? 'pendente') ?>"><?= htmlspecialchars($documento['status'] ?? 'Pendente') ?></span>
                                </div>
                                <?php if (!empty($documento['id_documento'])): ?>
                                    <a class="file-link" href="../../<?= htmlspecialchars($documento['caminho_arquivo']) ?>" target="_blank" rel="noopener noreferrer">Abrir documento ↗</a>
                                    <?php if (($documento['status'] ?? '') === 'Indeferido' && !empty($documento['justificativa_recusa'])): ?>
                                        <p class="document-rejection-note"><strong>Motivo:</strong> <?= htmlspecialchars($documento['justificativa_recusa']) ?></p>
                                    <?php endif; ?>
                                    <form action="../../controller/UsuarioController.php" method="POST">
                                        <input type="hidden" name="id_documento" value="<?= (int)$documento['id_documento'] ?>">
                                        <select name="status" aria-label="Avaliação de <?= htmlspecialchars($documento['tipo_nome']) ?>" required>
                                            <option value="Deferido" <?= ($documento['status'] ?? '') === 'Deferido' ? 'selected' : '' ?>>Deferir</option>
                                            <option value="Indeferido" <?= ($documento['status'] ?? '') === 'Indeferido' ? 'selected' : '' ?>>Indeferir</option>
                                        </select>
                                        <input type="text" name="justificativa_recusa" value="<?= htmlspecialchars($documento['justificativa_recusa'] ?? '') ?>" placeholder="Justificativa, se necessário" class="review-note" aria-label="Justificativa da avaliação">
                                        <button type="submit" name="btn_avaliar_documento" class="btn btn-primary">Registrar avaliação</button>
                                    </form>
                                <?php else: ?>
                                    <p class="missing-file">Documento ainda não enviado pelo aluno.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <form action="../../controller/UsuarioController.php" method="POST">
                        <input type="hidden" name="id_pre_matricula" value="<?= (int)$preMatricula['id_pre_matricula'] ?>">
                        <label for="status_<?= (int)$preMatricula['id_pre_matricula'] ?>">Decisão</label>
                        <select id="status_<?= (int)$preMatricula['id_pre_matricula'] ?>" name="status" required>
                            <option value="Aprovado">Aprovar</option>
                            <option value="Pendente de Documentacao">Solicitar documentação</option>
                            <option value="Indeferido">Indeferir</option>
                        </select>
                        <label for="observacao_<?= (int)$preMatricula['id_pre_matricula'] ?>">Observação</label>
                        <textarea id="observacao_<?= (int)$preMatricula['id_pre_matricula'] ?>" name="observacao" rows="3" placeholder="Informe uma observação, se necessário"></textarea>
                        <button type="submit" name="btn_analisar_pre_matricula" class="btn btn-primary">Registrar análise</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhuma pré-matrícula foi enviada.</p>
        <?php endif; ?>

    </div>
    </div>
</div>
</main>
</body>
</html>
