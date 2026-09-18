<?php
/* EduRank - página de edição (acesso restrito ao responsável). */
session_start();

if (empty($_SESSION['edurank_auth'])) {
    header('Location: index.html?erro=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>EduRank — Editar Ranking</title>
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
        <a href="upload.php" class="c-button c-button--dark">Enviar CSV</a>
        <a href="logout.php" class="c-button c-button--dark">Sair</a>
      </nav>
    </header>

    <div class="l-grid">
      <div class="l-grid__item">
        <div class="c-card">
          <div class="c-card__header">
            <h3>Editar Ranking</h3>
          </div>
          <div class="c-card__body">
            <form id="editForm">
              <table class="c-table">
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th id="thTurma">Turma</th>
                    <th>Imagem</th>
                    <th id="thPontos">Pontos</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody id="rankingTable"></tbody>
              </table>
              <button type="button" id="addRowButton" class="c-button c-button--secondary">Adicionar Linha</button>
              <button type="submit" class="c-button c-button--primary">Salvar Alterações</button>
            </form>
            <div id="message" role="status" aria-live="polite"></div>
          </div>
        </div>

        <div class="c-card">
          <div class="c-card__header">
            <h3>Editar por texto (CSV)</h3>
          </div>
          <div class="c-card__body">
            <details id="textDetails">
              <summary>Mostrar editor de texto</summary>
              <form id="textForm">
                <label for="csvContent">Conteúdo do ranking.csv (cabeçalho <code>nome,ano,img,pontos</code>)</label>
                <textarea id="csvContent" class="c-textarea" spellcheck="false"></textarea>
                <div class="c-actions">
                  <button type="button" id="loadButton" class="c-button c-button--secondary">Recarregar do servidor</button>
                  <button type="submit" class="c-button c-button--primary">Salvar texto</button>
                </div>
              </form>
              <div id="textMessage" role="status" aria-live="polite"></div>
            </details>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="./config.js"></script>
  <script>
  (function () {
    'use strict';

    var cfg = (typeof CONFIG !== 'undefined') ? CONFIG : (window.CONFIG || {});
    var rankingTable = document.getElementById('rankingTable');
    var messageDiv = document.getElementById('message');
    var textMessage = document.getElementById('textMessage');
    var csvContent = document.getElementById('csvContent');

    function definirTexto(id, valor) {
      var el = document.getElementById(id);
      if (el && valor !== undefined) { el.textContent = valor; }
    }
    definirTexto('thTurma', cfg.rotuloTurma || 'Turma');
    definirTexto('thPontos', cfg.rotuloPontos || 'Pontos');

    // --- Parser CSV próprio (mesmo comportamento do script.js) ---
    function parseCSV(textoBruto) {
      var texto = String(textoBruto).replace(/\r\n/g, '\n').replace(/\r/g, '\n');
      var linhas = [], linha = [], campo = '', entreAspas = false, i, ch;
      for (i = 0; i < texto.length; i++) {
        ch = texto.charAt(i);
        if (entreAspas) {
          if (ch === '"') {
            if (texto.charAt(i + 1) === '"') { campo += '"'; i++; }
            else { entreAspas = false; }
          } else { campo += ch; }
        } else if (ch === '"') { entreAspas = true; }
        else if (ch === ',') { linha.push(campo); campo = ''; }
        else if (ch === '\n') { linha.push(campo); linhas.push(linha); linha = []; campo = ''; }
        else { campo += ch; }
      }
      linha.push(campo);
      linhas.push(linha);
      return linhas;
    }

    function paraInteiro(valor) {
      var n = parseInt(valor, 10);
      return isNaN(n) ? 0 : n;
    }

    function extrairAlunos(texto) {
      var linhas = parseCSV(texto);
      var alunos = [];
      if (linhas.length < 2) { return alunos; }
      var cabecalho = linhas[0], idx = {}, i;
      for (i = 0; i < cabecalho.length; i++) { idx[cabecalho[i].trim().toLowerCase()] = i; }
      var iNome = idx.nome !== undefined ? idx.nome : 0;
      var iAno = idx.ano !== undefined ? idx.ano : 1;
      var iImg = idx.img !== undefined ? idx.img : 2;
      var iPontos = idx.pontos !== undefined ? idx.pontos : 3;
      for (i = 1; i < linhas.length; i++) {
        var col = linhas[i];
        if (!col || col.length === 0) { continue; }
        var nome = (col[iNome] || '').replace(/^\s+|\s+$/g, '');
        if (!nome) { continue; }
        alunos.push({
          nome: nome,
          ano: (col[iAno] || '').replace(/^\s+|\s+$/g, ''),
          img: (col[iImg] || '').replace(/^\s+|\s+$/g, '') || 'user.png',
          pontos: paraInteiro(col[iPontos])
        });
      }
      return alunos;
    }

    function ordenarAlunos(alunos) {
      alunos.sort(function (a, b) {
        if (b.pontos !== a.pontos) { return b.pontos - a.pontos; }
        var na = a.nome.toLowerCase(), nb = b.nome.toLowerCase();
        if (na < nb) { return -1; }
        if (na > nb) { return 1; }
        return 0;
      });
      return alunos;
    }

    function csvEscape(valor) {
      var v = String(valor == null ? '' : valor);
      if (/[",\n\r]/.test(v)) { return '"' + v.replace(/"/g, '""') + '"'; }
      return v;
    }

    function alunosParaCSV(alunos) {
      var linhas = ['nome,ano,img,pontos'];
      for (var i = 0; i < alunos.length; i++) {
        var a = alunos[i];
        linhas.push([csvEscape(a.nome), csvEscape(a.ano), csvEscape(a.img), a.pontos].join(','));
      }
      return linhas.join('\n');
    }

    function mostrar(el, tipo, msg) {
      if (!el) { return; }
      el.innerHTML = '';
      var p = document.createElement('p');
      p.className = 'u-mensagem-' + tipo;
      p.appendChild(document.createTextNode(msg));
      el.appendChild(p);
    }

    function ajustarPontos(linha, delta) {
      var input = linha.querySelector('input[name="pontos[]"]');
      var atual = parseInt(input.value, 10);
      if (isNaN(atual)) { atual = 0; }
      var novo = atual + delta;
      input.value = novo < 0 ? 0 : novo;
    }

    function botaoAcao(texto, titulo, classe, handler) {
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = texto;
      b.title = titulo;
      b.setAttribute('aria-label', titulo);
      b.className = classe;
      b.addEventListener('click', handler);
      return b;
    }

    function celulaInput(tipo, nome, valor, rotulo) {
      var td = document.createElement('td');
      var input = document.createElement('input');
      input.type = tipo;
      input.name = nome;
      input.value = valor;
      input.setAttribute('aria-label', rotulo);
      if (tipo === 'number') { input.min = '0'; input.step = '1'; }
      td.appendChild(input);
      return td;
    }

    function criarLinha(nome, ano, img, pontos) {
      var tr = document.createElement('tr');
      tr.appendChild(celulaInput('text', 'nome[]', nome || '', 'Nome do aluno'));
      tr.appendChild(celulaInput('text', 'ano[]', ano || '', cfg.rotuloTurma || 'Turma'));
      tr.appendChild(celulaInput('text', 'img[]', img || 'user.png', 'Arquivo de imagem'));
      tr.appendChild(celulaInput('number', 'pontos[]', (pontos === undefined || pontos === null) ? 0 : pontos, cfg.rotuloPontos || 'Pontos'));

      var td = document.createElement('td');
      td.className = 'c-actions';
      td.appendChild(botaoAcao('-', 'Diminuir pontos', 'c-button c-button--small', function () { ajustarPontos(tr, -1); }));
      td.appendChild(botaoAcao('+', 'Aumentar pontos', 'c-button c-button--small', function () { ajustarPontos(tr, 1); }));
      td.appendChild(botaoAcao('Excluir', 'Excluir aluno', 'c-button c-button--danger', function () {
        tr.parentNode.removeChild(tr);
      }));
      tr.appendChild(td);
      return tr;
    }

    function coletarAlunos() {
      var alunos = [];
      var linhas = rankingTable.querySelectorAll('tr');
      for (var i = 0; i < linhas.length; i++) {
        var l = linhas[i];
        var nome = l.querySelector('input[name="nome[]"]').value.replace(/^\s+|\s+$/g, '');
        if (!nome) { continue; }
        var ano = l.querySelector('input[name="ano[]"]').value.replace(/^\s+|\s+$/g, '');
        var img = l.querySelector('input[name="img[]"]').value.replace(/^\s+|\s+$/g, '') || 'user.png';
        alunos.push({ nome: nome, ano: ano, img: img, pontos: paraInteiro(l.querySelector('input[name="pontos[]"]').value) });
      }
      return alunos;
    }

    function renderizarTabela(alunos) {
      rankingTable.innerHTML = '';
      for (var i = 0; i < alunos.length; i++) {
        rankingTable.appendChild(criarLinha(alunos[i].nome, alunos[i].ano, alunos[i].img, alunos[i].pontos));
      }
    }

    function carregarDados() {
      return fetch('ranking.csv', { cache: 'no-store' })
        .then(function (r) { return r.text(); })
        .then(function (texto) {
          renderizarTabela(ordenarAlunos(extrairAlunos(texto)));
          if (csvContent) { csvContent.value = texto.replace(/\r\n/g, '\n').replace(/\r/g, '\n'); }
        })
        .catch(function () {
          renderizarTabela([]);
        });
    }

    function salvar(csv, destino) {
      fetch('save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csv: csv })
      })
      .then(function (r) { return r.json(); })
      .then(function (resultado) {
        if (resultado.status === 'success') {
          mostrar(destino, 'sucesso', resultado.message);
          carregarDados();
        } else {
          mostrar(destino, 'erro', resultado.message || 'Erro ao salvar.');
        }
      })
      .catch(function () {
        mostrar(destino, 'erro', 'Erro ao salvar o arquivo.');
      });
    }

    document.getElementById('addRowButton').addEventListener('click', function () {
      rankingTable.appendChild(criarLinha('', '', 'user.png', 0));
    });

    document.getElementById('editForm').addEventListener('submit', function (e) {
      e.preventDefault();
      var alunos = ordenarAlunos(coletarAlunos());
      if (alunos.length === 0) {
        mostrar(messageDiv, 'erro', 'Adicione pelo menos um aluno antes de salvar.');
        return;
      }
      salvar(alunosParaCSV(alunos), messageDiv);
    });

    document.getElementById('loadButton').addEventListener('click', function () {
      carregarDados().then(function () {
        mostrar(textMessage, 'sucesso', 'Conteúdo recarregado do servidor.');
      });
    });

    document.getElementById('textForm').addEventListener('submit', function (e) {
      e.preventDefault();
      var csv = csvContent.value.replace(/^\s+|\s+$/g, '');
      if (!csv) {
        mostrar(textMessage, 'erro', 'O conteúdo está vazio.');
        return;
      }
      salvar(csv, textMessage);
    });

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', carregarDados);
    } else {
      carregarDados();
    }
  })();
  </script>
</body>
</html>
