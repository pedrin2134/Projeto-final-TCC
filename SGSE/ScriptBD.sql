DROP DATABASE IF EXISTS secretaria_db;


    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE secretaria_db;

-- ============================================================
-- 1. PESSOAS
-- ============================================================

CREATE TABLE pessoas (
    id_pessoa INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    data_nascimento DATE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. PERFIS
-- ============================================================

CREATE TABLE perfis (
    id_perfil INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO perfis (id_perfil, nome) VALUES
(1, 'Administrador'),
(2, 'Secretaria'),
(3, 'Aluno');

-- ============================================================
-- 3. PERMISSOES
-- Uma permissao representa uma funcionalidade/domínio do sistema.
-- O acesso de cada perfil é controlado pela aplicacao.
-- ============================================================

CREATE TABLE permissoes (
    id_permissao INT AUTO_INCREMENT PRIMARY KEY,
    id_perfil INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255),

    CONSTRAINT fk_permissao_perfil
        FOREIGN KEY (id_perfil)
        REFERENCES perfis(id_perfil)
        ON DELETE CASCADE,

    CONSTRAINT uq_permissao_perfil
        UNIQUE (id_perfil, nome)
) ENGINE=InnoDB;

-- ============================================================
-- PERMISSOES DO ADMINISTRADOR
-- ============================================================

INSERT INTO permissoes (id_perfil, nome, descricao) VALUES
(1, 'REALIZAR_LOGIN',
    'Permitir acesso do administrador ao sistema'),

(1, 'GERENCIAR_PERFIL',
    'Consultar e atualizar os proprios dados'),

(1, 'GERENCIAR_USUARIOS',
    'Cadastrar, consultar, alterar e excluir usuarios'),

(1, 'GERENCIAR_ALUNOS',
    'Consultar, alterar e excluir alunos'),

(1, 'GERENCIAR_CURSOS',
    'Cadastrar, consultar, alterar e excluir cursos'),

(1, 'GERENCIAR_PRE_MATRICULAS',
    'Consultar e analisar pre-matriculas'),

(1, 'GERENCIAR_DOCUMENTOS',
    'Consultar e analisar documentos dos alunos'),

(1, 'GERENCIAR_SOLICITACOES',
    'Analisar e acompanhar solicitacoes dos alunos'),

(1, 'GERENCIAR_MATRICULAS',
    'Realizar e efetivar matriculas');

-- ============================================================
-- PERMISSOES DA SECRETARIA
-- ============================================================

INSERT INTO permissoes (id_perfil, nome, descricao) VALUES
(2, 'REALIZAR_LOGIN',
    'Permitir acesso da secretaria ao sistema'),

(2, 'GERENCIAR_PERFIL',
    'Consultar e atualizar os proprios dados'),

(2, 'GERENCIAR_ALUNOS',
    'Consultar, alterar e excluir alunos'),

(2, 'GERENCIAR_CURSOS',
    'Cadastrar, consultar, alterar e excluir cursos'),

(2, 'GERENCIAR_PRE_MATRICULAS',
    'Consultar e analisar pre-matriculas'),

(2, 'GERENCIAR_DOCUMENTOS',
    'Consultar e analisar documentos dos alunos'),

(2, 'GERENCIAR_MATRICULAS',
    'Realizar e efetivar matriculas'),

(2, 'GERENCIAR_SOLICITACOES',
    'Analisar, atender e finalizar solicitacoes'),

(2, 'GERENCIAR_PENDENCIAS',
    'Criar, acompanhar e finalizar pendencias'),

(2, 'EMITIR_DECLARACAO',
    'Emitir declaracoes solicitadas pelos alunos');

-- ============================================================
-- PERMISSOES DO ALUNO
-- ============================================================

INSERT INTO permissoes (id_perfil, nome, descricao) VALUES
(3, 'REALIZAR_LOGIN',
    'Permitir acesso do aluno ao sistema'),

(3, 'CADASTRAR_ALUNO',
    'Realizar o proprio cadastro no sistema'),

(3, 'GERENCIAR_PERFIL',
    'Consultar e atualizar os proprios dados'),

(3, 'GERENCIAR_PRE_MATRICULAS',
    'Realizar e acompanhar a propria pre-matricula'),

(3, 'GERENCIAR_DOCUMENTOS',
    'Enviar e consultar os proprios documentos'),

(3, 'GERENCIAR_SOLICITACOES',
    'Registrar e acompanhar as proprias solicitacoes');

-- ============================================================
-- 4. USUARIOS
-- ============================================================

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_pessoa INT NOT NULL UNIQUE,
    id_perfil INT NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255),
    cargo VARCHAR(100),
    setor VARCHAR(100),
    status ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_usuario_pessoa
        FOREIGN KEY (id_pessoa)
        REFERENCES pessoas(id_pessoa)
        ON DELETE RESTRICT,

    CONSTRAINT fk_usuario_perfil
        FOREIGN KEY (id_perfil)
        REFERENCES perfis(id_perfil)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 5. RECUPERACAO DE ACESSO
-- ============================================================

CREATE TABLE recuperacao_acesso (
    id_recuperacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    utilizado BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_recuperacao_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- USUARIOS PADRAO PARA TESTE
-- Senha dos dois usuarios: 123456
-- O hash abaixo e compativel com password_verify() do PHP.
-- ============================================================

-- ADMINISTRADOR

INSERT INTO pessoas (nome, cpf, telefone, data_nascimento) VALUES
('Administrador do Sistema',
 '111.444.777-35',
 '(61) 99999-0001',
 '1990-01-10');

INSERT INTO usuarios (
    id_pessoa,
    id_perfil,
    email,
    senha,
    cargo,
    setor,
    status
) VALUES (
    LAST_INSERT_ID(),
    1,
    'admin@escola.com',
    '$2y$12$J/CkmZyieUUkqtGxPvH6t.0ERuowjZHuQt5UNpobN0CUdsTusT5ii',
    'Administrador',
    'Administracao',
    'Ativo'
);

-- SECRETARIA

INSERT INTO pessoas (nome, cpf, telefone, data_nascimento) VALUES
('Maria da Secretaria',
 '529.982.247-25',
 '(61) 99999-0002',
 '1992-05-20');

INSERT INTO usuarios (
    id_pessoa,
    id_perfil,
    email,
    senha,
    cargo,
    setor,
    status
) VALUES (
    LAST_INSERT_ID(),
    2,
    'secretaria@escola.com',
    '$2y$12$J/CkmZyieUUkqtGxPvH6t.0ERuowjZHuQt5UNpobN0CUdsTusT5ii',
    'Secretaria Escolar',
    'Secretaria',
    'Ativo'
);

-- ============================================================
-- 6. CURSOS
-- ============================================================

CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    sigla VARCHAR(10) NOT NULL UNIQUE,
    imagem VARCHAR(255) NULL,
    status ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO cursos (nome, sigla, status) VALUES
('Administracao', 'ADM', 'Ativo'),
('Analise e Desenvolvimento de Sistemas', 'ADS', 'Ativo'),
('Pedagogia', 'PED', 'Ativo'),
('Enfermagem', 'ENF', 'Ativo');

-- ============================================================
-- 7. TIPOS_DOCUMENTO
-- ============================================================

CREATE TABLE tipos_documento (
    id_tipo_documento INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    obrigatorio BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

INSERT INTO tipos_documento (nome, obrigatorio) VALUES
('RG ou CNH', TRUE),
('CPF', TRUE),
('Comprovante de Residencia', TRUE),
('Historico Escolar do Ensino Medio', TRUE),
('Foto 3x4', TRUE);

-- ============================================================
-- 8. DOCUMENTOS_ENVIADOS
-- Os documentos pertencem ao aluno e podem ser enviados
-- antes da realizacao da pre-matricula.
-- ============================================================

CREATE TABLE documentos_enviados (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario_aluno INT NOT NULL,
    id_tipo_documento INT NOT NULL,
    caminho_arquivo VARCHAR(255),
    status ENUM(
        'Pendente',
        'Em Validacao',
        'Deferido',
        'Indeferido'
    ) DEFAULT 'Pendente',
    justificativa_recusa TEXT,
    data_envio DATETIME,
    data_avaliacao DATETIME,
    avaliado_por INT,

    CONSTRAINT fk_documento_aluno
        FOREIGN KEY (id_usuario_aluno)
        REFERENCES usuarios(id_usuario)
        ON DELETE RESTRICT,

    CONSTRAINT fk_documento_tipo
        FOREIGN KEY (id_tipo_documento)
        REFERENCES tipos_documento(id_tipo_documento)
        ON DELETE RESTRICT,

    CONSTRAINT fk_documento_avaliador
        FOREIGN KEY (avaliado_por)
        REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL,

    CONSTRAINT uq_documento_aluno_tipo
        UNIQUE (id_usuario_aluno, id_tipo_documento)
) ENGINE=InnoDB;

-- ============================================================
-- 9. PRE_MATRICULA
-- ============================================================

CREATE TABLE pre_matricula (
    id_pre_matricula INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_curso INT NOT NULL,
    status ENUM(
        'Em Analise',
        'Pendente de Documentacao',
        'Aprovado',
        'Indeferido'
    ) DEFAULT 'Em Analise',
    observacao TEXT,
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_analise DATETIME,
    analisado_por INT,

    CONSTRAINT fk_pre_matricula_aluno
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,

    CONSTRAINT fk_pre_matricula_curso
        FOREIGN KEY (id_curso)
        REFERENCES cursos(id_curso)
        ON DELETE RESTRICT,

    CONSTRAINT fk_pre_matricula_analisador
        FOREIGN KEY (analisado_por)
        REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 10. MATRICULAS
-- ============================================================

CREATE TABLE matriculas (
    id_matricula INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_pre_matricula INT NULL,
    codigo_matricula VARCHAR(20) NOT NULL UNIQUE,
    tipo_matricula ENUM(
        'Primeira Matricula',
        'Rematricula'
    ) NOT NULL,
    periodo_letivo VARCHAR(20) NOT NULL,
    status ENUM(
        'Ativo',
        'Trancado',
        'Cancelado',
        'Concluido'
    ) DEFAULT 'Ativo',
    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_matricula_aluno
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE RESTRICT,

    CONSTRAINT fk_matricula_pre_matricula
        FOREIGN KEY (id_pre_matricula)
        REFERENCES pre_matricula(id_pre_matricula)
        ON DELETE SET NULL,

    CONSTRAINT uq_aluno_periodo
        UNIQUE (id_usuario, periodo_letivo),

    CONSTRAINT uq_pre_matricula
        UNIQUE (id_pre_matricula)
) ENGINE=InnoDB;

-- ============================================================
-- 11. PENDENCIAS
-- ============================================================

CREATE TABLE pendencias (
    id_pendencia INT AUTO_INCREMENT PRIMARY KEY,
    id_pre_matricula INT NULL,
    id_usuario_aluno INT NULL,
    id_documento INT NULL,
    id_usuario_secretaria INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM(
        'Aberta',
        'Em Correcao',
        'Resolvida',
        'Cancelada'
    ) DEFAULT 'Aberta',
    data_abertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_resolucao DATETIME,
    resolvido_por INT NULL,

    CONSTRAINT fk_pendencia_pre_matricula
        FOREIGN KEY (id_pre_matricula)
        REFERENCES pre_matricula(id_pre_matricula)
        ON DELETE CASCADE,

    CONSTRAINT fk_pendencia_aluno
        FOREIGN KEY (id_usuario_aluno)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,

    CONSTRAINT fk_pendencia_documento
        FOREIGN KEY (id_documento)
        REFERENCES documentos_enviados(id_documento)
        ON DELETE CASCADE,

    CONSTRAINT fk_pendencia_secretaria
        FOREIGN KEY (id_usuario_secretaria)
        REFERENCES usuarios(id_usuario)
        ON DELETE RESTRICT,

    CONSTRAINT fk_pendencia_responsavel
        FOREIGN KEY (resolvido_por)
        REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 12. TIPOS_SOLICITACAO
-- ============================================================

CREATE TABLE tipos_solicitacao (
    id_tipo_solicitacao INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255),
    status ENUM('Ativo', 'Inativo') DEFAULT 'Ativo'
) ENGINE=InnoDB;

INSERT INTO tipos_solicitacao (nome, descricao) VALUES
('Declaracao de matricula',
 'Solicitacao de documento que comprove a matricula do aluno'),
('Declaracao de conclusao',
 'Solicitacao de documento que comprove a conclusao do curso'),
('Diploma de conclusao',
 'Solicitacao de diploma de conclusao'),
('Atualizacao cadastral',
 'Solicitacao de alteracao de dados cadastrais'),
('Segunda via de documento',
 'Solicitacao de segunda via de documento'),
('Outros',
 'Outras solicitacoes administrativas');

-- ============================================================
-- 13. SOLICITACOES
-- ============================================================

CREATE TABLE solicitacoes (
    id_solicitacao INT AUTO_INCREMENT PRIMARY KEY,
    numero_protocolo VARCHAR(30) NOT NULL UNIQUE,
    id_aluno INT NOT NULL,
    id_tipo_solicitacao INT NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM(
        'Aberta',
        'Em Atendimento',
        'Concluida',
        'Indeferida',
        'Cancelada'
    ) DEFAULT 'Aberta',
    data_abertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_conclusao DATETIME,
    atendido_por INT,
    resposta TEXT,

    CONSTRAINT fk_solicitacao_aluno
        FOREIGN KEY (id_aluno)
        REFERENCES usuarios(id_usuario)
        ON DELETE RESTRICT,

    CONSTRAINT fk_solicitacao_tipo
        FOREIGN KEY (id_tipo_solicitacao)
        REFERENCES tipos_solicitacao(id_tipo_solicitacao)
        ON DELETE RESTRICT,

    CONSTRAINT fk_solicitacao_atendente
        FOREIGN KEY (atendido_por)
        REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 14. DECLARACOES_EMITIDAS
-- ============================================================

CREATE TABLE declaracoes_emitidas (
    id_declaracao INT AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT NOT NULL,
    id_solicitacao INT NOT NULL UNIQUE,
    codigo_verificacao VARCHAR(50) NOT NULL UNIQUE,
    emitido_por INT NOT NULL,
    data_emissao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_declaracao_matricula
        FOREIGN KEY (id_matricula)
        REFERENCES matriculas(id_matricula)
        ON DELETE RESTRICT,

    CONSTRAINT fk_declaracao_solicitacao
        FOREIGN KEY (id_solicitacao)
        REFERENCES solicitacoes(id_solicitacao)
        ON DELETE RESTRICT,

    CONSTRAINT fk_declaracao_emissor
        FOREIGN KEY (emitido_por)
        REFERENCES usuarios(id_usuario)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 15. TRIGGER - VALIDAR PRE-MATRICULA
-- Somente ALUNO pode realizar pre-matricula e os documentos
-- obrigatorios precisam ter sido enviados previamente.
-- ============================================================

DELIMITER $$

CREATE TRIGGER trg_validar_pre_matricula
BEFORE INSERT ON pre_matricula
FOR EACH ROW
BEGIN

    DECLARE v_perfil INT;
    DECLARE v_documentos_obrigatorios INT;
    DECLARE v_documentos_enviados INT;

    SELECT id_perfil
    INTO v_perfil
    FROM usuarios
    WHERE id_usuario = NEW.id_usuario;

    IF v_perfil IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Usuario nao encontrado';
    END IF;

    IF v_perfil <> 3 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'A pre-matricula deve ser realizada por um usuario com perfil Aluno';
    END IF;

    SELECT COUNT(*)
    INTO v_documentos_obrigatorios
    FROM tipos_documento
    WHERE obrigatorio = TRUE;

    SELECT COUNT(DISTINCT id_tipo_documento)
    INTO v_documentos_enviados
    FROM documentos_enviados
    WHERE id_usuario_aluno = NEW.id_usuario
      AND caminho_arquivo IS NOT NULL;

    IF v_documentos_enviados < v_documentos_obrigatorios THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Todos os documentos obrigatorios devem ser enviados antes da pre-matricula';
    END IF;

END$$

DELIMITER ;

-- ============================================================
-- 16. TRIGGER - VALIDAR MATRICULA
-- ============================================================

DELIMITER $$

CREATE TRIGGER trg_validar_matricula
BEFORE INSERT ON matriculas
FOR EACH ROW
BEGIN

    DECLARE v_status VARCHAR(50);
    DECLARE v_usuario_pre INT;
    DECLARE v_perfil INT;

    IF NEW.tipo_matricula = 'Primeira Matricula' THEN

        IF NEW.id_pre_matricula IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Primeira matricula deve possuir uma pre-matricula';
        END IF;

        SELECT status, id_usuario
        INTO v_status, v_usuario_pre
        FROM pre_matricula
        WHERE id_pre_matricula = NEW.id_pre_matricula;

        IF v_usuario_pre IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pre-matricula nao encontrada';
        END IF;

        IF v_status <> 'Aprovado' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Pre-matricula ainda nao foi aprovada pela secretaria';
        END IF;

        SELECT id_perfil
        INTO v_perfil
        FROM usuarios
        WHERE id_usuario = NEW.id_usuario;

        IF v_perfil IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Usuario nao encontrado';
        END IF;

        IF v_perfil <> 3 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'O usuario da matricula deve possuir o perfil Aluno';
        END IF;

        IF NEW.id_usuario <> v_usuario_pre THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'O aluno nao corresponde ao usuario da pre-matricula';
        END IF;

    ELSEIF NEW.tipo_matricula = 'Rematricula' THEN

        IF NEW.id_pre_matricula IS NOT NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Rematricula nao deve possuir pre-matricula';
        END IF;

        SELECT id_perfil
        INTO v_perfil
        FROM usuarios
        WHERE id_usuario = NEW.id_usuario;

        IF v_perfil IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Usuario nao encontrado';
        END IF;

        IF v_perfil <> 3 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'O usuario da rematricula deve possuir o perfil Aluno';
        END IF;

    END IF;

END$$

DELIMITER ;

-- ============================================================
-- 17. CONFERENCIA FINAL
-- ============================================================

SHOW TABLES;

SELECT *
FROM perfis;

SELECT *
FROM permissoes
ORDER BY id_perfil, id_permissao;
