<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientAccount;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientService
{
    /**
     * Punto central para consultas filtradas por sucursal.
     */
    private function branchQuery(int $branchId)
    {
        return Client::forBranch($branchId);
    }

    public function createClient(array $data): Client
    {
        $validated = $this->validateClientData($data);
        return Client::create($validated);
    }

    public function getAllClients(int $branchId)
    {
        // Usamos el helper centralizado
        return $this->branchQuery($branchId)
            ->orderBy('full_name')
            ->get();
    }

    public function getClientById($id, ?int $branchId = null): Client
    {
        $query = Client::query();

        if ($branchId) {
            $query->forBranch($branchId);
        }

        return $query->findOrFail($id);
    }

    public function updateClient($id, array $data): Client
    {
        $client = $this->getClientById($id, $data['branch_id'] ?? null);
        $validated = $this->validateClientData($data, $client->id);

        $client->update($validated);
        return $client->fresh();
    }

    public function deleteClient($id, ?int $branchId = null): array
    {
        $client = $this->getClientById($id, $branchId);

        if ($client->orders()->exists()) {
            throw new \Exception('Cannot delete a client with associated orders', 400);
        }

        $client->delete();
        return ['message' => 'Client deleted'];
    }

    public function findOrCreate(array $clientData, int $branchId): Client
    {
        return Client::firstOrCreate(
            [
                'document' => $clientData['document'],
                'branch_id' => $branchId
            ],
            [
                'full_name' => $clientData['full_name'] ?? '',
                'email' => $clientData['email'] ?? null,
                'phone' => $clientData['phone'] ?? null,
                'address' => $clientData['address'] ?? null,
            ]
        );
    }

    public function getClientFromToken(string $token): Client
    {
        $clientAccount = ClientAccount::whereHas('tokens', function ($query) use ($token) {
            $query->where('token', hash('sha256', explode('|', $token)[1] ?? ''));
        })->first();

        if (!$clientAccount) {
            abort(401, 'Token inválido');
        }

        return $clientAccount->client;
    }

    public function validateClientData(array $data, $ignoreId = null): array
    {
        $branchId = $data['branch_id'] ?? null;
        $rules = [
            'branch_id' => 'required|exists:branches,id',
            'document' => [
                'required',
                'string',
                Rule::unique('clients')->where(fn($q) => $q->where('branch_id', $branchId))->ignore($ignoreId)
            ],
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|unique:clients,email' . ($ignoreId ? ",$ignoreId" : ''),
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function getClientsWithOrders()
    {
        return Client::with('orders')->get();
    }

    public function getAllClientsForDataTable(int $branchId)
    {
        // Reutilizamos el método que ya filtra por branch
        $clients = $this->getAllClients($branchId);

        return $clients->map(function ($client, $index) {
            return [
                'id' => $client->id,                    // Oculto pero disponible como data-id
                'is_system' => $client->is_system ? 1 : 0,
                'number' => $index + 1,                 // Número incremental visible
                'document' => $client->document,        // Visible
                'full_name' => $client->full_name,      // Visible
                'phone' => $client->phone,              // Visible
                'email' => $client->email,              // Visible
                'created_at' => $client->created_at->format('Y-m-d'), // Visible
            ];
        })->toArray();
    }
}
