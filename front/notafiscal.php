<?php

/**
 * Plugin Controle Interno - Lista de notas fiscais de produtos comprados.
 */
require_once __DIR__ . '/../../../inc/includes.php';

use GlpiPlugin\Controleinterno\NotaFiscal;
use GlpiPlugin\Controleinterno\Tabs;

global $CFG_GLPI, $DB;

Session::checkLoginUser();
Session::checkRight('plugin_controleinterno_ver', READ);

$podeEditar = Session::haveRight('plugin_controleinterno_ver', UPDATE);

Html::header(__('Controle Interno', 'controleinterno'), '', 'tools', 'plugincontroleinterno');

echo "<div class='container-fluid' style='margin-top: 1em;'>";
Tabs::desenhar('notasfiscais');

echo "<div class='card'>";
echo "<div class='card-body'>";

echo "<div class='d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3'>";
echo "<div>";
echo "<h2 class='mb-0'>" . __('Notas fiscais', 'controleinterno') . "</h2>";
echo "<div class='text-muted small'>" . __('Registro dos produtos comprados, com o arquivo da nota anexado.', 'controleinterno') . "</div>";
echo "</div>";
if ($podeEditar) {
    echo "<a class='btn btn-primary btn-sm' href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/notafiscal.form.php'>";
    echo "<i class='ti ti-plus'></i> " . __('Nova nota fiscal', 'controleinterno') . "</a>";
}
echo "</div>";

$linhas = $DB->request([
    'FROM'  => NotaFiscal::getTable(),
    'ORDER' => ['data DESC', 'id DESC'],
]);

if (count($linhas) === 0) {
    echo "<div class='text-muted small'>" . __('Nenhuma nota fiscal registrada ainda.', 'controleinterno') . "</div>";
} else {
    echo "<table class='table table-striped'>";
    echo "<thead><tr>";
    echo "<th>" . __('Fornecedor', 'controleinterno') . "</th>";
    echo "<th>" . __('Valor', 'controleinterno') . "</th>";
    echo "<th>" . __('Data', 'controleinterno') . "</th>";
    echo "<th>" . __('Descrição', 'controleinterno') . "</th>";
    echo "<th>" . __('Arquivo', 'controleinterno') . "</th>";
    echo "</tr></thead><tbody>";
    foreach ($linhas as $linha) {
        echo "<tr>";
        if ($podeEditar) {
            echo "<td><a href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/notafiscal.form.php?id=" . $linha['id'] . "'>";
            echo htmlescape($linha['fornecedor']) . "</a></td>";
        } else {
            echo "<td>" . htmlescape($linha['fornecedor']) . "</td>";
        }
        echo "<td>" . htmlescape(number_format((float) $linha['valor'], 2, ',', '.')) . "</td>";
        echo "<td>" . ($linha['data'] ? Html::convDate($linha['data']) : '-') . "</td>";
        echo "<td>" . htmlescape($linha['descricao'] ?: '-') . "</td>";
        echo "<td>";
        if ($linha['arquivo'] !== '') {
            echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/notafiscal.arquivo.php?id=" . $linha['id'] . "' target='_blank'>";
            echo "<i class='ti ti-file-invoice'></i> " . __('Ver anexo', 'controleinterno') . "</a>";
        } else {
            echo "<span class='text-muted'>" . __('Sem anexo', 'controleinterno') . "</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}

echo "</div></div>";

echo "</div>";

Html::footer();
