<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['perfil_id'] ?? 0) !== 1) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../../controller/UsuarioController.php';

$dashboard = null;
$dashboardErro = false;
try {
    $dashboard = (new UsuarioController())->obterResumoPainelAdministrador();
} catch (RuntimeException $e) {
    $dashboardErro = true;
}

$resumo = $dashboard['resumo'] ?? [];
$recentes = $dashboard['recentes'] ?? [];
$statusPreMatriculas = $dashboard['status_pre_matriculas'] ?? [];
$nomeAdmin = trim((string)($_SESSION['usuario_nome'] ?? 'Administrador'));
$primeiroNome = preg_split('/\s+/', $nomeAdmin)[0] ?? 'Administrador';
$diasSemana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$meses = [
    1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
    5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
    9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
];
$dataHoje = $diasSemana[(int)date('w')] . ', ' . date('j') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');
$statusConfiguracao = [
    'Em Analise' => ['label' => 'Em análise', 'cor' => '#0876b9'],
    'Pendente de Documentacao' => ['label' => 'Pendente de documentação', 'cor' => '#f5b400'],
    'Aprovado' => ['label' => 'Aprovada', 'cor' => '#23845b'],
    'Indeferido' => ['label' => 'Indeferida', 'cor' => '#dc5660']
];
$totalPreMatriculas = array_sum(array_map('intval', $statusPreMatriculas));
$graficosFatias = [];
$anguloAtual = 0;
if ($totalPreMatriculas > 0) {
    foreach ($statusConfiguracao as $chave => $configuracao) {
        $quantidade = (int)($statusPreMatriculas[$chave] ?? 0);
        if ($quantidade === 0) {
            continue;
        }
        $anguloSeguinte = $anguloAtual + ($quantidade / $totalPreMatriculas * 360);
        $graficosFatias[] = $configuracao['cor'] . ' ' . $anguloAtual . 'deg ' . $anguloSeguinte . 'deg';
        $anguloAtual = $anguloSeguinte;
    }
}
$estiloGrafico = $graficosFatias
    ? 'background: conic-gradient(' . implode(', ', $graficosFatias) . ');'
    : '';

$exibeMenuAdministrativo = true;
$classeCorpo = 'admin-dashboard-page';
include_once __DIR__ . '/../includes/topo.php';
?>
<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <div class="admin-dashboard">
            <div class="admin-dashboard-topline">
                <div class="admin-dashboard-breadcrumb">
                    <span aria-hidden="true">⌂</span>
                    <span>Escola de Ceilândia</span>
                    <span aria-hidden="true">/</span>
                    <strong>SGSE</strong>
                </div>
                <span class="admin-dashboard-role">Área administrativa</span>
            </div>

            <?php if ($dashboardErro): ?>
                <p class="admin-dashboard-alert" role="alert">Não foi possível carregar todos os dados do painel. Atualize a página para tentar novamente.</p>
            <?php endif; ?>

            <section class="admin-welcome" aria-labelledby="admin-welcome-title">
                <div>
                    <span class="admin-welcome-eyebrow">PAINEL DO ADMINISTRADOR</span>
                    <h1 id="admin-welcome-title">Olá, <?= htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8') ?>!</h1>
                    <p>Gerencie usuários, secretárias e cursos da Escola Técnica de Ceilândia.</p>
                </div>
                <div class="admin-welcome-mark" aria-hidden="true">
                    <span>SGSE</span>
                    <span class="admin-welcome-mark-icon">⌂</span>
                </div>
                <div class="admin-date-card">
                    <span class="admin-date-icon" aria-hidden="true">▣</span>
                    <div>
                        <small>HOJE É</small>
                        <strong><?= htmlspecialchars($dataHoje, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span>Gestão escolar em um só lugar.</span>
                    </div>
                </div>
            </section>

            <section class="admin-shortcuts" aria-label="Acessos rápidos">
                <a class="admin-shortcut shortcut-users" href="gerenciar_usuarios.php">
                    <span class="admin-shortcut-icon" aria-hidden="true">♙</span>
                    <span><strong>Gerenciar usuários</strong><small>Contas e acessos</small></span>
                    <span class="admin-shortcut-arrow" aria-hidden="true">→</span>
                </a>
                <a class="admin-shortcut shortcut-secretaries" href="secretarias.php">
                    <span class="admin-shortcut-icon" aria-hidden="true">▤</span>
                    <span><strong>Gerenciar secretárias</strong><small>Equipe administrativa</small></span>
                    <span class="admin-shortcut-arrow" aria-hidden="true">→</span>
                </a>
                <a class="admin-shortcut shortcut-courses" href="../cursos.php">
                    <span class="admin-shortcut-icon" aria-hidden="true">▦</span>
                    <span><strong>Gerenciar cursos</strong><small>Oferta acadêmica</small></span>
                    <span class="admin-shortcut-arrow" aria-hidden="true">→</span>
                </a>
            </section>

            <section class="admin-stat-grid" aria-label="Resumo do sistema">
                <article class="admin-stat-card">
                    <span class="admin-stat-icon stat-blue" aria-hidden="true">♙</span>
                    <div><span>Total de usuários</span><strong><?= number_format((int)($resumo['total_usuarios'] ?? 0), 0, ',', '.') ?></strong><small>Contas cadastradas</small></div>
                </article>
                <article class="admin-stat-card">
                    <span class="admin-stat-icon stat-gold" aria-hidden="true">▣</span>
                    <div><span>Alunos cadastrados</span><strong><?= number_format((int)($resumo['total_alunos'] ?? 0), 0, ',', '.') ?></strong><small>Perfil aluno</small></div>
                </article>
                <article class="admin-stat-card">
                    <span class="admin-stat-icon stat-blue" aria-hidden="true">✓</span>
                    <div><span>Matrículas ativas</span><strong><?= number_format((int)($resumo['matriculas_ativas'] ?? 0), 0, ',', '.') ?></strong><small>Registros efetivados</small></div>
                </article>
                <article class="admin-stat-card">
                    <span class="admin-stat-icon stat-gold" aria-hidden="true">▦</span>
                    <div><span>Cursos disponíveis</span><strong><?= number_format((int)($resumo['cursos_ativos'] ?? 0), 0, ',', '.') ?></strong><small>Cursos ativos</small></div>
                </article>
            </section>

            <section class="admin-dashboard-bottom">
                <article class="admin-panel admin-recent-panel">
                    <div class="admin-panel-heading">
                        <div><span class="admin-panel-eyebrow">ACOMPANHAMENTO</span><h2>Pré-matrículas recentes</h2></div>
                        <span class="admin-panel-count">Últimas <?= count($recentes) ?></span>
                    </div>
                    <?php if ($recentes): ?>
                        <ul class="admin-recent-list">
                            <?php foreach ($recentes as $preMatricula): ?>
                                <?php
                                $statusKey = (string)$preMatricula['status'];
                                $statusInfo = $statusConfiguracao[$statusKey] ?? ['label' => $statusKey, 'cor' => '#64748b'];
                                $timestamp = strtotime((string)$preMatricula['data_inscricao']);
                                ?>
                                <li>
                                    <span class="admin-recent-icon" aria-hidden="true">▣</span>
                                    <span class="admin-recent-info">
                                        <strong><?= htmlspecialchars($preMatricula['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small><?= htmlspecialchars($preMatricula['curso_nome'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </span>
                                    <span class="admin-status-pill" style="--status-color: <?= htmlspecialchars($statusInfo['cor'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($statusInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <time datetime="<?= htmlspecialchars((string)$preMatricula['data_inscricao'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= $timestamp !== false ? date('d/m/Y', $timestamp) : '—' ?>
                                    </time>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="admin-dashboard-empty">Ainda não há pré-matrículas para exibir.</p>
                    <?php endif; ?>
                </article>

                <article class="admin-panel admin-status-panel">
                    <div class="admin-panel-heading">
                        <div><span class="admin-panel-eyebrow">INSCRIÇÕES</span><h2>Status das pré-matrículas</h2></div>
                    </div>
                    <div class="admin-status-content">
                        <div class="admin-status-donut" style="<?= htmlspecialchars($estiloGrafico, ENT_QUOTES, 'UTF-8') ?>">
                            <div><strong><?= number_format($totalPreMatriculas, 0, ',', '.') ?></strong><span>Total</span></div>
                        </div>
                        <ul class="admin-status-legend">
                            <?php foreach ($statusConfiguracao as $chave => $configuracao): ?>
                                <?php $quantidade = (int)($statusPreMatriculas[$chave] ?? 0); ?>
                                <li>
                                    <span class="admin-legend-dot" style="--status-color: <?= htmlspecialchars($configuracao['cor'], ENT_QUOTES, 'UTF-8') ?>"></span>
                                    <span><?= htmlspecialchars($configuracao['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= number_format($quantidade, 0, ',', '.') ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </article>
            </section>
        </div>
    </div>
</div>
</main>
</body>
</html>
