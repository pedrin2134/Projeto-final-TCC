<?php
class UsuarioDTO {
    private $id;
    private $nome;
    private $email;
    private $senha;
    private $perfil_id;

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getNome() { return $this->nome; } // Ajustado para N maiúsculo (boa prática)
    public function setNome($nome) { $this->nome = $nome; } // Ajustado para N maiúsculo (boa prática)

    public function getEmail() { return $this->email; } // Corrigido o ;
    public function setEmail($email) { $this->email = $email; }

    public function getSenha() { return $this->senha; } // Corrigido retorno para $this->senha
    public function setSenha($senha) { $this->senha = $senha; }

    public function getPerfilId() { return $this->perfil_id; } // Corrigido digitação (Perfil)
    public function setPerfilId($perfil_id) { $this->perfil_id = $perfil_id; } // Corrigido atribuição para $this->perfil_id
}
?>