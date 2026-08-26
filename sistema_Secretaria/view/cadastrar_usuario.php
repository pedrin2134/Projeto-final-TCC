<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-cadastro | Secretaria Escolar</title>
    <link rel="stylesheet" href="../assets/css/preCadastro.css">
</head>
<body>

    <div class="login-layout">
        <!-- Coluna Esquerda: Intro Visual (Consistência com o Login) -->
        <div class="login-intro">
            <a href="../home/index.php" class="brand">SECRETARIA ESCOLAR</a>
            
            <div class="intro-content">
                <span class="eyebrow">Novo Registro</span>
                <h1>Faça parte do sistema.</h1>
                <p>Crie sua solicitação de acesso para acompanhar notas, turmas ou gerenciar a secretaria.</p>
            </div>
            
            <div class="intro-mark">●</div>
        </div>

        <!-- Coluna Direita: Formulário de Cadastro -->
        <div class="login-panel">
            <div class="form-wrap">
                <h2>Pré-cadastro</h2>
                <p class="form-description">Preencha seus dados. Após aprovação do administrador, você poderá entrar no sistema.</p>

                <form action="../controller/UsuarioController.php" method="POST">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" placeholder="Digite seu nome completo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="Digite seu melhor e-mail" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" placeholder="Crie uma senha segura" required>
                    </div>

                    <div class="form-group">
                        <label for="perfil_solicitado">Tipo de Perfil</label>
                        <select id="perfil_solicitado" name="perfil_solicitado" required>
                            <option value="" disabled>Selecione o tipo de perfil</option>
                            <option value="2" selected>Aluno</option>
                            <option value="1">Administrador</option>
                        </select>
                    </div>

                    <button type="submit" name="btn_pre_cadastrar">
                        Solicitar cadastro <span>→</span>
                    </button>
                </form>

               
        

            <footer>
                <a href="../home/index.php" style="color: inherit; text-decoration: none;">← Voltar para a home</a>
            </footer>
        </div>
    </div>

</body>
</html>
