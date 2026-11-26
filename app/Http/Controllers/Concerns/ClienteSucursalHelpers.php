<?php

namespace App\Http\Controllers\Concerns;

/**
 * Ayudas para escribir clientes en las tablas `clientes` de las sucursales (Magic).
 * Compartidas por ClientesController y ClientesHomologacionController (sacadas tal cual de ClientesController).
 */
trait ClienteSucursalHelpers
{
    /**
     * Retorna las columnas reales de una tabla en una sucursal (con caché en memoria).
     */
    private array $columnCache = [];

    private function getBranchColumns($conn, string $table): array
    {
        $key = spl_object_id($conn) . '.' . $table;
        if (!isset($this->columnCache[$key])) {
            $cols = $conn->select("SHOW COLUMNS FROM `{$table}`");
            $this->columnCache[$key] = array_map(fn($c) => $c->Field, $cols);
        }
        return $this->columnCache[$key];
    }

    /**
     * Resuelve el código abreviado para Vendedor/Asesor respetando el límite de 6 caracteres del ERP.
     */
    private function resolveCodigoVendedorAsesor(string $branchName): string
    {
        $name = strtoupper(trim($branchName));
        $map = [
            'TAPATIA' => 'EITSA',
            'DIMEGSA' => 'DIMEGS',
            'VALLARTA' => 'VALLAR',
            'QUERETARO' => 'QUERET',
            'ILUMINACION' => 'ILU',
        ];

        return $map[$name] ?? substr($name, 0, 6);
    }

    /**
     * Calcula el siguiente número de cliente (Cliente) en una sucursal,
     * respetando el consecutivo comercial real y evitando bloques
     * reservados como Cotizaciones (888888), CEDIS (888889-888913)
     * o números manuales históricos fuera de serie.
     */
    private function calcularSiguienteClienteSucursal($conn): int
    {
        $maxNormal = $conn->table('clientes')
            ->where('Cliente', '<', 800000)
            ->whereNotIn('Cliente', [555555, 601194, 333433, 333835, 333836, 228022, 225923, 225429])
            ->max('Cliente') ?? 0;

        if ($maxNormal === 0) {
            $maxNormal = $conn->table('clientes')->max('Cliente') ?? 0;
        }

        return (int) $maxNormal + 1;
    }

    /**
     * Convierte de snake_case (maestro) a PascalCase (sucursal) protegiendo límites de caracteres.
     */
    private function toBranchFormat(array $data): array
    {
        $fechaAlta = !empty($data['fecha_alta'])
            ? mb_substr(trim((string) $data['fecha_alta']), 0, 10)
            : now()->toDateString();

        return [
            'IdGlobal' => (int) ($data['id_global'] ?? 0),
            'RFC' => mb_substr(trim((string) ($data['rfc'] ?? '')), 0, 14),
            'Razon_Social' => mb_substr((string) ($data['razon_social'] ?? ''), 0, 255),
            'Calle' => mb_substr((string) ($data['calle'] ?? ''), 0, 70),
            'Exterior' => mb_substr((string) ($data['exterior'] ?? ''), 0, 10),
            'Interior' => mb_substr((string) ($data['interior'] ?? ''), 0, 10),
            'Colonia' => mb_substr((string) ($data['colonia'] ?? ''), 0, 60),
            'Cod_Postal' => (int) ($data['cod_postal'] ?? 0),
            'Ciudad' => mb_substr((string) ($data['ciudad'] ?? ''), 0, 4),
            'Municipio' => mb_substr((string) ($data['municipio'] ?? ''), 0, 30),
            'Telefono1' => mb_substr((string) ($data['telefono1'] ?? ''), 0, 15),
            'Telfono2' => mb_substr((string) ($data['telefono2'] ?? ''), 0, 15),   // typo real en la BD: "Telfono2"
            'Telefono3' => mb_substr((string) ($data['telefono3'] ?? ''), 0, 15),
            'Fax' => mb_substr((string) ($data['fax'] ?? ''), 0, 15),
            'Vendedor' => mb_substr((string) ($data['vendedor'] ?? ''), 0, 6),
            'Documentos' => mb_substr((string) ($data['documentos'] ?? ''), 0, 255),
            'Dias_Pago' => mb_substr((string) ($data['dias_pago'] ?? ''), 0, 6),
            'Dias_Revision' => mb_substr((string) ($data['dias_revision'] ?? ''), 0, 6),
            'Condicion_Pago' => mb_substr((string) ($data['condicion_pago'] ?? ''), 0, 4),
            'Dias_Credito' => (int) ($data['dias_credito'] ?? 0),
            'Limite_Credito' => (float) ($data['limite_credito'] ?? 0),
            'OtorgoCreditO' => (int) ($data['otorgo_credito'] ?? 0),
            'OtorgoCredito' => (int) ($data['otorgo_credito'] ?? 0),
            'e_mail' => (string) ($data['e_mail'] ?? $data['email'] ?? ''),
            'email' => (string) ($data['email'] ?? $data['e_mail'] ?? ''),
            'Status' => mb_substr((string) ($data['status'] ?? 'A'), 0, 1),
            'Cta_Contable' => mb_substr((string) ($data['cta_contable'] ?? ''), 0, 14),
            'Fecha_Alta' => $fechaAlta,
            'Representante' => mb_substr((string) ($data['representante'] ?? ''), 0, 60),
            'Addenda' => mb_substr((string) ($data['addenda'] ?? ''), 0, 15),
            'IdRegIdTrib' => mb_substr((string) ($data['id_reg_id_trib'] ?? ''), 0, 40),
            'RegimenFiscal' => mb_substr((string) ($data['regimen_fiscal'] ?? ''), 0, 3),
            'Anticipos' => mb_substr((string) ($data['anticipos'] ?? 'D'), 0, 1),
            'CodigoContpaq' => (int) ($data['codigo_contpaq'] ?? 0),
            'Clasificacion' => (int) ($data['clasificacion'] ?? 0),
            'ModificarFPMPFac' => (int) ($data['modificar_fpmpfac'] ?? 0),
            'Identificador' => mb_substr((string) ($data['identificador'] ?? ''), 0, 10),
            'IdOpcionBloqueo' => (int) ($data['id_opcion_bloqueo'] ?? 0),
            'ObservacionesCaja' => mb_substr((string) ($data['observaciones_caja'] ?? ''), 0, 255),
            'Sync' => (int) ($data['sync'] ?? 0),
            'PrefijoDescripcion' => (int) ($data['prefijo_descripcion'] ?? 0),
            'IdSugar' => mb_substr((string) ($data['id_sugar'] ?? ''), 0, 36),
            'GiroPrincipal' => (int) ($data['giro_principal'] ?? 0),
            'Asesor' => mb_substr((string) ($data['asesor'] ?? ''), 0, 6),
            'Ubicacion' => mb_substr((string) ($data['ubicacion'] ?? ''), 0, 3),
        ];
    }
}
