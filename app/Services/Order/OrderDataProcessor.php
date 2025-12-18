<?php

namespace App\Services\Order;

use App\Enums\OrderSource;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\Order;
use App\Models\User;
use App\Services\ClientService;
use App\Traits\AuthTrait;
use Illuminate\Support\Str;

class OrderDataProcessor
{
    protected ClientService $clientService;
    use AuthTrait;

    public function __construct(ClientService $clientService = null)
    {
        $this->clientService = $clientService ?? new ClientService();
    }

    public function prepare(array $validated, ?Order $existingOrder = null): array
    {
        $data = $validated;

        if (($validated['source'] ?? null) == OrderSource::Ecommerce->value) {
            // Si hay token, obtener cliente desde token
            if (isset($validated['token'])) {
                $this->handleTokenOrder($data);
            }
            // Si hay client, crear o buscar cliente desde los datos enviados
            elseif (isset($validated['client'])) {
                $this->handleEcommerceOrder($data);
            }
            // Si no hay ni token ni client, lanzar error
            else {
                throw new \Exception('Debes enviar token o client para pedidos Ecommerce');
            }
        } else {
            $this->handleInternalOrder($data);
        }

        return $data;
    }

    protected function handleTokenOrder(array &$data): void
    {
        $client = $this->clientService->getClientFromToken($data['token']);
        $data['customer_id'] = $client->id;
        $data['customer_type'] = Client::class;
        //$data['user_id'] = null;
        $data['user_id'] = $this->getDefaultEcommerceUser()->id;
        $data['branch_id'] = $data['branch_id'] ?? $this->currentBranchId() ?? null;
    }

    protected function handleEcommerceOrder(array &$data): void
    {
        // Validamos que client exista
        if (!isset($data['client'])) {
            throw new \Exception('No se proporcionó información de cliente');
        }

        $client = $this->clientService->findOrCreate($data['client']);
        $data['customer_id'] = $client->id;
        $data['customer_type'] = Client::class;
        $data['user_id'] = $this->getDefaultEcommerceUser()->id;
    }

    protected function handleInternalOrder(array &$data): void
    {
        $data['customer_type'] = $data['customer_type'] ?? Client::class;

        if ($data['customer_type'] === Client::class) {
            $data['customer_id'] = $data['client_id'];
            $data['branch_id'] = $data['branch_id'] ?? $this->currentBranchId();
        } else {
            $data['customer_id'] = $this->currentBranchId();
            $data['branch_id'] = $data['branch_recipient_id'];
        }
    }

    protected function getDefaultEcommerceUser(): User
    {
        return User::firstOrCreate(
            ['email' => config('app.ecommerce_default_user_email', 'ecommerce@system.com')],
            [
                'name' => 'Sistema E-commerce',
                'password' => bcrypt(Str::random(32)),
                'branch_id' => null,
            ]
        );
    }
}
