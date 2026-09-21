<?php
// controller/UsuarioController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';

class UsuarioController {

    public function cadastrar($nome, $cpf, $telefone, $dataNascimento, $foto, $email, $senha, $perfil_id) {
        $usuario = new UsuarioDTO();
        $usuario->setNome($nome);
        $usuario->setCpf($cpf);
        $usuario->setTelefone($telefone);
        $usuario->setDataNascimento($dataNascimento);
        $usuario->setFoto3x4($foto);
        $usuario->setEmail($email);

        // Gera o HASH seguro da senha antes de salvar no banco
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $usuario->setSenha($senhaHash);

        $usuario->setPerfilId($perfil_id);

        $dao = new UsuarioDAO();
        return $dao->cadastrar($usuario);
    }

    public function listar() {
        $dao = new UsuarioDAO();
        return $dao->listar();
    }

    public function buscarPorId(int $id) {
        $dao = new UsuarioDAO();
        return $dao->buscarPorId($id);
    }

    public function atualizar($id, $nome, $email, $senha, $perfil_id) {
        $dao = new UsuarioDAO();
        $usuarioAtual = $dao->buscarPorId($id);

        $usuario = new UsuarioDTO();
        $usuario->setId($id);
        $usuario->setNome($nome);
        $usuario->setEmail($email);

        // Se o usuário preencheu uma nova senha, gera novo hash. Caso contrário, mantém a senha atual.
        if (!empty($senha)) {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $usuario->setSenha($senhaHash);
        } else {
            $usuario->setSenha($usuarioAtual['senha']);
        }

        $usuario->setPerfilId($perfil_id);

        return $dao->atualizar($usuario);
    }

    public function excluir(int $id) {
        $dao = new UsuarioDAO();
        return $dao->excluir($id);
    }

    public function desativar(int $id) {
        $dao = new UsuarioDAO();
        return $dao->desativar($id);
    }

    public function ativar(int $id) {
        $dao = new UsuarioDAO();
        return $dao->ativar($id);
    }

    public function autenticar(string $email, string $senhaDigitada) {
        $dao = new UsuarioDAO();
        
        // Busca os dados do usuário apenas pelo e-mail
        $usuario = $dao->buscarPorEmail($email);

        // Compara a senha em texto puro digitada com o HASH armazenado no banco
        if ($usuario && password_verify($senhaDigitada, $usuario['senha'])) {
            
            // Verifica se o usuário está inativo
            if (isset($usuario['status']) && strtoupper($usuario['status']) === 'INATIVO') {
                return 'INATIVO';
            }

            if ((int)$usuario['perfil_id'] === 3) {
                $preMatricula = $dao->buscarPreMatriculaAluno((int)$usuario['id']);
                if (!$preMatricula || $preMatricula['status'] !== 'Aprovado') {
                    return 'AGUARDANDO';
                }
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['perfil_id'] = (int)$usuario['perfil_id'];
            
            return 'OK';
        }

        return 'INVALIDO';
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    public function buscarPreMatricula(int $idUsuario) {
        $dao = new UsuarioDAO();
        return $dao->buscarPreMatriculaAluno($idUsuario);
    }

    public function listarCursosAtivos() {
        $dao = new UsuarioDAO();
        return $dao->listarCursosAtivos();
    }

    public function criarPreMatricula(int $idUsuario, int $idCurso) {
        $dao = new UsuarioDAO();
        return $dao->criarPreMatricula($idUsuario, $idCurso);
    }

    public function iniciarInscricao(string $email, int $idCurso = 0) {
        $dao = new UsuarioDAO();
        $usuario = $dao->buscarPorEmail($email);

        if (!$usuario || (int)$usuario['perfil_id'] !== 3) {
            return false;
        }

        $_SESSION['inscricao_usuario_id'] = (int)$usuario['id'];

        if ($idCurso > 0) {
            if (!$dao->criarPreMatricula((int)$usuario['id'], $idCurso)) {
                return false;
            }
            $preMatricula = $dao->buscarPreMatriculaAluno((int)$usuario['id']);
            $_SESSION['inscricao_pre_id'] = (int)$preMatricula['id_pre_matricula'];
        }

        return true;
    }

    public function listarPreMatriculas() {
        $dao = new UsuarioDAO();
        return $dao->listarPreMatriculas();
    }

    public function analisarPreMatricula(int $idPreMatricula, int $idSecretaria, string $status, string $observacao) {
        $dao = new UsuarioDAO();
        return $dao->analisarPreMatricula($idPreMatricula, $idSecretaria, $status, $observacao);
    }

    public function listarTiposDocumento() {
        $dao = new UsuarioDAO();
        return $dao->listarTiposDocumento();
    }

    public function listarDocumentosPreMatricula(int $idPreMatricula, ?int $idUsuario = null) {
        $dao = new UsuarioDAO();
        return $dao->listarDocumentosPreMatricula($idPreMatricula, $idUsuario);
    }

    public function enviarDocumento(int $idPreMatricula, int $idUsuario, int $idTipoDocumento, array $arquivo) {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }

        if (($arquivo['size'] ?? 0) > 5 * 1024 * 1024) {
            return false;
        }

        $tiposPermitidos = [
            'application/pdf' => '.pdf',
            'image/jpeg' => '.jpg',
            'image/png' => '.png'
        ];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        if (!isset($tiposPermitidos[$mime])) {
            return false;
        }

        $diretorio = __DIR__ . '/../uploads/documentos';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) {
            return false;
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . $tiposPermitidos[$mime];
        $caminhoCompleto = $diretorio . '/' . $nomeArquivo;
        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            return false;
        }

        $caminhoBanco = 'uploads/documentos/' . $nomeArquivo;
        $dao = new UsuarioDAO();
        if (!$dao->enviarDocumento($idPreMatricula, $idUsuario, $idTipoDocumento, $caminhoBanco)) {
            @unlink($caminhoCompleto);
            return false;
        }

        return true;
    }

    public function avaliarDocumento(int $idDocumento, int $idSecretaria, string $status, string $justificativa) {
        $dao = new UsuarioDAO();
        return $dao->avaliarDocumento($idDocumento, $idSecretaria, $status, $justificativa);
    }

    public function enviarTodosDocumentos(int $idPreMatricula, int $idUsuario, array $arquivos) {
        $dao = new UsuarioDAO();
        $documentos = $dao->listarDocumentosPreMatricula($idPreMatricula, $idUsuario);
        $arquivosEnviados = 0;

        if (!$documentos || empty($arquivos['name'])) {
            return false;
        }

        foreach ($documentos as $documento) {
            $idTipo = (int)$documento['id_tipo_documento'];
            $arquivo = [
                'name' => $arquivos['name'][$idTipo] ?? '',
                'type' => $arquivos['type'][$idTipo] ?? '',
                'tmp_name' => $arquivos['tmp_name'][$idTipo] ?? '',
                'error' => $arquivos['error'][$idTipo] ?? UPLOAD_ERR_NO_FILE,
                'size' => $arquivos['size'][$idTipo] ?? 0
            ];

            if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
                if ($documento['id_documento'] && in_array($documento['status'], ['Pendente', 'Em Validacao', 'Deferido'], true)) {
                    continue;
                }
                return false;
            }

            if (!$this->enviarDocumento($idPreMatricula, $idUsuario, $idTipo, $arquivo)) {
                return false;
            }
            $arquivosEnviados++;
        }

        return $arquivosEnviados > 0;
    }

    public function salvarFotoCadastro(array $arquivo) {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($arquivo['size'] ?? 0) > 2 * 1024 * 1024) {
            return false;
        }

        $tiposPermitidos = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png'
        ];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        if (!isset($tiposPermitidos[$mime])) {
            return false;
        }

        $diretorio = __DIR__ . '/../uploads/fotos';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) {
            return false;
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . $tiposPermitidos[$mime];
        if (!move_uploaded_file($arquivo['tmp_name'], $diretorio . '/' . $nomeArquivo)) {
            return false;
        }

        return 'uploads/fotos/' . $nomeArquivo;
    }
}

// Processamento de requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new UsuarioController();

    // Ação de Login (Autenticação)
    if (isset($_GET['acao']) && $_GET['acao'] === 'autenticar') {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        $resultado = $controller->autenticar($email, $senha);

        if ($resultado === 'OK') {
            // Redireciona de acordo com o perfil cadastrado
            if ($_SESSION['perfil_id'] === 1) {
                header('Location: ../index.php');
            } elseif ($_SESSION['perfil_id'] === 3) {
                $preMatricula = $controller->buscarPreMatricula($_SESSION['usuario_id']);
                if ($preMatricula && $preMatricula['status'] === 'Aprovado') {
                    header('Location: ../view/usuario/home_usuario.php');
                } else {
                    header('Location: ../view/usuario/pre_matricula.php');
                }
            } elseif ($_SESSION['perfil_id'] === 2) {
                header('Location: ../view/secretaria/pre_matriculas.php');
            } else {
                header('Location: ../view/usuario/home_usuario.php');
            }
        } elseif ($resultado === 'INATIVO') {
            // Redireciona com o erro de conta inativa
            header('Location: ../view/login.php?erro=inativo');
        } elseif ($resultado === 'AGUARDANDO') {
            header('Location: ../view/login.php?erro=aguardando');
        } else {
            // Redireciona com o erro de e-mail/senha incorretos
            header('Location: ../view/login.php?erro=1');
        }
        exit();
    }

    // Ação de pré-matrícula do aluno
    if (isset($_POST['btn_pre_matricula'])) {
        $idCurso = (int)($_POST['id_curso'] ?? 0);
        $idUsuario = (int)($_SESSION['usuario_id'] ?? $_SESSION['inscricao_usuario_id'] ?? 0);

        if ($idUsuario > 0 && $idCurso > 0) {
            if ($controller->criarPreMatricula($idUsuario, $idCurso)) {
                $preMatricula = $controller->buscarPreMatricula($idUsuario);
                $_SESSION['inscricao_pre_id'] = (int)$preMatricula['id_pre_matricula'];
            }
        }

        header('Location: ../view/usuario/pre_matricula.php?etapa=documentos');
        exit();
    }

    // Análise de pré-matrícula pela secretaria
    if (isset($_POST['btn_analisar_pre_matricula'])) {
        $idSecretaria = (int)($_SESSION['usuario_id'] ?? 0);
        $statusPermitidos = ['Aprovado', 'Indeferido', 'Pendente de Documentacao'];
        $status = $_POST['status'] ?? '';
        $observacao = trim($_POST['observacao'] ?? '');

        if ((int)($_SESSION['perfil_id'] ?? 0) === 2 && in_array($status, $statusPermitidos, true)) {
            $resultadoAnalise = $controller->analisarPreMatricula(
                (int)$_POST['id_pre_matricula'],
                $idSecretaria,
                $status,
                $observacao
            );

            if (!$resultadoAnalise && $status === 'Aprovado') {
                header('Location: ../view/secretaria/pre_matriculas.php?erro=documentos');
                exit();
            }
        }

        header('Location: ../view/secretaria/pre_matriculas.php');
        exit();
    }

    // Envio de todos os documentos pelo aluno
    if (isset($_POST['btn_enviar_documentos'])) {
        $idUsuario = (int)($_SESSION['usuario_id'] ?? $_SESSION['inscricao_usuario_id'] ?? 0);
        $idPreMatricula = (int)($_POST['id_pre_matricula'] ?? 0);

        $sucesso = false;
        if ((int)($_SESSION['perfil_id'] ?? 0) === 3 || isset($_SESSION['inscricao_usuario_id'])) {
            $sucesso = $controller->enviarTodosDocumentos(
                $idPreMatricula,
                $idUsuario,
                $_FILES['documentos'] ?? []
            );
        }

        header('Location: ../view/usuario/pre_matricula.php?etapa=status&documento=' . ($sucesso ? 'ok' : 'erro'));
        exit();
    }

    // Avaliação de documento pela secretaria
    if (isset($_POST['btn_avaliar_documento'])) {
        $statusPermitidos = ['Deferido', 'Indeferido'];
        $status = $_POST['status'] ?? '';
        $sucesso = false;

        if ((int)($_SESSION['perfil_id'] ?? 0) === 2 && in_array($status, $statusPermitidos, true)) {
            $sucesso = $controller->avaliarDocumento(
                (int)$_POST['id_documento'],
                (int)$_SESSION['usuario_id'],
                $status,
                trim($_POST['justificativa_recusa'] ?? '')
            );
        }

        header('Location: ../view/secretaria/documentos.php?resultado=' . ($sucesso ? 'ok' : 'erro'));
        exit();
    }

    // Ação de Cadastrar
    if (isset($_POST['btn_cadastrar'])) {
        $perfilId = 3;
        $senha = $_POST['senha'] ?? '';
        $idCurso = (int)($_POST['id_curso'] ?? 0);
        $foto = $controller->salvarFotoCadastro($_FILES['foto_3x4'] ?? []);
        if ($foto === false) {
            header('Location: ../view/cadastrar_usuario.php?erro=foto');
            exit();
        }
        $sucesso = $controller->cadastrar(
            $_POST['nome'] ?? '',
            $_POST['cpf'] ?? '',
            $_POST['telefone'] ?? '',
            $_POST['data_nascimento'] ?? null,
            $foto,
            $_POST['email'] ?? '',
            $senha,
            $perfilId
        );

        if ($sucesso && $perfilId === 3 && $controller->iniciarInscricao($_POST['email'], $idCurso)) {
            header('Location: ../view/usuario/pre_matricula.php?etapa=' . ($idCurso > 0 ? 'documentos' : 'curso'));
        } elseif ($sucesso) {
            header('Location: ../view/cadastrar_usuario.php?sucesso=1');
        } else {
            header('Location: ../view/cadastrar_usuario.php?erro=cadastro');
        }
        exit();
    }

    // Ação de Atualizar
    if (isset($_POST['btn_atualizar'])) {
        $controller->atualizar($_POST['id'], $_POST['nome'], $_POST['email'], $_POST['senha'], $_POST['perfil_id']);
        header('Location: ../index.php');
        exit();
    }

    // Ação de Excluir
    if (isset($_POST['btn_excluir'])) {
        $controller->excluir((int)$_POST['id']);
        header('Location: ../index.php');
        exit();
    }
}

// Processamento de requisições GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    $controller = new UsuarioController();

    // Ação de Logout
    if ($_GET['acao'] === 'logout') {
        $controller->logout();
        header('Location: ../view/login.php');
        exit();
    }

    // Ações via parâmetro ID
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];

        if ($_GET['acao'] === 'desativar') {
            $controller->desativar($id);
        } elseif ($_GET['acao'] === 'ativar') {
            $controller->ativar($id);
        } elseif ($_GET['acao'] === 'excluir') {
            $controller->excluir($id);
        }

        header('Location: ../index.php');
        exit();
    }
}
?>