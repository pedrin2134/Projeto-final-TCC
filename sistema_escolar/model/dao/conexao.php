    <?php
    // model/dao/conexao.php

    // Evita recriar a classe caso o arquivo seja chamado mais de uma vez via 'require'
    if (!class_exists('Conexao')) {

        class Conexao {
            private static $instance = null;

            // Construtor privado para impedir instanciação direta via 'new'
            private function __construct() {}

            public static function getConexao() {
                if (!isset(self::$instance)) {
                    $host     = "localhost";
                    $dbname   = "sistema_escolar1";
                    $usuario  = "root";
                    $senha    = "";

                    try {
                        self::$instance = new PDO(
                            "mysql:host={$host};dbname={$dbname};charset=utf8",
                            $usuario,
                            $senha
                        );
                        
                        // Define atributos do PDO para tratamento de erros e busca padrão
                        self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        die("Erro na conexão com o banco de dados: " . $e->getMessage());
                    }
                }

                return self::$instance;
            }
        }

    }