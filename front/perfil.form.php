<?php

/**
 * Plugin Controle Interno - Grava o direito "plugin_controleinterno_ver" de
 * um perfil. Recebe o POST do checkbox em
 * GlpiPlugin\Controleinterno\ProfileTab::showForProfile() e volta para a
 * aba do perfil.
 *
 * NÃO chamar Session::checkCSRF($_POST) aqui: no GLPI 11 o CheckCsrfListener
 * já valida o _glpi_csrf_token no evento kernel.controller, ANTES de este
 * arquivo ser incluído - mesma armadilha já documentada no movimentacoes e
 * no glpipulseui.
 */
if (!class_exists(Session::class)) {
    require_once __DIR__ . '/../../../inc/includes.php';
}

Session::checkRight(Profile::$rightname, UPDATE);

$profilesId = (int) ($_POST['profiles_id'] ?? 0);
if ($profilesId <= 0) {
    Html::back();
    exit;
}

// Checkbox desmarcado não manda a chave no POST - ausência = 0 (sem
// acesso). READ+UPDATE juntos: um único direito governa o plugin inteiro,
// sem papel intermediário "só vê, não edita".
$valor = isset($_POST['plugin_controleinterno_ver']) ? (READ | UPDATE) : 0;

ProfileRight::updateProfileRights($profilesId, [
    'plugin_controleinterno_ver' => $valor,
]);

Html::back();
