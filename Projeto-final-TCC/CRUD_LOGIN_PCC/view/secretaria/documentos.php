<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['perfil_id'] ?? 0) !== 2) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../../controller/UsuarioController.php';

$controller = new UsuarioController();
$preMatriculas = $controller->listarPreMatriculas();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de documentos</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo.css'); ?>">
    <script src="../../assets/js/tema.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/tema.js'); ?>" defer></script>
</head>
<body class="workflow-page secretary-workflow">
    <div class="main-content workflow-shell wide-shell" style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">
        <header class="page-header workflow-header">
            <h1>Análise de documentos</h1>
            <p>Confira os arquivos enviados e registre a decisão de cada documento.</p>
            <button type="button" class="theme-toggle workflow-theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
        </header>

        <?php if (($_GET['resultado'] ?? '') === 'ok'): ?>
            <p style="color: green;">Avaliação registrada.</p>
        <?php endif; ?>

        <?php foreach ($preMatriculas as $preMatricula): ?>
            <div class="card applicant-card" style="margin-bottom: 20px;">
                <div class="applicant-heading">
                    <?php if (!empty($preMatricula['aluno_foto'])): ?><img class="student-photo" src="../../<?= htmlspecialchars($preMatricula['aluno_foto']) ?>" alt="Foto de <?= htmlspecialchars($preMatricula['aluno_nome']) ?>"><?php else: ?><span class="avatar-badge"><?= strtoupper(substr($preMatricula['aluno_nome'], 0, 1)) ?></span><?php endif; ?>
                    <div><h3><?= htmlspecialchars($preMatricula['aluno_nome']) ?></h3><p><?= htmlspecialchars($preMatricula['curso_nome']) ?> · <?= htmlspecialchars($preMatricula['aluno_email']) ?></p></div>
                </div>
                <div class="personal-data compact-data">
                    <div><strong>CPF</strong><span><?= htmlspecialchars($preMatricula['aluno_cpf'] ?: 'Não informado') ?></span></div>
                    <div><strong>Telefone</strong><span><?= htmlspecialchars($preMatricula['aluno_telefone'] ?: 'Não informado') ?></span></div>
                    <div><strong>Data de nascimento</strong><span><?= htmlspecialchars($preMatricula['aluno_data_nascimento'] ?: 'Não informado') ?></span></div>
                    <div><strong>Status da conta</strong><span><?= htmlspecialchars($preMatricula['aluno_status']) ?></span></div>
                </div>
                <?php $documentos = $controller->listarDocumentosPreMatricula((int)$preMatricula['id_pre_matricula']); ?>
                <?php foreach ($documentos as $documento): ?>
                    <div class="review-row">
                        <div class="review-meta"><strong><?= htmlspecialchars($documento['tipo_nome']) ?></strong><span class="status-pill status-<?= strtolower($documento['status'] ?? 'pendente') ?>"><?= htmlspecialchars($documento['status'] ?? 'Pendente') ?></span></div>
                        <?php if ($documento['id_documento']): ?>
                            <a class="file-link" href="../../<?= htmlspecialchars($documento['caminho_arquivo']) ?>" target="_blank">Abrir documento ↗</a>
                            <form action="../../controller/UsuarioController.php" method="POST">
                                <input type="hidden" name="id_documento" value="<?= (int)$documento['id_documento'] ?>">
                                <select name="status" required>
                                    <option value="Deferido">Deferir</option>
                                    <option value="Indeferido">Indeferir</option>
                                </select>
                                <input type="text" name="justificativa_recusa" placeholder="Justificativa, se necessário" class="review-note">
                                <button type="submit" name="btn_avaliar_documento" class="btn btn-primary">Registrar avaliação</button>
                            </form>
                        <?php else: ?>
                            <p class="missing-file">Documento ainda não enviado pelo aluno.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <p><a href="pre_matriculas.php">Analisar pré-matrículas</a></p>
        <p><a href="../../controller/UsuarioController.php?acao=logout">Sair</a></p>
    </div>
</body>
</html>
