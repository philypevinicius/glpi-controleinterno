<?php

namespace GlpiPlugin\Controleinterno;

/**
 * Barra de abas compartilhada entre as 3 telas do plugin (Funcionários/
 * Equipamentos/Notas fiscais) - mesma ideia do movimentacoes/src/Tabs.php,
 * mas sem variação por papel: é um "ou vê tudo, ou não vê nada" (um único
 * direito governa o plugin inteiro).
 */
class Tabs
{
    /** @return array<int, array{chave: string, titulo: string, url: string}> */
    public static function todas(): array
    {
        global $CFG_GLPI;

        $base = $CFG_GLPI['root_doc'] . '/plugins/controleinterno/front/';

        return [
            ['chave' => 'funcionarios', 'titulo' => __('Funcionários', 'controleinterno'), 'url' => $base . 'funcionario.php'],
            ['chave' => 'equipamentos', 'titulo' => __('Equipamentos', 'controleinterno'), 'url' => $base . 'equipamento.php'],
            ['chave' => 'notasfiscais', 'titulo' => __('Notas fiscais', 'controleinterno'), 'url' => $base . 'notafiscal.php'],
        ];
    }

    /** Imprime a faixa de abas, marcando $abaAtiva como selecionada. */
    public static function desenhar(string $abaAtiva): void
    {
        echo "<ul class='nav nav-tabs mb-3'>";
        foreach (self::todas() as $aba) {
            $ativa = $aba['chave'] === $abaAtiva;
            echo "<li class='nav-item'>";
            echo "<a class='nav-link" . ($ativa ? ' active' : '') . "' href='" . $aba['url'] . "'>" . $aba['titulo'] . "</a>";
            echo "</li>";
        }
        echo "</ul>";
    }
}
