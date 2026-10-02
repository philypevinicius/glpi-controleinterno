(function () {
    'use strict';

    var campoBusca = document.getElementById('ci-eq-busca');
    var divLista = document.getElementById('ci-eq-lista');

    if (!campoBusca || !divLista) {
        return;
    }

    var campoDescricao = document.getElementById('ci-eq-descricao');
    var campoTombamento = document.getElementById('ci-eq-tombamento');
    var campoSerie = document.getElementById('ci-eq-serie');
    var campoLocal = document.getElementById('ci-eq-local');
    var atrasoBusca = null;

    function escaparHtml(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function selecionarItem(item) {
        // Só preenche os campos - continuam editáveis depois, é só um
        // ponto de partida pra quem já está no cadastro do GLPI.
        campoDescricao.value = item.nome || (item.tipo_label + ' ' + (item.tombamento || item.numero_serie || ''));
        campoTombamento.value = item.tombamento || '';
        campoSerie.value = item.numero_serie || '';
        campoLocal.value = item.local_nome || '';
        divLista.innerHTML = '';
        campoBusca.value = '';
    }

    function desenharLista(itens) {
        if (!Array.isArray(itens) || itens.length === 0) {
            divLista.innerHTML = '<div class="text-muted p-2">' +
                (campoBusca.value.trim() === ''
                    ? 'Nenhum ativo com tombo/série cadastrado no GLPI.'
                    : 'Nada encontrado - preencha os campos na mão.') +
                '</div>';
            return;
        }

        var html = '';
        itens.forEach(function (item, indice) {
            var identificador = item.tombamento || item.numero_serie || item.nome;
            html += '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between" data-indice="' + indice + '">' +
                '<span>' + escaparHtml(identificador) + ' · ' + escaparHtml(item.tipo_label) + ' · ' + escaparHtml(item.nome) + '</span>' +
                '<span class="text-muted">' + escaparHtml(item.local_nome || '') + '</span>' +
                '</button>';
        });
        divLista.innerHTML = html;

        divLista.querySelectorAll('[data-indice]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                selecionarItem(itens[parseInt(botao.dataset.indice, 10)]);
            });
        });
    }

    function buscar() {
        var termo = campoBusca.value.trim();
        var url = CFG_GLPI.root_doc + '/plugins/controleinterno/ajax/buscar_equipamento.php?termo=' + encodeURIComponent(termo);

        fetch(url)
            .then(function (resposta) { return resposta.json(); })
            .then(function (itens) {
                desenharLista(itens);
            })
            .catch(function () {
                divLista.innerHTML = '<div class="text-danger p-2">Falha ao buscar. Tente de novo.</div>';
            });
    }

    campoBusca.addEventListener('input', function () {
        window.clearTimeout(atrasoBusca);
        atrasoBusca = window.setTimeout(buscar, 300);
    });

    // Lista inicial assim que a tela abre, igual o plugin Movimentações.
    buscar();
})();
