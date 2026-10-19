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
                <button type="button" class="theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
            </header>

            <?php if ($erroCarregamento): ?>
                <p class="service-message service-message-error" role="alert">Não foi possível carregar as solicitações. Atualize a página para tentar novamente.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'ok'): ?>
                <p class="service-message service-message-success" role="status">Solicitação registrada. Guarde o protocolo para acompanhar o atendimento.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'erro'): ?>
                <p class="service-message service-message-error" role="alert">Não foi possível registrar sua solicitação. Confira os dados e tente novamente.</p>
            <?php endif; ?>

            <section class="service-request-panel">
                <h2>Novo pedido</h2>
                <p>Escolha o documento e explique qualquer informação importante para o atendimento.</p>
                <?php if ($tiposSolicitacao): ?>
                    <form class="service-request-form" action="../../controller/UsuarioController.php" method="POST">
                        <label for="tipo_solicitacao">Tipo de solicitação</label>
                        <select id="tipo_solicitacao" name="id_tipo_solicitacao" required>
                            <option value="">Selecione uma opção</option>
                            <?php foreach ($tiposSolicitacao as $tipo): ?>
                                <option value="<?= (int)$tipo['id_tipo_solicitacao'] ?>"><?= htmlspecialchars($tipo['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="descricao_solicitacao">Detalhes do pedido</label>
                        <textarea id="descricao_solicitacao" name="descricao" rows="4" maxlength="2000" required placeholder="Informe, por exemplo, a finalidade do documento ou a turma/curso relacionado."></textarea>
                        <button type="submit" name="btn_solicitar_documento" class="btn btn-primary">Enviar solicitação</button>
                    </form>
                <?php else: ?>
                    <p class="service-empty">Não há tipos de solicitação disponíveis no momento.</p>
                <?php endif; ?>
            </section>

            <section class="service-request-list">
                <h2>Minhas solicitações</h2>
                <?php if ($solicitacoes): ?>
                    <?php foreach ($solicitacoes as $solicitacao): ?>
                        <article class="service-request-card">
                            <div class="service-request-heading">
                                <div>
                                    <span class="service-request-protocol">Protocolo <?= htmlspecialchars($solicitacao['numero_protocolo'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <h3><?= htmlspecialchars($solicitacao['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                                </div>
                                <span class="service-request-status status-<?= strtolower(str_replace(' ', '-', $solicitacao['status'])) ?>"><?= htmlspecialchars(str_replace('Concluida', 'Concluída', $solicitacao['status']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p><?= nl2br(htmlspecialchars($solicitacao['descricao'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <small>Solicitada em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitacao['data_abertura'])), ENT_QUOTES, 'UTF-8') ?></small>
                            <?php if (!empty($solicitacao['resposta'])): ?>
                                <div class="service-request-response"><strong>Resposta da secretaria:</strong><p><?= nl2br(htmlspecialchars($solicitacao['resposta'], ENT_QUOTES, 'UTF-8')) ?></p></div>
                            <?php endif; ?>
                            <?php if (!empty($solicitacao['arquivo_resposta'])): ?>
                                <p class="service-request-file"><a href="../../controller/UsuarioController.php?acao=baixar_arquivo_solicitacao&amp;id=<?= (int)$solicitacao['id_solicitacao'] ?>">Baixar documento enviado pela secretaria</a></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
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
