<?php
// Adicionado o require_once da Conexao.php
require_once __DIR__ . '/Conexao.php';

class UsuarioDAO {

    public function cadastrar(UsuarioDTO $u) {
        try {
            $sql = "INSERT INTO login (nome, email, senha, perfil_id) VALUES (?, ?, ?, ?)";
            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute([
                $u->getNome(), 
                $u->getEmail(), 
                $u->getSenha(), 
                $u->getPerfilId()
            ]);
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return false;
        }
    }

    public function listar() {
        try {
            $sql = "SELECT id, nome, email, perfil_id FROM LOGIN";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "Erro ao buscar usuários:<br>". $e->getMessage();
            return [];
        }
    }

    public function cadastrarPreCadastro($nome, $email, $senha, $perfilSolicitado) {
        try {
            $verifica = Conexao::getConexao()->prepare("SELECT id FROM login WHERE email = ? UNION SELECT id FROM pre_cadastro WHERE email = ? LIMIT 1");
            $verifica->execute([$email, $email]);
            if ($verifica->fetch()) {
                return false;
            }

            $sql = "INSERT INTO pre_cadastro (nome, email, senha, perfil_solicitado, status) VALUES (?, ?, ?, ?, 'pendente')";
            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute([$nome, $email, $senha, $perfilSolicitado]);
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return false;
        }
    }

    public function listarPreCadastrosPendentes() {
        try {
            $sql = "SELECT * FROM pre_cadastro WHERE status = 'pendente' ORDER BY id DESC";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return [];
        }
    }

    public function aprovarPreCadastro($id, $perfil_id) {
        try {
            $sqlBusca = "SELECT * FROM pre_cadastro WHERE id = ? AND status = 'pendente' LIMIT 1";
            $stmtBusca = Conexao::getConexao()->prepare($sqlBusca);
            $stmtBusca->execute([$id]);
            $solicitacao = $stmtBusca->fetch(PDO::FETCH_ASSOC);

            if (!$solicitacao) {
                return false;
            }

            $verifica = Conexao::getConexao()->prepare("SELECT id FROM login WHERE email = ? LIMIT 1");
            $verifica->execute([$solicitacao['email']]);
            if ($verifica->fetch()) {
                return false;
            }

            $sqlInsert = "INSERT INTO login (nome, email, senha, perfil_id) VALUES (?, ?, ?, ?)";
            $stmtInsert = Conexao::getConexao()->prepare($sqlInsert);
            $ok = $stmtInsert->execute([
                $solicitacao['nome'],
                $solicitacao['email'],
                $solicitacao['senha'],
                $perfil_id
            ]);

            if ($ok) {
                $sqlDelete = "DELETE FROM pre_cadastro WHERE id = ?";
                $stmtDelete = Conexao::getConexao()->prepare($sqlDelete);
                return $stmtDelete->execute([$id]);
            }

            return false;
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return false;
        }
    }

    public function rejeitarPreCadastro($id) {
        try {
            $sql = "DELETE FROM pre_cadastro WHERE id = ? AND status = 'pendente'";
            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return false;
        }
    }

    public function buscarPorEmailSenha($email, $senha) {
        try {
            $sql = "SELECT id, nome, email, perfil_id FROM login WHERE email = ? AND senha = ? LIMIT 1";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute([$email, $senha]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return null;
        }
    }

    public function buscarPorId(int $id) {
        try {
            $sql = "SELECT id, nome, email, perfil_id FROM login WHERE id = ?";
            $stmt = Conexao::getConexao()->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return null;
        }
    }

    public function atualizar(UsuarioDTO $u) {
        try {
            if (!empty($u->getSenha())) {
                $sql = "UPDATE login SET nome = ?, email = ?, senha = ?, perfil_id = ? WHERE id = ?";
                $params = [$u->getNome(), $u->getEmail(), $u->getSenha(), $u->getPerfilId(), $u->getId()];
            } else {
                $sql = "UPDATE login SET nome = ?, email = ?, perfil_id = ? WHERE id = ?";
                $params = [$u->getNome(), $u->getEmail(), $u->getPerfilId(), $u->getId()];
            }

            $stmt = Conexao::getConexao()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL:</b> " . $e->getMessage() . "</p>";
            return false;
        }
    }
}

?>