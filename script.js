/*
 * EduRank - carregamento e exibição do ranking.
 * JavaScript puro, sem bibliotecas. Compatível com navegadores
 * razoavelmente antigos (nenhum recurso ES6 obrigatório).
 */
(function () {
  'use strict';

  var cfg = (typeof CONFIG !== 'undefined') ? CONFIG : (window.CONFIG || {});

  function texto(chave, padrao) {
    return cfg[chave] !== undefined && cfg[chave] !== null ? cfg[chave] : padrao;
  }

  function criar(tag, classe, conteudo) {
    var el = document.createElement(tag);
    if (classe) { el.className = classe; }
    if (conteudo !== undefined && conteudo !== null) {
      el.appendChild(document.createTextNode(String(conteudo)));
    }
    return el;
  }

  /*
   * Parser de CSV próprio, sem bibliotecas.
   * Trata campos entre aspas (inclusive vírgulas e quebras de linha internas)
   * e normaliza quebras de linha (\r\n e \r para \n).
   */
  function parseCSV(textoBruto) {
    var texto = String(textoBruto).replace(/\r\n/g, '\n').replace(/\r/g, '\n');
    var linhas = [];
    var linha = [];
    var campo = '';
    var entreAspas = false;
    var i, ch;

    for (i = 0; i < texto.length; i++) {
      ch = texto.charAt(i);
      if (entreAspas) {
        if (ch === '"') {
          if (texto.charAt(i + 1) === '"') {
            campo += '"';
            i++;
          } else {
            entreAspas = false;
          }
        } else {
          campo += ch;
        }
      } else if (ch === '"') {
        entreAspas = true;
      } else if (ch === ',') {
        linha.push(campo);
        campo = '';
      } else if (ch === '\n') {
        linha.push(campo);
        linhas.push(linha);
        linha = [];
        campo = '';
      } else {
        campo += ch;
      }
    }
    linha.push(campo);
    linhas.push(linha);
    return linhas;
  }

  function montarIndices(cabecalho) {
    var indices = {};
    for (var i = 0; i < cabecalho.length; i++) {
      indices[cabecalho[i].trim().toLowerCase()] = i;
    }
    return indices;
  }

  function paraInteiro(valor) {
    var n = parseInt(valor, 10);
    return isNaN(n) ? 0 : n;
  }

  function extrairAlunos(textoCSV) {
    var linhas = parseCSV(textoCSV);
    var alunos = [];
    if (linhas.length < 2) { return alunos; }

    var idx = montarIndices(linhas[0]);
    var iNome = idx.nome !== undefined ? idx.nome : 0;
    var iAno = idx.ano !== undefined ? idx.ano : 1;
    var iImg = idx.img !== undefined ? idx.img : 2;
    var iPontos = idx.pontos !== undefined ? idx.pontos : 3;

    for (var i = 1; i < linhas.length; i++) {
      var colunas = linhas[i];
      if (!colunas || colunas.length === 0) { continue; }

      var nome = (colunas[iNome] || '').trim();
      if (!nome) { continue; }

      alunos.push({
        nome: nome,
        ano: (colunas[iAno] || '').trim(),
        img: (colunas[iImg] || '').trim() || 'user.png',
        pontos: paraInteiro(colunas[iPontos])
      });
    }
    return alunos;
  }

  function ordenar(alunos) {
    alunos.sort(function (a, b) {
      if (b.pontos !== a.pontos) { return b.pontos - a.pontos; }
      var na = a.nome.toLowerCase();
      var nb = b.nome.toLowerCase();
      if (na < nb) { return -1; }
      if (na > nb) { return 1; }
      return 0;
    });
    return alunos;
  }

  function emojiAleatorio() {
    var emojis = ['👏', '👍', '🙌', '🤩', '🔥', '⭐️', '🏆', '💯'];
    return emojis[Math.floor(Math.random() * emojis.length)];
  }

  function montarLinha(aluno, posicao) {
    var item = criar('li', 'c-list__item');
    var grade = criar('div', 'c-list__grid');

    var lugar = criar('div', 'c-flag c-place u-bg--transparent', posicao);

    var media = criar('div', 'c-media');
    var img = criar('img', 'c-avatar c-media__img');
    img.src = aluno.img;
    img.alt = 'Foto de ' + aluno.nome;
    var conteudo = criar('div', 'c-media__content');
    conteudo.appendChild(criar('div', 'c-media__title', aluno.nome));
    conteudo.appendChild(criar('span', 'c-media__link u-text--small',
      texto('rotuloTurma', 'Turma') + ': ' + aluno.ano));
    media.appendChild(img);
    media.appendChild(conteudo);

    var kudos = criar('div', 'u-text--right c-kudos');
    var pontosDiv = criar('div', 'u-mt--8');
    pontosDiv.appendChild(criar('strong', null, aluno.pontos));
    pontosDiv.appendChild(document.createTextNode(' ' + emojiAleatorio()));
    kudos.appendChild(pontosDiv);

    grade.appendChild(lugar);
    grade.appendChild(media);
    grade.appendChild(kudos);
    item.appendChild(grade);

    function destacar(cor) {
      lugar.classList.add('u-text--dark');
      lugar.classList.add(cor);
      kudos.classList.add(cor.replace('u-bg--', 'u-text--'));
    }
    if (posicao === 1) {
      destacar('u-bg--yellow');
    } else if (posicao === 2) {
      destacar('u-bg--teal');
    } else if (posicao === 3) {
      destacar('u-bg--orange');
    }
    return item;
  }

  function renderizarVencedor(alunos) {
    var card = document.getElementById('winner');
    if (!card) { return; }
    card.innerHTML = '';

    if (alunos.length === 0) {
      card.appendChild(criar('div', 'u-text--small u-text--medium',
        'Nenhum aluno cadastrado ainda.'));
      return;
    }

    var vencedor = alunos[0];
    card.appendChild(criar('div', 'u-text--small u-text--medium u-mb--16',
      texto('rotuloDestaque', 'Aluno Destaque')));

    var img = criar('img', 'c-avatar c-avatar--lg');
    img.src = vencedor.img;
    img.alt = 'Foto de ' + vencedor.nome;
    card.appendChild(img);

    card.appendChild(criar('h3', 'u-mt--16', vencedor.nome));
    card.appendChild(criar('span', 'u-text--teal u-text--small',
      texto('rotuloTurma', 'Turma') + ': ' + vencedor.ano));
  }

  function renderizarLista(alunos) {
    var lista = document.getElementById('list');
    if (!lista) { return; }

    while (lista.children.length > 1) {
      lista.removeChild(lista.lastChild);
    }

    if (alunos.length === 0) {
      var vazio = criar('li', 'c-list__item');
      vazio.appendChild(criar('div', 'u-text--small u-text--medium',
        'Nenhum aluno cadastrado ainda.'));
      lista.appendChild(vazio);
      return;
    }

    for (var i = 0; i < alunos.length; i++) {
      lista.appendChild(montarLinha(alunos[i], i + 1));
    }
  }

  function renderizar(textoCSV) {
    var alunos = ordenar(extrairAlunos(textoCSV));
    renderizarVencedor(alunos);
    renderizarLista(alunos);
  }

  function carregarCSV() {
    if (typeof fetch !== 'function') {
      renderizar('');
      return;
    }
    fetch('ranking.csv', { cache: 'no-store' })
      .then(function (resposta) { return resposta.text(); })
      .then(function (textoCSV) { renderizar(textoCSV); })
      .catch(function (erro) {
        if (window.console && console.error) {
          console.error('Erro ao carregar o CSV:', erro);
        }
        renderizar('');
      });
  }

  // Modal de acesso à área de edição (a senha é validada no servidor PHP).
  function iniciarModal() {
    var modal = document.getElementById('passwordDialog');
    var form = document.getElementById('passwordForm');
    if (!modal || !form) { return; }

    function abrir() {
      modal.style.display = 'block';
      var campo = document.getElementById('passwordInput');
      if (campo) { campo.focus(); }
    }
    function fechar() {
      modal.style.display = 'none';
    }

    var abrirBtn = document.getElementById('openEditButton');
    if (abrirBtn) {
      abrirBtn.addEventListener('click', function (e) {
        e.preventDefault();
        abrir();
      });
    }
    var fecharBtn = document.getElementById('closePasswordDialog');
    if (fecharBtn) {
      fecharBtn.addEventListener('click', function (e) {
        e.preventDefault();
        fechar();
      });
    }
    modal.addEventListener('click', function (e) {
      if (e.target === modal) { fechar(); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.style.display === 'block') { fechar(); }
    });

    // Se o login falhou, o PHP volta para index.html?erro=1.
    if (/[?&]erro=1/.test(window.location.search)) {
      var aviso = document.getElementById('loginError');
      if (aviso) { aviso.style.display = 'block'; }
      abrir();
    }
  }

  function definirTexto(id, valor) {
    var el = document.getElementById(id);
    if (el && valor !== undefined) { el.textContent = valor; }
  }

  function aplicarTextos() {
    document.title = texto('nomeProjeto', 'EduRank') + ' — ' + texto('subtitulo', 'Ranking Educacional');
    definirTexto('tituloDestaques', texto('tituloDestaques', 'Destaques e Desafios'));
    definirTexto('mensagemDestaque', texto('mensagemDestaque', ''));
    definirTexto('rotuloPontos', texto('rotuloPontos', 'Pontos'));
    definirTexto('rotuloTurma', texto('rotuloTurma', 'Turma'));
  }

  function iniciar() {
    aplicarTextos();
    carregarCSV();
    iniciarModal();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();
