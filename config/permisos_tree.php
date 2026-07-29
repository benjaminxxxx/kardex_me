<?php

use App\Constants\Permisos;

return [
    [
        'nombre' => 'Sistema',
        'hijos' => [
            [
                'nombre' => Permisos::USUARIOS_VER,
                'hijos' => [
                    ['nombre' => Permisos::USUARIOS_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::ROLES_VER,
                'hijos' => [
                    ['nombre' => Permisos::ROLES_GESTIONAR],
                    ['nombre' => Permisos::PERMISOS_SINCRONIZAR],
                ],
            ],
            [
                'nombre' => Permisos::CONFIGURACION_VER,
                'hijos' => [
                    ['nombre' => Permisos::CONFIGURACION_GESTIONAR],
                ],
            ],
        ],
    ],
    [
        'nombre' => 'Recursos Humanos',
        'hijos' => [
            [
                'nombre' => Permisos::EMPLEADOS_VER,
                'hijos' => [
                    ['nombre' => Permisos::EMPLEADOS_GESTIONAR],
                ],
            ],
        ],
    ],
    [
        'nombre' => 'Compras',
        'hijos' => [
            [
                'nombre' => Permisos::PROVEEDORES_VER,
                'hijos' => [
                    ['nombre' => Permisos::PROVEEDORES_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::COMPRAS_VER,
                'hijos' => [
                    ['nombre' => Permisos::COMPRAS_GESTIONAR],
                ],
            ],
        ],
    ],
    [
        'nombre' => 'Almacén',
        'hijos' => [
            [
                'nombre' => Permisos::PRODUCTOS_VER,
                'hijos' => [
                    ['nombre' => Permisos::PRODUCTOS_GESTIONAR],
                ],
            ],
            
        ],
    ],
    [
        'nombre' => 'Kardex',
        'hijos' => [
            [
                'nombre' => Permisos::ENTRADAS_VER,
                'hijos' => [
                    ['nombre' => Permisos::ENTRADAS_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::DESPACHOS_EXPLOSIVOS_VER,
                'hijos' => [
                    ['nombre' => Permisos::DESPACHOS_EXPLOSIVOS_GESTIONAR],
                    ['nombre' => Permisos::EXPLOSIVOS_ENTRADA_REGISTRAR],
                    ['nombre' => Permisos::EXPLOSIVOS_DESPACHO_REGISTRAR],
                    ['nombre' => Permisos::EXPLOSIVOS_DISTRIBUIR],
                    ['nombre' => Permisos::EXPLOSIVOS_RETIRAR],
                    ['nombre' => Permisos::EXPLOSIVOS_BUFFER_VER],
                ],
            ],
            [
                'nombre' => Permisos::DISTRIBUCION_REPORTE_VER,
                'hijos' => [],
            ],
            [
                'nombre' => Permisos::SALIDAS_VER,
                'hijos' => [
                    ['nombre' => Permisos::SALIDAS_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::LABORES_VER,
                'hijos' => [
                    ['nombre' => Permisos::LABORES_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::KARDEX_VER,
                'hijos' => [
                    ['nombre' => Permisos::KARDEX_GESTIONAR],
                ],
            ],
            [
                'nombre' => Permisos::MOVIMIENTOS_VER,
                'hijos' => [],
            ],
        ],
    ],
];