<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil_id'] != 1) {
    header('Location: home/index.php');
    exit;
}

require_once __DIR__ . '/controller/UsuarioController.php';

$controller = new UsuarioController();
$pendentes = $controller->listarPendentes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if (isset($_POST['btn_aprovar'])) {
        $perfil = (int)($_POST['perfil_id'] ?? 2);
        $controller->aprovarCadastro($id, $perfil);
    }

    if (isset($_POST['btn_rejeitar'])) {
        $controller->rejeitarCadastro($id);
    }

    header('Location: admin_aprovacao.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovação de Cadastros</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { margin: 0; background: #f4f7fb; color: #1f2937; padding: 40px 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 18px; box-shadow: 0 12px 30px rgba(15,23,42,0.08); padding: 30px; }
        h1 { color: #123a8a; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #dfe9f5; padding: 12px; text-align: left; }
        th { background: #eff6ff; }
        select, button { padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; }
        button { background: #1748a8; color: white; border: none; cursor: pointer; }
        .empty { color: #64748b; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastros pendentes</h1>

        <?php if (!empty($pendentes)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil solicitado</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendentes as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome']) ?></td>
                            <td><?= htmlspecialchars($item['email']) ?></td>
                            <td>
                                <?php echo ($item['perfil_solicitado'] == 1) ? 'Administrador' : 'Aluno'; ?>
                            </td>
                            <td>
                                <form method="post" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <select name="perfil_id">
                                        <option value="2" <?= $item['perfil_solicitado'] == 2 ? 'selected' : '' ?>>Aluno</option>
                                        <option value="1" <?= $item['perfil_solicitado'] == 1 ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                                    <button type="submit" name="btn_aprovar">Aprovar</button>
                                    <button type="submit" name="btn_rejeitar" style="background: #dc2626;">Rejeitar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty">Nenhum cadastro pendente.</p>
        <?php endif; ?>
    </div>
</body>
</html>
