<?php
// Usa __DIR__ para apontar corretamente a partir da pasta 'controller'
require_once __DIR__ . '/../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';

class UsuarioController {

    public function cadastrar($nome, $email, $cpf, $telefone, $endereco, $numero, $complemento, $bairro, $senha) {
        $usuario = new UsuarioDTO();
        $usuario->setNome($nome);
        $usuario->setEmail($email);
        $usuario->setCpf($cpf);
        $usuario->setTelefone($telefone);
        $usuario->setEndereco($endereco);
        $usuario->setNumero($numero);
        $usuario->setComplemento($complemento);
        $usuario->setBairro($bairro);
        $usuario->setSenha($senha);
        

        $dao = new UsuarioDAO();
        return $dao->cadastrar($usuario);

    }
}