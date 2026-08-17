<?php
class UsuarioDTO {
    private $id;
    private $nome;
    private $email;
    
    private $cpf;
    private $telefone;
    private $endereco;
    private $numero;
    private $complemento;
    private $bairro;
    private $senha;


    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getNome() { return $this->nome; } // Ajustado para N maiúsculo (boa prática)
    public function setNome($nome) { $this->nome = $nome; } // Ajustado para N maiúsculo (boa prática)

    public function getEmail() { return $this->email; } // Corrigido o ;
    public function setEmail($email) { $this->email = $email; }
    
    public function getCpf() { return $this->cpf; } // Corrigido o ;
    public function setCpf($cpf) { $this->cpf = $cpf; }

    public function getTelefone() { return $this->telefone; } // Corrigido o ;
    public function setTelefone($telefone) { $this->telefone = $telefone; }

    public function getEndereco() { return $this->endereco; } // Corrigido o ;
    public function setEndereco($endereco) { $this->endereco = $endereco; }

    public function getNumero() { return $this->numero; } // Corrigido o ;
    public function setNumero($numero) { $this->numero = $numero; }

    public function getComplemento() { return $this->complemento; } // Corrigido o ;
    public function setComplemento($complemento) { $this->complemento = $complemento; }

    public function getBairro() { return $this->bairro; } // Corrigido o ;
    public function setBairro($bairro) { $this->bairro = $bairro; }

    public function getSenha() { return $this->senha; } // Corrigido o ;
    public function setSenha($senha) { $this->senha = $senha; }
   }
?>