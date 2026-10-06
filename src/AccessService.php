<?php

namespace GlpiPlugin\Controleinterno;

/**
 * Decide quem enxerga o Controle Interno - fonte única de verdade,
 * consultada em TODO front/ajax do plugin (nenhum checa o direito "na
 * unha" via Session::checkRight() direto), igual ao padrão já usado no
 * CoordenacaoAccessService do glpipulseui.
 *
 * Pedido do usuário: além do direito dedicado (plugin_controleinterno_ver),
 * só deve aparecer pra quem estiver com a entidade ativa (o seletor de
 * unidade no topo do GLPI) em UTIC - não noutra unidade, mesmo tendo o
 * direito.
 */
class AccessService
{
    /** Nome do direito, tal como gravado em glpi_profilerights.name. */
    public const RIGHTNAME = 'plugin_controleinterno_ver';

    /**
     * Nome da entidade (glpi_entities.name) onde o plugin é liberado. Por
     * NOME, não por ID: o ID é gerado pelo banco e muda entre instalações -
     * um ID fixo quebraria ao implantar noutro ambiente.
     */
    private const ENTIDADE_PERMITIDA = 'UTIC';

    /**
     * O usuário da sessão atual pode acessar o Controle Interno?
     *
     * @param int $nivel READ (ver) ou UPDATE (cadastrar/editar/excluir).
     */
    public static function canAccess(int $nivel = READ): bool
    {
        if (!\Session::getLoginUserID()) {
            return false;
        }

        if (!\Session::haveRight(self::RIGHTNAME, $nivel)) {
            return false;
        }

        return self::entidadeAtivaPermitida();
    }

    /**
     * Força o acesso, lançando 403 se não tiver - para usar no topo dos
     * front/*.php e ajax/*.php, no lugar de Session::checkRight().
     */
    public static function exigirAcesso(int $nivel = READ): void
    {
        if (!self::canAccess($nivel)) {
            throw new \Glpi\Exception\Http\AccessDeniedHttpException();
        }
    }

    /**
     * A entidade ATIVA da sessão (o seletor de unidade, não todas as
     * entidades que o perfil alcança) é a entidade permitida?
     */
    private static function entidadeAtivaPermitida(): bool
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => \Entity::getTable(),
            'WHERE'  => ['name' => self::ENTIDADE_PERMITIDA],
        ]);

        // Entidade não cadastrada nesta instalação (ainda não criada, nome
        // diferente) - nega por segurança em vez de quebrar com erro.
        if (count($it) !== 1) {
            return false;
        }

        $entidadeId = (int) $it->current()['id'];

        return (int) \Session::getActiveEntity() === $entidadeId;
    }
}
