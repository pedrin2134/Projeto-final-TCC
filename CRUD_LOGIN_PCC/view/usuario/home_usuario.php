<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
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
include_once __DIR__ . '/../includes/topo.php';
?>

<!-- Importação dos estilos externos corrigida para a estrutura de pastas -->
<link rel="stylesheet" href="../../assets/css/estilo.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo.css'); ?>">
<link rel="stylesheet" href="../../assets/css/estilo_home.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estilo_home.css'); ?>">

<div class="main-content" style="max-width: 900px; margin: 30px auto; padding: 0 20px;">
    <header class="page-header">
        <h1>Área do Usuário</h1>
    </header>

    <div class="cards-container">
        
        <!-- Card 1: Perfil do Usuário -->
        <div class="card">
            <div>
                <h3>Meu Perfil</h3>
                <p>Visualize e atualize suas informações de cadastro e senha de acesso.</p>
            </div>
            <a href="../editar_usuario.php?id=<?php echo $_SESSION['usuario_id']; ?>" class="btn-card">Editar Perfil</a>
        </div>

        <!-- Card 2: Sem link -->
        <div class="card">
            <div>
                <h3>Card 2 do Usuário</h3>
                <p>Este é um segundo card sem ações vinculadas no momento.</p>
            </div>
        </div>

    </div>
</div>

<?php
if (file_exists(__DIR__ . '/../includes/rodape.php')) {
    include_once __DIR__ . '/../includes/rodape.php';
}
?>