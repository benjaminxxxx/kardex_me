<?php
namespace App\Services\Generators;

class CodeGeneratorService
{
    /**
     * Genera un código formateado con prefijo y relleno de ceros.
     * Sirve para Productos, Empleados, Facturas, etc.
     */
    public static function generateCode(?int $lastId, string $prefix, int $padding = 5): string
    {
        $nextId = ($lastId ?? 0) + 1;

        return sprintf('%s-%s', strtoupper($prefix), str_pad((string) $nextId, $padding, '0', STR_PAD_LEFT));
    }
}