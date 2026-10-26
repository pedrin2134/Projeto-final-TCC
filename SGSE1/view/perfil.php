<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controller/Autorizacao.php';

if (!isset($_SESSION['usuario_id']) || !in_array((int)($_SESSION['perfil_id'] ?? 0), [1, 2, 3], true)
    || !Autorizacao::usuarioTemPermissao('GERENCIAR_PERFIL')) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';
require_once __DIR__ . '/../controller/UsuarioController.php';

$controller = new UsuarioController();
$erroCarregamento = false;
try {
    $perfil = $controller->buscarPerfilProprio((int)$_SESSION['usuario_id']);
} catch (RuntimeException $e) {
    error_log('Erro ao exibir perfil próprio: ' . $e->getMessage());
    $perfil = null;
    $erroCarregamento = true;
}

if (!$perfil && !$erroCarregamento) {
    http_response_code(404);
    exit('Perfil não encontrado.');
}

$classeCorpo = 'profile-page';
$exibeMenuAdministrativo = true;
include __DIR__ . '/includes/topo.php';
?>

<div class="admin-workspace">
    <?php include __DIR__ . '/includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <main class="profile-content">
            <header class="profile-header">
                <div>
                    <span class="profile-eyebrow">MINHA CONTA</span>
                    <h1>Gerenciamento de Perfil</h1>
                    <p>Atualize sua foto, dados pessoais, informações de contato e credenciais de acesso.</p>
                </div>
            </header>

            <?php if ($erroCarregamento): ?>
                <p class="profile-message profile-message-error" role="alert">Não foi possível carregar seu perfil. Atualize a página para tentar novamente.</p>
            <?php else: ?>
                <?php
                $mensagensPerfil = [
                    'ok' => ['success', 'Perfil atualizado com sucesso.'],
                    'dados_invalidos' => ['error', 'Confira os dados informados. CPF, telefone, e-mail e data de nascimento devem ser válidos.'],
                    'duplicado' => ['error', 'O e-mail ou CPF informado já está vinculado a outra conta.'],
                    'foto_invalida' => ['error', 'A foto deve ser JPG, PNG ou WebP e ter no máximo 5 MB.'],
                    'erro' => ['error', 'Não foi possível salvar as alterações. Tente novamente.']
                ];
                $mensagensSenha = [
                    'senha_atualizada' => ['success', 'Senha alterada. Use a nova senha no próximo acesso.'],
                    'senha_atual_invalida' => ['error', 'A senha atual está incorreta. Nenhuma alteração foi feita. Informe a senha que você usa para entrar no sistema.'],
                    'senhas_diferentes' => ['error', 'A nova senha e a confirmação não são iguais. A senha não foi alterada.'],
                    'senha_fraca' => ['error', 'A nova senha deve ter pelo menos 8 caracteres e não ultrapassar 72 bytes.'],
                    'dados_senha_invalidos' => ['error', 'Preencha todos os campos de senha.'],
                    'erro_senha' => ['error', 'Não foi possível alterar sua senha. Tente novamente.']
                ];
                $resultadoPerfil = (string)($_GET['resultado'] ?? '');
                ?>
                <?php if (isset($mensagensPerfil[$resultadoPerfil])): ?>
                    <p class="profile-message profile-message-<?= $mensagensPerfil[$resultadoPerfil][0] ?>" role="<?= $mensagensPerfil[$resultadoPerfil][0] === 'error' ? 'alert' : 'status' ?>">
                        <?= htmlspecialchars($mensagensPerfil[$resultadoPerfil][1], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <form class="profile-form" action="../controller/UsuarioController.php" method="POST" enctype="multipart/form-data">
                    <section class="profile-card profile-photo-card">
                        <h2 class="profile-section-heading"><span aria-hidden="true">▣</span> Foto de exibição</h2>
                        <div class="profile-photo-panel">
                            <?php if (!empty($perfil['foto_perfil']) && preg_match('#^uploads/perfis/[a-f0-9]{32}\.(jpg|png|webp)$#', $perfil['foto_perfil'])): ?>
                                <img class="profile-photo-preview" src="../<?= htmlspecialchars($perfil['foto_perfil'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual do perfil">
                            <?php else: ?>
                                <span class="profile-photo-placeholder" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($perfil['nome'], 0, 2, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <div class="profile-photo-controls">
                                <input id="foto_perfil" class="profile-photo-input" type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp">
                                <label class="profile-photo-select" for="foto_perfil">Escolher nova foto</label>
                                <span>Arquivos aceitos: JPG, PNG ou WebP. Tamanho máximo: 5 MB.</span>
                            </div>
                        </div>
                    </section>

                    <section class="profile-card profile-details-card">
                        <h2 class="profile-section-heading"><span aria-hidden="true">▤</span> Dados cadastrais e contato</h2>
                        <div class="profile-form-grid">
                            <div>
                                <label for="nome">Nome completo</label>
                                <input id="nome" type="text" name="nome" maxlength="100" value="<?= htmlspecialchars($perfil['nome'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="email">E-mail de contato</label>
                                <input id="email" type="email" name="email" maxlength="100" value="<?= htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="cpf">CPF <span class="profile-readonly-badge">Somente leitura</span></label>
                                <input id="cpf" type="text" value="<?= htmlspecialchars($perfil['cpf'], ENT_QUOTES, 'UTF-8') ?>" readonly aria-readonly="true">
                            </div>
                            <div>
                                <label for="telefone">Telefone / WhatsApp</label>
                                <input id="telefone" type="tel" name="telefone" maxlength="20" value="<?= htmlspecialchars($perfil['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="data_nascimento">Data de nascimento</label>
                                <input id="data_nascimento" type="date" name="data_nascimento" value="<?= htmlspecialchars($perfil['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="perfil">Tipo de conta <span class="profile-readonly-badge">Somente leitura</span></label>
                                <input id="perfil" type="text" value="<?= [1 => 'Administrador', 2 => 'Secretaria', 3 => 'Aluno'][(int)$perfil['perfil_id']] ?? 'Usuário' ?>" readonly aria-readonly="true">
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button type="submit" name="btn_atualizar_perfil" class="profile-save-button">Salvar alterações</button>
                            <a href="<?= (int)$perfil['perfil_id'] === 1 ? 'admin/home_admin.php' : ((int)$perfil['perfil_id'] === 2 ? 'secretaria/pre_matriculas.php' : 'usuario/home_usuario.php') ?>" class="profile-cancel-button">Voltar</a>
                        </div>
                    </section>
                </form>

                <section class="profile-card profile-password-section" id="alterar-senha">
                    <h2 class="profile-section-heading">Alterar senha de acesso</h2>
                    <p>Informe exatamente a senha atual usada para entrar. Se ela estiver incorreta, a nova senha não será salva.</p>
                    <?php if (isset($mensagensSenha[$resultadoPerfil])): ?>
                        <p class="profile-message profile-message-<?= $mensagensSenha[$resultadoPerfil][0] ?>" role="<?= $mensagensSenha[$resultadoPerfil][0] === 'error' ? 'alert' : 'status' ?>">
                            <?= htmlspecialchars($mensagensSenha[$resultadoPerfil][1], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endif; ?>
                    <form class="profile-password-form" action="../controller/UsuarioController.php" method="POST">
                        <div>
                            <label for="senha_atual">Senha atual</label>
                            <input id="senha_atual" type="password" name="senha_atual" autocomplete="current-password" required>
                        </div>
                        <div>
                            <label for="senha_nova">Nova senha</label>
                            <input id="senha_nova" type="password" name="senha_nova" minlength="8" maxlength="72" autocomplete="new-password" required>
                        </div>
                        <div>
                            <label for="confirmar_senha_nova">Confirmar nova senha</label>
                            <input id="confirmar_senha_nova" type="password" name="confirmar_senha_nova" minlength="8" maxlength="72" autocomplete="new-password" required>
                        </div>
                        <button type="submit" name="btn_alterar_senha_propria" class="profile-save-button">Atualizar senha</button>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
</main>
</body>
</html>
