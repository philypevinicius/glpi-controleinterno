<?php

/**
 * Plugin Controle Interno - Novo/editar/excluir evento do histórico de
 * funcionários. Um arquivo só pro formulário (exibição + gravação), igual
 * o padrão de plugins/glpinetworkmap/front/floorplan.form.php - simples o
 * bastante pra não precisar separar como o movimentacoes faz.
 */
require_once __DIR__ . '/../../../inc/includes.php';

use GlpiPlugin\Controleinterno\Funcionario;
use GlpiPlugin\Controleinterno\Tabs;

global $CFG_GLPI;

Session::checkLoginUser();
Session::checkRight('plugin_controleinterno_ver', READ);

$item = new Funcionario();
$id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (isset($_GET['delete']) && $id > 0) {
    Session::checkRight('plugin_controleinterno_ver', UPDATE);
    $item->delete(['id' => $id], true);
    Session::addMessageAfterRedirect(__('Evento excluído.', 'controleinterno'), false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/controleinterno/front/funcionario.php');
    exit;
}

if (isset($_POST['add']) || isset($_POST['update'])) {
    Session::checkRight('plugin_controleinterno_ver', UPDATE);

    $nome = trim($_POST['nome'] ?? '');
    $evento = $_POST['evento'] ?? '';
    if ($nome === '' || !array_key_exists($evento, Funcionario::EVENTOS)) {
        Session::addMessageAfterRedirect(__('Preencha o nome e escolha um evento válido.', 'controleinterno'), false, ERROR);
        Html::back();
        exit;
    }

    $input = [
        'nome'       => $nome,
        'evento'     => $evento,
        'data'       => $_POST['data'] ?: null,
        'tickets_id' => (int) ($_POST['tickets_id'] ?? 0),
        'observacao' => trim($_POST['observacao'] ?? ''),
    ];

    if (isset($_POST['add'])) {
        $input['users_id_registro'] = Session::getLoginUserID();
        $item->add($input);
    } else {
        $input['id'] = $id;
        $item->update($input);
    }

    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/controleinterno/front/funcionario.php');
    exit;
}

// Exibição do formulário.
if ($id > 0) {
    $item->getFromDB($id);
}

Html::header(__('Controle Interno', 'controleinterno'), '', 'tools', 'plugincontroleinterno');

echo "<div class='container-fluid' style='margin-top: 1em;'>";
Tabs::desenhar('funcionarios');

echo "<div style='max-width: 700px;'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<div class='d-flex align-items-center gap-2 mb-3'>";
echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/funcionario.php' class='btn btn-outline-secondary btn-sm rounded-pill d-inline-flex align-items-center gap-1'><i class='ti ti-arrow-left'></i>" . __('Voltar', 'controleinterno') . "</a>";
echo "<h2 class='mb-0'>" . ($id > 0 ? __('Editar evento', 'controleinterno') : __('Novo evento', 'controleinterno')) . "</h2>";
echo "</div>";

echo "<form method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/funcionario.form.php'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo Html::hidden('id', ['value' => $id]);

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Funcionário', 'controleinterno') . "</label>";
echo Html::input('nome', ['value' => $item->fields['nome'] ?? '', 'required' => 'required']);
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Evento', 'controleinterno') . "</label>";
$opcoesEvento = [];
foreach (Funcionario::EVENTOS as $chave => $rotulo) {
    $opcoesEvento[$chave] = __($rotulo, 'controleinterno');
}
Dropdown::showFromArray('evento', $opcoesEvento, [
    'value'                => $item->fields['evento'] ?? '',
    'display_emptychoice'  => true,
]);
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Data', 'controleinterno') . "</label>";
Html::showDateField('data', ['value' => $item->fields['data'] ?? '']);
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Número do chamado (opcional)', 'controleinterno') . "</label>";
echo Html::input('tickets_id', [
    'value'       => ((int) ($item->fields['tickets_id'] ?? 0)) ?: '',
    'type'        => 'number',
    'placeholder' => __s('Ex.: 1234', 'controleinterno'),
]);
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Observação (opcional)', 'controleinterno') . "</label>";
echo "<textarea name='observacao' class='form-control' rows='3'>" . htmlescape($item->fields['observacao'] ?? '') . "</textarea>";
echo "</div>";

echo "<div class='d-flex gap-2'>";
echo Html::submit($id > 0 ? __('Salvar', 'controleinterno') : __('Adicionar', 'controleinterno'), ['name' => $id > 0 ? 'update' : 'add', 'class' => 'btn btn-primary']);
if ($id > 0) {
    echo "<a class='btn btn-outline-danger' href='?id=" . $id . "&delete=1' onclick=\"return confirm('" . __s('Excluir este evento?', 'controleinterno') . "');\">" . __('Excluir', 'controleinterno') . "</a>";
}
echo "</div>";

echo "</form>";

echo "</div></div></div>";
echo "</div>";

Html::footer();
