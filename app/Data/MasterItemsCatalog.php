<?php

namespace App\Data;

class MasterItemsCatalog
{
    private static ?array $items = null;

    /**
     * Obtener el catálogo oficial de 1.954 ítems provisto por el usuario
     * con sus descripciones literales sin modificar.
     */
    public static function getItems(): array
    {
        if (self::$items === null) {
            $jsonPath = __DIR__ . '/user_items.json';
            if (file_exists($jsonPath)) {
                $decoded = json_decode(file_get_contents($jsonPath), true);
                self::$items = is_array($decoded) ? $decoded : [];
            } else {
                self::$items = [];
            }
        }
        return self::$items;
    }

    /**
     * Buscar un ítem por su código exacto o flexible.
     * Retorna la descripción tal cual fue suministrada por el usuario.
     */
    public static function find(string $code): ?string
    {
        $items = self::getItems();
        $codeUpper = strtoupper(trim($code));

        if (isset($items[$codeUpper])) {
            return $items[$codeUpper];
        }

        // Búsqueda flexible sin ceros a la izquierda o quitando/poniendo prefijo 'A'
        $clean = ltrim(str_replace('A', '', $codeUpper), '0');
        if (!empty($clean)) {
            foreach ($items as $k => $desc) {
                $cleanK = ltrim(str_replace('A', '', strtoupper($k)), '0');
                if ($cleanK === $clean) {
                    return $desc;
                }
            }
        }

        return null;
    }
}
