<?php
// controller/UsuarioController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';

class UsuarioController {

    public function podeGerenciarUsuarios(): bool {
        return (int)($_SESSION['perfil_id'] ?? 0) === 1;
    }

    public function listarSecretarias(string $busca = ''): array {
        return (new UsuarioDAO())->listarSecretarias($busca);
    }

    public function buscarSecretariaPorId(int $id): ?array {
        return (new UsuarioDAO())->buscarSecretariaPorId($id);
    }

    public function cadastrarSecretaria(
        string $nome,
        string $cpf,
        string $telefone,
        string $dataNascimento,
        string $email,
        string $senha,
        string $confirmarSenha
    ): string {
        $cpf = preg_replace('/\D/', '', $cpf);
        $telefoneNumeros = preg_replace('/\D/', '', $telefone);
        $dataValida = DateTime::createFromFormat('!Y-m-d', $dataNascimento);

        if ($nome === '' || strlen($nome) > 100 || strlen($cpf) !== 11
            || strlen($telefoneNumeros) < 10 || strlen($telefoneNumeros) > 13
            || strlen($telefone) > 20
            || !$dataValida || $dataValida->format('Y-m-d') !== $dataNascimento
            || $dataValida > new DateTime('today')
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100
            || $senha === '') {
            return 'dados_invalidos';
        }
        if ($senha !== $confirmarSenha) {
            return 'senha_invalida';
        }
        if (!$this->cpfValido($cpf)) {
            return 'cpf_invalido';
        }
        if (!(new UsuarioDAO())->cadastroSecretariaDisponivel($email, $cpf)) {
            return 'duplicado';
        }

        return $this->cadastrar(
            $nome,
            $cpf,
            $telefone,
            $dataNascimento,
            $email,
            $senha,
            2
        ) ? 'ok' : 'erro';
    }

    public function atualizarSecretaria(
        int $id,
        string $nome,
        string $cpf,
        string $telefone,
        string $dataNascimento,
        string $email,
        string $senha,
        string $confirmarSenha
    ): string {
        $dao = new UsuarioDAO();
        if (!$dao->buscarSecretariaPorId($id)) {
            return 'nao_encontrado';
        }

        $cpf = preg_replace('/\D/', '', $cpf);
        $telefoneNumeros = preg_replace('/\D/', '', $telefone);
        $dataValida = DateTime::createFromFormat('!Y-m-d', $dataNascimento);
        if ($nome === '' || strlen($nome) > 100 || strlen($cpf) !== 11
            || strlen($telefoneNumeros) < 10 || strlen($telefoneNumeros) > 13
            || strlen($telefone) > 20
            || !$dataValida || $dataValida->format('Y-m-d') !== $dataNascimento
            || $dataValida > new DateTime('today')
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            return 'dados_invalidos';
        }
        if (!$this->cpfValido($cpf)) {
            return 'cpf_invalido';
        }
        if ($senha !== '' && $senha !== $confirmarSenha) {
            return 'senha_invalida';
        }
        if (!$dao->cadastroSecretariaDisponivel($email, $cpf, $id)) {
            return 'duplicado';
        }

        $usuario = new UsuarioDTO();
        $usuario->setId($id);
        $usuario->setNome($nome);
        $usuario->setCpf($cpf);
        $usuario->setTelefone($telefone);
        $usuario->setDataNascimento($dataNascimento);
        $usuario->setEmail($email);
        $usuario->setSenha($senha === '' ? '' : password_hash($senha, PASSWORD_DEFAULT));
        return $dao->atualizarSecretaria($usuario);
    }

    public function excluirSecretaria(int $id): string {
        return (new UsuarioDAO())->excluirSecretaria($id);
    }

    public function cadastrar($nome, $cpf, $telefone, $dataNascimento, $email, $senha, $perfil_id) {
        $usuario = new UsuarioDTO();
        $usuario->setNome($nome);
        $usuario->setCpf($cpf);
        $usuario->setTelefone($telefone);
        $usuario->setDataNascimento($dataNascimento);
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

    public function obterResumoPainelAdministrador(): array {
        return (new UsuarioDAO())->obterResumoPainelAdministrador();
    }

    public function buscarPorId(int $id) {
        $dao = new UsuarioDAO();
        return $dao->buscarPorId($id);
    }

    public function buscarPerfilProprio(int $idUsuario): ?array {
        if ($idUsuario <= 0 || (int)($_SESSION['usuario_id'] ?? 0) !== $idUsuario) {
            return null;
        }
        return (new UsuarioDAO())->buscarPerfilProprio($idUsuario);
    }

    public function atualizarPerfilProprio(
        int $idUsuario,
        string $nome,
        string $cpfInformado,
        string $telefone,
        string $dataNascimento,
        string $email,
        ?string $fotoPerfil
    ): string {
        if ($idUsuario <= 0 || (int)($_SESSION['usuario_id'] ?? 0) !== $idUsuario) {
            return 'nao_autorizado';
        }

        $cpf = preg_replace('/\D/', '', $cpfInformado);
        $telefoneNumeros = preg_replace('/\D/', '', $telefone);
        $dataValida = DateTime::createFromFormat('!Y-m-d', $dataNascimento);
        if ($nome === '' || mb_strlen($nome, 'UTF-8') > 100 || strlen($cpf) !== 11
            || !$this->cpfValido($cpf)
            || strlen($telefoneNumeros) < 10 || strlen($telefoneNumeros) > 13
            || strlen($telefone) > 20
            || !$dataValida || $dataValida->format('Y-m-d') !== $dataNascimento
            || $dataValida > new DateTime('today')
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            return 'dados_invalidos';
        }

        return (new UsuarioDAO())->atualizarPerfilProprio(
            $idUsuario,
            $nome,
            $cpf,
            $telefone,
            $dataNascimento,
            $email,
            $fotoPerfil
        ) ? 'ok' : 'duplicado';
    }

    public function salvarFotoPerfil(array $arquivo): array {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['resultado' => 'ok', 'caminho' => null];
        }
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !isset($arquivo['tmp_name'], $arquivo['size'])
            || !is_uploaded_file($arquivo['tmp_name'])
            || (int)$arquivo['size'] <= 0 || (int)$arquivo['size'] > 5 * 1024 * 1024) {
            return ['resultado' => 'foto_invalida', 'caminho' => null];
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        $extensoes = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp'
        ];
        $dimensoes = @getimagesize($arquivo['tmp_name']);
        if (!isset($extensoes[$mime]) || $dimensoes === false
            || !in_array($dimensoes['mime'] ?? '', array_keys($extensoes), true)) {
            return ['resultado' => 'foto_invalida', 'caminho' => null];
        }

        $diretorio = __DIR__ . '/../uploads/perfis';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) {
            error_log('Não foi possível criar o diretório de fotos de perfil.');
            return ['resultado' => 'erro', 'caminho' => null];
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . $extensoes[$mime];
        if (!move_uploaded_file($arquivo['tmp_name'], $diretorio . '/' . $nomeArquivo)) {
            error_log('Não foi possível salvar a foto de perfil enviada.');
            return ['resultado' => 'erro', 'caminho' => null];
        }
        return ['resultado' => 'ok', 'caminho' => 'uploads/perfis/' . $nomeArquivo];
    }

    public function removerFotoPerfil(?string $caminho): void {
        if (!$caminho || !preg_match('#^uploads/perfis/[a-f0-9]{32}\.(jpg|png|webp)$#', $caminho)) {
            return;
        }
        $arquivo = __DIR__ . '/../' . $caminho;
        if (is_file($arquivo)) {
            unlink($arquivo);
        }
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

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            unset($_SESSION['inscricao_usuario_id']);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['perfil_id'] = (int)$usuario['perfil_id'];
            $_SESSION['foto_perfil'] = $usuario['foto_perfil'] ?? null;
            
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

    public function listarCursosGerenciamento(string $busca = ''): array {
        return (new UsuarioDAO())->listarCursosGerenciamento($busca);
    }

    public function cadastrarCurso(string $nome, string $sigla, ?string $imagem = null): string {
        return (new UsuarioDAO())->cadastrarCurso($nome, $sigla, $imagem);
    }

    public function atualizarCurso(
        int $idCurso,
        string $nome,
        string $sigla,
        string $status,
        ?string $imagem = null,
        bool $removerImagem = false
    ): string {
        return (new UsuarioDAO())->atualizarCurso(
            $idCurso,
            $nome,
            $sigla,
            $status,
            $imagem,
            $removerImagem
        );
    }

    public function excluirCurso(int $idCurso): string {
        return (new UsuarioDAO())->excluirCurso($idCurso);
    }

    public function buscarImagemCurso(int $idCurso): ?string {
        return (new UsuarioDAO())->buscarImagemCurso($idCurso);
    }

    public function receberImagemCurso(array $arquivo): array {
        $erroUpload = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($erroUpload === UPLOAD_ERR_NO_FILE) {
            return ['resultado' => 'ok', 'caminho' => null];
        }
        if ($erroUpload === UPLOAD_ERR_INI_SIZE || $erroUpload === UPLOAD_ERR_FORM_SIZE) {
            return ['resultado' => 'imagem_tamanho', 'caminho' => null];
        }
        if ($erroUpload !== UPLOAD_ERR_OK
            || !is_string($arquivo['tmp_name'] ?? null)
            || !is_uploaded_file($arquivo['tmp_name'])
            || !is_int($arquivo['size'] ?? null)) {
            return ['resultado' => 'imagem_invalida', 'caminho' => null];
        }
        if ($arquivo['size'] < 1 || $arquivo['size'] > 5 * 1024 * 1024) {
            return ['resultado' => 'imagem_tamanho', 'caminho' => null];
        }

        $informacoes = @getimagesize($arquivo['tmp_name']);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $arquivo['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
        $extensoes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];
        if (!$informacoes || !is_string($mime) || !isset($extensoes[$mime])
            || ($informacoes['mime'] ?? null) !== $mime) {
            return ['resultado' => 'imagem_invalida', 'caminho' => null];
        }

        $diretorio = __DIR__ . '/../uploads/cursos';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
            error_log('Não foi possível criar a pasta de imagens dos cursos.');
            return ['resultado' => 'erro', 'caminho' => null];
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensoes[$mime];
        if (!move_uploaded_file($arquivo['tmp_name'], $diretorio . '/' . $nomeArquivo)) {
            error_log('Não foi possível salvar a imagem do curso enviada.');
            return ['resultado' => 'erro', 'caminho' => null];
        }

        return [
            'resultado' => 'ok',
            'caminho' => 'uploads/cursos/' . $nomeArquivo
        ];
    }

    public function removerImagemCurso(?string $caminho): void {
        if (!$caminho || !preg_match('#^uploads/cursos/[a-f0-9]{32}\.(jpg|png|webp)$#', $caminho)) {
            return;
        }
        $arquivo = __DIR__ . '/../' . $caminho;
        if (is_file($arquivo) && !unlink($arquivo)) {
            error_log('Não foi possível remover uma imagem de curso substituída.');
        }
    }

    public function podeGerenciarCursos(): bool {
        return in_array((int)($_SESSION['perfil_id'] ?? 0), [1, 2], true);
    }

    public function criarPreMatricula(int $idUsuario, int $idCurso) {
        $dao = new UsuarioDAO();
        return $dao->criarPreMatricula($idUsuario, $idCurso);
    }

    public function cadastroDisponivel(string $email, string $cpf): bool {
        return (new UsuarioDAO())->cadastroDisponivel($email, $cpf);
    }

    public function cpfValido(string $cpf): bool {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($digito = 9; $digito < 11; $digito++) {
            $soma = 0;
            for ($indice = 0; $indice < $digito; $indice++) {
                $soma += (int)$cpf[$indice] * (($digito + 1) - $indice);
            }
            $resto = ($soma * 10) % 11;
            $verificador = $resto === 10 ? 0 : $resto;

            if ((int)$cpf[$digito] !== $verificador) {
                return false;
            }
        }

        return true;
    }

    public function iniciarInscricao(string $email) {
        $dao = new UsuarioDAO();
        $usuario = $dao->buscarPorEmail($email);

        if (!$usuario || (int)$usuario['perfil_id'] !== 3) {
            return false;
        }

        $_SESSION['inscricao_usuario_id'] = (int)$usuario['id'];
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

    public function listarDocumentosAluno(int $idUsuario) {
        $dao = new UsuarioDAO();
        return $dao->listarDocumentosAluno($idUsuario);
    }

    public function avaliarDocumento(int $idDocumento, int $idSecretaria, string $status, string $justificativa) {
        $dao = new UsuarioDAO();
        return $dao->avaliarDocumento($idDocumento, $idSecretaria, $status, $justificativa);
    }

    public function listarTiposSolicitacaoAtivos(): array {
        return (new UsuarioDAO())->listarTiposSolicitacaoAtivos();
    }

    public function listarSolicitacoesAluno(int $idAluno): array {
        return (new UsuarioDAO())->listarSolicitacoesAluno($idAluno);
    }

    public function abrirSolicitacao(int $idAluno, int $idTipo, string $descricao): ?string {
        $descricao = trim($descricao);
        if ($idAluno <= 0 || $idTipo <= 0 || $descricao === '' || mb_strlen($descricao, 'UTF-8') > 2000) {
            return null;
        }
        return (new UsuarioDAO())->abrirSolicitacao($idAluno, $idTipo, $descricao);
    }

    public function listarSolicitacoesAtendimento(): array {
        return (new UsuarioDAO())->listarSolicitacoesAtendimento();
    }

    public function atualizarSolicitacao(
        int $idSolicitacao,
        int $idAtendente,
        string $status,
        string $resposta,
        ?string $arquivoResposta = null
    ): bool {
        $statusPermitidos = ['Em Atendimento', 'Concluida', 'Cancelada'];
        $resposta = trim($resposta);
        if ($idSolicitacao <= 0 || $idAtendente <= 0
            || !in_array($status, $statusPermitidos, true)
            || mb_strlen($resposta, 'UTF-8') > 2000
            || ($status === 'Concluida' && $resposta === '')) {
            return false;
        }
        return (new UsuarioDAO())->atualizarSolicitacao(
            $idSolicitacao,
            $idAtendente,
            $status,
            $resposta,
            $arquivoResposta
        );
    }

    public function salvarArquivoSolicitacao(array $arquivo): array {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['resultado' => 'ok', 'caminho' => null];
        }
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !isset($arquivo['tmp_name'], $arquivo['size'])
            || !is_uploaded_file($arquivo['tmp_name'])
            || (int)$arquivo['size'] <= 0 || (int)$arquivo['size'] > 10 * 1024 * 1024) {
            return ['resultado' => 'arquivo_invalido', 'caminho' => null];
        }

        $extensoes = [
            'application/pdf' => '.pdf',
            'image/jpeg' => '.jpg',
            'image/png' => '.png'
        ];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        if (!isset($extensoes[$mime])) {
            return ['resultado' => 'arquivo_invalido', 'caminho' => null];
        }
        if (str_starts_with($mime, 'image/')) {
            $dimensoes = @getimagesize($arquivo['tmp_name']);
            if ($dimensoes === false || ($dimensoes['mime'] ?? '') !== $mime) {
                return ['resultado' => 'arquivo_invalido', 'caminho' => null];
            }
        }

        $diretorio = __DIR__ . '/../uploads/solicitacoes';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) {
            error_log('Não foi possível criar o diretório de arquivos de solicitações.');
            return ['resultado' => 'erro', 'caminho' => null];
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . $extensoes[$mime];
        if (!move_uploaded_file($arquivo['tmp_name'], $diretorio . '/' . $nomeArquivo)) {
            error_log('Não foi possível salvar o documento da solicitação.');
            return ['resultado' => 'erro', 'caminho' => null];
        }
        return ['resultado' => 'ok', 'caminho' => 'uploads/solicitacoes/' . $nomeArquivo];
    }

    public function removerArquivoSolicitacao(?string $caminho): void {
        if (!$caminho || !preg_match('#^uploads/solicitacoes/[a-f0-9]{32}\.(pdf|jpg|png)$#', $caminho)) {
            return;
        }
        $arquivo = __DIR__ . '/../' . $caminho;
        if (is_file($arquivo) && !unlink($arquivo)) {
            error_log('Não foi possível remover um arquivo substituído da solicitação.');
        }
    }

    public function enviarTodosDocumentos(int $idUsuario, array $arquivos): string {
        $dao = new UsuarioDAO();
        $documentos = $dao->listarDocumentosAluno($idUsuario);
        $arquivosParaSalvar = [];

        if (!$documentos || !isset($arquivos['name'], $arquivos['error'], $arquivos['tmp_name'], $arquivos['size'])
            || !is_array($arquivos['name']) || !is_array($arquivos['error'])
            || !is_array($arquivos['tmp_name']) || !is_array($arquivos['size'])) {
            return 'erro';
        }

        foreach ($documentos as $documento) {
            $idTipo = (int)$documento['id_tipo_documento'];
            $arquivo = [
                'name' => $arquivos['name'][$idTipo] ?? '',
                'tmp_name' => $arquivos['tmp_name'][$idTipo] ?? '',
                'error' => $arquivos['error'][$idTipo] ?? UPLOAD_ERR_NO_FILE,
                'size' => $arquivos['size'][$idTipo] ?? 0
            ];

            if (!is_string($arquivo['tmp_name']) || !is_numeric($arquivo['size'])
                || !is_int($arquivo['error'])) {
                return 'arquivo_invalido';
            }

            if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
                if ($documento['caminho_arquivo'] && $documento['status'] !== 'Indeferido') {
                    continue;
                }
                return 'faltante';
            }

            if ($arquivo['error'] !== UPLOAD_ERR_OK || $arquivo['size'] > 5 * 1024 * 1024
                || !is_uploaded_file($arquivo['tmp_name'])) {
                return 'arquivo_invalido';
            }

            $tiposPermitidos = [
                'application/pdf' => '.pdf',
                'image/jpeg' => '.jpg',
                'image/png' => '.png'
            ];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
            if (!isset($tiposPermitidos[$mime])
                || ($documento['tipo_nome'] === 'Foto 3x4' && $mime === 'application/pdf')) {
                return 'arquivo_invalido';
            }

            if ($documento['tipo_nome'] === 'Foto 3x4') {
                $dimensoes = @getimagesize($arquivo['tmp_name']);
                if ($dimensoes === false) {
                    return 'foto_invalida';
                }

                [$largura, $altura] = $dimensoes;
                $proporcao = $largura / $altura;
                if ($largura < 300 || $altura < 400 || $altura <= $largura
                    || abs($proporcao - 0.75) > 0.015) {
                    return 'foto_invalida';
                }
            }

            $arquivosParaSalvar[$idTipo] = $arquivo;
        }

        if (!$arquivosParaSalvar) {
            return 'ok';
        }

        $diretorio = __DIR__ . '/../uploads/documentos';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) {
            error_log('Não foi possível criar o diretório de documentos enviados.');
            return 'erro';
        }

        $caminhosSalvos = [];
        $arquivosMovidos = [];
        foreach ($arquivosParaSalvar as $idTipo => $arquivo) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
            $extensao = $mime === 'application/pdf' ? '.pdf' : ($mime === 'image/png' ? '.png' : '.jpg');
            $nomeArquivo = bin2hex(random_bytes(16)) . $extensao;
            $caminhoCompleto = $diretorio . '/' . $nomeArquivo;

            if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                foreach ($arquivosMovidos as $caminho) {
                    unlink($caminho);
                }
                error_log('Não foi possível armazenar um documento enviado pelo aluno.');
                return 'erro';
            }

            $arquivosMovidos[] = $caminhoCompleto;
            $caminhosSalvos[$idTipo] = 'uploads/documentos/' . $nomeArquivo;
        }

        if (!$dao->salvarDocumentosAluno($idUsuario, $caminhosSalvos)) {
            foreach ($arquivosMovidos as $caminho) {
                unlink($caminho);
            }
            return 'erro';
        }

        return 'ok';
    }

}

// Processamento de requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new UsuarioController();

    if (isset($_POST['btn_atualizar_perfil'])) {
        $idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
        if ($idUsuario <= 0 || !in_array((int)($_SESSION['perfil_id'] ?? 0), [1, 2, 3], true)) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }

        $perfilAtual = $controller->buscarPerfilProprio($idUsuario);
        if (!$perfilAtual) {
            header('Location: ../view/perfil.php?resultado=erro');
            exit();
        }

        $upload = $controller->salvarFotoPerfil($_FILES['foto_perfil'] ?? []);
        if ($upload['resultado'] !== 'ok') {
            header('Location: ../view/perfil.php?resultado=' . $upload['resultado']);
            exit();
        }

        $resultado = 'erro';
        try {
            $resultado = $controller->atualizarPerfilProprio(
                $idUsuario,
                is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '',
                is_string($_POST['cpf'] ?? null) ? $_POST['cpf'] : '',
                is_string($_POST['telefone'] ?? null) ? trim($_POST['telefone']) : '',
                is_string($_POST['data_nascimento'] ?? null) ? $_POST['data_nascimento'] : '',
                is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '',
                $upload['caminho']
            );
        } catch (RuntimeException $e) {
            error_log('Erro ao salvar perfil próprio: ' . $e->getMessage());
            $resultado = 'erro';
        }

        if ($resultado === 'ok') {
            $_SESSION['usuario_nome'] = trim((string)$_POST['nome']);
            $_SESSION['usuario_email'] = trim((string)$_POST['email']);
            if ($upload['caminho'] !== null) {
                $_SESSION['foto_perfil'] = $upload['caminho'];
                $controller->removerFotoPerfil($perfilAtual['foto_perfil'] ?? null);
            }
        } else {
            $controller->removerFotoPerfil($upload['caminho']);
        }

        header('Location: ../view/perfil.php?resultado=' . $resultado);
        exit();
    }

    if (isset($_POST['btn_solicitar_documento'])) {
        $idUsuario = (int)($_SESSION['usuario_id'] ?? 0);
        $idTipo = filter_var($_POST['id_tipo_solicitacao'] ?? null, FILTER_VALIDATE_INT);
        $descricao = is_string($_POST['descricao'] ?? null) ? trim($_POST['descricao']) : '';

        if ((int)($_SESSION['perfil_id'] ?? 0) !== 3 || $idUsuario <= 0) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }

        try {
            $protocolo = $idTipo && $idTipo > 0
                ? $controller->abrirSolicitacao($idUsuario, $idTipo, $descricao)
                : null;
        } catch (RuntimeException $e) {
            error_log('Falha ao registrar pedido do aluno: ' . $e->getMessage());
            $protocolo = null;
        }

        header('Location: ../view/usuario/solicitar_documentos.php?resultado=' . ($protocolo !== null ? 'ok' : 'erro'));
        exit();
    }

    if (isset($_POST['btn_atualizar_solicitacao'])) {
        $perfil = (int)($_SESSION['perfil_id'] ?? 0);
        if (!in_array($perfil, [1, 2], true)) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }

        $idSolicitacao = filter_var($_POST['id_solicitacao'] ?? null, FILTER_VALIDATE_INT);
        $status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
        $resposta = is_string($_POST['resposta'] ?? null) ? $_POST['resposta'] : '';
        if (!$idSolicitacao || $idSolicitacao < 1) {
            header('Location: ../view/secretaria/solicitacoes.php?resultado=erro');
            exit();
        }

        $upload = $controller->salvarArquivoSolicitacao($_FILES['arquivo_resposta'] ?? []);
        if ($upload['resultado'] !== 'ok') {
            header('Location: ../view/secretaria/solicitacoes.php?resultado=' . $upload['resultado']);
            exit();
        }

        $arquivoAnterior = null;
        if ($upload['caminho'] !== null) {
            try {
                $solicitacaoAtual = (new UsuarioDAO())->buscarArquivoSolicitacao((int)$idSolicitacao);
                $arquivoAnterior = $solicitacaoAtual['arquivo_resposta'] ?? null;
            } catch (RuntimeException $e) {
                error_log('Falha ao verificar arquivo atual da solicitação: ' . $e->getMessage());
                $controller->removerArquivoSolicitacao($upload['caminho']);
                header('Location: ../view/secretaria/solicitacoes.php?resultado=erro');
                exit();
            }
        }
        try {
            $sucesso = $idSolicitacao && $idSolicitacao > 0
                ? $controller->atualizarSolicitacao(
                    $idSolicitacao,
                    (int)$_SESSION['usuario_id'],
                    $status,
                    $resposta,
                    $upload['caminho']
                )
                : false;
        } catch (RuntimeException $e) {
            error_log('Falha ao atualizar pedido de documento: ' . $e->getMessage());
            $sucesso = false;
        }

        if ($sucesso && $upload['caminho'] !== null) {
            $controller->removerArquivoSolicitacao($arquivoAnterior);
        } elseif (!$sucesso) {
            $controller->removerArquivoSolicitacao($upload['caminho']);
        }
        header('Location: ../view/secretaria/solicitacoes.php?resultado=' . ($sucesso ? 'ok' : 'erro'));
        exit();
    }

    if (isset($_POST['btn_cadastrar_secretaria'])) {
        if (!$controller->podeGerenciarUsuarios()) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }
        $resultado = $controller->cadastrarSecretaria(
            is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '',
            is_string($_POST['cpf'] ?? null) ? $_POST['cpf'] : '',
            is_string($_POST['telefone'] ?? null) ? trim($_POST['telefone']) : '',
            is_string($_POST['data_nascimento'] ?? null) ? $_POST['data_nascimento'] : '',
            is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '',
            is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '',
            is_string($_POST['confirmar_senha'] ?? null) ? $_POST['confirmar_senha'] : ''
        );
        header('Location: ../view/admin/secretarias.php?resultado=' . $resultado);
        exit();
    }

    if (isset($_POST['btn_atualizar_secretaria'])) {
        if (!$controller->podeGerenciarUsuarios()) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $resultado = $id && $id > 0
            ? $controller->atualizarSecretaria(
                $id,
                is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '',
                is_string($_POST['cpf'] ?? null) ? $_POST['cpf'] : '',
                is_string($_POST['telefone'] ?? null) ? trim($_POST['telefone']) : '',
                is_string($_POST['data_nascimento'] ?? null) ? $_POST['data_nascimento'] : '',
                is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '',
                is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '',
                is_string($_POST['confirmar_senha'] ?? null) ? $_POST['confirmar_senha'] : ''
            )
            : 'dados_invalidos';
        header('Location: ../view/admin/secretarias.php?resultado=' . $resultado);
        exit();
    }

    if (isset($_POST['btn_excluir_secretaria'])) {
        if (!$controller->podeGerenciarUsuarios()) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $resultado = $id && $id > 0
            ? $controller->excluirSecretaria($id)
            : 'dados_invalidos';
        header('Location: ../view/admin/secretarias.php?resultado=' . $resultado);
        exit();
    }

    if (isset($_POST['btn_cadastrar_curso'])) {
        if (!$controller->podeGerenciarCursos()) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }

        $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '';
        $sigla = is_string($_POST['sigla'] ?? null) ? strtoupper(trim($_POST['sigla'])) : '';
        if ($nome === '' || strlen($nome) > 100 || !preg_match('/^[A-Z0-9]{2,10}$/', $sigla)) {
            header('Location: ../view/cursos.php?resultado=dados_invalidos');
            exit();
        }

        $upload = $controller->receberImagemCurso($_FILES['imagem'] ?? []);
        if ($upload['resultado'] !== 'ok') {
            header('Location: ../view/cursos.php?resultado=' . $upload['resultado']);
            exit();
        }

        $resultado = $controller->cadastrarCurso($nome, $sigla, $upload['caminho']);
        if ($resultado !== 'ok') {
            $controller->removerImagemCurso($upload['caminho']);
        }
        header('Location: ../view/cursos.php?resultado=' . $resultado);
        exit();
    }

    if (isset($_POST['btn_atualizar_curso'])) {
        if (!$controller->podeGerenciarCursos()) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }

        $idCurso = filter_var($_POST['id_curso'] ?? null, FILTER_VALIDATE_INT);
        $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '';
        $sigla = is_string($_POST['sigla'] ?? null) ? strtoupper(trim($_POST['sigla'])) : '';
        $status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
        $removerImagem = ($_POST['remover_imagem'] ?? '') === '1';
        if (!$idCurso || $idCurso < 1 || $nome === '' || strlen($nome) > 100
            || !preg_match('/^[A-Z0-9]{2,10}$/', $sigla)
            || !in_array($status, ['Ativo', 'Inativo'], true)) {
            header('Location: ../view/cursos.php?resultado=dados_invalidos');
            exit();
        }

        $upload = $controller->receberImagemCurso($_FILES['imagem'] ?? []);
        if ($upload['resultado'] !== 'ok') {
            header('Location: ../view/cursos.php?editar=' . $idCurso . '&resultado=' . $upload['resultado']);
            exit();
        }
        if ($removerImagem && $upload['caminho'] !== null) {
            $controller->removerImagemCurso($upload['caminho']);
            header('Location: ../view/cursos.php?editar=' . $idCurso . '&resultado=imagem_invalida');
            exit();
        }

        $imagemAnterior = $controller->buscarImagemCurso($idCurso);
        $resultado = $controller->atualizarCurso(
            $idCurso,
            $nome,
            $sigla,
            $status,
            $upload['caminho'],
            $removerImagem
        );
        if ($resultado === 'ok') {
            if ($upload['caminho'] !== null || $removerImagem) {
                $controller->removerImagemCurso($imagemAnterior);
            }
            $resultado = 'alterado';
        } else {
            $controller->removerImagemCurso($upload['caminho']);
        }
        header('Location: ../view/cursos.php?resultado=' . $resultado);
        exit();
    }

    if (isset($_POST['btn_excluir_curso'])) {
        if (!$controller->podeGerenciarCursos()) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }

        $idCurso = filter_var($_POST['id_curso'] ?? null, FILTER_VALIDATE_INT);
        if (!$idCurso || $idCurso < 1) {
            header('Location: ../view/cursos.php?resultado=dados_invalidos');
            exit();
        }
        $imagem = $controller->buscarImagemCurso($idCurso);
        $resultado = $controller->excluirCurso($idCurso);
        if ($resultado === 'ok') {
            $controller->removerImagemCurso($imagem);
            $resultado = 'excluido';
        }
        header('Location: ../view/cursos.php?resultado=' . $resultado);
        exit();
    }

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
        $perfilAluno = (int)($_SESSION['perfil_id'] ?? 0) === 3
            || isset($_SESSION['inscricao_usuario_id']);

        if ($perfilAluno && $idUsuario > 0 && $idCurso > 0) {
            if ($controller->criarPreMatricula($idUsuario, $idCurso)) {
                header('Location: ../view/usuario/pre_matricula.php?etapa=status');
                exit();
            }
        }

        header('Location: ../view/usuario/pre_matricula.php?etapa=curso&erro=curso');
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
        $perfilAluno = (int)($_SESSION['perfil_id'] ?? 0) === 3
            || isset($_SESSION['inscricao_usuario_id']);
        $resultado = $perfilAluno && $idUsuario > 0
            ? $controller->enviarTodosDocumentos($idUsuario, $_FILES['documentos'] ?? [])
            : 'erro';
        $preMatricula = $idUsuario > 0 ? $controller->buscarPreMatricula($idUsuario) : null;
        $etapa = !$preMatricula && $resultado === 'ok' ? 'curso' : 'documentos';

        header('Location: ../view/usuario/pre_matricula.php?etapa=' . $etapa . '&documento=' . $resultado);
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

        header('Location: ../view/secretaria/pre_matriculas.php?resultado=' . ($sucesso ? 'ok' : 'erro'));
        exit();
    }

    // Ação de Cadastrar
    if (isset($_POST['btn_cadastrar'])) {
        $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '';
        $cpfInformado = is_string($_POST['cpf'] ?? null) ? $_POST['cpf'] : '';
        $cpf = preg_replace('/\D/', '', $cpfInformado);
        $telefone = is_string($_POST['telefone'] ?? null) ? trim($_POST['telefone']) : '';
        $dataNascimento = is_string($_POST['data_nascimento'] ?? null) ? $_POST['data_nascimento'] : '';
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
        $confirmarSenha = is_string($_POST['confirmar_senha'] ?? null) ? $_POST['confirmar_senha'] : '';
        $telefoneNumeros = preg_replace('/\D/', '', $telefone);
        $dataValida = DateTime::createFromFormat('!Y-m-d', $dataNascimento);
        $erro = '';

        if (empty($_POST['aceite_lgpd']) || $_POST['aceite_lgpd'] !== '1') {
            $erro = 'consentimento';
        } elseif ($nome === '' || strlen($nome) > 100 || strlen($cpf) !== 11
            || strlen($telefoneNumeros) < 10 || strlen($telefoneNumeros) > 13
            || strlen($telefone) > 20
            || !$dataValida || $dataValida->format('Y-m-d') !== $dataNascimento
            || $dataValida > new DateTime('today')) {
            $erro = 'dados';
        } elseif (!$controller->cpfValido($cpf)) {
            $erro = 'cpf';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $erro = 'email';
        } elseif ($senha === '' || $senha !== $confirmarSenha) {
            $erro = 'senha';
        } elseif (!$controller->cadastroDisponivel($email, $cpf)) {
            $erro = 'cadastro';
        }

        if ($erro !== '') {
            $_SESSION['cadastro_formulario'] = [
                'nome' => $nome,
                'cpf' => $cpfInformado,
                'telefone' => $telefone,
                'data_nascimento' => $dataNascimento,
                'email' => $email
            ];
            header('Location: ../view/cadastrar_usuario.php?erro=' . $erro);
            exit();
        }

        $sucesso = $controller->cadastrar(
            $nome,
            $cpf,
            $telefone,
            $dataNascimento,
            $email,
            $senha,
            3
        );

        if ($sucesso && $controller->iniciarInscricao($email)) {
            unset($_SESSION['cadastro_formulario']);
            header('Location: ../view/usuario/pre_matricula.php?etapa=documentos');
            exit();
        }

        $_SESSION['cadastro_formulario'] = [
            'nome' => $nome,
            'cpf' => $cpfInformado,
            'telefone' => $telefone,
            'data_nascimento' => $dataNascimento,
            'email' => $email
        ];
        header('Location: ../view/cadastrar_usuario.php?erro=cadastro');
        exit();
    }

    // Ação de Atualizar
    if (isset($_POST['btn_atualizar'])) {
        $controller->atualizar($_POST['id'], $_POST['nome'], $_POST['email'], $_POST['senha'], $_POST['perfil_id']);
        header('Location: ../view/admin/gerenciar_usuarios.php');
        exit();
    }

    // Ação de Excluir
    if (isset($_POST['btn_excluir'])) {
        $controller->excluir((int)$_POST['id']);
        header('Location: ../view/admin/gerenciar_usuarios.php');
        exit();
    }
}

// Processamento de requisições GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    $controller = new UsuarioController();

    if ($_GET['acao'] === 'baixar_arquivo_solicitacao') {
        $idSolicitacao = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $perfil = (int)($_SESSION['perfil_id'] ?? 0);
        if (!$idSolicitacao || $idSolicitacao < 1
            || empty($_SESSION['usuario_id'])
            || !in_array($perfil, [1, 2, 3], true)) {
            http_response_code(403);
            exit('Acesso não autorizado.');
        }

        try {
            $arquivo = (new UsuarioDAO())->buscarArquivoSolicitacao((int)$idSolicitacao);
        } catch (RuntimeException $e) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }
        if (!$arquivo || empty($arquivo['arquivo_resposta'])
            || ($perfil === 3 && (int)$arquivo['id_aluno'] !== (int)$_SESSION['usuario_id'])
            || !preg_match('#^uploads/solicitacoes/[a-f0-9]{32}\.(pdf|jpg|png)$#', $arquivo['arquivo_resposta'])) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        $caminhoArquivo = __DIR__ . '/../' . $arquivo['arquivo_resposta'];
        if (!is_file($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($caminhoArquivo);
        $mimePermitidos = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mime, $mimePermitidos, true)) {
            http_response_code(415);
            exit('Tipo de arquivo não permitido.');
        }

        $nomeDownload = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$arquivo['numero_protocolo'])
            . '.' . pathinfo($caminhoArquivo, PATHINFO_EXTENSION);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($caminhoArquivo));
        header('Content-Disposition: attachment; filename="' . $nomeDownload . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($caminhoArquivo);
        exit();
    }

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

        header('Location: ../view/admin/gerenciar_usuarios.php');
        exit();
    }
}
?>