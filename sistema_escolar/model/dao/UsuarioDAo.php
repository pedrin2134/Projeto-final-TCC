<?php
// model/dao/UsuarioDAO.php
class UsuarioDAO {

    public function cadastrar(UsuarioDTO $u) {
        try {
            $conexao = Conexao::getConexao();

            // ETAPA 1: Salva os dados pessoais usando parâmetros nomeados
            $sqlPessoa = "INSERT INTO pessoa (nome, email, cpf, telefone, endereco, numero, complemento, bairro) 
                          VALUES (:nome, :email, :cpf, :telefone, :endereco, :numero, :complemento, :bairro)";
            
            $stmtPessoa = $conexao->prepare($sqlPessoa);
            $stmtPessoa->execute([
                ':nome'        => $u->getNome(), 
                ':email'       => $u->getEmail(),
                ':cpf'         => $u->getCpf(),
                ':telefone'    => $u->getTelefone(),
                ':endereco'    => $u->getEndereco(),
                ':numero'      => $u->getNumero(),
                ':complemento' => $u->getComplemento(),
                ':bairro'      => $u->getBairro()
            ]);

            // ETAPA 2: Pega o ID gerado para a pessoa
            $idPessoaGerado = $conexao->lastInsertId();

            // ETAPA 3: Salva as credenciais na tabela usuario usando parâmetros nomeados
            $sqlUsuario = "INSERT INTO usuario (id_pessoa, login, senha_hash, ativo) 
                           VALUES (:id_pessoa, :login, :senha_hash, :ativo)";
            
            $stmtUsuario = $conexao->prepare($sqlUsuario);
            return $stmtUsuario->execute([
                ':id_pessoa'  => $idPessoaGerado,
                ':login'      => $u->getEmail(),    // Usa o e-mail como login de acesso
                ':senha_hash' => $u->getSenha(),    // Pega a senha vinda do formulário
                ':ativo'      => 1                  // Define ativo como true (1)
            ]);

        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>Erro MySQL no Cadastro:</b> " . $e->getMessage() . "</p>";
            return false;
        }
    }
}

