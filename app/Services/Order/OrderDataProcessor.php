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

        if (($data['source'] ?? null) == OrderSource::Ecommerce->value) {
            if (($data['customer_type'] ?? null) === Client::class) {
                if (isset($data['token'])) {
                    $this->handleTokenOrder($data);
                } elseif (isset($data['client'])) {
                    $this->handleEcommerceOrder($data);
                }
            } elseif (($data['customer_type'] ?? null) === \App\Models\Branch::class) {
                // Usamos la misma lógica interna para procesar los IDs de sucursal
                $this->handleInternalOrder($data);
                $data['user_id'] = $this->getDefaultEcommerceUser()->id;
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
            // FLUJO DE SUCURSALES
            $myBranchId = $this->currentBranchId();

            // Si existe branch_recipient_id, es un CREATE (necesita inversión inicial)
            if (isset($data['branch_recipient_id'])) {
                $data['branch_id'] = $data['branch_recipient_id'];
                $data['customer_id'] = $myBranchId;
            }
            // Si no existe, es un UPDATE o ya viene con customer_id definido
            else {
                // Validamos que existan los campos necesarios
                $data['branch_id'] = $data['branch_id'] ?? null;
                $data['customer_id'] = $data['customer_id'] ?? $myBranchId;
            }

            if (!$data['branch_id']) {
                throw new \Exception('Debe seleccionar una sucursal proveedora.');
            }

            if ($data['branch_id'] == $data['customer_id']) {
                throw new \Exception('No puedes realizar un pedido a la misma sucursal.');
            }

            $data['customer_type'] = \App\Models\Branch::class;
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
