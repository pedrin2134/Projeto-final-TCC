<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || !in_array((int)($_SESSION['perfil_id'] ?? 0), [1, 2], true)
    || !Autorizacao::usuarioTemPermissao('GERENCIAR_CURSOS')) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../controller/UsuarioController.php';

$controller = new UsuarioController();
$busca = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$cursos = $controller->listarCursosGerenciamento($busca);
$cursoEdicao = null;
$idEdicao = filter_var($_GET['editar'] ?? null, FILTER_VALIDATE_INT);

if ($idEdicao && $idEdicao > 0) {
    foreach ($cursos as $curso) {
        if ((int)$curso['id_curso'] === $idEdicao) {
            $cursoEdicao = $curso;
            break;
        }
    }
}

$mensagens = [
    'ok' => 'Curso cadastrado com sucesso.',
    'alterado' => 'Curso alterado com sucesso.',
    'excluido' => 'Curso excluído com sucesso.',
    'duplicado' => 'Já existe um curso com essa sigla. Escolha outra sigla.',
    'dados_invalidos' => 'Confira os campos: nome obrigatório (até 100 caracteres) e sigla de 2 a 10 letras ou números.',
    'imagem_invalida' => 'Envie uma imagem JPG, PNG ou WebP válida.',
    'imagem_tamanho' => 'A imagem deve ter no máximo 5 MB.',
    'vinculado' => 'Este curso não pode ser excluído porque possui pré-matrículas vinculadas.',
    'nao_encontrado' => 'O curso não foi encontrado. Atualize a lista e tente novamente.',
    'erro' => 'Não foi possível concluir a operação. Tente novamente.'
];
$resultado = is_string($_GET['resultado'] ?? null) ? $_GET['resultado'] : '';
$exibeMenuAdministrativo = true;
$classeCorpo = 'workflow-page';
include __DIR__ . '/includes/topo.php';
?>
<script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>" defer></script>
<div class="admin-workspace">
    <?php include __DIR__ . '/includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
    <div class="main-content wide-shell">
        <header class="workflow-header">
            <h1>Gerenciar cursos</h1>
            <p>Cadastre, consulte, altere e exclua cursos disponíveis para pré-matrícula.</p>
            <button type="button" class="theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
        </header>

        <?php if (isset($mensagens[$resultado])): ?>
            <?php $sucesso = in_array($resultado, ['ok', 'alterado', 'excluido'], true); ?>
            <p class="course-operation-message <?= $sucesso ? 'success' : 'error' ?>"
               role="<?= $sucesso ? 'status' : 'alert' ?>">
                <?= htmlspecialchars($mensagens[$resultado]) ?>
            </p>
        <?php endif; ?>

        <section class="card course-card">
            <h2><?= $cursoEdicao ? 'Alterar curso' : 'Cadastrar curso' ?></h2>
            <form action="../controller/UsuarioController.php" method="POST" enctype="multipart/form-data">
                <?php if ($cursoEdicao): ?>
                    <input type="hidden" name="id_curso" value="<?= (int)$cursoEdicao['id_curso'] ?>">
                <?php endif; ?>
                <label for="nome">Nome do curso</label>
                <input type="text" id="nome" name="nome" maxlength="100" required
                       value="<?= htmlspecialchars($cursoEdicao['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label for="sigla">Sigla (2 a 10 letras ou números)</label>
                <input type="text" id="sigla" name="sigla" maxlength="10" pattern="[A-Za-z0-9]{2,10}" required
                       value="<?= htmlspecialchars($cursoEdicao['sigla'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <label for="imagem">Foto do curso (JPG, PNG ou WebP; até 5 MB)</label>
                <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">
                <?php if ($cursoEdicao && !empty($cursoEdicao['imagem'])): ?>
                    <div class="course-image-current">
                        <img src="../<?= htmlspecialchars($cursoEdicao['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                             alt="Foto atual de <?= htmlspecialchars($cursoEdicao['nome'], ENT_QUOTES, 'UTF-8') ?>">
                        <label>
                            <input type="checkbox" name="remover_imagem" value="1">
                            Remover a foto atual
                        </label>
                    </div>
                <?php endif; ?>
                <?php if ($cursoEdicao): ?>
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Ativo" <?= $cursoEdicao['status'] === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="Inativo" <?= $cursoEdicao['status'] === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                    <button type="submit" name="btn_atualizar_curso" class="btn">Salvar alterações</button>
                    <a class="btn" href="cursos.php">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="btn_cadastrar_curso" class="btn">Cadastrar curso</button>
                <?php endif; ?>
            </form>
        </section>

        <section class="card">
            <h2>Cursos cadastrados</h2>
            <form action="cursos.php" method="GET" role="search">
                <label for="busca">Buscar por nome ou sigla</label>
                <input type="search" id="busca" name="busca" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn">Buscar</button>
                <?php if ($busca !== ''): ?>
                    <a class="btn" href="cursos.php">Limpar busca</a>
                <?php endif; ?>
            </form>

            <?php if ($cursos): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr><th>Foto</th><th>Nome</th><th>Sigla</th><th>Status</th><th>Pré-matrículas</th><th>Ações</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cursos as $curso): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($curso['imagem'])): ?>
                                            <img class="course-image-thumbnail"
                                                 src="../<?= htmlspecialchars($curso['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                                 alt="Foto de <?= htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?php else: ?>
                                            <span class="course-image-placeholder">Sem foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($curso['nome']) ?></td>
                                    <td><?= htmlspecialchars($curso['sigla']) ?></td>
                                    <td><?= htmlspecialchars($curso['status']) ?></td>
                                    <td><?= (int)$curso['total_pre_matriculas'] ?></td>
                                    <td class="acoes-container">
                                        <a class="btn" href="cursos.php?editar=<?= (int)$curso['id_curso'] ?><?= $busca !== '' ? '&amp;busca=' . rawurlencode($busca) : '' ?>">Alterar</a>
                                        <form action="../controller/UsuarioController.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="id_curso" value="<?= (int)$curso['id_curso'] ?>">
                                            <button type="submit" name="btn_excluir_curso" class="btn"
                                                    onclick="return confirm('Confirma a exclusão deste curso? Cursos com pré-matrículas vinculadas não podem ser excluídos.');">
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><?= $busca !== '' ? 'Nenhum curso encontrado. Faça outra busca ou limpe o filtro.' : 'Nenhum curso cadastrado.' ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>
</main>
</body>
</html>
