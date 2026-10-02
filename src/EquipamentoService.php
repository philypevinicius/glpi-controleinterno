<?php

namespace GlpiPlugin\Controleinterno;

/**
 * Busca de ativos no cadastro do GLPI por tombo (campo nativo `otherserial`)
 * OU número de série (campo nativo `serial`) - mesma ideia da busca por
 * tombamento do plugin Movimentações (MovimentacaoService::
 * buscarPorTombamento()), mas também casando pelo número de série, porque
 * nem todo equipamento sob nosso controle tem tombo.
 */
class EquipamentoService
{
    private const CLASSES_POR_TIPO = [
        'Computer'         => \Computer::class,
        'Monitor'          => \Monitor::class,
        'Printer'          => \Printer::class,
        'Peripheral'       => \Peripheral::class,
        'NetworkEquipment' => \NetworkEquipment::class,
        'Phone'            => \Phone::class,
    ];

    private const LIMITE_LISTA = 30;

    /**
     * @return array<int, array{itemtype: string, items_id: int, nome: string,
     *     tombamento: string, numero_serie: string, tipo_label: string,
     *     locations_id: int, local_nome: string}>
     */
    public static function buscarPorTomboOuSerie(string $termo): array
    {
        $termo = trim($termo);

        global $DB;
        $resultado = [];
        foreach (self::CLASSES_POR_TIPO as $tipo => $classe) {
            if (count($resultado) >= self::LIMITE_LISTA) {
                break;
            }

            $where = ['is_deleted' => 0];
            if ($termo !== '') {
                $where['OR'] = [
                    'otherserial' => ['LIKE', '%' . $termo . '%'],
                    'serial'      => ['LIKE', '%' . $termo . '%'],
                ];
            } else {
                $where['OR'] = [
                    'otherserial' => ['<>', ''],
                    'serial'      => ['<>', ''],
                ];
            }

            $it = $DB->request([
                'FROM'  => $classe::getTable(),
                'WHERE' => $where,
                'LIMIT' => self::LIMITE_LISTA,
            ]);

            foreach ($it as $linha) {
                $localNome = $linha['locations_id'] ? \Dropdown::getDropdownName('glpi_locations', $linha['locations_id']) : '';

                $resultado[] = [
                    'itemtype'     => $tipo,
                    'items_id'     => (int) $linha['id'],
                    'nome'         => $linha['name'] ?? '',
                    'tombamento'   => $linha['otherserial'] ?? '',
                    'numero_serie' => $linha['serial'] ?? '',
                    'tipo_label'   => $classe::getTypeName(1),
                    'locations_id' => (int) $linha['locations_id'],
                    'local_nome'   => $localNome,
                ];
            }
        }

        return $resultado;
    }
}
