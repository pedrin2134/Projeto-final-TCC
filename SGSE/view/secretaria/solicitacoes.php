<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || !in_array((int)($_SESSION['perfil_id'] ?? 0), [1, 2], true)) {
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

$exibeMenuAdministrativo = true;
$classeCorpo = 'workflow-page service-requests-page';
include __DIR__ . '/../includes/topo.php';
?>
<script src="../../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../../assets/js/tema.js') ?>" defer></script>

<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <div class="main-content workflow-shell wide-shell service-requests-content">
            <header class="page-header workflow-header service-requests-header">
                <div>
                    <h1>Solicitações de documentos</h1>
                    <p>Consulte os pedidos dos alunos, atualize o andamento e registre a resposta.</p>
                </div>
                <button type="button" class="theme-toggle workflow-theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
            </header>

            <?php if ($erroCarregamento): ?>
                <p class="service-message service-message-error" role="alert">Não foi possível carregar a fila de solicitações.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'ok'): ?>
                <p class="service-message service-message-success" role="status">Solicitação atualizada com sucesso.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'erro'): ?>
                <p class="service-message service-message-error" role="alert">Não foi possível atualizar a solicitação. Ela pode já estar encerrada ou os dados são inválidos.</p>
            <?php elseif (($_GET['resultado'] ?? '') === 'arquivo_invalido'): ?>
                <p class="service-message service-message-error" role="alert">O anexo deve ser PDF, JPG ou PNG e ter no máximo 10 MB.</p>
            <?php endif; ?>

            <?php if ($solicitacoes): ?>
                <?php foreach ($solicitacoes as $solicitacao): ?>
                    <article class="service-request-card">
                        <div class="service-request-heading">
                            <div>
                                <span class="service-request-protocol">Protocolo <?= htmlspecialchars($solicitacao['numero_protocolo'], ENT_QUOTES, 'UTF-8') ?></span>
                                <h3><?= htmlspecialchars($solicitacao['tipo_nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($solicitacao['aluno_nome'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($solicitacao['aluno_email'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="service-request-status status-<?= strtolower(str_replace(' ', '-', $solicitacao['status'])) ?>"><?= htmlspecialchars(str_replace('Concluida', 'Concluída', $solicitacao['status']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p><?= nl2br(htmlspecialchars($solicitacao['descricao'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <small>Recebida em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitacao['data_abertura'])), ENT_QUOTES, 'UTF-8') ?></small>
                        <?php if (!empty($solicitacao['arquivo_resposta'])): ?>
                            <p class="service-request-file"><a href="../../controller/UsuarioController.php?acao=baixar_arquivo_solicitacao&amp;id=<?= (int)$solicitacao['id_solicitacao'] ?>">Baixar documento enviado ao aluno</a></p>
                        <?php endif; ?>

                        <?php if (in_array($solicitacao['status'], ['Aberta', 'Em Atendimento'], true)): ?>
                            <form class="service-request-form service-staff-form" action="../../controller/UsuarioController.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="id_solicitacao" value="<?= (int)$solicitacao['id_solicitacao'] ?>">
                                <label for="status_<?= (int)$solicitacao['id_solicitacao'] ?>">Andamento</label>
                                <select id="status_<?= (int)$solicitacao['id_solicitacao'] ?>" name="status" required>
                                    <option value="Em Atendimento" <?= $solicitacao['status'] === 'Em Atendimento' ? 'selected' : '' ?>>Em atendimento</option>
                                    <option value="Concluida">Concluída</option>
                                    <option value="Cancelada">Cancelar solicitação</option>
                                </select>
                                <label for="resposta_<?= (int)$solicitacao['id_solicitacao'] ?>">Resposta ao aluno</label>
                                <textarea id="resposta_<?= (int)$solicitacao['id_solicitacao'] ?>" name="resposta" rows="3" maxlength="2000" placeholder="Informe a orientação ou o resultado do atendimento"><?= htmlspecialchars($solicitacao['resposta'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                <label for="arquivo_resposta_<?= (int)$solicitacao['id_solicitacao'] ?>">Anexar documento para o aluno (opcional)</label>
                                <input id="arquivo_resposta_<?= (int)$solicitacao['id_solicitacao'] ?>" type="file" name="arquivo_resposta" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                <small class="service-request-file-help">PDF, JPG ou PNG · até 10 MB<?= !empty($solicitacao['arquivo_resposta']) ? ' · um arquivo já foi enviado; selecionar outro irá substituí-lo' : '' ?></small>
                                <button type="submit" name="btn_atualizar_solicitacao" class="btn btn-primary">Salvar atendimento</button>
                            </form>
                        <?php elseif (!empty($solicitacao['resposta'])): ?>
                            <div class="service-request-response"><strong>Resposta registrada:</strong><p><?= nl2br(htmlspecialchars($solicitacao['resposta'], ENT_QUOTES, 'UTF-8')) ?></p></div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="service-empty">Não há solicitações registradas.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
</body>
</html>
