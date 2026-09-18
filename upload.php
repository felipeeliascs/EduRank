<?php
/*
 * EduRank - envio de um novo ranking.csv (somente autenticado).
 * Página com formulário; valida extensão, tamanho e cabeçalho.
 * Cria backup da versão anterior antes de substituir.
 */
session_start();

if (empty($_SESSION['edurank_auth'])) {
    header('Location: index.html?erro=1');
    exit;
}

$mensagem = '';
$tipo = '';
$maxBytes = 1024 * 1024; // 1 MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csvFile']) || !is_array($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
        $tipo = 'erro';
        $mensagem = 'Nenhum arquivo válido foi enviado.';
    } else {
        $arquivo = $_FILES['csvFile'];
        $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $tipo = 'erro';
            $mensagem = 'Envie apenas arquivos com extensão .csv.';
        } elseif ($arquivo['size'] <= 0 || $arquivo['size'] > $maxBytes) {
            $tipo = 'erro';
            $mensagem = 'O arquivo deve ter no máximo 1 MB.';
        } elseif (!is_uploaded_file($arquivo['tmp_name'])) {
            $tipo = 'erro';
            $mensagem = 'Upload inválido.';
        } else {
            $conteudo = (string) file_get_contents($arquivo['tmp_name']);
            $primeiraLinha = strtolower((string) strtok($conteudo, "\n"));

            if (strpos($primeiraLinha, 'nome') === false || strpos($primeiraLinha, 'pontos') === false) {
                $tipo = 'erro';
                $mensagem = 'CSV inválido: o cabeçalho deve ser nome,ano,img,pontos.';
            } else {
                $csvFile = __DIR__ . '/ranking.csv';
                $backupDir = __DIR__ . '/backup';

                if (!is_dir($backupDir)) {
                    mkdir($backupDir, 0775, true);
                }

                $backupOk = true;
                if (is_file($csvFile)) {
                    $base = $backupDir . '/ranking_' . date('Y-m-d_H-i-s');
                    $backupFile = $base . '.csv';
                    $i = 1;
                    while (file_exists($backupFile)) {
                        $backupFile = $base . '_' . $i . '.csv';
                        $i++;
                    }
                    $backupOk = copy($csvFile, $backupFile);
                }

                if (!$backupOk) {
                    $tipo = 'erro';
                    $mensagem = 'Erro ao criar o backup. Nada foi alterado.';
                } elseif (move_uploaded_file($arquivo['tmp_name'], $csvFile)) {
                    $tipo = 'sucesso';
                    $mensagem = 'CSV enviado com sucesso e backup criado.';
                } else {
                    $tipo = 'erro';
                    $mensagem = 'Erro ao salvar o arquivo enviado.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>EduRank — Enviar CSV</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./style.css">
</head>
<body>
  <div class="l-wrapper">
    <header class="c-header">
      <a class="c-logo-link" href="index.html" aria-label="EduRank">
        <img class="c-logo" src="logo.png" alt="EduRank" draggable="false"
             onerror="this.onerror=null;this.style.display='none';document.getElementById('logoFallback').style.display='inline-block';">
        <span class="c-logo-fallback" id="logoFallback">EDURANK</span>
      </a>
      <nav class="c-nav">
        <a href="edit.php" class="c-button c-button--dark">Voltar à edição</a>
        <a href="logout.php" class="c-button c-button--dark">Sair</a>
      </nav>
    </header>
    <div class="l-grid">
      <div class="l-grid__item">
        <div class="c-card">
          <div class="c-card__header">
            <h3>Enviar arquivo CSV</h3>
          </div>
          <div class="c-card__body">
            <?php if ($mensagem !== ''): ?>
              <p class="u-mensagem-<?php echo $tipo === 'sucesso' ? 'sucesso' : 'erro'; ?>"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <form id="uploadForm" method="post" action="upload.php" enctype="multipart/form-data">
              <label for="csvFile">Arquivo CSV (máx. 1 MB, cabeçalho <code>nome,ano,img,pontos</code>)</label>
              <input type="file" id="csvFile" name="csvFile" accept=".csv,text/csv" required>
              <button type="submit" class="c-button c-button--primary">Enviar</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
