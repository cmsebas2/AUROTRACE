<?php

namespace App\Helpers;

use ZipArchive;
use SimpleXMLElement;
use Exception;

class SimpleXLSXReader
{
    /**
     * Parse an .xlsx file into sheets array.
     * Return format:
     * [
     *   'SheetName' => [
     *      6 => [1 => 'ValA', 2 => 'ValB', ..., 8 => 'LOTE123', ...],
     *      7 => [...],
     *   ]
     * ]
     */
    public static function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("El archivo no existe: {$filePath}");
        }

        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            return static::parseWithPhpSpreadsheet($filePath);
        }

        return static::parseNativeZip($filePath);
    }

    private static function parseWithPhpSpreadsheet(string $filePath): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $result = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = trim($sheet->getTitle());
            $highestRow = $sheet->getHighestRow();
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
            
            $rows = [];
            for ($r = 1; $r <= $highestRow; $r++) {
                $rowCells = [];
                for ($c = 1; $c <= $highestCol; $c++) {
                    $cell = $sheet->getCell([$c, $r]);
                    $val = $cell->getFormattedValue();
                    if ($val === null || $val === '') {
                        $val = $cell->getValue();
                    }
                    $rowCells[$c] = trim((string)$val);
                }
                $rows[$r] = $rowCells;
            }
            $result[$sheetName] = $rows;
        }
        return $result;
    }

    private static function parseNativeZip(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("No se pudo abrir el archivo .xlsx: {$filePath}");
        }

        // 1. Shared Strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string)$val->t;
                    } elseif (isset($val->r)) {
                        $tText = '';
                        foreach ($val->r as $r) {
                            $tText .= (string)$r->t;
                        }
                        $sharedStrings[] = $tText;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Workbook Rels (rId => target)
        $rels = [];
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml !== false) {
            $xml = @simplexml_load_string($relsXml);
            if ($xml && isset($xml->Relationship)) {
                foreach ($xml->Relationship as $rel) {
                    $rels[(string)$rel['Id']] = (string)$rel['Target'];
                }
            }
        }

        // 3. Workbook Sheets (sheetName => file)
        $sheetsMap = [];
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml !== false) {
            $xml = @simplexml_load_string($workbookXml);
            if ($xml && isset($xml->sheets->sheet)) {
                foreach ($xml->sheets->sheet as $sheet) {
                    $name = (string)$sheet['name'];
                    $rId = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
                    $target = $rels[$rId] ?? '';
                    if (!str_contains($target, 'worksheets/')) {
                        $target = 'worksheets/' . basename($target);
                    }
                    $target = ltrim($target, '/');
                    if (!str_starts_with($target, 'xl/')) {
                        $target = 'xl/' . $target;
                    }
                    $sheetsMap[$name] = $target;
                }
            }
        }

        // 4. Parse each sheet
        $result = [];
        foreach ($sheetsMap as $sheetName => $targetPath) {
            $sheetXml = $zip->getFromName($targetPath);
            if ($sheetXml === false) {
                continue;
            }

            $xml = @simplexml_load_string($sheetXml);
            if (!$xml || !isset($xml->sheetData->row)) {
                $result[$sheetName] = [];
                continue;
            }

            $rows = [];
            foreach ($xml->sheetData->row as $rowNode) {
                $rIndex = (int)$rowNode['r'];
                $cells = [];

                foreach ($rowNode->c as $cNode) {
                    $rRef = (string)$cNode['r'];
                    preg_match('/^([A-Z]+)(\d+)$/', $rRef, $m);
                    $colStr = $m[1] ?? 'A';
                    $cIndex = static::columnLetterToIndex($colStr);

                    $type = (string)$cNode['t'];
                    $val = (string)$cNode->v;

                    if ($type === 's') {
                        $sId = (int)$val;
                        $cellVal = $sharedStrings[$sId] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $cellVal = (string)($cNode->is->t ?? '');
                    } else {
                        $cellVal = $val;
                    }

                    $cells[$cIndex] = trim($cellVal);
                }

                $rows[$rIndex] = $cells;
            }

            $result[$sheetName] = $rows;
        }

        $zip->close();

        return $result;
    }

    public static function columnLetterToIndex(string $col): int
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index;
    }

    public static function indexToColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $m = ($index - 1) % 26;
            $letter = chr(65 + $m) . $letter;
            $index = (int)(($index - $m) / 26);
        }
        return $letter;
    }
}
