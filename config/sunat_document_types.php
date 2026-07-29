<?php

/**
 * Traduce el document_type interno de Purchase al código oficial
 * de la Tabla 10 de SUNAT (Tipo de Comprobante de Pago), usado
 * en el Formato 13.1 del Kardex.
 *
 * "nota_venta" no es un comprobante tributario reconocido por SUNAT
 * (no otorga crédito fiscal ni sustenta costo/gasto formalmente),
 * por eso no tiene código de Tabla 10 — se deja null.
 */
return [
    'factura' => '01',
    'boleta' => '03',
    'nota_venta' => null,
];