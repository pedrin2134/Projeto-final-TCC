            <?php
            require 'model/dao/conexao.php';
            require 'model/dao/UsuarioDAO.php';
            require 'model/dto/UsuarioDTO.php';
            require 'controller/UsuarioController.php';


                // Processa cadastro de usuário
                if (isset($_POST['btn_cadastrar'])) {
                    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
                    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
                    $cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
                    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
                    $endereco = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';
                    $numero = isset($_POST['numero']) ? trim($_POST['numero']) : '';
                    $complemento = isset($_POST['complemento']) ? trim($_POST['complemento']) : '';
                    $bairro = isset($_POST['bairro']) ? trim($_POST['bairro']) : '';
                    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

                    if ($nome === '' || $email === '' || $senha === '') {
                        $msg = "<p style='color:red;'>Preencha todos os campos.</p>";
                    } else {
                        try {
                            $controller = new UsuarioController();
                            $ok = $controller->cadastrar($nome, $email, $cpf, $telefone, $endereco, $numero, $complemento, $bairro , $senha);
                            if ($ok) {
                                $msg = "<p style='color:green;'>Usuário cadastrado com sucesso.</p>";
                            } else {
                                $msg = "<p style='color:red;'>Falha ao cadastrar usuário.</p>";
                            }
                        } catch (Exception $e) {
                            $msga = '<p style="color:red;">Erro no cadastro: ' . $e->getMessage() . "</p>";
                        }
                    }
                }
            ?>

            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Painel</title>
                <link rel="stylesheet" href="css/cadastro.css">


            <body>
                <header>
                    
                <h2>SISTEMA ESCOLAR</h2>


                </header>
            <hr>

            <main>

            <form method="post">
                <h3>Cadastrar Usuário</h3>
        <div>
                <input type="text" name="nome" placeholder="Nome" require><br><br>
                <input type="email" name="email" placeholder="E-mail" require><br><br>
                <input type="text" name="cpf" placeholder="CPF" require><br><br>
                <input type="text" name="telefone" placeholder="Telefone" require><br><br>
                <input type="text" name="endereco" placeholder="Endereço" require><br><br>
                <input type="text" name="numero" placeholder="Número" require><br><br>
                <input type="text" name="complemento" placeholder="Complemento" require><br><br>
                <input type="text" name="bairro" placeholder="Bairro" require><br><br>
                <input type="password" name="senha" placeholder="Senha" require><br><br>
        </div>
        
            <button type="submit" name="btn_cadastrar">Cadastrar</button>

            <?php
            echo "$msg"
            ?>
            

            </form>

        </main>

        
    <footer>
        
    </footer>

            </body>
            </html>