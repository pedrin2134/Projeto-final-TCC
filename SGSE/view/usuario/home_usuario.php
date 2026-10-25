<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../../controller/Autorizacao.php';
if ((int)($_SESSION['perfil_id'] ?? 0) === 3
    && !Autorizacao::usuarioTemPermissao('GERENCIAR_PERFIL')) {
    http_response_code(403);
    exit('Acesso não autorizado.');
}

if ((int)($_SESSION['perfil_id'] ?? 0) === 3) {
    require_once __DIR__ . '/../../model/dao/Conexao.php';
    require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';

    $preMatricula = (new UsuarioDAO())->buscarPreMatriculaAluno((int)$_SESSION['usuario_id']);
    if (!$preMatricula || $preMatricula['status'] !== 'Aprovado') {
        header("Location: pre_matricula.php");
        exit();
    }
}

// Inclui o topo padrão da pasta includes
$classeCorpo = 'user-home-page';
$exibeMenuAdministrativo = true;
include_once __DIR__ . '/../includes/topo.php';
?>

<div class="admin-workspace">
    <?php include __DIR__ . '/../includes/menu_administrativo.php'; ?>
    <div class="admin-workspace-content">
        <div class="main-content user-home-content">
            <header class="page-header user-home-header">
                <h1>Área do Usuário</h1>
            </header>

            <div class="cards-container user-home-cards">
                <div class="card user-home-card">
                    <div>
                        <h3>Meu Perfil</h3>
                        <p>Visualize e atualize suas informações de cadastro e senha de acesso.</p>
                    </div>
                    <a href="../perfil.php" class="btn-card">Editar Perfil</a>
                </div>

                <div class="card user-home-card">
                    <div>
                        <h3>Solicitar documentos</h3>
                        <p>Peça declarações, diploma, segunda via e outros documentos à secretaria.</p>
                    </div>
                    <a href="solicitar_documentos.php" class="btn-card">Fazer solicitação</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (file_exists(__DIR__ . '/../includes/rodape.php')) {
    include_once __DIR__ . '/../includes/rodape.php';
}
?>