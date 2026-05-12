<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderStatusNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $message
    ) {}

    // Define los canales
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    // Datos para la tabla notifications
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status_label' => $this->order->status->label(),
            'source_label' => $this->order->source?->label() ?? 'Desconocido',
            'message' => $this->message,
        ];
    }

    // Datos transmitidos por WebSocket
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'order_id' => $this->order->id,
            'status_label' => $this->order->status->label(),
            'message' => $this->message,
        ]);
    }
}