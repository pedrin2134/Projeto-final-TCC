<?php

require_once __DIR__ . '/../model/dao/Conexao.php';

class Autorizacao {
    public static function usuarioTemPermissao(string $permissao, ?int $idUsuario = null): bool {
        $idUsuario = $idUsuario ?? (int)($_SESSION['usuario_id'] ?? 0);
        if ($idUsuario <= 0 || $permissao === '') {
            return false;
        }

        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT 1
                 FROM usuarios u
                 INNER JOIN permissoes p ON p.id_perfil = u.id_perfil
                 WHERE u.id_usuario = ? AND p.nome = ?
                 LIMIT 1"
            );
            $stmt->execute([$idUsuario, $permissao]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao verificar permissão do usuário: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível verificar a permissão de acesso.', 0, $e);
        }
    }

    public static function perfilTemPermissao(int $idPerfil, string $permissao): bool {
        if ($idPerfil <= 0 || $permissao === '') {
            return false;
        }

        try {
            $stmt = Conexao::getConexao()->prepare(
                "SELECT 1
                 FROM permissoes
                 WHERE id_perfil = ? AND nome = ?
                 LIMIT 1"
            );
            $stmt->execute([$idPerfil, $permissao]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao verificar permissão do perfil: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível verificar a permissão do perfil.', 0, $e);
        }
    }
}
