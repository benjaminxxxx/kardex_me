<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use App\Models\SupplierBranch;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [

            // ==========================================================
            // FAMESA
            // ==========================================================

            [
                'supplier_code' => 'PROV000001',
                'status' => 'approved',
                'notes' => 'Proveedor principal de explosivos.',

                'person' => [
                    'document_number' => '20100070970',
                    'company_name' => 'FAMESA EXPLOSIVOS S.A.C.',
                    'email' => 'ventas@famesa.com.pe',
                    'phone' => '014567890',
                ],

                'branches' => [
                    [
                        'type' => 'fiscal',
                        'name' => 'Oficina Principal Lima',
                        'is_main' => true,
                        'country' => 'Perú',
                        'state' => 'Lima',
                        'city' => 'Lima',
                        'address' => 'Av. Industrial 1000',
                        'phone' => '014567890',
                        'email' => 'ventas@famesa.com.pe',
                    ],
                    [
                        'type' => 'field',
                        'name' => 'Sucursal Arequipa',
                        'is_main' => false,
                        'country' => 'Perú',
                        'state' => 'Arequipa',
                        'city' => 'Arequipa',
                        'address' => 'Parque Industrial Río Seco',
                        'phone' => '054123456',
                        'email' => 'arequipa@famesa.com.pe',
                    ],
                ],

                'bank_accounts' => [
                    [
                        'type' => 'bank_account',
                        'bank_name' => 'BCP',
                        'account_number' => '191123456789',
                        'cci' => '00219100012345678912',
                        'currency' => 'PEN',
                        'account_holder_name' => 'FAMESA EXPLOSIVOS S.A.C.',
                        'is_main' => true,
                        'notes' => null,
                    ],
                ],
            ],

            // ==========================================================
            // SEGURIDAD INDUSTRIAL
            // ==========================================================

            [
                'supplier_code' => 'PROV000002',
                'status' => 'approved',
                'notes' => 'Proveedor de equipos de protección personal.',

                'person' => [
                    'document_number' => '20512345678',
                    'company_name' => 'SEGURIDAD INDUSTRIAL DEL SUR S.A.C.',
                    'email' => 'ventas@segsur.pe',
                    'phone' => '054445566',
                ],

                'branches' => [
                    [
                        'type' => 'fiscal',
                        'name' => 'Casa Matriz',
                        'is_main' => true,
                        'country' => 'Perú',
                        'state' => 'Arequipa',
                        'city' => 'Arequipa',
                        'address' => 'Av. Ejército 1234',
                        'phone' => '054445566',
                        'email' => 'ventas@segsur.pe',
                    ],
                ],

                'bank_accounts' => [
                    [
                        'type' => 'bank_account',
                        'bank_name' => 'BBVA',
                        'account_number' => '001123456789',
                        'cci' => '01112300012345678945',
                        'currency' => 'PEN',
                        'account_holder_name' => 'SEGURIDAD INDUSTRIAL DEL SUR S.A.C.',
                        'is_main' => true,
                        'notes' => null,
                    ],
                ],
            ],

            // ==========================================================
            // FERREMIN
            // ==========================================================

            [
                'supplier_code' => 'PROV000003',
                'status' => 'prospect',
                'notes' => 'Proveedor de ferretería y herramientas.',

                'person' => [
                    'document_number' => '20612345679',
                    'company_name' => 'FERREMIN S.A.C.',
                    'email' => 'ventas@ferremin.pe',
                    'phone' => '016543210',
                ],

                'branches' => [
                    [
                        'type' => 'fiscal',
                        'name' => 'Sucursal Lima',
                        'is_main' => true,
                        'country' => 'Perú',
                        'state' => 'Lima',
                        'city' => 'Lima',
                        'address' => 'Av. Argentina 555',
                        'phone' => '016543210',
                        'email' => 'ventas@ferremin.pe',
                    ],
                ],

                'bank_accounts' => [
                    [
                        'type' => 'bank_account',
                        'bank_name' => 'Interbank',
                        'account_number' => '898989898989',
                        'cci' => '00389800012345678965',
                        'currency' => 'PEN',
                        'account_holder_name' => 'FERREMIN S.A.C.',
                        'is_main' => true,
                        'notes' => null,
                    ],
                ],
            ],

        ];

        foreach ($suppliers as $data) {

            // ======================================================
            // PERSONA
            // ======================================================

            $person = Person::updateOrCreate(
                [
                    'document_type' => 'RUC',
                    'document_number' => $data['person']['document_number'],
                ],
                [
                    'code' => $data['supplier_code'],
                    'type' => 'company',

                    'document_type' => 'RUC',
                    'document_number' => $data['person']['document_number'],

                    'company_name' => $data['person']['company_name'],
                    'display_name' => $data['person']['company_name'],
                    'legal_name' => $data['person']['company_name'],

                    'phone' => $data['person']['phone'],
                    'email' => $data['person']['email'],

                    'country' => 'Perú',
                    'state' => 'Lima',
                    'city' => 'Lima',

                    'is_active' => true,
                ]
            );

            // ======================================================
            // PROVEEDOR
            // ======================================================

            $supplier = Supplier::updateOrCreate(
                [
                    'supplier_code' => $data['supplier_code'],
                ],
                [
                    'person_id' => $person->id,
                    'status' => $data['status'],
                    'notes' => $data['notes'],
                ]
            );

            // ======================================================
            // SUCURSALES
            // ======================================================

            foreach ($data['branches'] as $branch) {

                SupplierBranch::updateOrCreate(
                    [
                        'supplier_id' => $supplier->id,
                        'name' => $branch['name'],
                    ],
                    [
                        'type' => $branch['type'],
                        'is_main' => $branch['is_main'],
                        'country' => $branch['country'],
                        'state' => $branch['state'],
                        'city' => $branch['city'],
                        'address' => $branch['address'],
                        'phone' => $branch['phone'],
                        'email' => $branch['email'],
                    ]
                );
            }

            // ======================================================
            // CUENTAS BANCARIAS
            // ======================================================

            foreach ($data['bank_accounts'] as $account) {

                SupplierBankAccount::updateOrCreate(
                    [
                        'supplier_id' => $supplier->id,
                        'account_number' => $account['account_number'],
                    ],
                    [
                        'type' => $account['type'],
                        'bank_name' => $account['bank_name'],
                        'account_number' => $account['account_number'],
                        'cci' => $account['cci'],
                        'currency' => $account['currency'],
                        'wallet_provider' => null,
                        'wallet_phone' => null,
                        'account_holder_name' => $account['account_holder_name'],
                        'is_main' => $account['is_main'],
                        'notes' => $account['notes'],
                    ]
                );
            }
        }
    }
}