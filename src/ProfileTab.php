<?php

namespace GlpiPlugin\Controleinterno;

/**
 * Aba "Controle Interno" em Configurar > Perfis > [perfil] - mesmo padrão
 * já validado no movimentacoes e no Painel de Coordenação do glpipulseui
 * (classe "provedora de aba" pura, sem tabela própria).
 */
class ProfileTab extends \CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return __('Controle Interno', 'controleinterno');
    }

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0)
    {
        if (!($item instanceof \Profile) || !$item->fields['id']) {
            return '';
        }

        return self::createTabEntry(self::getTypeName());
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!($item instanceof \Profile)) {
            return false;
        }

        self::showForProfile((int) $item->getID());

        return true;
    }

    /**
     * Formulário da aba: um checkbox só, "Acessar Controle Interno". Quem
     * pode editar é quem já pode editar perfis (direito nativo "profile").
     */
    public static function showForProfile(int $profilesId): void
    {
        global $CFG_GLPI;

        $podeEditar = \Session::haveRight(\Profile::$rightname, UPDATE);
        $direitos   = \ProfileRight::getProfileRights($profilesId, ['plugin_controleinterno_ver']);
        $temDireito = (int) ($direitos['plugin_controleinterno_ver'] ?? 0) & READ;

        echo "<form method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/controleinterno/front/perfil.form.php'>";
        echo \Html::hidden('profiles_id', ['value' => $profilesId]);
        echo \Html::hidden('_glpi_csrf_token', ['value' => \Session::getNewCSRFToken()]);

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . __('Controle Interno', 'controleinterno') . "</th></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Acessar o Controle Interno (funcionários, equipamentos, notas fiscais)', 'controleinterno') . "</td>";
        echo "<td>";
        \Html::showCheckbox([
            'name'     => 'plugin_controleinterno_ver',
            'checked'  => (bool) $temDireito,
            'readonly' => !$podeEditar,
        ]);
        echo "</td>";
        echo "</tr>";
        echo "</table>";

        if ($podeEditar) {
            echo "<div class='center mt-2'>";
            echo \Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>";
        }

        \Html::closeForm();
    }
}
