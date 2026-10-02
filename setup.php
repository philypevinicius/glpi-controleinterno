<?php

define('PLUGIN_CONTROLEINTERNO_VERSION', '0.1.0');
// Máxima abaixo de 13.0.0 = comparação exclusiva (Plugin::checkGlpiVersion()
// usa version_compare(..., 'max', '<')), mesmo padrão do movimentacoes.
define('PLUGIN_CONTROLEINTERNO_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_CONTROLEINTERNO_MAX_GLPI_VERSION', '13.0.0');

function plugin_version_controleinterno()
{
    return [
        'name'         => 'Controle Interno',
        'version'      => PLUGIN_CONTROLEINTERNO_VERSION,
        'author'       => 'SEBRAE',
        'license'      => 'GPLv3+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_CONTROLEINTERNO_MIN_GLPI_VERSION,
                'max' => PLUGIN_CONTROLEINTERNO_MAX_GLPI_VERSION,
            ],
            'php' => [
                'min' => '8.2.0',
            ],
        ],
    ];
}

function plugin_init_controleinterno()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['controleinterno'] = true;

    // Sem isto, Plugin::doHookFunction('redefine_menus', ...) nunca chama
    // plugin_controleinterno_redefine_menus() - mesma observação já
    // documentada no plugin movimentacoes.
    $PLUGIN_HOOKS['redefine_menus']['controleinterno'] = 'plugin_controleinterno_redefine_menus';

    // Aba "Controle Interno" em Configurar > Perfis > [perfil] - ver
    // src/ProfileTab.php.
    Plugin::registerClass(\GlpiPlugin\Controleinterno\ProfileTab::class, ['addtabon' => ['Profile']]);
}

function plugin_controleinterno_check_prerequisites()
{
    return true;
}

function plugin_controleinterno_check_config()
{
    return true;
}
