<?php

namespace App\Constants;

class Permisos
{
    // =========================================================================
    // ROLES / PERMISOS (administración del propio sistema de permisos)
    // =========================================================================

    const ROL_ADMIN = 'admin';

    const ROLES_VER = 'roles.ver';
    const ROLES_GESTIONAR = 'roles.gestionar';
    const PERMISOS_SINCRONIZAR = 'permisos.sincronizar'; // botón manual, solo Developer/Admin en la práctica

    // =========================================================================
    // USUARIOS
    // =========================================================================

    const USUARIOS_VER = 'usuarios.ver';
    const USUARIOS_GESTIONAR = 'usuarios.gestionar';

    // =========================================================================
    // EMPLEADOS
    // =========================================================================

    const EMPLEADOS_VER = 'empleados.ver';
    const EMPLEADOS_GESTIONAR = 'empleados.gestionar';

    // =========================================================================
    // PRODUCTOS
    // =========================================================================

    const PRODUCTOS_VER = 'productos.ver';
    const PRODUCTOS_GESTIONAR = 'productos.gestionar';

    // =========================================================================
    // PROVEEDORES
    // =========================================================================

    const PROVEEDORES_VER = 'proveedores.ver';
    const PROVEEDORES_GESTIONAR = 'proveedores.gestionar';

    // =========================================================================
    // ENTRADAS
    // =========================================================================

    const ENTRADAS_VER = 'entradas.ver';
    const ENTRADAS_GESTIONAR = 'entradas.gestionar';

    // =========================================================================
    // SALIDAS (genérico)
    // =========================================================================

    const SALIDAS_VER = 'salidas.ver';
    const SALIDAS_GESTIONAR = 'salidas.gestionar';

    // =========================================================================
    // DESPACHO DE EXPLOSIVOS (dominio regulado, permisos propios)
    // =========================================================================

    const DESPACHOS_EXPLOSIVOS_VER = 'despachos_explosivos.ver';
    const DESPACHOS_EXPLOSIVOS_GESTIONAR = 'despachos_explosivos.gestionar'; // acceso general al submódulo

    const EXPLOSIVOS_ENTRADA_REGISTRAR = 'explosivos.entrada_registrar';   // registrar ingreso de explosivos a almacén
    const EXPLOSIVOS_DESPACHO_REGISTRAR = 'explosivos.despacho_registrar'; // almacenero: registra el despacho a campo
    const EXPLOSIVOS_DISTRIBUIR = 'explosivos.distribuir';                 // supervisor: reparte por perforista/labor
    const EXPLOSIVOS_RETIRAR = 'explosivos.retirar';                       // elegible para figurar como "supervisor que retira"
    const EXPLOSIVOS_BUFFER_VER = 'explosivos.buffer_ver';                 // ver arqueo/sobrante acumulado (futuro reporte)

    // =========================================================================
    // ZONAS / LABORES
    // =========================================================================

    const LABORES_VER = 'labores.ver';
    const LABORES_GESTIONAR = 'labores.gestionar';

    // =========================================================================
    // KARDEX
    // =========================================================================

    const KARDEX_VER = 'kardex.ver';
    const KARDEX_GESTIONAR = 'kardex.gestionar';

    const CONFIGURACION_VER = 'configuracion.ver';
    const CONFIGURACION_GESTIONAR = 'configuracion.gestionar';
    const DISTRIBUCION_REPORTE_VER = 'distribucion_reporte.ver';
    const COMPRAS_VER = 'compras.ver';
    const COMPRAS_GESTIONAR = 'compras.gestionar';
}