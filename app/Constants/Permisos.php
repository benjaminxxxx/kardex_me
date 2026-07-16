<?php

namespace App\Constants;

class Permisos
{
    const ROL_ADMIN = 'admin';

    // Usuarios (cuentas de acceso al sistema)
    const USUARIOS_VER = 'usuarios.ver';
    const USUARIOS_GESTIONAR = 'usuarios.gestionar'; // crear, editar, eliminar, otorgar acceso

    // Empleados (dominio de personal)
    const EMPLEADOS_VER = 'empleados.ver';
    const EMPLEADOS_GESTIONAR = 'empleados.gestionar'; // crear, editar, eliminar

    // Futuro: cuando actives el módulo Kardex
    // const KARDEX_VER = 'kardex.ver';
    // const KARDEX_GESTIONAR = 'kardex.gestionar';
}