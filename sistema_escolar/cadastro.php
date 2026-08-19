            <?php
            require_once __DIR__ . '/model/dao/conexao.php';
            require_once __DIR__ . '/model/dao/UsuarioDAO.php';
            require_once __DIR__ . '/model/dto/UsuarioDTO.php';
            require_once __DIR__ . '/controller/UsuarioController.php';

            $msg = '';

            if (isset($_POST['btn_cadastrar'])) {
                $nome = trim($_POST['nome'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $cpf = trim($_POST['cpf'] ?? '');
                $telefone = trim($_POST['telefone'] ?? '');
                $endereco = trim($_POST['endereco'] ?? '');
                $numero = trim($_POST['numero'] ?? '');
                $complemento = trim($_POST['complemento'] ?? '');
                $bairro = trim($_POST['bairro'] ?? '');
                $senha = trim($_POST['senha'] ?? '');

                if ($nome === '' || $email === '' || $senha === '') {
                    $msg = 'Preencha todos os campos obrigatórios.';
                } else {
                    try {
                        $controller = new UsuarioController();
                        $ok = $controller->cadastrar(
                            $nome,
                            $email,
                            $cpf,
                            $telefone,
                            $endereco,
                            $numero,
                            $complemento,
                            $bairro,
                            $senha
                        );

                        $msg = $ok
                            ? 'Usuário cadastrado com sucesso.'
                            : 'Falha ao cadastrar usuário.';
                    } catch (Exception $e) {
                        $msg = 'Erro no cadastro: ' . $e->getMessage();
                    }
                }
            }

            require __DIR__ . '/view/viewCadastro.php';