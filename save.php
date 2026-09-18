<?php
/*
 * EduRank - salva ranking.csv criando backup da versão anterior.
 * Somente usuários autenticados podem salvar.
 */
header('Content-Type: application/json; charset=utf-8');

session_start();

if (empty($_SESSION['edurank_auth'])) {
    http_response_code(401);
    echo json_encode(array('status' => 'error', 'message' => 'Sessão expirada. Faça login novamente.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('status' => 'error', 'message' => 'Método não permitido.'));
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data) || !isset($data['csv']) || !is_string($data['csv'])) {
    echo json_encode(array('status' => 'error', 'message' => 'Dados inválidos.'));
    exit;
}

$csv = str_replace(array("\r\n", "\r"), "\n", $data['csv']);
$csv = trim($csv);

if ($csv === '' || strlen($csv) > 2097152) {
    echo json_encode(array('status' => 'error', 'message' => 'Conteúdo vazio ou grande demais.'));
    exit;
}

$linhas = explode("\n", $csv);
$cabecalho = strtolower(isset($linhas[0]) ? $linhas[0] : '');

if (strpos($cabecalho, 'nome') === false || strpos($cabecalho, 'pontos') === false) {
    echo json_encode(array('status' => 'error', 'message' => 'O CSV precisa ter o cabeçalho nome,ano,img,pontos.'));
    exit;
}

$csvFile = __DIR__ . '/ranking.csv';
$backupDir = __DIR__ . '/backup';

if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    echo json_encode(array('status' => 'error', 'message' => 'Não foi possível criar a pasta de backup.'));
    exit;
}

// Backup da versão anterior ANTES de sobrescrever.
if (is_file($csvFile)) {
    $base = $backupDir . '/ranking_' . date('Y-m-d_H-i-s');
    $backupFile = $base . '.csv';
    $i = 1;
    while (file_exists($backupFile)) {
        $backupFile = $base . '_' . $i . '.csv';
        $i++;
    }
    if (!copy($csvFile, $backupFile)) {
        echo json_encode(array('status' => 'error', 'message' => 'Erro ao criar o backup. Nada foi alterado.'));
        exit;
    }
}

if (file_put_contents($csvFile, $csv . "\n", LOCK_EX) === false) {
    echo json_encode(array('status' => 'error', 'message' => 'Erro ao salvar o arquivo.'));
    exit;
}

echo json_encode(array('status' => 'success', 'message' => 'Arquivo salvo com sucesso e backup criado.'));
