<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientAccount;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClientService
{
    public function createClient(array $data): Client
    {
        $validated = $this->validateClientData($data);
        return Client::create($validated);
    }

    public function getAllClients()
    {
        return Client::orderBy('full_name')->get();
    }

    public function getClientById($id): Client
    {
        return Client::findOrFail($id);
    }

    public function updateClient($id, array $data): Client
    {
        $client = $this->getClientById($id);
        $validated = $this->validateClientData($data, $client->id);

        $client->update($validated);
        return $client->fresh();
    }

    public function deleteClient($id): array
    {
        $client = $this->getClientById($id);

        // Verificar si tiene órdenes asociadas
        if ($client->orders()->count() > 0) {
            throw new \Exception('Cannot delete a client with associated orders', 400);
        }

        $client->delete();
        return ['message' => 'Client deleted'];
    }

    public function findOrCreate(array $clientData): Client
    {
        return Client::firstOrCreate(
            ['document' => $clientData['document']],
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
        $rules = [
            'document' => 'required|string|unique:clients,document' . ($ignoreId ? ",$ignoreId" : ''),
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

    public function getAllClientsForDataTable()
    {
        $clients = $this->getAllClients();

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
