<?php

/**
 * ============================================================================
 * CATÁLOGO DE DATOS: MasterItemsCatalog (Motor O(1) de Ítems Corporativos)
 * ============================================================================
 * ¿QUÉ HACE ESTE ARCHIVO? (Explicación "For Dummies")
 * Gestiona en memoria y en caché de alta velocidad (`Cache::rememberForever`)
 * los dos catálogos maestros de Aurofarma:
 *   1. Catálogo Literal de 1,954 Ítems (`items_catalog.php`).
 *   2. Catálogo Corporativo de 170 Productos Terminados con Registro ICA,
 *      forma farmacéutica y meses de vigencia.
 *
 * OPTIMIZACIÓN PILAR 4:
 * En lugar de recorrer 1,954 elementos con un bucle `foreach` en cada búsqueda
 * flexible, construye un índice hash normalizado (`$normalizedIndex`) que permite
 * encontrar cualquier código (con o sin prefijo 'A' o ceros a la izquierda) en
 * tiempo constante O(1) (< 0.1 milisegundos).
 * ============================================================================
 */

namespace App\Data;

use Illuminate\Support\Facades\Cache;

class MasterItemsCatalog
{
    private static ?array $items = null;
    private static ?array $normalizedIndex = null;
    private static ?array $corporateNormalizedIndex = null;

    /**
     * Obtiene el catálogo oficial de 1,954 ítems con sus descripciones literales,
     * utilizando memoización en memoria estática + caché persistente.
     */
    public static function getItems(): array
    {
        if (self::$items === null) {
            try {
                self::$items = Cache::rememberForever('aurotrace_master_items_catalog_v2', function () {
                    return self::loadItemsFromDisk();
                });
            } catch (\Throwable $e) {
                self::$items = self::loadItemsFromDisk();
            }
        }
        return self::$items;
    }

    /**
     * Carga los ítems desde el archivo PHP o JSON en disco.
     */
    private static function loadItemsFromDisk(): array
    {
        $phpPath = __DIR__ . '/items_catalog.php';
        if (file_exists($phpPath)) {
            $data = require $phpPath;
            return is_array($data) ? $data : [];
        }

        $jsonPath = __DIR__ . '/user_items.json';
        if (file_exists($jsonPath)) {
            $decoded = json_decode(file_get_contents($jsonPath), true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Construye y retorna el índice normalizado O(1) para búsquedas sin prefijo 'A' o sin ceros.
     */
    private static function getNormalizedIndex(): array
    {
        if (self::$normalizedIndex === null) {
            $items = self::getItems();
            $index = [];
            foreach ($items as $k => $desc) {
                $cleanK = ltrim(str_replace('A', '', strtoupper((string) $k)), '0');
                if ($cleanK !== '' && !isset($index[$cleanK])) {
                    $index[$cleanK] = $desc;
                }
            }
            self::$normalizedIndex = $index;
        }
        return self::$normalizedIndex;
    }

    /**
     * Busca un ítem por su código exacto o flexible en tiempo O(1).
     * Retorna la descripción tal cual fue suministrada por el usuario.
     */
    public static function find(string $code): ?string
    {
        $codeUpper = strtoupper(trim($code));
        if ($codeUpper === '') {
            return null;
        }

        $items = self::getItems();

        // 1. Búsqueda directa O(1) por código exacto
        if (isset($items[$codeUpper])) {
            return $items[$codeUpper];
        }

        // 2. Búsqueda directa O(1) en el índice normalizado (sin 'A' ni ceros a la izquierda)
        $clean = ltrim(str_replace('A', '', $codeUpper), '0');
        if ($clean !== '') {
            $normIndex = self::getNormalizedIndex();
            if (isset($normIndex[$clean])) {
                return $normIndex[$clean];
            }
        }

        return null;
    }

    /**
     * Busca en tiempo O(1) dentro del catálogo corporativo de 170 productos de Maquila,
     * devolviendo tanto el código oficial como sus metadatos (`['codigo' => ..., 'data' => ...]`).
     */
    public static function findInCorporateCatalog(string $code, array $corporateCatalog): ?array
    {
        $codeUpper = strtoupper(trim($code));
        if ($codeUpper === '') {
            return null;
        }

        if (isset($corporateCatalog[$codeUpper])) {
            return [
                'codigo' => $codeUpper,
                'data'   => $corporateCatalog[$codeUpper],
            ];
        }

        if (self::$corporateNormalizedIndex === null) {
            $idx = [];
            foreach ($corporateCatalog as $mCode => $mData) {
                $cleanM = ltrim(str_replace('A', '', strtoupper((string) $mCode)), '0');
                if ($cleanM !== '' && !isset($idx[$cleanM])) {
                    $idx[$cleanM] = [
                        'codigo' => $mCode,
                        'data'   => $mData,
                    ];
                }
            }
            self::$corporateNormalizedIndex = $idx;
        }

        $clean = ltrim(str_replace('A', '', $codeUpper), '0');
        if ($clean !== '' && isset(self::$corporateNormalizedIndex[$clean])) {
            return self::$corporateNormalizedIndex[$clean];
        }

        return null;
    }
}
