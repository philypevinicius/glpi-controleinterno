<?php

/**
 * Plugin Controle Interno - Lista do histórico de movimentação de
 * funcionários (admissão, desligamento, renovação/bloqueio/desbloqueio de
 * acesso).
 */
if (!class_exists(Session::class)) {
    require_once __DIR__ . '/../../../inc/includes.php';
}

use GlpiPlugin\Controleinterno\AccessService;
use GlpiPlugin\Controleinterno\Funcionario;
use GlpiPlugin\Controleinterno\Tabs;

global $CFG_GLPI, $DB;

Session::checkLoginUser();
AccessService::exigirAcesso();

$podeEditar = AccessService::canAccess(UPDATE);

Html::header(__('Controle Interno', 'controleinterno'), '', 'tools', 'plugincontroleinterno');

echo "<div class='container-fluid' style='margin-top: 1em;'>";
Tabs::desenhar('funcionarios');

echo "<div class='card'>";
echo "<div class='card-body'>";

echo "<div class='d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3'>";
echo "<div>";
echo "<h2 class='mb-0'>" . __('Funcionários', 'controleinterno') . "</h2>";
echo "<div class='text-muted small'>" . __('Histórico de admissão, desligamento e acesso - um registro por evento.', 'controleinterno') . "</div>";
echo "</div>";
if ($podeEditar) {
    echo "<a class='btn btn-primary btn-sm' href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/funcionario.form.php'>";
    echo "<i class='ti ti-plus'></i> " . __('Novo evento', 'controleinterno') . "</a>";
}
echo "</div>";

$linhas = $DB->request([
    'FROM'  => Funcionario::getTable(),
    'ORDER' => ['data DESC', 'id DESC'],
]);

if (count($linhas) === 0) {
    echo "<div class='text-muted small'>" . __('Nenhum evento registrado ainda.', 'controleinterno') . "</div>";
} else {
    echo "<div class='row g-2 mb-3'>";
    echo "<div class='col-md-6'>";
    echo "<input type='text' class='form-control' data-busca-tabela='ci-tabela-funcionarios' placeholder='" . __s('Pesquisar...', 'controleinterno') . "'>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<select class='form-select' data-filtro-select='ci-tabela-funcionarios' data-filtro-col='evento'>";
    echo "<option value=''>" . __('Todos os eventos', 'controleinterno') . "</option>";
    foreach (Funcionario::EVENTOS as $chave => $rotulo) {
        echo "<option value='" . $chave . "'>" . htmlescape(__($rotulo, 'controleinterno')) . "</option>";
    }
    echo "</select>";
    echo "</div>";
    echo "</div>";

    echo "<table class='table table-striped' id='ci-tabela-funcionarios'>";
    echo "<thead><tr>";
    echo "<th>" . __('Funcionário', 'controleinterno') . "</th>";
    echo "<th>" . __('Evento', 'controleinterno') . "</th>";
    echo "<th>" . __('Data', 'controleinterno') . "</th>";
    echo "<th>" . __('Chamado', 'controleinterno') . "</th>";
    echo "<th>" . __('Observação', 'controleinterno') . "</th>";
    echo "<th>" . __('Registrado por', 'controleinterno') . "</th>";
    echo "</tr></thead><tbody>";
    foreach ($linhas as $linha) {
        echo "<tr>";
        if ($podeEditar) {
            echo "<td><a href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/funcionario.form.php?id=" . $linha['id'] . "'>";
            echo htmlescape($linha['nome']) . "</a></td>";
        } else {
            echo "<td>" . htmlescape($linha['nome']) . "</td>";
        }
        echo "<td data-col='evento' data-valor='" . htmlescape($linha['evento']) . "'>" . htmlescape(Funcionario::rotuloEvento($linha['evento'])) . "</td>";
        echo "<td>" . ($linha['data'] ? Html::convDate($linha['data']) : '-') . "</td>";
        echo "<td>" . ((int) $linha['tickets_id'] > 0
            ? "<a href='/front/ticket.form.php?id=" . (int) $linha['tickets_id'] . "' target='_blank'>#" . (int) $linha['tickets_id'] . "</a>"
            : '-') . "</td>";
        echo "<td>" . htmlescape($linha['observacao'] ?: '-') . "</td>";
        echo "<td>" . ($linha['users_id_registro'] ? htmlescape(getUserName($linha['users_id_registro'])) : '-') . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}

echo "</div></div>";

echo "</div>";

echo Html::script('plugins/controleinterno/public/js/busca-tabela.js');

Html::footer();
