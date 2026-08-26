<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro pendente</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #edf5ff 0%, #f5f9ff 100%);
            padding: 20px;
        }
        .box {
            max-width: 540px;
            width: 100%;
            background: white;
            border-radius: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 34px 28px;
            text-align: center;
        }
        h1 {
            color: #123a8a;
            margin-bottom: 16px;
        }
        p {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 18px;
        }
        .btn {
            display: inline-block;
            background: #1748a8;
            color: white;
            padding: 12px 22px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Cadastro solicitado</h1>
        <p>
            Seu cadastro foi enviado com sucesso e está sendo analisado pelo administrador.
            Enquanto isso, você não pode fazer login no sistema.
        </p>
        <p>
            Assim que o cadastro for aprovado, você receberá acesso ao sistema e poderá entrar normalmente.
        </p>
        <a href="home/index.php" class="btn">Voltar para a home</a>
    </div>
</body>
</html>
