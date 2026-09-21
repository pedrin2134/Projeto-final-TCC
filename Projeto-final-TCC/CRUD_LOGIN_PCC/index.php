<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Trava de Segurança: Redireciona para o login caso não esteja autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: view/login.php");
    exit();
}

// Restrição de Perfil: Apenas Administradores (perfil_id = 1) acessam o index geral
if (isset($_SESSION['perfil_id']) && $_SESSION['perfil_id'] != 1) {
    header("Location: view/usuario/home_usuario.php");
    exit();
}

// Carrega todas as dependências na ordem correta
require_once 'model/dto/UsuarioDTO.php';
require_once 'model/dao/Conexao.php';
require_once 'model/dao/UsuarioDAO.php';
require_once 'controller/UsuarioController.php';

$usuarioController = new UsuarioController(); 
$usuarios = $usuarioController->listar();

// Inclui o topo padrão da aplicação
include_once 'view/includes/topo.php';
?>

<!-- Importa a folha de estilo principal e do topo -->
<link rel="stylesheet" href="assets/css/estilo.css?v=<?php echo filemtime('assets/css/estilo.css'); ?>">

<div class="main-content" style="max-width: 1000px; margin: 30px auto; padding: 0 20px;">
    
    <header class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1>Gerenciamento de Usuários</h1>
        <a href="view/cadastrar_usuario.php" class="btn btn-ativar">+ Novo Usuário</a>
    </header>

    <h2>Lista de Usuários</h2>

    <?php if (!empty($usuarios)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil ID</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['perfil_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['status'] ?? 'Ativo') ?></td>
                        
                        <td class="acoes-container">
                            <!-- Botão Editar -->
                            <a href="view/editar_usuario.php?id=<?= $u['id'] ?>" class="btn btn-editar">
                                Editar
                            </a>

                            <?php if (strtoupper($u['status'] ?? '') === 'INATIVO'): ?>
                                <!-- Se estiver Inativo, mostra o botão ATIVAR com confirmação -->
                                <a href="controller/UsuarioController.php?acao=ativar&id=<?= $u['id'] ?>" 
                                   class="btn btn-ativar" 
                                   onclick="return confirm('Tem certeza que deseja ativar este usuário?');">
                                    Ativar
                                </a>
                            <?php else: ?>
                                <!-- Caso contrário (Ativo), mostra o botão DESATIVAR com confirmação -->
                                <a href="controller/UsuarioController.php?acao=desativar&id=<?= $u['id'] ?>" 
                                   class="btn btn-desativar" 
                                   onclick="return confirm('Tem certeza que deseja desativar este usuário?');">
                                    Desativar
                                </a>
                            <?php endif; ?>

                            <!-- Botão Excluir -->
                            <form action="controller/UsuarioController.php" method="POST" class="form-excluir" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" name="btn_excluir" class="btn btn-excluir" onclick="return confirm('ATENÇÃO: Tem certeza que deseja excluir este usuário permanentemente?');">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhum usuário encontrado.</p>
    <?php endif; ?>

</div>

<?php
if (file_exists('view/includes/rodape.php')) {
    include_once 'view/includes/rodape.php';
}
?>