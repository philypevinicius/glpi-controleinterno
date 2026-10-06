<?php

/**
 * Plugin Controle Interno - Hook functions.
 */

/**
 * Instalação: cria as 3 tabelas do plugin (uma por módulo - Funcionários,
 * Equipamentos, Notas fiscais) e o direito único de acesso, concedido
 * automaticamente só a Admin/Super-Admin/Technician/Coordenador (perfis de
 * TI) - os demais ficam sem acesso até um admin liberar manualmente pela
 * aba de Perfil.
 */
function plugin_controleinterno_install()
{
    global $DB;

    $migration = new Migration(PLUGIN_CONTROLEINTERNO_VERSION);

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

    if (!$DB->tableExists('glpi_plugin_controleinterno_funcionarios')) {
        $query = "CREATE TABLE `glpi_plugin_controleinterno_funcionarios` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `nome` varchar(255) NOT NULL DEFAULT '',
            `evento` varchar(30) NOT NULL DEFAULT '',
            `data` date DEFAULT NULL,
            `tickets_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            `observacao` text,
            `users_id_registro` int {$default_key_sign} NOT NULL DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `evento` (`evento`),
            KEY `nome` (`nome`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};";
        $DB->doQuery($query) or die($DB->error());
    } else {
        // Instalação já existente (Fase 1) - acrescenta a coluna nova sem
        // perder os eventos já cadastrados. addField() já confere sozinho
        // se o campo existe antes de tentar criar.
        $migration->addField('glpi_plugin_controleinterno_funcionarios', 'tickets_id', 'integer', [
            'value' => 0,
            'after' => 'data',
        ]);
    }

    if (!$DB->tableExists('glpi_plugin_controleinterno_equipamentos')) {
        $query = "CREATE TABLE `glpi_plugin_controleinterno_equipamentos` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `descricao` varchar(255) NOT NULL DEFAULT '',
            `tombamento` varchar(255) NOT NULL DEFAULT '',
            `numero_serie` varchar(255) NOT NULL DEFAULT '',
            `modelo` varchar(255) NOT NULL DEFAULT '',
            `localizacao` varchar(255) NOT NULL DEFAULT '',
            `responsavel` varchar(255) NOT NULL DEFAULT '',
            `observacao` text,
            `users_id_registro` int {$default_key_sign} NOT NULL DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tombamento` (`tombamento`),
            KEY `numero_serie` (`numero_serie`),
            KEY `modelo` (`modelo`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};";
        $DB->doQuery($query) or die($DB->error());
    } else {
        $migration->addField('glpi_plugin_controleinterno_equipamentos', 'numero_serie', 'string', [
            'value' => '',
            'after' => 'tombamento',
        ]);
        $migration->addField('glpi_plugin_controleinterno_equipamentos', 'modelo', 'string', [
            'value' => '',
            'after' => 'numero_serie',
        ]);
        $migration->addKey('glpi_plugin_controleinterno_equipamentos', 'modelo');
    }

    if (!$DB->tableExists('glpi_plugin_controleinterno_notasfiscais')) {
        $query = "CREATE TABLE `glpi_plugin_controleinterno_notasfiscais` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `fornecedor` varchar(255) NOT NULL DEFAULT '',
            `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
            `data` date DEFAULT NULL,
            `etiqueta_compra` varchar(255) NOT NULL DEFAULT '',
            `descricao` text,
            `arquivo` varchar(255) NOT NULL DEFAULT '',
            `arquivo_tipo` varchar(10) NOT NULL DEFAULT '',
            `users_id_registro` int {$default_key_sign} NOT NULL DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `fornecedor` (`fornecedor`),
            KEY `data` (`data`),
            KEY `etiqueta_compra` (`etiqueta_compra`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};";
        $DB->doQuery($query) or die($DB->error());
    } else {
        $migration->addField('glpi_plugin_controleinterno_notasfiscais', 'etiqueta_compra', 'string', [
            'value' => '',
            'after' => 'data',
        ]);
        $migration->addKey('glpi_plugin_controleinterno_notasfiscais', 'etiqueta_compra');
        $migration->addKey('glpi_plugin_controleinterno_notasfiscais', 'fornecedor');
        $migration->addKey('glpi_plugin_controleinterno_notasfiscais', 'data');
    }

    // Direito único (READ+UPDATE juntos - uma aba só, sem papel
    // intermediário "só vê, não edita", mesma decisão já tomada no
    // movimentacoes). addProfileRights() cria a linha pra TODO perfil com
    // valor 0; em seguida, só os 4 perfis de TI recebem o valor real -
    // assim o runtime sempre consulta Session::haveRight(), nunca uma
    // lista fixa de IDs (ver HomeAccessService x CoordenacaoAccessService
    // no glpipulseui pro motivo desta escolha).
    // Guardado por existsão: addProfileRights() não é idempotente (dá erro
    // de chave duplicada se rodar de novo numa instalação já existente,
    // ex. ao reinstalar pra aplicar uma migração de coluna nova).
    $direitoJaExiste = (bool) $DB->request([
        'FROM'  => 'glpi_profilerights',
        'WHERE' => ['name' => 'plugin_controleinterno_ver'],
        'LIMIT' => 1,
    ])->current();
    if (!$direitoJaExiste) {
        ProfileRight::addProfileRights(['plugin_controleinterno_ver']);
    }

    // IDs confirmados neste ambiente via glpi_profiles: 3=Admin,
    // 4=Super-Admin, 6=Technician, 11=Coordenador (perfil do plugin
    // Movimentações). Se a instalação de produção tiver perfis de TI
    // diferentes, o admin ajusta depois pela aba de Perfil - não precisa
    // mexer em código.
    foreach ([3, 4, 6, 11] as $profileId) {
        ProfileRight::updateProfileRights($profileId, [
            'plugin_controleinterno_ver' => READ | UPDATE,
        ]);
    }

    $migration->executeMigration();

    return true;
}

/**
 * Desinstalação: remove as 3 tabelas e o direito, sem deixar rastro.
 */
function plugin_controleinterno_uninstall()
{
    global $DB;

    $tabelas = [
        'glpi_plugin_controleinterno_funcionarios',
        'glpi_plugin_controleinterno_equipamentos',
        'glpi_plugin_controleinterno_notasfiscais',
    ];
    foreach ($tabelas as $tabela) {
        $DB->doQuery("DROP TABLE IF EXISTS `{$tabela}`") or die($DB->error());
    }

    ProfileRight::deleteProfileRights(['plugin_controleinterno_ver']);

    return true;
}

/**
 * Item de menu "Controle Interno", com dropdown de 3 subitens (Funcionários/
 * Equipamentos/Notas fiscais) - diferente do movimentacoes (link único),
 * aqui faz sentido dropdown porque são 3 telas de mesmo nível, sem uma
 * página "central" óbvia.
 */
function plugin_controleinterno_redefine_menus($menus)
{
    global $CFG_GLPI;

    if (!\GlpiPlugin\Controleinterno\AccessService::canAccess()) {
        return $menus;
    }

    $base = $CFG_GLPI['root_doc'] . '/plugins/controleinterno/front/';

    $menus['controleinterno'] = [
        'title'   => __('Controle Interno', 'controleinterno'),
        'icon'    => 'ti ti-clipboard-list',
        'content' => [
            'funcionarios' => [
                'title' => __('Funcionários', 'controleinterno'),
                'page'  => $base . 'funcionario.php',
                'icon'  => 'ti ti-users',
            ],
            'equipamentos' => [
                'title' => __('Equipamentos', 'controleinterno'),
                'page'  => $base . 'equipamento.php',
                'icon'  => 'ti ti-device-desktop',
            ],
            'notasfiscais' => [
                'title' => __('Notas fiscais', 'controleinterno'),
                'page'  => $base . 'notafiscal.php',
                'icon'  => 'ti ti-file-invoice',
            ],
        ],
    ];

    return $menus;
}
