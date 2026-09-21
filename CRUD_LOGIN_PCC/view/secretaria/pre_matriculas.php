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

$preMatriculas = (new UsuarioController())->listarPreMatriculas();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de pré-matrículas</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo.css'); ?>">
    <script src="../../assets/js/tema.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/tema.js'); ?>" defer></script>
</head>
<body class="workflow-page secretary-workflow">
    <div class="main-content workflow-shell wide-shell" style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">
        <header class="page-header workflow-header">
            <h1>Análise de pré-matrículas</h1>
            <p>Revise as inscrições e decida os próximos passos de cada aluno.</p>
            <button type="button" class="theme-toggle workflow-theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
        </header>

        <?php if (($_GET['erro'] ?? '') === 'documentos'): ?>
            <p style="color: red;">A pré-matrícula só pode ser aprovada depois que todos os documentos obrigatórios forem deferidos.</p>
        <?php endif; ?>

        <?php if ($preMatriculas): ?>
            <?php foreach ($preMatriculas as $preMatricula): ?>
                <div class="card applicant-card" style="margin-bottom: 20px;">
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

        <p><a href="documentos.php">Analisar documentos</a></p>
        <p><a href="../../controller/UsuarioController.php?acao=logout">Sair</a></p>
    </div>
</body>
</html>
