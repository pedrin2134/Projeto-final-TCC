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

    public function solicitarCadastro($nome, $email, $senha, $perfilSolicitado) {
        $dao = new UsuarioDAO();
        return $dao->cadastrarPreCadastro($nome, $email, $senha, $perfilSolicitado);
    }

    public function listarPendentes() {
        $dao = new UsuarioDAO();
        return $dao->listarPreCadastrosPendentes();
    }

    public function aprovarCadastro($id, $perfil_id) {
        $dao = new UsuarioDAO();
        return $dao->aprovarPreCadastro($id, $perfil_id);
    }

    public function rejeitarCadastro($id) {
        $dao = new UsuarioDAO();
        return $dao->rejeitarPreCadastro($id);
    }

    public function login($email, $senha) {
        $dao = new UsuarioDAO();
        return $dao->buscarPorEmailSenha($email, $senha);
    }

    public function listar() {
        $dao = new UsuarioDAO();
        return $dao->listar();
    }

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_pre_cadastrar'])) {
    $controller = new UsuarioController();

    try {
        $sucesso = $controller->solicitarCadastro(
            $_POST['nome'],
            $_POST['email'],
            $_POST['senha'],
            $_POST['perfil_solicitado']
        );

        if ($sucesso) {
            header('Location: ../cadastro_pendente.php');
            exit;
        }

        header('Location: ../view/cadastrar_usuario.php?erro=ja_cadastrado');
        exit;
    } catch (PDOException $e) {
        header('Location: ../view/cadastrar_usuario.php?erro=erro');
        exit;
    }
}

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