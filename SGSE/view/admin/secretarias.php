<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['perfil_id'] ?? 0) !== 1
    || !Autorizacao::usuarioTemPermissao('GERENCIAR_USUARIOS')) {
    http_response_code(403);
    exit('Acesso não autorizado.');
}

require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../../controller/UsuarioController.php';

$controller = new UsuarioController();
$busca = is_string($_GET['busca'] ?? null) ? trim($_GET['busca']) : '';
$erroListagem = false;
try {
    $secretarias = $controller->listarSecretarias($busca);
} catch (RuntimeException $e) {
    $secretarias = [];
    $erroListagem = true;
}
$idConsulta = filter_var($_GET['ver'] ?? null, FILTER_VALIDATE_INT);
$secretariaConsulta = null;
$erroConsulta = false;
if ($idConsulta && $idConsulta > 0) {
    try {
        $secretariaConsulta = $controller->buscarSecretariaPorId($idConsulta);
    } catch (RuntimeException $e) {
        $erroConsulta = true;
    }
}
$mensagens = [
    'ok' => ['success', 'Operação realizada com sucesso.'],
    'dados_invalidos' => ['error', 'Confira os campos obrigatórios e os dados informados.'],
    'cpf_invalido' => ['error', 'Informe um CPF válido.'],
    'duplicado' => ['error', 'Já existe uma conta cadastrada com esse CPF ou e-mail.'],
    'vinculado' => ['error', 'Esta secretária possui registros vinculados e não pode ser excluída.'],
    'nao_encontrado' => ['error', 'A secretária não foi encontrada. Atualize a lista e tente novamente.'],
    'erro' => ['error', 'Não foi possível concluir a operação. Tente novamente.']
];
$resultado = is_string($_GET['resultado'] ?? null) ? $_GET['resultado'] : '';
$exibeMenuAdministrativo = true;
include __DIR__ . '/../includes/topo.php';
?>
<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <div class="secretary-management">
            <header class="secretary-management-header">
                <div>
                    <h1>Gerenciar secretárias</h1>
                    <p>Cadastre, consulte, altere e exclua as contas da secretaria escolar.</p>
                </div>
                <a class="btn" href="cadastrar_secretaria.php">+ Cadastrar secretária</a>
            </header>

            <?php if (isset($mensagens[$resultado])): ?>
                <p class="secretary-alert <?= $mensagens[$resultado][0] ?>" role="status">
                    <?= htmlspecialchars($mensagens[$resultado][1], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['ver'])): ?>
                <?php if ($erroConsulta): ?>
                    <p class="secretary-alert error" role="alert">Não foi possível consultar os dados. Tente novamente.</p>
                <?php elseif ($secretariaConsulta): ?>
                    <section class="secretary-detail card">
                        <div class="secretary-detail-heading">
                            <h2>Dados da secretária</h2>
                            <div class="secretary-actions">
                                <a class="btn" href="editar_secretaria.php?id=<?= (int)$secretariaConsulta['id'] ?>">Alterar</a>
                                <form action="../../controller/UsuarioController.php" method="POST">
                                    <input type="hidden" name="id" value="<?= (int)$secretariaConsulta['id'] ?>">
                                    <button type="submit" name="btn_excluir_secretaria"
                                            onclick="return confirm('Confirma a exclusão desta secretária? A operação só será possível se não houver registros vinculados.');">
                                        Excluir
                                    </button>
                                </form>
                                <a class="btn secretary-secondary-button" href="secretarias.php">Voltar à lista</a>
                            </div>
                        </div>
                        <dl class="secretary-data">
                            <div><dt>Nome</dt><dd><?= htmlspecialchars($secretariaConsulta['nome'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>CPF</dt><dd><?= htmlspecialchars($secretariaConsulta['cpf'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Telefone</dt><dd><?= htmlspecialchars($secretariaConsulta['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Data de nascimento</dt><dd><?= htmlspecialchars($secretariaConsulta['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>E-mail</dt><dd><?= htmlspecialchars($secretariaConsulta['email'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Perfil</dt><dd>Secretaria</dd></div>
                            <div><dt>Status</dt><dd><?= htmlspecialchars($secretariaConsulta['status'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        </dl>
                    </section>
                <?php else: ?>
                    <p class="secretary-alert error" role="alert">A secretária solicitada não foi encontrada.</p>
                <?php endif; ?>
            <?php endif; ?>

            <section class="secretary-list card">
                <h2>Secretárias cadastradas</h2>
                <form class="secretary-search" action="secretarias.php" method="GET" role="search">
                    <label for="busca-secretaria">Buscar por nome ou e-mail</label>
                    <div>
                        <input type="search" id="busca-secretaria" name="busca"
                               value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Buscar</button>
                        <?php if ($busca !== ''): ?>
                            <a class="btn secretary-secondary-button" href="secretarias.php">Limpar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($erroListagem): ?>
                    <p class="secretary-alert error" role="alert">Não foi possível carregar a lista de secretárias. Atualize a página e tente novamente.</p>
                <?php elseif ($secretarias): ?>
                    <div class="secretary-table-wrap">
                        <table>
                            <thead>
                                <tr><th>Nome</th><th>E-mail</th><th>Status</th><th>Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($secretarias as $secretaria): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($secretaria['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($secretaria['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($secretaria['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="secretary-actions">
                                            <a class="btn" href="secretarias.php?ver=<?= (int)$secretaria['id'] ?>">Consultar</a>
                                            <a class="btn" href="editar_secretaria.php?id=<?= (int)$secretaria['id'] ?>">Alterar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="secretary-empty">
                        <?= $busca !== ''
                            ? 'Nenhuma secretária foi encontrada. Tente outra busca ou limpe o filtro.'
                            : 'Nenhuma secretária cadastrada.' ?>
                    </p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
</main>
</body>
</html>
