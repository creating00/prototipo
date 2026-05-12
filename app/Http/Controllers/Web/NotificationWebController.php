<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationWebController extends Controller
{
    public function index()
    {
        $headers = ['#', 'Pedido', 'Mensaje', 'Origen', 'Estado', 'Fecha'];
        $hiddenFields = [];

        $rowData = $this->getFormattedNotifications();

        return view('admin.notifications.index', compact('headers', 'rowData', 'hiddenFields'));
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notificación no encontrada.'
        ], 404);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones fueron leídas.'
        ]);
    }

    public function readAndRedirect($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);

        // 1. Marcar como leída
        $notification->markAsRead();

        // 2. Obtener el ID del pedido del JSON data
        $orderId = $notification->data['order_id'] ?? null;

        // 3. Redirigir al detalle o al índice si no hay ID
        if ($orderId) {
            return redirect()->to("web/orders/{$orderId}/details");
        }

        return redirect()->route('web.notifications.index');
    }

    private function getFormattedNotifications(): array
    {
        $notifications = Auth::user()->notifications;

        return $notifications->map(function ($notification, $index) {
            $isRead = $notification->read_at !== null;
            $statusLabel = $isRead ? 'Leída' : 'No leída';
            $statusColor = $isRead ? 'secondary' : 'warning';

            $sourceLabel = $notification->data['source_label'] ?? 'Desconocido';
            $sourceColor = $sourceLabel === 'E-commerce' ? 'info' : 'primary';

            $orderId = $notification->data['order_id'] ?? null;

            return [
                // --- 1. CELDAS VISIBLES ---
                '#' => $index + 1,
                'Pedido' => '<span class="fw-bold">' . Order::formatOrderNumber($orderId) . '</span>',
                'Mensaje' => $notification->data['message'] ?? '',
                'Origen' => '<span class="badge bg-' . $sourceColor . '">' . $sourceLabel . '</span>',
                'Estado' => '<span class="badge bg-' . $statusColor . '">' . $statusLabel . '</span>',
                'Fecha' => $notification->created_at->format('d/m/Y H:i'),

                // --- 2. ATRIBUTOS DE FILA (data-attributes) ---
                '_row_attributes' => [
                    'id' => $notification->id,
                    'order_id' => $orderId,
                    'is_read' => $isRead ? '1' : '0',
                ]
            ];
        })->toArray();
    }
}
