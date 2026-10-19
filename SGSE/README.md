# SGSE — Sistema de Gestão de Secretaria Escolar

O SGSE é uma aplicação web para apoiar processos de uma secretaria escolar. O sistema reúne o cadastro e o gerenciamento de usuários, o fluxo de pré-matrícula e o atendimento de solicitações de documentos.

## Funcionalidades disponíveis

- Cadastro de usuário e autenticação.
- Acesso às áreas do sistema conforme o perfil do usuário: administrador, secretaria ou aluno.
- Área administrativa com painel, gerenciamento de usuários, gerenciamento de secretárias e consulta de cursos.
- Fluxo de pré-matrícula do aluno, com envio de documentos, escolha de curso e acompanhamento do status.
- Análise de pré-matrículas e avaliação dos documentos enviados pela secretaria.
- Solicitação de documentos pelo aluno e acompanhamento dos pedidos.
- Atendimento das solicitações pela secretaria, incluindo atualização do andamento, resposta e envio de arquivo.
- Consulta e atualização do próprio perfil.

## Tecnologias e requisitos

- PHP com suporte a PDO e ao driver PDO MySQL.
- MySQL ou MariaDB.
- Apache para servir a aplicação.
- XAMPP ou outro ambiente local equivalente.
- Navegador web atualizado.

O projeto não informa uma versão mínima específica de PHP ou MySQL. Use versões compatíveis com os recursos e extensões utilizados pelo código, incluindo PDO MySQL.

## Como instalar e executar localmente

1. Copie a pasta `SGSE` para o diretório público do servidor local, por exemplo `C:\xampp\htdocs\Projeto-final-TCC\SGSE`.
2. Abra o painel do XAMPP e inicie os serviços **Apache** e **MySQL**.
3. Importe o arquivo `ScriptBD.sql` pelo phpMyAdmin ou pelo cliente MySQL.
4. Confira as configurações de conexão em `model/dao/Conexao.php`. No estado atual, elas estão definidas para `localhost`, banco `secretaria_db`, usuário `root` e senha vazia, configuração comum em uma instalação local padrão do XAMPP.
5. Acesse `http://localhost/Projeto-final-TCC/SGSE/view/login.php` no navegador. Se o projeto estiver em outro diretório, ajuste o endereço para corresponder ao local da pasta.
6. Para criar uma conta de aluno, use a tela de cadastro disponível a partir do login. As áreas internas dependem de uma sessão autenticada.

O arquivo `index.php` encaminha usuários autenticados para a área correspondente ao perfil. Para acessar o sistema sem uma sessão iniciada, use a tela de login.

## Atenção ao banco de dados

O `ScriptBD.sql` executa `DROP DATABASE IF EXISTS secretaria_db` antes de criar o banco novamente. Portanto, importar ou executar esse script apaga o banco `secretaria_db` existente e todos os dados nele. Use-o somente em uma instalação local nova ou depois de fazer um backup; não o execute em um banco com dados que deseja preservar.

O script cria as tabelas usadas pelo sistema, incluindo usuários, perfis, permissões, cursos, documentos, pré-matrículas, matrículas e solicitações. Para usar uma base já existente, ajuste a configuração de conexão e aplique as alterações de estrutura de forma controlada, sem executar o comando de remoção do banco.

O controle de acesso consulta a tabela `permissoes` vinculada ao perfil cadastrado para o usuário. Em bancos criados antes das permissões de solicitações do administrador e edição de perfil da secretaria, execute `MigracaoPermissoes.sql` no phpMyAdmin. Essa migração insere apenas as permissões ausentes e não apaga os dados. Para adicionar o status de solicitação `Indeferida` em um banco já existente, execute também `MigracaoStatusSolicitacoes.sql`; ela altera apenas os valores aceitos pela coluna de status.

## Perfis de acesso

- **Administrador:** acessa o painel e as áreas de gerenciamento de usuários e secretárias, além da consulta de cursos.
- **Secretaria:** analisa pré-matrículas, avalia documentos enviados e atende solicitações de documentos.
- **Aluno:** acessa sua área, atualiza o perfil, realiza e acompanha a pré-matrícula e solicita documentos.

## Estrutura do projeto

SGSE/
├── assets/       Arquivos estáticos, como CSS, JavaScript e imagens
├── controller/   Regras de fluxo e processamento das ações do sistema
├── model/
│   ├── dao/      Acesso ao banco de dados
│   └── dto/      Objetos de transferência de dados
├── uploads/      Arquivos enviados pela aplicação
├── view/         Telas da aplicação, organizadas por área de acesso
├── index.php     Redirecionamento inicial conforme a sessão e o perfil
└── ScriptBD.sql  Criação do banco de dados e das tabelas
```

Dentro de `view/`, as páginas são organizadas nas áreas `admin/`, `secretaria/` e `usuario/`. A pasta `includes/` contém componentes compartilhados pelas telas, como cabeçalho e menu.

## Observações

- O servidor web precisa ter permissão para gravar os arquivos enviados na pasta `uploads/`.
- A conexão do banco no código está configurada para desenvolvimento local. Em outros ambientes, atualize as credenciais e não utilize credenciais de desenvolvimento em produção.
- O escopo documentado aqui corresponde às telas e aos componentes presentes no projeto; a existência de tabelas no banco não significa, por si só, que todos os módulos escolares estejam implementados na interface.
