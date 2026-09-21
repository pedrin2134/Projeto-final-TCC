<?php
class UsuarioDTO {
    private $id;
    private $nome;
    private $cpf;
    private $telefone;
    private $data_nascimento;
    private $foto_3x4;
    private $email;
    private $senha;
    private $perfil_id;

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getNome() { return $this->nome; } 
    public function setNome($nome) { $this->nome = $nome; } 

    public function getCpf() { return $this->cpf; }
    public function setCpf($cpf) { $this->cpf = $cpf; }

    public function getTelefone() { return $this->telefone; }
    public function setTelefone($telefone) { $this->telefone = $telefone; }

    public function getDataNascimento() { return $this->data_nascimento; }
    public function setDataNascimento($data_nascimento) { $this->data_nascimento = $data_nascimento; }

    public function getFoto3x4() { return $this->foto_3x4; }
    public function setFoto3x4($foto_3x4) { $this->foto_3x4 = $foto_3x4; }

    public function getEmail() { return $this->email; } 
    public function setEmail($email) { $this->email = $email; }

    public function getSenha() { return $this->senha; } 
    public function setSenha($senha) { $this->senha = $senha; }

    public function getPerfilId() { return $this->perfil_id; } 
    public function setPerfilId($perfil_id) { $this->perfil_id = $perfil_id; }
}
?>