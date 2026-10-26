<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['perfil_id'] ?? 0) !== 3
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
    $tiposSolicitacao = $controller->listarTiposSolicitacaoAtivos();
    $solicitacoes = $controller->listarSolicitacoesAluno((int)$_SESSION['usuario_id']);
} catch (RuntimeException $e) {
    error_log('Erro ao carregar solicitações na área do aluno: ' . $e->getMessage());
    $tiposSolicitacao = [];
    $solicitacoes = [];
    $erroCarregamento = true;
}

$exibeMenuAdministrativo = true;
$classeCorpo = 'user-home-page service-requests-page';
include __DIR__ . '/../includes/topo.php';
?>
<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <div class="main-content service-requests-content">
            <header class="page-header service-requests-header">
                <div>
                    <h1>Solicitar documentos</h1>
                    <p>Envie seu pedido para a secretaria e acompanhe o andamento pelo protocolo.</p>
                </div>
            </header>

            <?php if ($erroCarregamento): ?>
                <p class="service-message service-message-error" role="alert">Não foi possível carregar as solicitações. Atualize a página para tentar novamente.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'ok'): ?>
                <p class="service-message service-message-success" role="status">Solicitação registrada. Guarde o protocolo para acompanhar o atendimento.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'erro'): ?>
                <p class="service-message service-message-error" role="alert">Não foi possível registrar sua solicitação. Confira os dados e tente novamente.</p>
            <?php endif; ?>

            <section class="service-request-panel">
                <div class="student-request-heading">
                    <div>
                        <span class="student-request-kicker">NOVO PEDIDO</span>
                        <h2>Solicitar documentos</h2>
                        <p>Escolha um documento e explique para que você precisa dele.</p>
                    </div>
                </div>
                <?php if ($tiposSolicitacao): ?>
                    <form class="service-request-form student-request-form" action="../../controller/UsuarioController.php" method="POST">
                        <fieldset class="student-request-types">
                            <legend>Tipo de documento <span aria-hidden="true">*</span></legend>
                            <div class="student-request-type-grid">
                            <?php foreach ($tiposSolicitacao as $tipo): ?>
                                <label class="student-request-type">
                                    <input type="radio" name="id_tipo_solicitacao" value="<?= (int)$tipo['id_tipo_solicitacao'] ?>" required>
                                    <span class="student-request-type-card">
                                        <strong><?= htmlspecialchars($tipo['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if (!empty($tipo['descricao'])): ?>
                                            <small><?= htmlspecialchars($tipo['descricao'], ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php else: ?>
                                            <small>Documento escolar oficial</small>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <div class="student-request-details">
                            <div class="student-request-details-heading">
                                <label for="descricao_solicitacao">Detalhes do pedido / observações</label>
                                <span><span data-request-character-count>0</span>/2000 caracteres</span>
                            </div>
                            <p class="student-request-document-tip" data-request-document-tip role="status" aria-live="polite" hidden></p>
                            <textarea id="descricao_solicitacao" name="descricao" rows="3" maxlength="2000" required data-request-description placeholder="Explique para que você precisa do documento ou acrescente uma informação importante."></textarea>
                        </div>
                        <button type="submit" name="btn_solicitar_documento" class="btn btn-primary student-request-submit">Enviar solicitação <span aria-hidden="true">→</span></button>
                    </form>
                <?php else: ?>
                    <p class="service-empty"><?= $erroCarregamento ? 'Não foi possível carregar a lista de documentos.' : 'Não há documentos disponíveis para solicitação no momento.' ?></p>
                <?php endif; ?>
            </section>

            <section class="service-request-list">
                <div class="student-requests-list-heading">
                    <div>
                        <h2>Minhas solicitações</h2>
                        <p>Acompanhe o andamento dos documentos que você pediu.</p>
                    </div>
                    <span><?= count($solicitacoes) ?> <?= count($solicitacoes) === 1 ? 'solicitação' : 'solicitações' ?></span>
                </div>
                <?php if ($solicitacoes): ?>
                    <div class="admin-table-wrap service-requests-table-wrap student-request-history-table-wrap">
                        <table class="service-requests-table student-request-history-table">
                            <thead>
                                <tr>
                                    <th scope="col">Protocolo</th>
                                    <th scope="col">Documento</th>
                                    <th scope="col">Data de envio</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitacoes as $solicitacao): ?>
                                    <?php
                                    $descricaoSolicitacao = (string)$solicitacao['descricao'];
                                    $descricaoSolicitacao = preg_replace(
                                        '/^Finalidade:\s*[^\r\n]*(?:\r?\n|$)/u',
                                        '',
                                        $descricaoSolicitacao,
                                        1
                                    ) ?? $descricaoSolicitacao;
                                    $statusOriginal = (string)$solicitacao['status'];
                                    $temArquivoResposta = !empty($solicitacao['arquivo_resposta']);
                                    $statusSolicitacao = $statusOriginal === 'Concluida' && $temArquivoResposta
                                        ? 'Pronto para download'
                                        : ([
                                            'Aberta' => 'Pendente',
                                            'Em Atendimento' => 'Em análise',
                                            'Concluida' => 'Concluída',
                                            'Indeferida' => 'Indeferida',
                                            'Cancelada' => 'Cancelada'
                                        ][$statusOriginal] ?? $statusOriginal);
                                    $classeStatus = strtolower(str_replace(' ', '-', $statusOriginal));
                                    $extensaoArquivo = strtolower(pathinfo((string)($solicitacao['arquivo_resposta'] ?? ''), PATHINFO_EXTENSION));
                                    $anoSolicitacao = date('Y', strtotime($solicitacao['data_abertura']));
                                    $protocoloExibido = '#' . $anoSolicitacao . '-' . str_pad(
                                        (string)$solicitacao['id_solicitacao'],
                                        4,
                                        '0',
                                        STR_PAD_LEFT
                                    );
                                    ?>
                                    <tr>
                                        <td class="service-request-table-protocol"><?= htmlspecialchars($protocoloExibido, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="student-request-history-document">
                                            <strong><?= htmlspecialchars($solicitacao['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if ($descricaoSolicitacao !== ''): ?>
                                                <small><?= htmlspecialchars($descricaoSolicitacao, ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitacao['data_abertura'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="service-request-status status-<?= htmlspecialchars($classeStatus, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusSolicitacao, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($solicitacao['resposta'])): ?>
                                                <small class="student-request-history-response"><?= htmlspecialchars($solicitacao['resposta'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="service-request-table-actions">
                                            <?php if ($temArquivoResposta): ?>
                                                <a class="student-request-download" href="../../controller/UsuarioController.php?acao=baixar_arquivo_solicitacao&amp;id=<?= (int)$solicitacao['id_solicitacao'] ?>">
                                                    <span aria-hidden="true">▣</span> Baixar <?= $extensaoArquivo === 'pdf' ? 'PDF' : 'documento' ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="student-request-download-pending">Aguardando</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="service-empty">Você ainda não enviou solicitações.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
</main>
</body>
</html>
