<?php
// model/dao/UsuarioDAO.php

require_once __DIR__ . '/Conexao.php';

class UsuarioDAO {

    // Método para buscar o usuário por e-mail (usado pela autenticação)
    public function buscarPorEmail(string $email) {
        try {
            $sql = "SELECT u.id_usuario AS id, p.nome, p.cpf, u.email, u.senha,
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
                "INSERT INTO pessoas (nome, cpf, telefone, data_nascimento, foto_3x4)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmtPessoa->execute([
                $u->getNome(),
                $u->getCpf(),
                $u->getTelefone(),
                $u->getDataNascimento(),
                $u->getFoto3x4()
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
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return false;
        }
    }

    public function listar() {
        try {
            $sql = "SELECT u.id_usuario AS id, p.nome, u.email,
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
            $sql = "SELECT pm.id_pre_matricula, pm.status, pm.observacao,
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

    public function listarCursosAtivos() {
        try {
            $stmt = Conexao::getConexao()->query(
                "SELECT id_curso, nome, sigla
                 FROM cursos
                 WHERE status = 'Ativo'
                 ORDER BY nome"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro ao buscar cursos: " . $e->getMessage();
            return [];
        }
    }

    public function criarPreMatricula(int $idUsuario, int $idCurso) {
        try {
            $sql = "INSERT INTO pre_matricula (id_usuario, id_curso)
                    VALUES (?, ?)";
            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute([$idUsuario, $idCurso]);
        } catch (PDOException $e) {
            echo "Erro ao criar pré-matrícula: " . $e->getMessage();
            return false;
        }
    }

    public function listarPreMatriculas() {
        try {
            $sql = "SELECT pm.id_pre_matricula, pm.status, pm.observacao,
                           pm.data_inscricao, p.nome AS aluno_nome,
                           p.cpf AS aluno_cpf, p.telefone AS aluno_telefone,
                           p.data_nascimento AS aluno_data_nascimento,
                           p.foto_3x4 AS aluno_foto, u.email AS aluno_email,
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
                            SUM(CASE WHEN de.status = 'Deferido' THEN 1 ELSE 0 END) AS deferidos
                     FROM tipos_documento td
                     LEFT JOIN documentos_enviados de
                       ON de.id_tipo_documento = td.id_tipo_documento
                      AND de.id_pre_matricula = ?
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

    public function listarDocumentosPreMatricula(int $idPreMatricula, ?int $idUsuario = null) {
        try {
            $filtroUsuario = $idUsuario === null ? '' : 'AND pm.id_usuario = ?';
            $params = [$idPreMatricula, $idPreMatricula];
            if ($idUsuario !== null) {
                $params[] = $idUsuario;
            }

            $sql = "SELECT de.id_documento, de.id_tipo_documento,
                           td.nome AS tipo_nome, de.caminho_arquivo,
                           de.status, de.justificativa_recusa,
                           de.data_envio, de.data_avaliacao
                    FROM tipos_documento td
                    LEFT JOIN documentos_enviados de
                      ON de.id_tipo_documento = td.id_tipo_documento
                     AND de.id_pre_matricula = ?
                    INNER JOIN pre_matricula pm
                      ON pm.id_pre_matricula = ?
                    WHERE td.obrigatorio = TRUE $filtroUsuario
                    ORDER BY td.id_tipo_documento";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Erro ao buscar documentos: " . $e->getMessage();
            return [];
        }
    }

    public function enviarDocumento(int $idPreMatricula, int $idUsuario, int $idTipoDocumento, string $caminhoArquivo) {
        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT id_documento
                 FROM documentos_enviados de
                 INNER JOIN pre_matricula pm ON pm.id_pre_matricula = de.id_pre_matricula
                 WHERE de.id_pre_matricula = ? AND pm.id_usuario = ? AND de.id_tipo_documento = ?"
            );
            $stmt->execute([$idPreMatricula, $idUsuario, $idTipoDocumento]);
            $idDocumento = $stmt->fetchColumn();

            if ($idDocumento) {
                $stmt = Conexao::getConexao()->prepare(
                    "UPDATE documentos_enviados
                     SET caminho_arquivo = ?, status = 'Pendente', justificativa_recusa = NULL,
                         data_envio = NOW(), data_avaliacao = NULL, avaliado_por = NULL
                     WHERE id_documento = ?"
                );
                return $stmt->execute([$caminhoArquivo, $idDocumento]);
            }

            $stmt = Conexao::getConexao()->prepare(
                "INSERT INTO documentos_enviados
                    (id_pre_matricula, id_tipo_documento, caminho_arquivo, status, data_envio)
                 SELECT ?, ?, ?, 'Pendente', NOW()
                 FROM pre_matricula
                 WHERE id_pre_matricula = ? AND id_usuario = ?"
            );
            $stmt->execute([$idPreMatricula, $idTipoDocumento, $caminhoArquivo, $idPreMatricula, $idUsuario]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            echo "Erro ao enviar documento: " . $e->getMessage();
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
}
?>