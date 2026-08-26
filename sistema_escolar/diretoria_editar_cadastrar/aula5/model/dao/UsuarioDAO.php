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
// Adicione estes métodos dentro da classe UsuarioDAO

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
        // Atualiza a senha apenas se o campo foi preenchido
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