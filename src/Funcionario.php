<?php

namespace GlpiPlugin\Controleinterno;

/**
 * Um evento do histórico de movimentação de funcionário (admissão,
 * desligamento, renovação/bloqueio/desbloqueio de acesso).
 */
class Funcionario extends \CommonDBTM
{
    /** Valores válidos do campo `evento`, na ordem em que aparecem no select. */
    public const EVENTOS = [
        'admissao'           => 'Admissão',
        'desligamento'       => 'Desligamento',
        'renovacao_acesso'   => 'Renovação de acesso',
        'bloqueio_acesso'    => 'Bloqueio de acesso',
        'desbloqueio_acesso' => 'Desbloqueio de acesso',
    ];

    public static function getTable($classname = null)
    {
        return 'glpi_plugin_controleinterno_funcionarios';
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Funcionário', 'Funcionários', $nb, 'controleinterno');
    }

    /** Rótulo traduzido do evento, para exibição na lista. */
    public static function rotuloEvento(string $evento): string
    {
        return __(self::EVENTOS[$evento] ?? $evento, 'controleinterno');
    }
}
