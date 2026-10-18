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
<script src="../assets/js/tema.js?v=<?= filemtime(__DIR__ . '/../assets/js/tema.js') ?>" defer></script>

<div class="admin-workspace">
    <?php include __DIR__ . '/includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <main class="profile-content">
            <header class="profile-header">
                <div>
                    <span class="profile-eyebrow">MINHA CONTA</span>
                    <h1>Meu perfil</h1>
                    <p>Atualize seus dados pessoais e a foto que aparece no menu.</p>
                </div>
                <button type="button" class="theme-toggle" data-alternar-tema aria-label="Alternar tema"></button>
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
                $resultadoPerfil = (string)($_GET['resultado'] ?? '');
                ?>
                <?php if (isset($mensagensPerfil[$resultadoPerfil])): ?>
                    <p class="profile-message profile-message-<?= $mensagensPerfil[$resultadoPerfil][0] ?>" role="<?= $mensagensPerfil[$resultadoPerfil][0] === 'error' ? 'alert' : 'status' ?>">
                        <?= htmlspecialchars($mensagensPerfil[$resultadoPerfil][1], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <section class="profile-card">
                    <div class="profile-photo-panel">
                        <?php if (!empty($perfil['foto_perfil']) && preg_match('#^uploads/perfis/[a-f0-9]{32}\.(jpg|png|webp)$#', $perfil['foto_perfil'])): ?>
                            <img class="profile-photo-preview" src="../<?= htmlspecialchars($perfil['foto_perfil'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual do perfil">
                        <?php else: ?>
                            <span class="profile-photo-placeholder" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($perfil['nome'], 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <div><strong>Foto do perfil</strong><span>JPG, PNG ou WebP · até 5 MB</span></div>
                    </div>

                    <form class="profile-form" action="../controller/UsuarioController.php" method="POST" enctype="multipart/form-data">
                        <label for="foto_perfil">Alterar foto</label>
                        <input id="foto_perfil" type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp">

                        <div class="profile-form-grid">
                            <div>
                                <label for="nome">Nome completo</label>
                                <input id="nome" type="text" name="nome" maxlength="100" value="<?= htmlspecialchars($perfil['nome'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="email">E-mail</label>
                                <input id="email" type="email" name="email" maxlength="100" value="<?= htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="cpf">CPF</label>
                                <input id="cpf" type="text" name="cpf" inputmode="numeric" maxlength="14" value="<?= htmlspecialchars($perfil['cpf'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="telefone">Telefone</label>
                                <input id="telefone" type="tel" name="telefone" maxlength="20" value="<?= htmlspecialchars($perfil['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="data_nascimento">Data de nascimento</label>
                                <input id="data_nascimento" type="date" name="data_nascimento" value="<?= htmlspecialchars($perfil['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div>
                                <label for="perfil">Tipo de conta</label>
                                <input id="perfil" type="text" value="<?= [1 => 'Administrador', 2 => 'Secretaria', 3 => 'Aluno'][(int)$perfil['perfil_id']] ?? 'Usuário' ?>" readonly>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button type="submit" name="btn_atualizar_perfil" class="profile-save-button">Salvar alterações</button>
                            <a href="<?= (int)$perfil['perfil_id'] === 1 ? 'admin/home_admin.php' : ((int)$perfil['perfil_id'] === 2 ? 'secretaria/pre_matriculas.php' : 'usuario/home_usuario.php') ?>" class="profile-cancel-button">Voltar</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
</main>
</body>
</html>
