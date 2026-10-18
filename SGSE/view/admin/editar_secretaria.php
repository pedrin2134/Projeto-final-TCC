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

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$erroConsulta = false;
$secretaria = null;
if ($id && $id > 0) {
    try {
        $secretaria = (new UsuarioController())->buscarSecretariaPorId($id);
    } catch (RuntimeException $e) {
        $erroConsulta = true;
    }
}
if (!$secretaria) {
    http_response_code(404);
}

$mensagens = [
    'dados_invalidos' => 'Confira os campos obrigatórios e os dados informados.',
    'cpf_invalido' => 'Informe um CPF válido.',
    'duplicado' => 'Já existe outra conta cadastrada com esse CPF ou e-mail.',
    'senha_invalida' => 'As senhas não coincidem.',
    'erro' => 'Não foi possível atualizar a secretária. Tente novamente.'
];
$erro = is_string($_GET['resultado'] ?? null) ? $_GET['resultado'] : '';
$exibeMenuAdministrativo = true;
include __DIR__ . '/../includes/topo.php';
?>
<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <?php if ($secretaria): ?>
            <section class="secretary-form-card card">
                <header>
                    <h1>Alterar secretária</h1>
                    <p>Atualize os dados cadastrais. Deixe a senha em branco para mantê-la.</p>
                </header>
                <?php if (isset($mensagens[$erro])): ?>
                    <p class="secretary-alert error" role="alert"><?= htmlspecialchars($mensagens[$erro], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form action="../../controller/UsuarioController.php" method="POST" class="secretary-form">
                    <input type="hidden" name="id" value="<?= (int)$secretaria['id'] ?>">

                    <label for="nome">Nome completo</label>
                    <input type="text" id="nome" name="nome" maxlength="100" value="<?= htmlspecialchars($secretaria['nome'], ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" maxlength="14" inputmode="numeric" value="<?= htmlspecialchars($secretaria['cpf'], ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" maxlength="20" value="<?= htmlspecialchars($secretaria['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($secretaria['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="email">E-mail de acesso</label>
                    <input type="email" id="email" name="email" maxlength="100" value="<?= htmlspecialchars($secretaria['email'], ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="senha">Nova senha (opcional)</label>
                    <input type="password" id="senha" name="senha" autocomplete="new-password">

                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password">

                    <p class="secretary-role-note">O perfil permanece definido como <strong>Secretaria</strong>.</p>
                    <div class="secretary-form-actions">
                        <button type="submit" name="btn_atualizar_secretaria">Salvar alterações</button>
                        <a class="btn secretary-secondary-button" href="secretarias.php?ver=<?= (int)$secretaria['id'] ?>">Cancelar</a>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <p class="secretary-alert error" role="alert">
                <?= $erroConsulta ? 'Não foi possível consultar os dados da secretária.' : 'A secretária não foi encontrada.' ?>
                <a href="secretarias.php">Voltar à lista</a>
            </p>
        <?php endif; ?>
    </div>
</div>
</main>
</body>
</html>
