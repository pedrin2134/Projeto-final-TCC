<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['perfil_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Acesso não autorizado.');
}

$mensagens = [
    'dados_invalidos' => 'Confira os campos obrigatórios e os dados informados.',
    'cpf_invalido' => 'Informe um CPF válido.',
    'duplicado' => 'Já existe uma conta cadastrada com esse CPF ou e-mail.',
    'senha_invalida' => 'As senhas não coincidem.',
    'erro' => 'Não foi possível cadastrar a secretária. Tente novamente.'
];
$erro = is_string($_GET['resultado'] ?? null) ? $_GET['resultado'] : '';
$exibeMenuAdministrativo = true;
include __DIR__ . '/../includes/topo.php';
?>
<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <section class="secretary-form-card card">
            <header>
                <h1>Cadastrar secretária</h1>
                <p>Informe os dados pessoais e as credenciais de acesso ao sistema.</p>
            </header>
            <?php if (isset($mensagens[$erro])): ?>
                <p class="secretary-alert error" role="alert"><?= htmlspecialchars($mensagens[$erro], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form action="../../controller/UsuarioController.php" method="POST" class="secretary-form">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" maxlength="100" autocomplete="name" required>

                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" maxlength="14" inputmode="numeric" placeholder="000.000.000-00" required>

                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" maxlength="20" autocomplete="tel" placeholder="(00) 00000-0000" required>

                <label for="data_nascimento">Data de nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" max="<?= date('Y-m-d') ?>" required>

                <label for="email">E-mail de acesso</label>
                <input type="email" id="email" name="email" maxlength="100" autocomplete="email" required>

                <label for="senha">Senha inicial</label>
                <input type="password" id="senha" name="senha" autocomplete="new-password" required>

                <label for="confirmar_senha">Confirmar senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password" required>

                <p class="secretary-role-note">O perfil será definido como <strong>Secretaria</strong>.</p>
                <div class="secretary-form-actions">
                    <button type="submit" name="btn_cadastrar_secretaria">Cadastrar secretária</button>
                    <a class="btn secretary-secondary-button" href="secretarias.php">Cancelar</a>
                </div>
            </form>
        </section>
    </div>
</div>
</main>
</body>
</html>
