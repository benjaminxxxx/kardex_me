<?php

namespace App\Services;

use App\Models\Product;

class StockDisplayService
{
    /**
     * Convierte una cantidad en unidad base a su desglose legible
     * usando las presentaciones del producto, de mayor a menor factor.
     *
     * Ej: 1501 metros, con Rollo=500 y Caja=250 -> "3 Rollo, 1 metro"
     */
    public static function breakdown(Product $product, float $quantityBase): string
    {
        if ($quantityBase <= 0) {
            return '0 ' . ($product->unit->alias ?: $product->unit->name);
        }

        $presentaciones = $product->presentations
            ->where('is_active', true)
            ->sortByDesc('conversion_factor');

        $restante = $quantityBase;
        $partes = [];

        foreach ($presentaciones as $presentacion) {
            $factor = (float) $presentacion->conversion_factor;
            if ($factor <= 0) continue;

            $cantidad = intdiv((int) floor($restante), (int) floor($factor));

            if ($cantidad > 0) {
                $partes[] = "{$cantidad} {$presentacion->name}" . ($cantidad > 1 ? 's' : '');
                $restante -= $cantidad * $factor;
            }
        }

        // Lo que sobra tras usar todas las presentaciones, en unidad base
        if ($restante > 0.0001 || empty($partes)) {
            $unidadBase = $product->unit->alias ?: $product->unit->name;
            $restanteFormateado = rtrim(rtrim(number_format($restante, 4, '.', ''), '0'), '.');
            $partes[] = "{$restanteFormateado} {$unidadBase}";
        }

        return implode(', ', $partes);
    }
}