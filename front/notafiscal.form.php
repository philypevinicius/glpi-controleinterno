<?php

/**
 * Plugin Controle Interno - Nova/editar/excluir nota fiscal, com upload do
 * arquivo (PDF ou imagem) - mesmo padrão manual de upload já usado em
 * plugins/glpinetworkmap/front/floorplan.form.php (nome do arquivo NUNCA
 * confiado, regerado com uniqid()), mas servido por um endpoint PRÓPRIO
 * (front/notafiscal.arquivo.php) em vez de front/pluginimage.send.php -
 * esse arquivo não existe no GLPI 11 (confirmado rodando `test -f` dentro
 * do container), então aquele pedaço do glpinetworkmap está incompleto e
 * não devia ser copiado.
 */
require_once __DIR__ . '/../../../inc/includes.php';

use GlpiPlugin\Controleinterno\NotaFiscal;
use GlpiPlugin\Controleinterno\Tabs;

global $CFG_GLPI;

Session::checkLoginUser();
Session::checkRight('plugin_controleinterno_ver', READ);

$item = new NotaFiscal();
$id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (isset($_GET['delete']) && $id > 0) {
    Session::checkRight('plugin_controleinterno_ver', UPDATE);
    $item->getFromDB($id);
    if (!empty($item->fields['arquivo'])) {
        $caminho = NotaFiscal::getArquivoDir() . '/' . $item->fields['arquivo'];
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }
    $item->delete(['id' => $id], true);
    Session::addMessageAfterRedirect(__('Nota fiscal excluída.', 'controleinterno'), false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/controleinterno/front/notafiscal.php');
    exit;
}

if (isset($_POST['add']) || isset($_POST['update'])) {
    Session::checkRight('plugin_controleinterno_ver', UPDATE);

    $fornecedor = trim($_POST['fornecedor'] ?? '');
    if ($fornecedor === '') {
        Session::addMessageAfterRedirect(__('Preencha o fornecedor.', 'controleinterno'), false, ERROR);
        Html::back();
        exit;
    }

    $input = [
        'fornecedor'      => $fornecedor,
        'valor'           => (float) str_replace(',', '.', $_POST['valor'] ?? '0'),
        'data'            => $_POST['data'] ?: null,
        'etiqueta_compra' => trim($_POST['etiqueta_compra'] ?? ''),
        'descricao'       => trim($_POST['descricao'] ?? ''),
    ];

    $upload = plugin_controleinterno_handle_upload_notafiscal();
    if ($upload !== null) {
        $input['arquivo']      = $upload['arquivo'];
        $input['arquivo_tipo'] = $upload['tipo'];
    }

    if (isset($_POST['add'])) {
        if (empty($input['arquivo'])) {
            Session::addMessageAfterRedirect(__('Anexe o arquivo da nota fiscal (PDF ou imagem).', 'controleinterno'), false, ERROR);
            Html::back();
            exit;
        }
        $input['users_id_registro'] = Session::getLoginUserID();
        $item->add($input);
    } else {
        if ($upload !== null) {
            // Substitui o arquivo antigo para não acumular anexo órfão a cada edição.
            $item->getFromDB($id);
            if (!empty($item->fields['arquivo'])) {
                $antigo = NotaFiscal::getArquivoDir() . '/' . $item->fields['arquivo'];
                if (is_file($antigo)) {
                    @unlink($antigo);
                }
            }
        }
        $input['id'] = $id;
        $item->update($input);
    }

    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/controleinterno/front/notafiscal.php');
    exit;
}

// Exibição do formulário.
if ($id > 0) {
    $item->getFromDB($id);
}

Html::header(__('Controle Interno', 'controleinterno'), '', 'tools', 'plugincontroleinterno');

echo "<div class='container-fluid' style='margin-top: 1em;'>";
Tabs::desenhar('notasfiscais');

echo "<div style='max-width: 700px;'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<div class='d-flex align-items-center gap-2 mb-3'>";
echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/notafiscal.php' class='btn btn-outline-secondary btn-sm rounded-pill d-inline-flex align-items-center gap-1'><i class='ti ti-arrow-left'></i>" . __('Voltar', 'controleinterno') . "</a>";
echo "<h2 class='mb-0'>" . ($id > 0 ? __('Editar nota fiscal', 'controleinterno') : __('Nova nota fiscal', 'controleinterno')) . "</h2>";
echo "</div>";

echo "<form method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/notafiscal.form.php' enctype='multipart/form-data'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo Html::hidden('id', ['value' => $id]);

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Fornecedor', 'controleinterno') . "</label>";
echo Html::input('fornecedor', ['value' => $item->fields['fornecedor'] ?? '', 'required' => 'required']);
echo "</div>";

echo "<div class='row'>";
echo "<div class='col-6 mb-3'>";
echo "<label class='form-label'>" . __('Valor (R$)', 'controleinterno') . "</label>";
echo Html::input('valor', ['value' => isset($item->fields['valor']) ? number_format((float) $item->fields['valor'], 2, '.', '') : '']);
echo "</div>";
echo "<div class='col-6 mb-3'>";
echo "<label class='form-label'>" . __('Data', 'controleinterno') . "</label>";
Html::showDateField('data', ['value' => $item->fields['data'] ?? '']);
echo "</div>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Etiqueta da compra', 'controleinterno') . "</label>";
echo Html::input('etiqueta_compra', [
    'value'       => $item->fields['etiqueta_compra'] ?? '',
    'placeholder' => __s('Ex.: Notebooks para TI, Material de escritório...', 'controleinterno'),
]);
echo "<div class='form-text'>" . __('Do que foi essa compra - usado pra filtrar as notas depois.', 'controleinterno') . "</div>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Descrição (opcional)', 'controleinterno') . "</label>";
echo "<textarea name='descricao' class='form-control' rows='3'>" . htmlescape($item->fields['descricao'] ?? '') . "</textarea>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>" . __('Arquivo da nota (PDF ou imagem)', 'controleinterno') . "</label>";
if (!empty($item->fields['arquivo'])) {
    echo "<div class='mb-2'><a href='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/notafiscal.arquivo.php?id=" . $id . "' target='_blank'>";
    echo "<i class='ti ti-file-invoice'></i> " . __('Ver anexo atual', 'controleinterno') . "</a></div>";
    echo "<div class='mb-1 text-muted'>" . __('Envie um novo arquivo para substituir o anexo atual.', 'controleinterno') . "</div>";
}
echo "<input type='file' name='arquivo' accept='.pdf,.png,.jpg,.jpeg'" . ($id > 0 ? '' : " required") . ">";
echo "</div>";

echo "<div class='d-flex gap-2'>";
echo Html::submit($id > 0 ? __('Salvar', 'controleinterno') : __('Adicionar', 'controleinterno'), ['name' => $id > 0 ? 'update' : 'add', 'class' => 'btn btn-primary']);
if ($id > 0) {
    echo "<a class='btn btn-outline-danger' href='?id=" . $id . "&delete=1' onclick=\"return confirm('" . __s('Excluir esta nota fiscal?', 'controleinterno') . "');\">" . __('Excluir', 'controleinterno') . "</a>";
}
echo "</div>";

echo "</form>";

echo "</div></div></div>";
echo "</div>";

Html::footer();

/**
 * Processa o upload do anexo, se houver um arquivo válido em $_FILES.
 * Retorna ['arquivo' => nome_gerado, 'tipo' => extensao] ou null quando não
 * há upload nesta requisição (ex: edição só da descrição).
 */
function plugin_controleinterno_handle_upload_notafiscal(): ?array
{
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        Session::addMessageAfterRedirect(__('Falha no upload do arquivo.', 'controleinterno'), false, ERROR);
        return null;
    }

    $tmpName  = $_FILES['arquivo']['tmp_name'];
    $original = $_FILES['arquivo']['name'];
    $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, NotaFiscal::EXTENSOES_ACEITAS, true)) {
        Session::addMessageAfterRedirect(__('Formato não aceito. Envie PDF, PNG ou JPEG.', 'controleinterno'), false, ERROR);
        return null;
    }

    // Document::isImage() só valida imagem - PDF não tem helper nativo
    // equivalente, então confere o cabeçalho mágico "%PDF-" direto.
    $conteudoValido = $ext === 'pdf'
        ? substr(file_get_contents($tmpName, false, null, 0, 5), 0, 5) === '%PDF-'
        : Document::isImage($tmpName);

    if (!$conteudoValido) {
        Session::addMessageAfterRedirect(__('O arquivo enviado não é um PDF nem uma imagem válida.', 'controleinterno'), false, ERROR);
        return null;
    }

    $filename = uniqid('nf_', true) . '.' . $ext;
    $dest     = NotaFiscal::getArquivoDir() . '/' . $filename;

    if (!move_uploaded_file($tmpName, $dest)) {
        Session::addMessageAfterRedirect(__('Não foi possível salvar o arquivo enviado.', 'controleinterno'), false, ERROR);
        return null;
    }

    return ['arquivo' => $filename, 'tipo' => $ext];
}
