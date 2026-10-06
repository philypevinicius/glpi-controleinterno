<?php

/**
 * Plugin Controle Interno - Novo/editar/excluir equipamento. A busca por
 * tombo/série (ajax/buscar_equipamento.php + public/js/equipamento.js) só
 * PREENCHE os campos abaixo - eles continuam editáveis e podem ser
 * digitados na mão pra equipamento sem tombo, sem passar pela busca.
 */
if (!class_exists(Session::class)) {
    require_once __DIR__ . '/../../../inc/includes.php';
}

use GlpiPlugin\Controleinterno\AccessService;
use GlpiPlugin\Controleinterno\Equipamento;
use GlpiPlugin\Controleinterno\Tabs;

global $CFG_GLPI;

Session::checkLoginUser();
AccessService::exigirAcesso();

$item = new Equipamento();
$id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (isset($_GET['delete']) && $id > 0) {
    AccessService::exigirAcesso(UPDATE);
    $item->delete(['id' => $id], true);
    Session::addMessageAfterRedirect(__('Equipamento excluído.', 'controleinterno'), false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/controleinterno/front/equipamento.php');
    exit;
}

if (isset($_POST['add']) || isset($_POST['update'])) {
    AccessService::exigirAcesso(UPDATE);

    $descricao = trim($_POST['descricao'] ?? '');
    if ($descricao === '') {
        Session::addMessageAfterRedirect(__('Preencha a descrição do equipamento.', 'controleinterno'), false, ERROR);
        Html::back();
        exit;
    }

    $input = [
        'descricao'    => $descricao,
        'tombamento'   => trim($_POST['tombamento'] ?? ''),
        'numero_serie' => trim($_POST['numero_serie'] ?? ''),
        'modelo'       => trim($_POST['modelo'] ?? ''),
        'localizacao'  => trim($_POST['localizacao'] ?? ''),
        'responsavel'  => trim($_POST['responsavel'] ?? ''),
        'observacao'   => trim($_POST['observacao'] ?? ''),
    ];

    if (isset($_POST['add'])) {
        $input['users_id_registro'] = Session::getLoginUserID();
        $item->add($input);
    } else {
        $input['id'] = $id;
        $item->update($input);
    }

    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/controleinterno/front/equipamento.php');
    exit;
}

// Exibição do formulário.
if ($id > 0) {
    $item->getFromDB($id);
}

Html::header(__('Controle Interno', 'controleinterno'), '', 'tools', 'plugincontroleinterno');

echo "<div class='container-fluid' style='margin-top: 1em;'>";
Tabs::desenhar('equipamentos');

echo "<div style='max-width: 700px;'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<div class='d-flex align-items-center gap-2 mb-3'>";
echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/equipamento.php' class='btn btn-outline-secondary btn-sm rounded-pill d-inline-flex align-items-center gap-1'><i class='ti ti-arrow-left'></i>" . __('Voltar', 'controleinterno') . "</a>";
echo "<h2 class='mb-0'>" . ($id > 0 ? __('Editar equipamento', 'controleinterno') : __('Novo equipamento', 'controleinterno')) . "</h2>";
echo "</div>";

if ($id === 0) {
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>" . __('Buscar no cadastro do GLPI por tombo ou número de série', 'controleinterno') . "</label>";
    echo "<input type='text' id='ci-eq-busca' class='form-control' placeholder='" . __s('Digite o tombo ou o número de série', 'controleinterno') . "' autocomplete='off'>";
    echo "<div id='ci-eq-lista' class='list-group list-group-flush mt-2'></div>";
    echo "<div class='form-text'>" . __('Não encontrou? Sem problema - preencha os campos abaixo na mão.', 'controleinterno') . "</div>";
    echo "</div>";
    echo "<hr class='my-3'>";
}

echo "<form method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/equipamento.form.php'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo Html::hidden('id', ['value' => $id]);

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Descrição', 'controleinterno') . "</label>";
echo Html::input('descricao', ['value' => $item->fields['descricao'] ?? '', 'id' => 'ci-eq-descricao', 'required' => 'required']);
echo "</div>";

echo "<div class='row'>";
echo "<div class='col-6 mb-3'>";
echo "<label class='form-label'>" . __('Tombo', 'controleinterno') . "</label>";
echo Html::input('tombamento', ['value' => $item->fields['tombamento'] ?? '', 'id' => 'ci-eq-tombamento']);
echo "</div>";
echo "<div class='col-6 mb-3'>";
echo "<label class='form-label'>" . __('Número de série', 'controleinterno') . "</label>";
echo Html::input('numero_serie', ['value' => $item->fields['numero_serie'] ?? '', 'id' => 'ci-eq-serie']);
echo "</div>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Modelo', 'controleinterno') . "</label>";
echo Html::input('modelo', ['value' => $item->fields['modelo'] ?? '', 'id' => 'ci-eq-modelo']);
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Local', 'controleinterno') . "</label>";
echo Html::input('localizacao', ['value' => $item->fields['localizacao'] ?? '', 'id' => 'ci-eq-local']);
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Responsável', 'controleinterno') . "</label>";
echo Html::input('responsavel', ['value' => $item->fields['responsavel'] ?? '']);
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Observação (opcional)', 'controleinterno') . "</label>";
echo "<textarea name='observacao' class='form-control' rows='3'>" . htmlescape($item->fields['observacao'] ?? '') . "</textarea>";
echo "</div>";

echo "<div class='d-flex gap-2'>";
echo Html::submit($id > 0 ? __('Salvar', 'controleinterno') : __('Adicionar', 'controleinterno'), ['name' => $id > 0 ? 'update' : 'add', 'class' => 'btn btn-primary']);
if ($id > 0) {
    echo "<a class='btn btn-outline-danger' href='?id=" . $id . "&delete=1' onclick=\"return confirm('" . __s('Excluir este equipamento?', 'controleinterno') . "');\">" . __('Excluir', 'controleinterno') . "</a>";
}
echo "</div>";

echo "</form>";

echo "</div></div></div>";
echo "</div>";

if ($id === 0) {
    echo Html::script('plugins/controleinterno/public/js/equipamento.js');
}

Html::footer();
