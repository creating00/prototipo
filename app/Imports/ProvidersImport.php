<?php

namespace App\Imports;

use App\Models\Provider;
use App\Services\ProviderService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ProvidersImport implements ToModel, WithHeadingRow
{
    private $providerService;

    public function __construct()
    {
        $this->providerService = app(ProviderService::class);
    }

    public function model(array $row)
    {
        Log::info("Procesando fila: " . json_encode($row));

        $taxId = trim($row['tax_id'] ?? $row['cuit'] ?? '');
        if (empty($taxId)) return null;

        $data = [
            'business_name' => (string) ($row['razon_social'] ?? $row['business_name'] ?? 'S/N'),
            'tax_id'        => (string) $taxId,
            'short_name'    => isset($row['nombre_corto']) ? (string)$row['nombre_corto'] : ($row['short_name'] ?? null),
            'contact_name'  => isset($row['contacto']) ? (string)$row['contacto'] : ($row['contact_name'] ?? null),
            'email'         => $row['email'] ?? null,
            'phone'         => isset($row['telefono']) ? (string)$row['telefono'] : ($row['phone'] ?? null),
            'address'       => $row['direccion'] ?? $row['address'] ?? null,
        ];

        try {
            $existingProvider = Provider::where('tax_id', $data['tax_id'])->first();

            if ($existingProvider) {
                $this->providerService->updateProvider($existingProvider->id, $data);
                return null;
            }

            $this->providerService->createProvider($data);
            return null;
        } catch (ValidationException $e) {
            Log::error("Error validación importación Provider ({$taxId}): " . json_encode($e->errors()));
            return null;
        } catch (\Exception $e) {
            Log::error("Error general importación Provider: " . $e->getMessage());
            return null;
        }
    }
}
