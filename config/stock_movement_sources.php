<?php

/**
 * Traduce el source_type (clase polimórfica) de stock_movements a un
 * nombre legible en español para mostrar en el módulo de Movimientos.
 *
 * Actualizar esta lista cada vez que un nuevo proceso de negocio
 * empiece a generar stock_movements (nuevo "origen" del movimiento).
 */
return [
    \App\Models\ExplosiveFieldDispatch::class => 'Despacho de explosivos',
    \App\Models\Purchase::class => 'Compra',
    \App\Models\WarehouseTransfer::class => 'Transferencia entre almacenes',
];