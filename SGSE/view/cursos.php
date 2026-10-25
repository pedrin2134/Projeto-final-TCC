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
$statusFiltro = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$statusFiltro = in_array($statusFiltro, ['Ativo', 'Inativo'], true) ? $statusFiltro : '';
$todosCursos = $controller->listarCursosGerenciamento();
$totalCursos = count($todosCursos);
$totalCursosAtivos = count(array_filter($todosCursos, static function (array $curso): bool {
    return ($curso['status'] ?? '') === 'Ativo';
}));
$cursos = array_values(array_filter($todosCursos, static function (array $curso) use ($busca, $statusFiltro): bool {
    $correspondeBusca = $busca === ''
        || mb_stripos((string)($curso['nome'] ?? ''), $busca, 0, 'UTF-8') !== false
        || mb_stripos((string)($curso['sigla'] ?? ''), $busca, 0, 'UTF-8') !== false;
    $correspondeStatus = $statusFiltro === '' || ($curso['status'] ?? '') === $statusFiltro;
    return $correspondeBusca && $correspondeStatus;
}));
$cursoEdicao = null;
$idEdicao = filter_var($_GET['editar'] ?? null, FILTER_VALIDATE_INT);

if ($idEdicao && $idEdicao > 0) {
    foreach ($todosCursos as $curso) {
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
$classeCorpo = 'workflow-page admin-courses-page';
include __DIR__ . '/includes/topo.php';
?>
<div class="admin-workspace">
    <?php include __DIR__ . '/includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
    <div class="main-content wide-shell">
        <header class="workflow-header admin-page-header">
            <div>
                <h1><span aria-hidden="true">▣</span> Gestão de Cursos Técnicos</h1>
                <p>Gerencie a oferta de cursos técnicos da instituição.</p>
            </div>
            <div class="course-editor">
                <button type="button" class="course-editor-trigger" data-open-course-editor>
                    <?= $cursoEdicao ? 'Editar curso' : '+ Novo Curso' ?>
                </button>
                <dialog class="course-editor-dialog" aria-labelledby="course-editor-title" data-course-editor
                        <?= $cursoEdicao ? 'data-open-on-load' : '' ?>>
                    <section class="course-editor-panel">
                        <header class="course-editor-heading">
                            <span class="course-editor-icon" aria-hidden="true">▣</span>
                            <div>
                                <h2 id="course-editor-title"><?= $cursoEdicao ? 'Alterar Curso' : 'Cadastrar Novo Curso' ?></h2>
                                <p>Preencha as informações necessárias do curso técnico</p>
                            </div>
                            <button type="button" class="course-editor-close" data-close-course-editor aria-label="Fechar">×</button>
                        </header>
                        <form action="../controller/UsuarioController.php" method="POST" enctype="multipart/form-data">
                        <?php if ($cursoEdicao): ?>
                            <input type="hidden" name="id_curso" value="<?= (int)$cursoEdicao['id_curso'] ?>">
                        <?php endif; ?>
                        <div class="course-form-grid">
                            <div class="course-form-field course-form-field-wide">
                                <label for="nome">Nome do curso *</label>
                                <input type="text" id="nome" name="nome" maxlength="100" required
                                       placeholder="Ex: Análise e Desenvolvimento de Sistemas"
                                       value="<?= htmlspecialchars($cursoEdicao['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="course-form-field course-form-field-wide">
                                <label for="sigla">Sigla *</label>
                                <input type="text" id="sigla" name="sigla" maxlength="10" pattern="[A-Za-z0-9]{2,10}" required
                                       placeholder="Ex: ADS"
                                       value="<?= htmlspecialchars($cursoEdicao['sigla'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="course-form-field course-form-field-wide">
                                <label for="imagem">Foto do curso (JPG, PNG ou WebP; até 5 MB)</label>
                                <label class="course-image-dropzone" for="imagem" data-course-dropzone>
                                    <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp" data-course-image>
                                    <span class="course-image-dropzone-icon" aria-hidden="true">▧</span>
                                    <strong data-course-image-name>Arraste a capa aqui ou <span>clique para selecionar</span></strong>
                                    <small>Formatos suportados: JPG, PNG ou WebP · até 5 MB</small>
                                </label>
                                <?php if ($cursoEdicao && is_string($cursoEdicao['imagem'] ?? null) && preg_match('#^uploads/cursos/[a-f0-9]{32}\.(jpg|png|webp)$#', $cursoEdicao['imagem']) && is_file(__DIR__ . '/../' . $cursoEdicao['imagem'])): ?>
                                    <div class="course-image-current">
                                        <img src="../<?= htmlspecialchars($cursoEdicao['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                             alt="Foto atual de <?= htmlspecialchars($cursoEdicao['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                        <label>
                                            <input type="checkbox" name="remover_imagem" value="1">
                                            Remover a foto atual
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($cursoEdicao): ?>
                                <div class="course-form-field course-form-field-wide">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" required>
                                        <option value="Ativo" <?= $cursoEdicao['status'] === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="Inativo" <?= $cursoEdicao['status'] === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="course-editor-actions">
                            <button type="submit" name="<?= $cursoEdicao ? 'btn_atualizar_curso' : 'btn_cadastrar_curso' ?>" class="btn">
                                <?= $cursoEdicao ? 'Salvar alterações' : 'Cadastrar curso' ?>
                            </button>
                            <button type="button" class="btn course-cancel-button" data-close-course-editor>Cancelar</button>
                        </div>
                    </form>
                    </section>
                </dialog>
            </div>
        </header>

        <?php if (isset($mensagens[$resultado])): ?>
            <?php $sucesso = in_array($resultado, ['ok', 'alterado', 'excluido'], true); ?>
            <p class="course-operation-message <?= $sucesso ? 'success' : 'error' ?>"
               role="<?= $sucesso ? 'status' : 'alert' ?>">
                <?= htmlspecialchars($mensagens[$resultado]) ?>
            </p>
        <?php endif; ?>

        <section class="course-admin-summary" aria-label="Resumo dos cursos">
            <article><span>Total de cursos</span><strong><?= number_format($totalCursos, 0, ',', '.') ?></strong></article>
            <article><span>Cursos ativos</span><strong><?= number_format($totalCursosAtivos, 0, ',', '.') ?></strong></article>
        </section>

        <section class="course-admin-list" aria-labelledby="course-list-title">
            <div class="course-admin-toolbar">
                <h2 id="course-list-title">Cursos cadastrados</h2>
                <form action="cursos.php" method="GET" role="search">
                    <label class="visually-hidden" for="busca">Filtrar curso por nome ou sigla</label>
                    <input type="search" id="busca" name="busca" placeholder="Filtrar curso por nome..." value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>">
                    <label class="visually-hidden" for="status-filtro">Filtrar por status</label>
                    <select id="status-filtro" name="status">
                        <option value="">Todos os status</option>
                        <option value="Ativo" <?= $statusFiltro === 'Ativo' ? 'selected' : '' ?>>Ativos</option>
                        <option value="Inativo" <?= $statusFiltro === 'Inativo' ? 'selected' : '' ?>>Inativos</option>
                    </select>
                    <button type="submit" class="btn">Filtrar</button>
                    <?php if ($busca !== '' || $statusFiltro !== ''): ?>
                        <a class="btn course-cancel-button" href="cursos.php">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($cursos): ?>
                <div class="course-admin-grid">
                    <?php foreach ($cursos as $curso): ?>
                        <?php
                        $nomeCurso = (string)$curso['nome'];
                        $imagemCurso = is_string($curso['imagem'] ?? null)
                            && preg_match('#^uploads/cursos/[a-f0-9]{32}\.(jpg|png|webp)$#', $curso['imagem'])
                            && is_file(__DIR__ . '/../' . $curso['imagem'])
                            ? $curso['imagem']
                            : null;
                        $parametrosFiltroCurso = http_build_query(array_filter([
                            'busca' => $busca,
                            'status' => $statusFiltro
                        ], static function (string $valor): bool {
                            return $valor !== '';
                        }));
                        $urlEditarCurso = 'cursos.php?editar=' . (int)$curso['id_curso']
                            . ($parametrosFiltroCurso !== '' ? '&' . $parametrosFiltroCurso : '');
                        ?>
                        <article class="course-admin-card">
                            <div class="course-admin-photo">
                                <?php if ($imagemCurso !== null): ?>
                                    <img src="../<?= htmlspecialchars($imagemCurso, ENT_QUOTES, 'UTF-8') ?>"
                                         alt="Foto do curso <?= htmlspecialchars($nomeCurso, ENT_QUOTES, 'UTF-8') ?>">
                                <?php else: ?>
                                    <span class="course-admin-photo-placeholder" aria-label="Foto não cadastrada">
                                        <span aria-hidden="true">▣</span>
                                        <span>Foto não cadastrada</span>
                                    </span>
                                <?php endif; ?>
                                <span class="course-admin-code"><?= htmlspecialchars((string)$curso['sigla'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="admin-status-badge <?= $curso['status'] === 'Inativo' ? 'is-inactive' : 'is-active' ?>">
                                    <?= htmlspecialchars((string)$curso['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="course-admin-card-content">
                                <h3><?= htmlspecialchars($nomeCurso, ENT_QUOTES, 'UTF-8') ?></h3>
                                <div class="course-admin-card-actions">
                                    <a class="btn" href="<?= htmlspecialchars($urlEditarCurso, ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                                    <form action="../controller/UsuarioController.php" method="POST">
                                        <input type="hidden" name="id_curso" value="<?= (int)$curso['id_curso'] ?>">
                                        <button type="submit" name="btn_excluir_curso" class="btn course-delete-button"
                                                onclick="return confirm('Confirma a exclusão deste curso? Cursos com pré-matrículas vinculadas não podem ser excluídos.');">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="course-admin-empty"><?= $busca !== '' || $statusFiltro !== '' ? 'Nenhum curso corresponde aos filtros selecionados.' : 'Nenhum curso cadastrado.' ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>
</main>
<script src="../assets/js/course-editor.js?v=<?= filemtime(__DIR__ . '/../assets/js/course-editor.js') ?>" defer></script>
</body>
</html>
