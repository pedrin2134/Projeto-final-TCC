<?php
// controller/UsuarioController.php

// Usa __DIR__ para apontar corretamente a partir da pasta 'controller'
require_once __DIR__ . '/../model/dto/UsuarioDTO.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';

class UsuarioController {

    public function cadastrar($nome, $email, $senha, $perfil_id) {
        $usuario = new UsuarioDTO();
        $usuario->setNome($nome);
        $usuario->setEmail($email);
        $usuario->setSenha($senha);
        $usuario->setPerfilId($perfil_id);

        $dao = new UsuarioDAO();
        return $dao->cadastrar($usuario);
    }

    // MÉTODO ADICIONADO PARA LISTAR OS USUÁRIOS
    public function listar() {
        $dao = new UsuarioDAO();
        return $dao->listar();
    }

    // Adicione estes métodos dentro da classe UsuarioController

public function buscarPorId(int $id) {
    $dao = new UsuarioDAO();
    return $dao->buscarPorId($id);
}

public function atualizar($id, $nome, $email, $senha, $perfil_id) {
    $usuario = new UsuarioDTO();
    $usuario->setId($id);
    $usuario->setNome($nome);
    $usuario->setEmail($email);
    $usuario->setSenha($senha);
    $usuario->setPerfilId($perfil_id);

    $dao = new UsuarioDAO();
    return $dao->atualizar($usuario);
}

    
}

// Processa a requisição via POST do formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_cadastrar'])) {
    $controller = new UsuarioController();
    
    try {
        $sucesso = $controller->cadastrar(
            $_POST['nome'],
            $_POST['email'],
            $_POST['senha'],
            $_POST['perfil_id']
        );
        
        echo $sucesso ? "Usuário cadastrado com sucesso!<br><br>" : "Erro ao cadastrar usuário.<br><br>";

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Erro: O e-mail informado já está cadastrado.<br><br>";
        } else {
            echo "Erro ao cadastrar: " . $e->getMessage() . "<br><br>";
        }
    }

    echo "<a href='../view/cadastrar_usuario.php'><button type='button'>Voltar ao cadastro</button></a> ";
    echo "<a href='../index.php'><button type='button'>Ir para o início</button></a>";

    
}


?>