<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\Notification;

class OrderObserver
{
    public function created(Order $order)
    {
        // Vacio. La notificacion inicial se hara desde el Service
        // despues de guardar los items.
    }

    public function updated(Order $order)
    {
        if ($order->wasChanged('status')) {
            // Usamos el accesor que ya tienes en tu modelo Order
            $customerName = $order->customer_name;

            $message = match ($order->status) {
                OrderStatus::Cancelled => "El pedido de {$customerName} ha sido cancelado.",
                OrderStatus::Confirmed => "El pedido de {$customerName} fue confirmado.",
                default => "Actualización en el pedido de {$customerName}.",
            };

            $this->sendNotification($order, $message);
        }
    }

    protected function sendNotification(Order $order, string $message)
    {
        $targetUsers = User::where('branch_id', $order->branch_id)->get();

        if ($targetUsers->isNotEmpty()) {
            Notification::send($targetUsers, new OrderStatusNotification($order, $message));
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
