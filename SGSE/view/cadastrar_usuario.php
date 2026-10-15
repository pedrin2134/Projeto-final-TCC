<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cadastroDados = $_SESSION['cadastro_formulario'] ?? [];
unset($_SESSION['cadastro_formulario']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuário</title>
    <link rel="stylesheet" href="../assets/css/estilo_login.css?v=<?php echo filemtime('../assets/css/estilo_login.css'); ?>">
    <!-- Importação do JS externo -->
    <script src="../assets/js/usuario.js?v=<?php echo filemtime('../assets/js/usuario.js'); ?>" defer></script>
    <script src="../assets/js/tema.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/tema.js'); ?>" defer></script>
</head>
<body class="cadastro-page">
    <header class="site-header cadastro-header">
        <a class="brand" href="login.php">
            <img src="../assets/img/sua_logo.png" alt="Logo da Escola Técnica de Ceilândia">
            <span>ESCOLA TÉCNICA<br><small>DE CEILÂNDIA</small></span>
        </a>
        <a class="back-link" href="login.php">← Voltar para o acesso</a>
        <button type="button" class="theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
    </header>

    <main class="register-layout">
        <aside class="register-intro">
            <p class="eyebrow">NOVA INSCRIÇÃO</p>
            <h1>Comece sua<br><strong>história acadêmica.</strong></h1>
            <p>Informe seus dados pessoais. Em seguida, você enviará os documentos e escolherá um curso.</p>
            <div class="steps" aria-label="Etapas da inscrição">
                <div class="step active"><span>1</span><div><strong>Dados pessoais</strong><small>Crie sua conta</small></div></div>
                <div class="step"><span>2</span><div><strong>Documentos</strong><small>Envie os arquivos obrigatórios</small></div></div>
                <div class="step"><span>3</span><div><strong>Curso</strong><small>Escolha sua formação</small></div></div>
                <div class="step"><span>4</span><div><strong>Avaliação</strong><small>Aguarde a secretaria</small></div></div>
            </div>
        </aside>

        <section class="register-card">
            <div class="card-kicker">ETAPA 1 DE 3</div>
            <h2>Crie seu cadastro</h2>
            <p class="card-intro">Após validar seus dados, o sistema apresentará a etapa de envio dos documentos.</p>

        <?php
        $mensagensErro = [
            'consentimento' => 'Confirme que leu e concorda com os termos de privacidade.',
            'dados' => 'Confira os dados pessoais e preencha todos os campos obrigatórios.',
            'cpf' => 'Informe um CPF válido.',
            'email' => 'Informe um endereço de e-mail válido.',
            'senha' => 'As senhas são obrigatórias e precisam ser iguais.',
            'cadastro' => 'Não foi possível cadastrar. Verifique se o CPF ou e-mail já está em uso.'
        ];
        $erroCadastro = $mensagensErro[$_GET['erro'] ?? ''] ?? null;
        ?>
        <?php if ($erroCadastro): ?>
            <div role="alert" style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
                <?= htmlspecialchars($erroCadastro) ?>
            </div>
        <?php endif; ?>

        <form class="register-form" action="../controller/UsuarioController.php" method="POST" onsubmit="return validarSenhas();">
            <div class="input-group field-name">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" maxlength="100" placeholder="Digite seu nome" value="<?= htmlspecialchars($cadastroDados['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="input-group field-cpf">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="Digite seu CPF" value="<?= htmlspecialchars($cadastroDados['cpf'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="input-group field-phone">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" maxlength="20" placeholder="(00) 00000-0000" value="<?= htmlspecialchars($cadastroDados['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="input-group field-birth">
                <label for="data_nascimento">Data de nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($cadastroDados['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="input-group field-email">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" maxlength="100" placeholder="Digite seu e-mail" value="<?= htmlspecialchars($cadastroDados['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="input-group field-password">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
            </div>

            <div class="input-group field-confirm-password">
                <label for="confirmar_senha">Confirmar Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita sua senha" required>
            </div>

            <!-- Termos da LGPD -->
            <div class="input-group privacy-box">
                <label>Termos de Privacidade (LGPD)</label>
                <textarea readonly style="width: 100%; height: 70px; padding: 8px; font-size: 0.8rem; border-radius: 6px; border: 1px solid #ccc; resize: none; background: #f9f9f9;">
Termos de Consentimento - LGPD:
Ao se cadastrar, você autoriza o tratamento de seus dados pessoais e dos documentos enviados exclusivamente para cadastro, identificação, análise da pré-matrícula, controle de acesso e comunicação institucional, em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).
                </textarea>
            </div>

            <!-- Checkbox de Aceitação -->
            <div class="input-group consent-box">
                <input type="checkbox" id="aceite_lgpd" name="aceite_lgpd" value="1" required style="width: auto; cursor: pointer;">
                <label for="aceite_lgpd" style="margin-bottom: 0; font-size: 0.85rem; cursor: pointer;">
                    Li e concordo com os termos da LGPD
                </label>
            </div>

            <div class="button-group register-actions">
                <button type="submit" name="btn_cadastrar" class="btn btn-primary">Enviar dados e continuar</button>
            </div>
        </form>
        </section>
    </main>

    <footer class="login-footer">© <?= date('Y') ?> Escola Técnica de Ceilândia <span>•</span> Portal do aluno</footer>

</body>
</html>