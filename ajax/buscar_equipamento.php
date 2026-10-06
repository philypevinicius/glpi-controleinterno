<?php

/**
 * Plugin Controle Interno - Busca ativo por tombo ou número de série
 * (chamado pelo JS de front/equipamento.form.php).
 */
if (!class_exists(Session::class)) {
    require_once __DIR__ . '/../../../inc/includes.php';
}

use GlpiPlugin\Controleinterno\AccessService;
use GlpiPlugin\Controleinterno\EquipamentoService;

Session::checkLoginUser();
header('Content-Type: application/json; charset=utf-8');

if (!AccessService::canAccess()) {
    http_response_code(403);
    echo json_encode(['erro' => __('Sem permissão.', 'controleinterno')]);
    exit;
}

// Vazio lista os primeiros ativos, pra já mostrar algo assim que a tela abre.
$termo = $_GET['termo'] ?? '';
echo json_encode(EquipamentoService::buscarPorTomboOuSerie(is_string($termo) ? $termo : ''));
