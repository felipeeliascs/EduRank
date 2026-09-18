# EduRank

Sistema leve de ranking educacional, desenvolvido para funcionar inclusive em
computadores com poucos recursos disponíveis em escolas.

## Características

- sem banco de dados;
- dados armazenados em CSV;
- HTML/CSS/JavaScript puro;
- backend PHP mínimo apenas para edição;
- baixo consumo de recursos;
- funciona em servidor local;
- funciona sem acesso à internet;
- aplicável a qualquer componente curricular;
- ranking automático;
- destaque dos primeiros colocados;
- backup automático do CSV;
- área de atualização protegida.

## Objetivo

O EduRank surgiu inicialmente como um ranking utilizado em um projeto escolar
de incentivo à leitura e foi posteriormente generalizado para permitir seu uso
em diferentes disciplinas e projetos educacionais — Matemática, Língua
Portuguesa, Ciências, História, Geografia, Arte, Educação Física, Robótica,
olimpíadas, feiras científicas e projetos interdisciplinares.

A lógica é intencionalmente simples: **aluno + turma/ano + imagem + pontuação**.
A pontuação representa os pontos acumulados pelo estudante nas atividades
definidas pelo professor. Não há cadastro de usuários, histórico individual ou
banco de dados. A simplicidade é uma decisão de projeto.

Os textos da interface podem ser personalizados em `config.js`.

## Estrutura

```
edurank/
├── index.html          # ranking (página pública)
├── edit.php            # edição (protegida por senha)
├── login.php           # autenticação no servidor
├── logout.php          # encerra a sessão
├── save.php            # salva o CSV com backup
├── upload.php          # envio de CSV (protegido)
├── config.js           # textos configuráveis
├── config.example.php  # exemplo de configuração da senha
├── script.js           # carregamento e ordenação do ranking
├── style.css           # estilos
├── ranking.csv         # base de dados (CSV)
├── user.png            # avatar padrão
├── logo.png            # logotipo genérico (opcional; há fallback em texto)
├── backup/             # backups automáticos do CSV
└── README.md
```

## Formato dos dados

`ranking.csv` usa o cabeçalho:

```
nome,ano,img,pontos
```

- `nome`: nome do aluno;
- `ano`: turma ou ano/turma (exibido como "Turma");
- `img`: arquivo de imagem do aluno (`user.png` por padrão);
- `pontos`: número acumulado.

O ranking é calculado automaticamente pelo JavaScript, em ordem decrescente de
pontos. Em caso de empate, a ordem é alfabética pelo nome. A ordem das linhas
no CSV não importa.

## Requisitos

- servidor web com PHP (XAMPP, WAMP, Laragon ou similar);
- navegador básico;
- nenhuma instalação de banco de dados;
- nenhuma dependência externa (funciona offline).

## Como executar

1. Copie a pasta do projeto para o diretório web do seu servidor local
   (por exemplo, `C:\xampp\htdocs\edurank`).
2. Inicie o Apache no painel do XAMPP/WAMP/Laragon.
3. Acesse `http://localhost/edurank/`.

### Configurar a senha da área de edição

1. Copie `config.example.php` para `config.php`.
2. Gere o hash da senha:

   ```
   php -r "echo password_hash('suaSenha', PASSWORD_DEFAULT), PHP_EOL;"
   ```

3. Cole o resultado em `$PASSWORD_HASH` dentro de `config.php`.
4. O arquivo `config.php` está no `.gitignore` e nunca deve ser publicado.
   Troque a senha padrão por uma de sua escolha antes de usar em produção.

A senha é verificada **somente no servidor** (`password_verify`). Nenhuma senha
fica gravada no HTML ou no JavaScript.

### Backup

A cada salvamento ou upload, a versão anterior de `ranking.csv` é copiada para
`backup/` no formato `ranking_AAAA-MM-DD_HH-MM-SS.csv`.

## Privacidade

A base incluída neste repositório é **fictícia**, apenas para demonstração.
Não publique nomes reais de alunos. A pasta `backup/` e o arquivo `config.php`
estão no `.gitignore`.

## Aplicações

Matemática, Língua Portuguesa, Ciências, História, Geografia, Arte, Educação
Física, Robótica, projetos interdisciplinares, olimpíadas, feiras científicas e
demais atividades escolares.
