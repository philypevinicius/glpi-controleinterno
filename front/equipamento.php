<?php

/**
 * Plugin Controle Interno - Lista do registro interno de equipamentos
 * (inclusive os que não têm tombamento no GLPI).
 */
require_once __DIR__ . '/../../../inc/includes.php';

use GlpiPlugin\Controleinterno\Equipamento;
use GlpiPlugin\Controleinterno\Tabs;

global $CFG_GLPI, $DB;

Session::checkLoginUser();
Session::checkRight('plugin_controleinterno_ver', READ);

$podeEditar = Session::haveRight('plugin_controleinterno_ver', UPDATE);

Html::header(__('Controle Interno', 'controleinterno'), '', 'tools', 'plugincontroleinterno');

echo "<div class='container-fluid' style='margin-top: 1em;'>";
Tabs::desenhar('equipamentos');

echo "<div class='card'>";
echo "<div class='card-body'>";

echo "<div class='d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3'>";
echo "<div>";
echo "<h2 class='mb-0'>" . __('Equipamentos', 'controleinterno') . "</h2>";
echo "<div class='text-muted small'>" . __('Registro interno de onde está cada equipamento - inclusive os sem tombamento.', 'controleinterno') . "</div>";
echo "</div>";
if ($podeEditar) {
    echo "<a class='btn btn-primary btn-sm' href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/equipamento.form.php'>";
    echo "<i class='ti ti-plus'></i> " . __('Novo equipamento', 'controleinterno') . "</a>";
}
echo "</div>";

$linhas = $DB->request([
    'FROM'  => Equipamento::getTable(),
    'ORDER' => ['descricao ASC'],
]);

if (count($linhas) === 0) {
    echo "<div class='text-muted small'>" . __('Nenhum equipamento registrado ainda.', 'controleinterno') . "</div>";
} else {
    // Lista de modelos distintos já cadastrados - só pra popular o filtro,
    // não é uma lista fechada (modelo é texto livre no formulário).
    $modelos = $DB->request([
        'SELECT'   => ['modelo'],
        'DISTINCT' => true,
        'FROM'     => Equipamento::getTable(),
        'WHERE'    => ['modelo' => ['<>', '']],
        'ORDER'    => ['modelo ASC'],
    ]);

    echo "<div class='row g-2 mb-3'>";
    echo "<div class='col-md-6'>";
    echo "<input type='text' class='form-control' data-busca-tabela='ci-tabela-equipamentos' placeholder='" . __s('Pesquisar...', 'controleinterno') . "'>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<select class='form-select' data-filtro-select='ci-tabela-equipamentos' data-filtro-col='modelo'>";
    echo "<option value=''>" . __('Todos os modelos', 'controleinterno') . "</option>";
    foreach ($modelos as $linhaModelo) {
        echo "<option value='" . htmlescape($linhaModelo['modelo']) . "'>" . htmlescape($linhaModelo['modelo']) . "</option>";
    }
    echo "</select>";
    echo "</div>";
    echo "</div>";

    echo "<table class='table table-striped' id='ci-tabela-equipamentos'>";
    echo "<thead><tr>";
    echo "<th>" . __('Descrição', 'controleinterno') . "</th>";
    echo "<th>" . __('Tombo', 'controleinterno') . "</th>";
    echo "<th>" . __('Número de série', 'controleinterno') . "</th>";
    echo "<th>" . __('Modelo', 'controleinterno') . "</th>";
    echo "<th>" . __('Local', 'controleinterno') . "</th>";
    echo "<th>" . __('Responsável', 'controleinterno') . "</th>";
    echo "</tr></thead><tbody>";
    foreach ($linhas as $linha) {
        echo "<tr>";
        if ($podeEditar) {
            echo "<td><a href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/equipamento.form.php?id=" . $linha['id'] . "'>";
            echo htmlescape($linha['descricao']) . "</a></td>";
        } else {
            echo "<td>" . htmlescape($linha['descricao']) . "</td>";
        }
        echo "<td>" . ($linha['tombamento'] !== '' ? htmlescape($linha['tombamento']) : "<span class='text-muted'>" . __('Sem tombo', 'controleinterno') . "</span>") . "</td>";
        echo "<td>" . htmlescape($linha['numero_serie'] ?: '-') . "</td>";
        echo "<td data-col='modelo' data-valor='" . htmlescape($linha['modelo']) . "'>" . htmlescape($linha['modelo'] ?: '-') . "</td>";
        echo "<td>" . htmlescape($linha['localizacao'] ?: '-') . "</td>";
        echo "<td>" . htmlescape($linha['responsavel'] ?: '-') . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}

echo "</div></div>";

echo "</div>";

echo Html::script('plugins/controleinterno/public/js/busca-tabela.js');

Html::footer();
