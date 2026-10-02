<?php

namespace GlpiPlugin\Controleinterno;

/**
 * Uma nota fiscal de produto comprado - registro solto, sem vínculo com
 * ativo específico (confirmado com o usuário), com o arquivo da nota
 * (PDF ou imagem) anexado.
 */
class NotaFiscal extends \CommonDBTM
{
    /** Extensões aceitas pro anexo - mesmas checadas em ajax/upload, aqui só pra referência central. */
    public const EXTENSOES_ACEITAS = ['pdf', 'png', 'jpg', 'jpeg'];

    public static function getTable($classname = null)
    {
        return 'glpi_plugin_controleinterno_notasfiscais';
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Nota fiscal', 'Notas fiscais', $nb, 'controleinterno');
    }

    /**
     * Pasta onde os arquivos de nota fiscal ficam guardados - mesma
     * convenção do glpinetworkmap (GLPI_PLUGIN_DOC_DIR, não o _uploads/
     * nativo), criada sob demanda.
     */
    public static function getArquivoDir(): string
    {
        $dir = GLPI_PLUGIN_DOC_DIR . '/controleinterno/notasfiscais';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }
}
