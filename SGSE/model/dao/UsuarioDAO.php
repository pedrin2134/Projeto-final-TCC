<?php
// model/dao/UsuarioDAO.php

require_once __DIR__ . '/Conexao.php';

class UsuarioDAO {

    // Método para buscar o usuário por e-mail (usado pela autenticação)
    public function buscarPorEmail(string $email) {
        try {
            $sql = "SELECT u.id_usuario AS id, p.nome, p.cpf, u.email, u.senha,
                           u.foto_perfil,
                           u.id_perfil AS perfil_id, u.status
                    FROM usuarios u
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                    WHERE u.email = ?
                    LIMIT 1";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            echo "Erro ao buscar usuário por e-mail: " . $e->getMessage();
            return null;
        }
    }

    // Método para autenticar/validar o login do usuário
    public function autenticar(string $email, string $senha) {
        try {
            $usuario = $this->buscarPorEmail($email);

            if ($usuario) {
                // Impede o login caso a conta esteja inativa
                if (strtoupper($usuario['status'] ?? '') === 'INATIVO') {
                    return false;
                }

                // Validação estrita via hash
                if (password_verify($senha, $usuario['senha'])) {
                    return $usuario;
                }
            }

            return false;
        } catch (PDOException $e) {
            echo "Erro ao autenticar: " . $e->getMessage();
            return false;
        }
    }

    // Insere um novo usuário (a senha já chega criptografada pelo controller)
    public function cadastrar(UsuarioDTO $u) {
        $conexao = Conexao::getConexao();

        try {
            $conexao->beginTransaction();

            $stmtPessoa = $conexao->prepare(
                "INSERT INTO pessoas (nome, cpf, telefone, data_nascimento)
                 VALUES (?, ?, ?, ?)"
            );
            $stmtPessoa->execute([
                $u->getNome(),
                $u->getCpf(),
                $u->getTelefone(),
                $u->getDataNascimento()
            ]);

            $stmtUsuario = $conexao->prepare(
                "INSERT INTO usuarios (id_pessoa, id_perfil, email, senha)
                 VALUES (?, ?, ?, ?)"
            );
            $stmtUsuario->execute([
                $conexao->lastInsertId(),
                $u->getPerfilId(),
                $u->getEmail(),
                $u->getSenha()
            ]);

            $conexao->commit();
            return true;
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
            return false;
        }
    }

    public function cadastroDisponivel(string $email, string $cpf): bool {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT COUNT(*)
                 FROM usuarios u
                 INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                 WHERE u.email = ?
                    OR REPLACE(REPLACE(REPLACE(p.cpf, '.', ''), '-', ''), ' ', '') = ?"
            );
            $stmt->execute([$email, $cpf]);
            return (int)$stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            error_log('Erro ao validar disponibilidade do cadastro: ' . $e->getMessage());
            return false;
        }
    }

    public function cadastroSecretariaDisponivel(string $email, string $cpf, ?int $idUsuario = null): bool {
        try {
            $sql = "SELECT COUNT(*)
                    FROM usuarios u
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                    WHERE (u.email = ?
                       OR REPLACE(REPLACE(REPLACE(p.cpf, '.', ''), '-', ''), ' ', '') = ?)";
            $params = [$email, $cpf];
            if ($idUsuario !== null) {
                $sql .= " AND u.id_usuario <> ?";
                $params[] = $idUsuario;
            }
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            error_log('Erro ao validar os dados da secretaria: ' . $e->getMessage());
            return false;
        }
    }

    public function listarSecretarias(string $busca = ''): array {
        try {
            $sql = "SELECT u.id_usuario AS id, p.nome, p.cpf, p.telefone,
                               p.data_nascimento, u.email, u.foto_perfil, u.status
                    FROM usuarios u
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                    WHERE u.id_perfil = 2";
            $params = [];
            if ($busca !== '') {
                $sql .= " AND (p.nome LIKE ? OR u.email LIKE ?)";
                $termo = '%' . $busca . '%';
                $params = [$termo, $termo];
            }
            $sql .= " ORDER BY p.nome, u.id_usuario";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao listar secretarias: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar as secretarias.', 0, $e);
        }
    }

    public function buscarSecretariaPorId(int $id): ?array {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT u.id_usuario AS id, p.nome, p.cpf, p.telefone,
                        p.data_nascimento, u.email, u.foto_perfil, u.status
                 FROM usuarios u
                 INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                 WHERE u.id_usuario = ? AND u.id_perfil = 2"
            );
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Erro ao consultar secretaria: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível consultar a secretaria.', 0, $e);
        }
    }

    public function atualizarSecretaria(UsuarioDTO $usuario): string {
        try {
            $sql = "UPDATE usuarios u
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                    SET p.nome = ?, p.cpf = ?, p.telefone = ?, p.data_nascimento = ?,
                        u.email = ?";
            $params = [
                $usuario->getNome(),
                $usuario->getCpf(),
                $usuario->getTelefone(),
                $usuario->getDataNascimento(),
                $usuario->getEmail()
            ];
            if ($usuario->getSenha() !== '') {
                $sql .= ", u.senha = ?";
                $params[] = $usuario->getSenha();
            }
            $sql .= " WHERE u.id_usuario = ? AND u.id_perfil = 2";
            $params[] = $usuario->getId();
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute($params);
            return 'ok';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return 'duplicado';
            }
            error_log('Erro ao atualizar secretaria: ' . $e->getMessage());
            return 'erro';
        }
    }

    public function excluirSecretaria(int $id): string {
        $conexao = Conexao::getConexao();
        try {
            $conexao->beginTransaction();

            $stmtSecretaria = $conexao->prepare(
                "SELECT id_pessoa FROM usuarios
                 WHERE id_usuario = ? AND id_perfil = 2
                 FOR UPDATE"
            );
            $stmtSecretaria->execute([$id]);
            $idPessoa = $stmtSecretaria->fetchColumn();
            if ($idPessoa === false) {
                $conexao->rollBack();
                return 'nao_encontrado';
            }

            $stmtVinculos = $conexao->prepare(
                "SELECT
                    (SELECT COUNT(*) FROM documentos_enviados WHERE id_usuario_aluno = ? OR avaliado_por = ?) +
                    (SELECT COUNT(*) FROM pre_matricula WHERE id_usuario = ? OR analisado_por = ?) +
                    (SELECT COUNT(*) FROM matriculas WHERE id_usuario = ?) +
                    (SELECT COUNT(*) FROM pendencias
                     WHERE id_usuario_aluno = ? OR id_usuario_secretaria = ? OR resolvido_por = ?) +
                    (SELECT COUNT(*) FROM solicitacoes WHERE id_aluno = ? OR atendido_por = ?) +
                    (SELECT COUNT(*) FROM declaracoes_emitidas WHERE emitido_por = ?)"
            );
            $stmtVinculos->execute(array_fill(0, 11, $id));
            if ((int)$stmtVinculos->fetchColumn() > 0) {
                $conexao->rollBack();
                return 'vinculado';
            }

            $stmtUsuario = $conexao->prepare(
                "DELETE FROM usuarios WHERE id_usuario = ? AND id_perfil = 2"
            );
            $stmtUsuario->execute([$id]);
            if ($stmtUsuario->rowCount() === 0) {
                $conexao->rollBack();
                return 'nao_encontrado';
            }

            $stmtPessoa = $conexao->prepare("DELETE FROM pessoas WHERE id_pessoa = ?");
            $stmtPessoa->execute([$idPessoa]);
            $conexao->commit();
            return 'ok';
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            if ($e->getCode() === '23000') {
                return 'vinculado';
            }
            error_log('Erro ao excluir secretaria: ' . $e->getMessage());
            return 'erro';
        }
    }

    public function listar() {
        try {
            $sql = "SELECT u.id_usuario AS id, p.nome, u.email, u.foto_perfil,
                           u.id_perfil AS perfil_id, u.status
                    FROM usuarios u
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                    ORDER BY u.id_usuario";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro ao buscar usuários:<br>". $e->getMessage();
            return [];
        }
    }

    public function obterResumoPainelAdministrador(): array {
        try {
            $conexao = Conexao::getConexao();
            $resumo = $conexao->query(
                "SELECT
                    (SELECT COUNT(*) FROM usuarios) AS total_usuarios,
                    (SELECT COUNT(*) FROM usuarios WHERE id_perfil = 3) AS total_alunos,
                    (SELECT COUNT(*) FROM matriculas WHERE status = 'Ativo') AS matriculas_ativas,
                    (SELECT COUNT(*) FROM cursos WHERE status = 'Ativo') AS cursos_ativos"
            )->fetch(PDO::FETCH_ASSOC);

            $recentes = $conexao->query(
                "SELECT pm.id_pre_matricula, pm.status, pm.data_inscricao,
                        p.nome AS aluno_nome, c.nome AS curso_nome
                 FROM pre_matricula pm
                 INNER JOIN usuarios u ON u.id_usuario = pm.id_usuario
                 INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                 INNER JOIN cursos c ON c.id_curso = pm.id_curso
                 ORDER BY pm.data_inscricao DESC, pm.id_pre_matricula DESC
                 LIMIT 5"
            )->fetchAll(PDO::FETCH_ASSOC);

            $statusPreMatriculas = $conexao->query(
                "SELECT status, COUNT(*) AS quantidade
                 FROM pre_matricula
                 GROUP BY status"
            )->fetchAll(PDO::FETCH_KEY_PAIR);

            return [
                'resumo' => $resumo,
                'recentes' => $recentes,
                'status_pre_matriculas' => $statusPreMatriculas
            ];
        } catch (PDOException $e) {
            error_log('Erro ao carregar o painel do administrador: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar os dados do painel.', 0, $e);
        }
    }

    public function listarNotificacoesAdministrador(): array {
        try {
            $stmt = Conexao::getConexao()->query(
                "SELECT notificacoes.id, notificacoes.tipo, notificacoes.titulo,
                        notificacoes.descricao, notificacoes.criado_em
                 FROM (
                    SELECT CONCAT('solicitacao-', s.id_solicitacao) AS id,
                           'solicitacao' AS tipo,
                           CONCAT('Solicitação #', s.numero_protocolo) AS titulo,
                           CONCAT(ts.nome, ' solicitada por ', p.nome, '.') AS descricao,
                           s.data_abertura AS criado_em
                    FROM solicitacoes s
                    INNER JOIN tipos_solicitacao ts
                      ON ts.id_tipo_solicitacao = s.id_tipo_solicitacao
                    INNER JOIN usuarios u ON u.id_usuario = s.id_aluno
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa

                    UNION ALL

                    SELECT CONCAT('pre-matricula-', pm.id_pre_matricula) AS id,
                           'pre_matricula' AS tipo,
                           'Nova pré-matrícula' AS titulo,
                           CONCAT(p.nome, ' fez pré-matrícula no curso de ', c.sigla, '.') AS descricao,
                           pm.data_inscricao AS criado_em
                    FROM pre_matricula pm
                    INNER JOIN usuarios u ON u.id_usuario = pm.id_usuario
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                    INNER JOIN cursos c ON c.id_curso = pm.id_curso
                 ) AS notificacoes
                 ORDER BY notificacoes.criado_em DESC
                 LIMIT 10"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao carregar notificações administrativas: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar as notificações.', 0, $e);
        }
    }

    public function obterContadoresMenuAdministrador(): array {
        try {
            $stmt = Conexao::getConexao()->query(
                "SELECT
                    (SELECT COUNT(*) FROM usuarios) AS usuarios,
                    (SELECT COUNT(*) FROM cursos WHERE status = 'Ativo') AS cursos,
                    (SELECT COUNT(*) FROM solicitacoes
                     WHERE status IN ('Aberta', 'Em Atendimento')) AS solicitacoes"
            );
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'usuarios' => 0,
                'cursos' => 0,
                'solicitacoes' => 0
            ];
        } catch (PDOException $e) {
            error_log('Erro ao carregar contadores do menu administrativo: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar os contadores do menu.', 0, $e);
        }
    }

    public function buscarPorId(int $id) {
        try {
            $sql = "SELECT u.id_usuario AS id, p.nome, p.cpf, u.email, u.senha,
                           u.id_perfil AS perfil_id, u.status
                    FROM usuarios u
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                    WHERE u.id_usuario = ?";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            echo "Erro MYSQL: " . $e->getMessage();
            return null;
        }
    }

    public function buscarPerfilProprio(int $idUsuario): ?array {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT u.id_usuario AS id, p.nome, p.cpf, p.telefone,
                        p.data_nascimento, u.email, u.foto_perfil,
                        u.id_perfil AS perfil_id
                 FROM usuarios u
                 INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                 WHERE u.id_usuario = ?
                 LIMIT 1"
            );
            $stmt->execute([$idUsuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Erro ao carregar o próprio perfil: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar o perfil.', 0, $e);
        }
    }

    public function atualizarPerfilProprio(
        int $idUsuario,
        string $nome,
        string $cpf,
        string $telefone,
        string $dataNascimento,
        string $email,
        ?string $fotoPerfil
    ): bool {
        $conexao = Conexao::getConexao();
        try {
            $stmt = $conexao->prepare(
                "SELECT u.id_usuario
                 FROM usuarios u
                 INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                 WHERE u.id_usuario <> ?
                   AND (u.email = ?
                     OR REPLACE(REPLACE(REPLACE(p.cpf, '.', ''), '-', ''), ' ', '') = ?)
                 LIMIT 1"
            );
            $stmt->execute([$idUsuario, $email, $cpf]);
            if ($stmt->fetchColumn()) {
                return false;
            }

            $conexao->beginTransaction();
            $stmtPessoa = $conexao->prepare(
                "UPDATE pessoas p
                 INNER JOIN usuarios u ON u.id_pessoa = p.id_pessoa
                 SET p.nome = ?, p.cpf = ?, p.telefone = ?, p.data_nascimento = ?
                 WHERE u.id_usuario = ?"
            );
            $stmtPessoa->execute([$nome, $cpf, $telefone, $dataNascimento, $idUsuario]);
            if ($stmtPessoa->rowCount() === 0) {
                $stmtExiste = $conexao->prepare("SELECT 1 FROM usuarios WHERE id_usuario = ?");
                $stmtExiste->execute([$idUsuario]);
                if (!$stmtExiste->fetchColumn()) {
                    $conexao->rollBack();
                    return false;
                }
            }

            $sqlUsuario = "UPDATE usuarios SET email = ?";
            $parametrosUsuario = [$email];
            if ($fotoPerfil !== null) {
                $sqlUsuario .= ", foto_perfil = ?";
                $parametrosUsuario[] = $fotoPerfil;
            }
            $sqlUsuario .= " WHERE id_usuario = ?";
            $parametrosUsuario[] = $idUsuario;
            $stmtUsuario = $conexao->prepare($sqlUsuario);
            $stmtUsuario->execute($parametrosUsuario);

            $conexao->commit();
            return true;
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            error_log('Erro ao atualizar o próprio perfil: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível atualizar o perfil.', 0, $e);
        }
    }

    public function atualizar(UsuarioDTO $u) {
        try {
            if (!empty($u->getSenha())) {
                $sql = "UPDATE usuarios u
                        INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                        SET p.nome = ?, u.email = ?, u.senha = ?, u.id_perfil = ?
                        WHERE u.id_usuario = ?";
                $params = [
                    $u->getNome(), 
                    $u->getEmail(),
                    $u->getSenha(), 
                    $u->getPerfilId(),
                    $u->getId()
                ];
            } else {
                $sql = "UPDATE usuarios u
                        INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                        SET p.nome = ?, u.email = ?, u.id_perfil = ?
                        WHERE u.id_usuario = ?";
                $params = [
                    $u->getNome(), 
                    $u->getEmail(),
                    $u->getPerfilId(),
                    $u->getId()
                ];
            }
            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            echo "Erro Banco: " . $e->getMessage();
            return false;
        }
    }

    public function excluir(int $id) {
        $conexao = Conexao::getConexao();

        try {
            $conexao->beginTransaction();

            $stmtPessoa = $conexao->prepare(
                "SELECT id_pessoa FROM usuarios WHERE id_usuario = ?"
            );
            $stmtPessoa->execute([$id]);
            $idPessoa = $stmtPessoa->fetchColumn();

            $stmtUsuario = $conexao->prepare(
                "DELETE FROM usuarios WHERE id_usuario = ?"
            );
            $stmtUsuario->execute([$id]);

            if ($idPessoa !== false) {
                $stmtPessoa = $conexao->prepare(
                    "DELETE FROM pessoas WHERE id_pessoa = ?"
                );
                $stmtPessoa->execute([$idPessoa]);
            }

            $conexao->commit();
            return true;
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            echo "Erro ao excluir: " . $e->getMessage();
            return false;
        }
    }

    public function desativar(int $id) {
        try {
            $sql = "UPDATE usuarios SET status = 'Inativo' WHERE id_usuario = ?";
            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            echo "Erro ao desativar: " . $e->getMessage();
            return false;
        }
    }

    public function ativar(int $id) {
        try {
            $sql = "UPDATE usuarios SET status = 'Ativo' WHERE id_usuario = ?";
            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            echo "Erro ao ativar: " . $e->getMessage();
            return false;
        }
    }

    public function buscarPreMatriculaAluno(int $idUsuario) {
        try {
            $sql = "SELECT pm.id_pre_matricula, pm.id_usuario, pm.status, pm.observacao,
                           pm.data_inscricao, c.nome AS curso_nome,
                           c.sigla AS curso_sigla
                    FROM pre_matricula pm
                    INNER JOIN cursos c ON c.id_curso = pm.id_curso
                    WHERE pm.id_usuario = ?
                    ORDER BY pm.id_pre_matricula DESC
                    LIMIT 1";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute([$idUsuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            echo "Erro ao buscar pré-matrícula: " . $e->getMessage();
            return null;
        }
    }

    public function listarCursosAtivos(): array {
        try {
            $stmt = Conexao::getConexao()->query(
                "SELECT id_curso, nome, sigla, imagem
                 FROM cursos
                 WHERE status = 'Ativo'
                 ORDER BY nome"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao buscar cursos ativos: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar os cursos.', 0, $e);
        }
    }

    public function listarCursosGerenciamento(): array {
        try {
            $stmt = Conexao::getConexao()->query(
                "SELECT id_curso, nome, sigla, imagem, status
                 FROM cursos
                 ORDER BY nome"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao listar cursos para gerenciamento: ' . $e->getMessage());
            return [];
        }
    }

    public function cadastrarCurso(string $nome, string $sigla, ?string $imagem = null): string {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT COUNT(*) FROM cursos WHERE nome = ? OR sigla = ?"
            );
            $stmt->execute([$nome, $sigla]);
            if ((int)$stmt->fetchColumn() > 0) {
                return 'duplicado';
            }

            $stmt = Conexao::getConexao()->prepare(
                "INSERT INTO cursos (nome, sigla, imagem, status) VALUES (?, ?, ?, 'Ativo')"
            );
            $stmt->execute([$nome, $sigla, $imagem]);
            return 'ok';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return 'duplicado';
            }
            error_log('Erro ao cadastrar curso: ' . $e->getMessage());
            return 'erro';
        }
    }

    public function atualizarCurso(
        int $idCurso,
        string $nome,
        string $sigla,
        string $status,
        ?string $imagem = null,
        bool $removerImagem = false
    ): string {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT COUNT(*) FROM cursos
                 WHERE (nome = ? OR sigla = ?) AND id_curso <> ?"
            );
            $stmt->execute([$nome, $sigla, $idCurso]);
            if ((int)$stmt->fetchColumn() > 0) {
                return 'duplicado';
            }

            $sql = "UPDATE cursos SET nome = ?, sigla = ?, status = ?";
            $params = [$nome, $sigla, $status];
            if ($imagem !== null) {
                $sql .= ", imagem = ?";
                $params[] = $imagem;
            } elseif ($removerImagem) {
                $sql .= ", imagem = NULL";
            }
            $sql .= " WHERE id_curso = ?";
            $params[] = $idCurso;
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute($params);
            if ($stmt->rowCount() > 0) {
                return 'ok';
            }

            $stmt = Conexao::getConexao()->prepare(
                "SELECT 1 FROM cursos WHERE id_curso = ?"
            );
            $stmt->execute([$idCurso]);
            return $stmt->fetchColumn() ? 'ok' : 'nao_encontrado';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return 'duplicado';
            }
            error_log('Erro ao atualizar curso: ' . $e->getMessage());
            return 'erro';
        }
    }

    public function excluirCurso(int $idCurso): string {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT COUNT(*) FROM pre_matricula WHERE id_curso = ?"
            );
            $stmt->execute([$idCurso]);
            if ((int)$stmt->fetchColumn() > 0) {
                return 'vinculado';
            }

            $stmt = Conexao::getConexao()->prepare(
                "DELETE FROM cursos WHERE id_curso = ?"
            );
            $stmt->execute([$idCurso]);
            return $stmt->rowCount() > 0 ? 'ok' : 'nao_encontrado';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return 'vinculado';
            }
            error_log('Erro ao excluir curso: ' . $e->getMessage());
            return 'erro';
        }
    }

    public function buscarImagemCurso(int $idCurso): ?string {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT imagem FROM cursos WHERE id_curso = ?"
            );
            $stmt->execute([$idCurso]);
            $imagem = $stmt->fetchColumn();
            return is_string($imagem) && $imagem !== '' ? $imagem : null;
        } catch (PDOException $e) {
            error_log('Erro ao consultar imagem do curso: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível consultar a imagem do curso.', 0, $e);
        }
    }

    public function criarPreMatricula(int $idUsuario, int $idCurso) {
        try {
            $stmtInscricao = Conexao::getConexao()->prepare(
                "SELECT COUNT(*)
                 FROM pre_matricula
                 WHERE id_usuario = ? AND status <> 'Indeferido'"
            );
            $stmtInscricao->execute([$idUsuario]);
            if ((int)$stmtInscricao->fetchColumn() > 0) {
                return false;
            }

            $stmtDocumentos = Conexao::getConexao()->prepare(
                "SELECT COUNT(*)
                 FROM tipos_documento td
                 INNER JOIN documentos_enviados de
                   ON de.id_tipo_documento = td.id_tipo_documento
                  AND de.id_usuario_aluno = ?
                  AND de.caminho_arquivo IS NOT NULL
                 WHERE td.obrigatorio = TRUE"
            );
            $stmtDocumentos->execute([$idUsuario]);
            $totalDocumentos = (int)$stmtDocumentos->fetchColumn();

            $stmtObrigatorios = Conexao::getConexao()->query(
                "SELECT COUNT(*) FROM tipos_documento WHERE obrigatorio = TRUE"
            );
            if ($totalDocumentos === 0 || $totalDocumentos !== (int)$stmtObrigatorios->fetchColumn()) {
                return false;
            }

            $sql = "INSERT INTO pre_matricula (id_usuario, id_curso)
                    SELECT ?, c.id_curso
                    FROM cursos c
                    WHERE c.id_curso = ? AND c.status = 'Ativo'";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute([$idUsuario, $idCurso]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao criar pré-matrícula: ' . $e->getMessage());
            return false;
        }
    }

    public function listarPreMatriculas() {
        try {
            $sql = "SELECT pm.id_pre_matricula, pm.id_usuario, pm.status, pm.observacao,
                           pm.data_inscricao, p.nome AS aluno_nome,
                           p.cpf AS aluno_cpf, p.telefone AS aluno_telefone,
                           p.data_nascimento AS aluno_data_nascimento,
                           (SELECT de.caminho_arquivo
                            FROM documentos_enviados de
                            INNER JOIN tipos_documento td
                              ON td.id_tipo_documento = de.id_tipo_documento
                            WHERE de.id_usuario_aluno = u.id_usuario
                              AND td.nome = 'Foto 3x4'
                            LIMIT 1) AS aluno_foto,
                           u.email AS aluno_email,
                           u.cargo AS aluno_cargo, u.setor AS aluno_setor,
                           u.status AS aluno_status, c.nome AS curso_nome,
                           c.sigla AS curso_sigla
                    FROM pre_matricula pm
                    INNER JOIN usuarios u ON u.id_usuario = pm.id_usuario
                    INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                    INNER JOIN cursos c ON c.id_curso = pm.id_curso
                    ORDER BY pm.data_inscricao DESC";
            $stmt = Conexao::getConexao()->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro ao listar pré-matrículas: " . $e->getMessage();
            return [];
        }
    }

    public function analisarPreMatricula(int $idPreMatricula, int $idSecretaria, string $status, string $observacao) {
        try {
            if ($status === 'Aprovado') {
                $stmtObrigatorios = Conexao::getConexao()->prepare(
                    "SELECT COUNT(td.id_tipo_documento) AS total,
                            SUM(CASE WHEN de.status = 'Deferido'
                                          AND de.caminho_arquivo IS NOT NULL
                                     THEN 1 ELSE 0 END) AS deferidos
                     FROM tipos_documento td
                     INNER JOIN pre_matricula pm ON pm.id_pre_matricula = ?
                     LEFT JOIN documentos_enviados de
                       ON de.id_tipo_documento = td.id_tipo_documento
                      AND de.id_usuario_aluno = pm.id_usuario
                     WHERE td.obrigatorio = TRUE"
                );
                $stmtObrigatorios->execute([$idPreMatricula]);
                $documentos = $stmtObrigatorios->fetch(PDO::FETCH_ASSOC);

                if ((int)$documentos['total'] !== (int)$documentos['deferidos']) {
                    return false;
                }
            }

            $sql = "UPDATE pre_matricula
                    SET status = ?, observacao = ?, data_analise = NOW(), analisado_por = ?
                    WHERE id_pre_matricula = ?";
            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute([
                $status,
                $observacao !== '' ? $observacao : null,
                $idSecretaria,
                $idPreMatricula
            ]);
        } catch (PDOException $e) {
            echo "Erro ao analisar pré-matrícula: " . $e->getMessage();
            return false;
        }
    }

    public function listarTiposDocumento() {
        try {
            $stmt = Conexao::getConexao()->query(
                "SELECT id_tipo_documento, nome
                 FROM tipos_documento
                 WHERE obrigatorio = TRUE
                 ORDER BY id_tipo_documento"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro ao buscar tipos de documento: " . $e->getMessage();
            return [];
        }
    }

    public function listarDocumentosAluno(int $idUsuario) {
        try {
            $sql = "SELECT de.id_documento, td.id_tipo_documento,
                           td.nome AS tipo_nome, de.caminho_arquivo,
                           de.status, de.justificativa_recusa,
                           de.data_envio, de.data_avaliacao
                    FROM tipos_documento td
                    LEFT JOIN documentos_enviados de
                      ON de.id_tipo_documento = td.id_tipo_documento
                     AND de.id_usuario_aluno = ?
                    WHERE td.obrigatorio = TRUE
                    ORDER BY td.id_tipo_documento";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute([$idUsuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao buscar documentos do aluno: ' . $e->getMessage());
            return [];
        }
    }

    public function salvarDocumentosAluno(int $idUsuario, array $documentos) {
        $conexao = Conexao::getConexao();

        try {
            $stmt = $conexao->prepare(
                "SELECT id_usuario
                 FROM usuarios
                 WHERE id_usuario = ? AND id_perfil = 3"
            );
            $stmt->execute([$idUsuario]);
            if (!$stmt->fetchColumn()) {
                return false;
            }

            $conexao->beginTransaction();
            $stmt = $conexao->prepare(
                "INSERT INTO documentos_enviados
                    (id_usuario_aluno, id_tipo_documento, caminho_arquivo, status, data_envio)
                 VALUES (?, ?, ?, 'Pendente', NOW())
                 ON DUPLICATE KEY UPDATE
                    caminho_arquivo = VALUES(caminho_arquivo),
                    status = 'Pendente',
                    justificativa_recusa = NULL,
                    data_envio = NOW(),
                    data_avaliacao = NULL,
                    avaliado_por = NULL"
            );

            foreach ($documentos as $idTipoDocumento => $caminhoArquivo) {
                $stmt->execute([$idUsuario, (int)$idTipoDocumento, $caminhoArquivo]);
            }

            $conexao->commit();
            return true;
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            error_log('Erro ao salvar documentos do aluno: ' . $e->getMessage());
            return false;
        }
    }

    public function avaliarDocumento(int $idDocumento, int $idSecretaria, string $status, string $justificativa) {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "UPDATE documentos_enviados
                 SET status = ?, justificativa_recusa = ?, data_avaliacao = NOW(), avaliado_por = ?
                 WHERE id_documento = ?"
            );
            return $stmt->execute([
                $status,
                $justificativa !== '' ? $justificativa : null,
                $idSecretaria,
                $idDocumento
            ]);
        } catch (PDOException $e) {
            echo "Erro ao avaliar documento: " . $e->getMessage();
            return false;
        }
    }

    public function listarTiposSolicitacaoAtivos(): array {
        try {
            $stmt = Conexao::getConexao()->query(
                "SELECT id_tipo_solicitacao, nome, descricao
                 FROM tipos_solicitacao
                 WHERE status = 'Ativo'
                 ORDER BY nome"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao listar tipos de solicitação: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar os tipos de solicitação.', 0, $e);
        }
    }

    public function listarSolicitacoesAluno(int $idAluno): array {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT s.id_solicitacao, s.numero_protocolo, s.descricao, s.status,
                        s.data_abertura, s.data_conclusao, s.resposta,
                        ts.nome AS tipo_nome
                 FROM solicitacoes s
                 INNER JOIN tipos_solicitacao ts
                   ON ts.id_tipo_solicitacao = s.id_tipo_solicitacao
                 WHERE s.id_aluno = ?
                 ORDER BY s.data_abertura DESC, s.id_solicitacao DESC"
            );
            $stmt->execute([$idAluno]);
            return $this->decodificarRespostasSolicitacoes($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            error_log('Erro ao listar solicitações do aluno: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar suas solicitações.', 0, $e);
        }
    }

    public function abrirSolicitacao(int $idAluno, int $idTipo, string $descricao): ?string {
        try {
            $protocolo = 'SGSE-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = Conexao::getConexao()->prepare(
                "INSERT INTO solicitacoes
                    (numero_protocolo, id_aluno, id_tipo_solicitacao, descricao)
                 SELECT ?, ?, id_tipo_solicitacao, ?
                 FROM tipos_solicitacao
                 WHERE id_tipo_solicitacao = ? AND status = 'Ativo'"
            );
            $stmt->execute([$protocolo, $idAluno, $descricao, $idTipo]);
            return $stmt->rowCount() === 1 ? $protocolo : null;
        } catch (PDOException $e) {
            error_log('Erro ao abrir solicitação de documento: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível registrar a solicitação.', 0, $e);
        }
    }

    public function listarSolicitacoesAtendimento(): array {
        try {
            $stmt = Conexao::getConexao()->query(
                "SELECT s.id_solicitacao, s.numero_protocolo, s.descricao, s.status,
                        s.data_abertura, s.data_conclusao, s.resposta,
                        ts.nome AS tipo_nome, p.nome AS aluno_nome, u.email AS aluno_email
                 FROM solicitacoes s
                 INNER JOIN tipos_solicitacao ts
                   ON ts.id_tipo_solicitacao = s.id_tipo_solicitacao
                 INNER JOIN usuarios u ON u.id_usuario = s.id_aluno
                 INNER JOIN pessoas p ON p.id_pessoa = u.id_pessoa
                 ORDER BY FIELD(s.status, 'Aberta', 'Em Atendimento', 'Concluida', 'Cancelada'),
                          s.data_abertura ASC, s.id_solicitacao ASC"
            );
            return $this->decodificarRespostasSolicitacoes($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            error_log('Erro ao listar solicitações para atendimento: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível carregar a fila de solicitações.', 0, $e);
        }
    }

    public function atualizarSolicitacao(
        int $idSolicitacao,
        int $idAtendente,
        string $status,
        string $resposta,
        ?string $arquivoResposta = null
    ): bool {
        $conexao = Conexao::getConexao();
        try {
            $conexao->beginTransaction();
            $stmt = $conexao->prepare(
                "SELECT resposta
                 FROM solicitacoes
                 WHERE id_solicitacao = ?
                   AND status IN ('Aberta', 'Em Atendimento')
                 FOR UPDATE"
            );
            $stmt->execute([$idSolicitacao]);
            $respostaAtual = $stmt->fetchColumn();
            if ($respostaAtual === false) {
                $conexao->rollBack();
                return false;
            }

            $dadosAtuais = $this->decodificarRespostaSolicitacao($respostaAtual);
            $arquivoRespostaAtualizado = $arquivoResposta ?? $dadosAtuais['arquivo_resposta'];
            $respostaArmazenada = $this->codificarRespostaSolicitacao(
                $resposta !== '' ? $resposta : null,
                $arquivoRespostaAtualizado
            );

            $stmt = $conexao->prepare(
                "UPDATE solicitacoes
                 SET status = ?, resposta = ?, atendido_por = ?,
                     data_conclusao = CASE
                         WHEN ? IN ('Concluida', 'Cancelada') THEN NOW()
                         ELSE NULL
                     END
                 WHERE id_solicitacao = ?
                   AND status IN ('Aberta', 'Em Atendimento')"
            );
            $stmt->execute([
                $status,
                $respostaArmazenada,
                $idAtendente,
                $status,
                $idSolicitacao
            ]);
            $conexao->commit();
            return true;
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            error_log('Erro ao atualizar solicitação de documento: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível atualizar a solicitação.', 0, $e);
        } catch (Throwable $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            throw $e;
        }
    }

    public function buscarArquivoSolicitacao(int $idSolicitacao): ?array {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT id_aluno, resposta, numero_protocolo
                 FROM solicitacoes
                 WHERE id_solicitacao = ?
                 LIMIT 1"
            );
            $stmt->execute([$idSolicitacao]);
            $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$solicitacao) {
                return null;
            }
            $dadosResposta = $this->decodificarRespostaSolicitacao($solicitacao['resposta']);
            $solicitacao['arquivo_resposta'] = $dadosResposta['arquivo_resposta'];
            return $solicitacao;
        } catch (PDOException $e) {
            error_log('Erro ao buscar arquivo da solicitação: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível localizar o arquivo da solicitação.', 0, $e);
        }
    }

    private function decodificarRespostasSolicitacoes(array $solicitacoes): array {
        foreach ($solicitacoes as &$solicitacao) {
            $dadosResposta = $this->decodificarRespostaSolicitacao($solicitacao['resposta'] ?? null);
            $solicitacao['resposta'] = $dadosResposta['resposta'];
            $solicitacao['arquivo_resposta'] = $dadosResposta['arquivo_resposta'];
        }
        unset($solicitacao);
        return $solicitacoes;
    }

    private function decodificarRespostaSolicitacao(?string $resposta): array {
        $prefixo = '__SOLICITACAO_V1__:';
        if ($resposta !== null && str_starts_with($resposta, $prefixo)) {
            $dados = json_decode(substr($resposta, strlen($prefixo)), true);
            if (is_array($dados) && array_key_exists('texto', $dados) && is_string($dados['arquivo'] ?? null)) {
                return [
                    'resposta' => is_string($dados['texto']) ? $dados['texto'] : '',
                    'arquivo_resposta' => preg_match(
                        '#^uploads/solicitacoes/[a-f0-9]{32}\.(pdf|jpg|png)$#',
                        $dados['arquivo']
                    ) ? $dados['arquivo'] : null
                ];
            }
        }
        return ['resposta' => $resposta ?? '', 'arquivo_resposta' => null];
    }

    private function codificarRespostaSolicitacao(?string $resposta, ?string $arquivoResposta): ?string {
        if ($arquivoResposta === null) {
            return $resposta;
        }
        if (!preg_match('#^uploads/solicitacoes/[a-f0-9]{32}\.(pdf|jpg|png)$#', $arquivoResposta)) {
            throw new RuntimeException('O caminho do anexo da solicitação é inválido.');
        }

        $dados = json_encode(
            ['texto' => $resposta ?? '', 'arquivo' => $arquivoResposta],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($dados === false) {
            throw new RuntimeException('Não foi possível registrar a resposta da solicitação.');
        }
        return '__SOLICITACAO_V1__:' . $dados;
    }
}
?>