<?php

namespace GlpiPlugin\Controleinterno;

/**
 * Um equipamento no registro interno de controle - inclusive os que não
 * têm tombamento no GLPI.
 */
class Equipamento extends \CommonDBTM
{
    public static function getTable($classname = null)
    {
        return 'glpi_plugin_controleinterno_equipamentos';
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Equipamento', 'Equipamentos', $nb, 'controleinterno');
    }
}
