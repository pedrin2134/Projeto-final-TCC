<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || !in_array((int)($_SESSION['perfil_id'] ?? 0), [1, 2], true)
    || !Autorizacao::usuarioTemPermissao('GERENCIAR_SOLICITACOES')) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../../controller/UsuarioController.php';

$controller = new UsuarioController();
$erroCarregamento = false;
try {
    $solicitacoes = $controller->listarSolicitacoesAtendimento();
} catch (RuntimeException $e) {
    error_log('Erro ao carregar fila de solicitações: ' . $e->getMessage());
    $solicitacoes = [];
    $erroCarregamento = true;
}

$buscaSolicitacoes = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$statusFiltro = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$statusPermitidos = ['', 'Aberta', 'Em Atendimento', 'Concluida', 'Indeferida', 'Cancelada'];
if (!in_array($statusFiltro, $statusPermitidos, true)) {
    $statusFiltro = '';
}
$tipoFiltro = filter_var($_GET['tipo'] ?? 0, FILTER_VALIDATE_INT);
$tipoFiltro = $tipoFiltro !== false && $tipoFiltro > 0 ? $tipoFiltro : 0;
$tiposSolicitacao = [];
foreach ($solicitacoes as $solicitacao) {
    $tiposSolicitacao[(int)$solicitacao['id_tipo_solicitacao']] = (string)$solicitacao['tipo_nome'];
}
asort($tiposSolicitacao, SORT_NATURAL | SORT_FLAG_CASE);
$solicitacoesExibidas = array_values(array_filter(
    $solicitacoes,
    static function (array $solicitacao) use ($buscaSolicitacoes, $statusFiltro, $tipoFiltro): bool {
        if ($statusFiltro !== '' && $solicitacao['status'] !== $statusFiltro) {
            return false;
        }
        if ($tipoFiltro && (int)$solicitacao['id_tipo_solicitacao'] !== $tipoFiltro) {
            return false;
        }
        if ($buscaSolicitacoes === '') {
            return true;
        }

        foreach ([
            $solicitacao['numero_protocolo'] ?? '',
            $solicitacao['aluno_nome'] ?? '',
            $solicitacao['aluno_cpf'] ?? '',
            $solicitacao['aluno_email'] ?? '',
            $solicitacao['tipo_nome'] ?? '',
            $solicitacao['curso_nome'] ?? '',
            $solicitacao['codigo_matricula'] ?? ''
        ] as $campoBusca) {
            if (mb_stripos((string)$campoBusca, $buscaSolicitacoes, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }
));

$exibeMenuAdministrativo = true;
$classeCorpo = 'workflow-page service-requests-page';
include __DIR__ . '/../includes/topo.php';
?>
<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <div class="main-content admin-list-page service-requests-content">
            <header class="admin-page-header service-requests-header">
                <div>
                    <h1>Central de Requerimentos e Documentos</h1>
                    <p>Gerencie, acompanhe e responda às solicitações de documentos dos alunos.</p>
                </div>
            </header>

            <?php if ($erroCarregamento): ?>
                <p class="service-message service-message-error" role="alert">Não foi possível carregar a fila de solicitações.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'ok'): ?>
                <p class="service-message service-message-success" role="status">Solicitação atualizada com sucesso.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'erro'): ?>
                <p class="service-message service-message-error" role="alert">Não foi possível atualizar a solicitação. Ela pode já estar encerrada ou os dados são inválidos.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'arquivo_invalido'): ?>
                <p class="service-message service-message-error" role="alert">O anexo deve ser PDF, JPG ou PNG e ter no máximo 10 MB.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'arquivo_obrigatorio'): ?>
                <p class="service-message service-message-error" role="alert">Anexe o documento solicitado antes de concluir o atendimento.</p>
            <?php endif; ?>

            <section class="admin-list-panel service-requests-panel" aria-label="Fila de solicitações">
                <form class="admin-list-filters service-requests-filters" action="solicitacoes.php" method="GET" role="search">
                    <label class="admin-search-field">
                        <span class="visually-hidden">Buscar por aluno, CPF ou protocolo</span>
                        <input type="search" name="busca" placeholder="Buscar por aluno, CPF ou protocolo..." value="<?= htmlspecialchars($buscaSolicitacoes, ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span class="visually-hidden">Filtrar por status</span>
                        <select name="status">
                            <option value="">Todos os status</option>
                            <?php foreach (['Aberta' => 'Pendente', 'Em Atendimento' => 'Em análise', 'Concluida' => 'Concluída', 'Indeferida' => 'Indeferida (Pendência)', 'Cancelada' => 'Cancelada'] as $valorStatus => $rotuloStatus): ?>
                                <option value="<?= htmlspecialchars($valorStatus, ENT_QUOTES, 'UTF-8') ?>" <?= $statusFiltro === $valorStatus ? 'selected' : '' ?>><?= htmlspecialchars($rotuloStatus, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span class="visually-hidden">Filtrar por documento</span>
                        <select name="tipo">
                            <option value="0">Todos os documentos</option>
                            <?php foreach ($tiposSolicitacao as $idTipo => $nomeTipo): ?>
                                <option value="<?= $idTipo ?>" <?= $tipoFiltro === $idTipo ? 'selected' : '' ?>><?= htmlspecialchars($nomeTipo, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="admin-primary-button">Filtrar</button>
                    <?php if ($buscaSolicitacoes !== '' || $statusFiltro !== '' || $tipoFiltro): ?>
                        <a class="admin-filter-reset" href="solicitacoes.php">Limpar</a>
                    <?php endif; ?>
                    <span class="admin-list-count">Exibindo <?= count($solicitacoesExibidas) ?> de <?= count($solicitacoes) ?></span>
                </form>

                <?php if ($solicitacoesExibidas): ?>
                    <div class="admin-table-wrap service-requests-table-wrap">
                        <table class="service-requests-table">
                            <thead>
                                <tr>
                                    <th scope="col">Protocolo</th>
                                    <th scope="col">Aluno / Curso</th>
                                    <th scope="col">Documento</th>
                                    <th scope="col">Data</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitacoesExibidas as $solicitacao): ?>
                                    <?php
                                    $idSolicitacao = (int)$solicitacao['id_solicitacao'];
                                    $statusSolicitacao = (string)$solicitacao['status'];
                                    $statusExibido = [
                                        'Aberta' => 'Pendente',
                                        'Em Atendimento' => 'Em análise',
                                        'Concluida' => 'Concluída',
                                        'Indeferida' => 'Indeferido',
                                        'Cancelada' => 'Cancelada'
                                    ][$statusSolicitacao] ?? $statusSolicitacao;
                                    $temAtendimentoAberto = in_array($statusSolicitacao, ['Aberta', 'Em Atendimento'], true);
                                    $nomeDialog = 'atendimento_' . $idSolicitacao;
                                    ?>
                                    <tr>
                                        <td class="service-request-table-protocol"><?= htmlspecialchars($solicitacao['numero_protocolo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <strong class="service-request-table-student"><?= htmlspecialchars($solicitacao['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="service-request-table-meta">
                                                <?= htmlspecialchars($solicitacao['curso_nome'] ?: 'Curso não informado', ENT_QUOTES, 'UTF-8') ?>
                                                <?php if (!empty($solicitacao['codigo_matricula'])): ?> · Matrícula: <?= htmlspecialchars($solicitacao['codigo_matricula'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                            </small>
                                        </td>
                                        <td><?= htmlspecialchars($solicitacao['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitacao['data_abertura'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="service-request-status status-<?= strtolower(str_replace(' ', '-', $statusSolicitacao)) ?>"><?= htmlspecialchars($statusExibido, ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="service-request-table-actions">
                                            <button type="button" class="service-request-open-button <?= $temAtendimentoAberto ? '' : 'is-secondary' ?>" data-open-request="<?= $nomeDialog ?>">
                                                <?= $temAtendimentoAberto ? 'Atender' : 'Ver Detalhes' ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="service-empty"><?= $solicitacoes ? 'Nenhuma solicitação corresponde aos filtros.' : 'Não há solicitações registradas.' ?></p>
                <?php endif; ?>
            </section>
            <?php foreach ($solicitacoesExibidas as $solicitacao): ?>
                <?php
                $idSolicitacao = (int)$solicitacao['id_solicitacao'];
                $statusSolicitacao = (string)$solicitacao['status'];
                $temAtendimentoAberto = in_array($statusSolicitacao, ['Aberta', 'Em Atendimento'], true);
                $nomeDialog = 'atendimento_' . $idSolicitacao;
                ?>
                <dialog class="service-request-dialog" id="<?= $nomeDialog ?>" aria-labelledby="<?= $nomeDialog ?>_title" data-request-dialog>
                    <header class="service-request-dialog-header">
                        <div>
                            <h2 id="<?= $nomeDialog ?>_title"><?= $temAtendimentoAberto ? 'Atendimento de Requerimento' : 'Detalhes da Solicitação' ?></h2>
                            <p>Protocolo: <?= htmlspecialchars($solicitacao['numero_protocolo'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <button type="button" class="service-request-dialog-close" data-close-request aria-label="Fechar">×</button>
                    </header>
                    <div class="service-request-dialog-body">
                        <div class="service-request-dialog-student">
                            <strong><?= htmlspecialchars($solicitacao['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span>Solicitação: <?= htmlspecialchars($solicitacao['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars($solicitacao['aluno_email'], ENT_QUOTES, 'UTF-8') ?> · CPF <?= htmlspecialchars($solicitacao['aluno_cpf'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars($solicitacao['curso_nome'] ?: 'Curso não informado', ENT_QUOTES, 'UTF-8') ?><?= !empty($solicitacao['codigo_matricula']) ? ' · Matrícula: ' . htmlspecialchars($solicitacao['codigo_matricula'], ENT_QUOTES, 'UTF-8') : '' ?></span>
                        </div>
                        <div class="service-request-message">
                            <strong>Pedido do aluno</strong>
                            <p><?= nl2br(htmlspecialchars($solicitacao['descricao'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <small>Solicitado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitacao['data_abertura'])), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <?php if ($temAtendimentoAberto): ?>
                            <?php if (!empty($solicitacao['arquivo_resposta'])): ?>
                                <p class="service-request-file"><a href="../../controller/UsuarioController.php?acao=baixar_arquivo_solicitacao&amp;id=<?= $idSolicitacao ?>">Baixar documento já enviado</a></p>
                            <?php endif; ?>
                            <form class="service-request-dialog-form" action="../../controller/UsuarioController.php" method="POST" enctype="multipart/form-data" data-request-form>
                                <input type="hidden" name="id_solicitacao" value="<?= $idSolicitacao ?>">
                                <fieldset class="service-request-quick-replies">
                                    <legend>Respostas rápidas em 1 clique</legend>
                                    <button type="button" data-response-template="deferido">Deferido e Anexado</button>
                                    <button type="button" data-response-template="indeferido">Indeferido (Pendência)</button>
                                </fieldset>
                                <div class="service-request-dialog-field">
                                    <label for="resposta_<?= $idSolicitacao ?>">Mensagem / Observação para o aluno</label>
                                    <textarea id="resposta_<?= $idSolicitacao ?>" name="resposta" rows="3" maxlength="2000" required placeholder="Informe o resultado do atendimento ou a pendência"><?= htmlspecialchars($solicitacao['resposta'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="service-request-dialog-field">
                                    <label for="arquivo_resposta_<?= $idSolicitacao ?>">Anexar documento solicitado</label>
                                    <input id="arquivo_resposta_<?= $idSolicitacao ?>" type="file" name="arquivo_resposta" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required data-request-file>
                                    <small>PDF, JPG ou PNG · até 10 MB. O aluno poderá baixar este arquivo.</small>
                                </div>
                                <div class="service-request-preview" data-request-preview>
                                    <strong>Prévia do documento para o aluno</strong>
                                    <p>Selecione um arquivo para conferir antes de enviar.</p>
                                    <img alt="Prévia do documento selecionado" hidden data-request-preview-image>
                                    <iframe title="Prévia do documento PDF selecionado" hidden data-request-preview-pdf></iframe>
                                </div>
                                <footer class="service-request-dialog-actions">
                                    <button type="button" class="service-request-cancel" data-close-request>Cancelar</button>
                                    <button type="submit" name="btn_atualizar_solicitacao" value="indeferir" class="service-request-reject" data-submit-action="indeferir">Indeferir</button>
                                    <button type="submit" name="btn_atualizar_solicitacao" value="concluir" class="service-request-complete" data-submit-action="concluir">Concluir e Emitir Documento</button>
                                </footer>
                            </form>
                        <?php else: ?>
                            <?php
                            $statusAluno = (string)($solicitacao['aluno_status'] ?? '—');
                            $statusMatricula = (string)($solicitacao['matricula_status'] ?? '');
                            $situacaoAluno = $statusAluno === 'Ativo' && $statusMatricula === 'Ativo'
                                ? 'Ativo / Frequente'
                                : ($statusAluno === 'Inativo' ? 'Conta inativa' : ($statusMatricula !== '' ? $statusMatricula : $statusAluno));
                            $statusMatriculaExibido = [
                                'Ativo' => 'Ativa',
                                'Trancado' => 'Trancada',
                                'Cancelado' => 'Cancelada',
                                'Concluido' => 'Concluída'
                            ][$statusMatricula] ?? 'Sem matrícula';
                            ?>
                            <div class="service-request-dossier">
                                <section class="service-request-dossier-section">
                                    <h3>1. Informações cadastrais do aluno</h3>
                                    <div class="service-request-dossier-grid">
                                        <div class="service-request-dossier-field">
                                            <span>Nome completo</span>
                                            <strong><?= htmlspecialchars($solicitacao['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="service-request-dossier-field">
                                            <span>Curso técnico</span>
                                            <strong><?= htmlspecialchars($solicitacao['curso_nome'] ?: 'Não informado', ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="service-request-dossier-field">
                                            <span>Matrícula</span>
                                            <strong><?= htmlspecialchars($solicitacao['codigo_matricula'] ?: 'Sem matrícula', ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="service-request-dossier-field">
                                            <span>Situação do aluno</span>
                                            <strong class="service-request-dossier-status"><?= htmlspecialchars($situacaoAluno, ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                    </div>
                                </section>
                                <section class="service-request-dossier-section">
                                    <h3>2. Dados da solicitação</h3>
                                    <div class="service-request-dossier-grid">
                                        <div class="service-request-dossier-field">
                                            <span>Documento requerido</span>
                                            <strong><?= htmlspecialchars($solicitacao['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="service-request-dossier-field">
                                            <span>Data de abertura</span>
                                            <strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitacao['data_abertura'])), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="service-request-dossier-field">
                                            <span>Status atual</span>
                                            <strong class="service-request-dossier-status status-<?= strtolower(str_replace(' ', '-', $statusSolicitacao)) ?>"><?= htmlspecialchars($statusExibido, ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="service-request-dossier-field">
                                            <span>Situação da matrícula</span>
                                            <strong><?= htmlspecialchars($statusMatriculaExibido, ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="service-request-dossier-field">
                                            <span>Atendido por</span>
                                            <strong><?= htmlspecialchars($solicitacao['atendente_nome'] ?: 'Não informado', ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                    </div>
                                    <div class="service-request-dossier-description">
                                        <strong>Descrição do pedido</strong>
                                        <p><?= nl2br(htmlspecialchars($solicitacao['descricao'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    </div>
                                    <?php if (!empty($solicitacao['resposta'])): ?>
                                        <div class="service-request-dossier-description">
                                            <strong>Resposta registrada</strong>
                                            <p><?= nl2br(htmlspecialchars($solicitacao['resposta'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </section>
                                <section class="service-request-dossier-section">
                                    <h3>3. Histórico de auditoria</h3>
                                    <ul class="service-request-audit-history">
                                        <li><strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitacao['data_abertura'])), ENT_QUOTES, 'UTF-8') ?></strong> — Requerimento registrado via Portal do Aluno.</li>
                                        <?php if (!empty($solicitacao['data_conclusao'])): ?>
                                            <li><strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitacao['data_conclusao'])), ENT_QUOTES, 'UTF-8') ?></strong> — Solicitação <?= htmlspecialchars(mb_strtolower($statusExibido, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?><?= !empty($solicitacao['atendente_nome']) ? ' por ' . htmlspecialchars($solicitacao['atendente_nome'], ENT_QUOTES, 'UTF-8') : '' ?>.</li>
                                        <?php endif; ?>
                                    </ul>
                                </section>
                            </div>
                            <footer class="service-request-dialog-actions service-request-dossier-actions">
                                <button type="button" class="service-request-print" data-print-request>Imprimir</button>
                                <?php if (!empty($solicitacao['arquivo_resposta'])): ?>
                                    <a class="service-request-download" href="../../controller/UsuarioController.php?acao=baixar_arquivo_solicitacao&amp;id=<?= $idSolicitacao ?>">Baixar documento enviado</a>
                                <?php endif; ?>
                                <button type="button" class="service-request-cancel" data-close-request>Fechar</button>
                            </footer>
                        <?php endif; ?>
                    </div>
                </dialog>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</main>
</body>
</html>
