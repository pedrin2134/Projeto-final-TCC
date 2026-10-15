# SGSE — Sistema de Gestão de Secretaria Escolar
Sistema de gestão escolar para centralização de processos acadêmicos, administrativos e pedagógicos, com gerenciamento de alunos, professores, responsáveis, turmas, disciplinas, matrículas, notas, frequência e controle de usuários.

Essas pastas ficam dentro de view/ e guardam as telas do sistema, separadas por tipo de usuário:

includes/
São partes compartilhadas pelas páginas:

topo.php: inicia a sessão, monta o cabeçalho, mostra o usuário logado e carrega os estilos comuns.
menu_administrativo.php: monta a barra lateral. Os links mudam conforme o perfil: administrador, secretaria ou aluno.
rodape.php: está vazio no momento.
A ideia é incluir o cabeçalho e o menu em várias páginas sem duplicar esse código.

admin/
Telas de administração, protegidas para que só o perfil de administrador possa acessá-las:

secretarias.php: lista e pesquisa secretárias; permite consultar, alterar e excluir cadastros.
cadastrar_secretaria.php: formulário para criar uma conta de secretaria.
editar_secretaria.php: formulário para alterar os dados de uma secretária.
home_admin.php: atualmente apenas redireciona para a tela de análise de pré-matrículas.
secretaria/
Telas para o trabalho da secretaria:

pre_matriculas.php: mostra inscrições, documentos enviados e permite deferir ou indeferir documentos e pré-matrículas.
solicitacoes.php: exibe pedidos de documentos feitos pelos alunos; permite atualizar o andamento, responder e anexar um arquivo.
documentos.php: atualmente só redireciona para pre_matriculas.php.
usuario/
Telas da área do aluno:

home_usuario.php: página inicial com atalhos para editar o perfil e solicitar documentos. Se a pré-matrícula ainda não foi aprovada, encaminha o aluno para a etapa de pré-matrícula.
pre_matricula.php: guia o aluno pelo envio dos documentos, escolha do curso e acompanhamento do resultado.
solicitar_documentos.php: permite abrir pedidos para a secretaria e acompanhar protocolo, status, resposta e eventual documento enviado.
O arquivo marcado, cadastrar_usuario.php, fica diretamente em view, fora dessas quatro pastas. Ele é a tela inicial de cadastro: coleta dados pessoais, senha e aceite dos termos de privacidade, depois envia as informações ao UsuarioController.php.
