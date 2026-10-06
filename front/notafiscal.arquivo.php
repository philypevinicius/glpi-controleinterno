<?php

/**
 * Plugin Controle Interno - Serve o arquivo anexado de uma nota fiscal.
 *
 * Deliberadamente NÃO usa front/pluginimage.send.php (padrão do
 * glpinetworkmap) - esse arquivo não existe no GLPI 11. Em vez disso:
 * direito checado aqui, nome do arquivo vem SEMPRE do banco (pelo id),
 * nunca de um parâmetro da URL - evita qualquer travessia de caminho.
 */
if (!class_exists(Session::class)) {
    require_once __DIR__ . '/../../../inc/includes.php';
}

use GlpiPlugin\Controleinterno\AccessService;
use GlpiPlugin\Controleinterno\NotaFiscal;

Session::checkLoginUser();
AccessService::exigirAcesso();

$id = (int) ($_GET['id'] ?? 0);
$item = new NotaFiscal();
if ($id <= 0 || !$item->getFromDB($id) || empty($item->fields['arquivo'])) {
    throw new \Glpi\Exception\Http\NotFoundHttpException();
}

// basename() por segurança extra, ainda que o nome já venha do banco (nunca
// da URL) e tenha sido gerado por uniqid() no upload - nunca contém '/'.
$nomeArquivo = basename($item->fields['arquivo']);
$caminho = NotaFiscal::getArquivoDir() . '/' . $nomeArquivo;

if (!is_file($caminho)) {
    throw new \Glpi\Exception\Http\NotFoundHttpException();
}

$tiposMime = [
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
];
$mime = $tiposMime[$item->fields['arquivo_tipo']] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($caminho));
// inline, não attachment: PDF/imagem abre direto no navegador (clicado com
// target='_blank' nas telas de lista/edição).
header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
header('X-Content-Type-Options: nosniff');

readfile($caminho);
