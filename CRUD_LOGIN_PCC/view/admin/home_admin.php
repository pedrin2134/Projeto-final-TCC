<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se está logado e se é Administrador (perfil_id = 1)
if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil_id'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Inclui o topo padrão
include_once '../includes/topo.php';
?>

<!-- Importação dos estilos externos -->
<link rel="stylesheet" href="../../assets/css/estilo.css?v=<?php echo filemtime('../../assets/css/estilo.css'); ?>">
<link rel="stylesheet" href="../../assets/css/estilo_home.css?v=<?php echo filemtime('../../assets/css/estilo_home.css'); ?>">

<div class="main-content" style="max-width: 900px; margin: 30px auto; padding: 0 20px;">
    <header class="page-header">
        <h1>Painel Administrativo</h1>
    </header>

    <div class="cards-container">
        
        <!-- Card: Gerenciar Usuários com 2 botões -->
        <div class="card card-admin">
            <div>
                <h3>Gerenciar Usuários</h3>
                <p>Cadastre, edite, ative ou desative contas de usuários no sistema.</p>
            </div>
            
            <div class="card-buttons">
                <a href="../cadastrar_usuario.php" class="btn-card">+ Novo Usuário</a>
                <a href="../../index.php" class="btn-card btn-card-secondary">Gerenciar Usuários</a>
            </div>
        </div>

        <div class="card card-admin">
            <div>
                <h3>Área do Cliente</h3>
                <p>Acesse a visão padrão do usuário comum para realizar seus testes de perfil.</p>
            </div>
            <a href="../usuario/home_usuario.php" class="btn-card">Ver Área Usuário</a>
        </div>

    </div>
</div>

<?php
if (file_exists('../includes/rodape.php')) {
    include_once '../includes/rodape.php';
}
?>