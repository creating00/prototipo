<?php

namespace App\Services;

use App\Enums\SaleType;
use App\Services\Sale\SaleValidator;
use App\Services\Sale\SaleCreator;
use App\Services\Sale\SaleUpdater;
use App\Services\Sale\SaleDeleter;
use App\Services\Sale\SalePaymentManager;
use App\Models\Sale;
use App\Traits\AuthTrait;
use App\Services\Traits\DataTableFormatter;
use App\Services\Product\ProductStockService;

class SaleService
{
    use DataTableFormatter;
    use AuthTrait;

    protected ProductStockService $stockService;
    protected SaleValidator $validator;
    protected SaleCreator $creator;
    protected SaleUpdater $updater;
    protected SaleDeleter $deleter;
    protected SalePaymentManager $paymentManager;

    public function __construct(
        ProductStockService $stockService,
        SaleValidator $validator,
        SaleCreator $creator,
        SaleUpdater $updater,
        SaleDeleter $deleter,
        SalePaymentManager $paymentManager
    ) {
        $this->stockService = $stockService;
        $this->validator = $validator;
        $this->creator = $creator;
        $this->updater = $updater;
        $this->deleter = $deleter;
        $this->paymentManager = $paymentManager;
    }

    public function getAllSales()
    {
        return Sale::with(['branch', 'customer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSaleById($id)
    {
        return Sale::with(['branch', 'items.product', 'discount', 'customer'])
            ->findOrFail($id);
    }

    public function getAllSalesForDataTable(): array
    {
        $branchId = $this->currentBranchId();

        $sales = Sale::with(['branch', 'customer'])
            ->forBranch($branchId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $sales->map(
            fn($sale, $index) => $this->formatForDataTable($sale, $index)
        )->toArray();
    }

    public function createSale(array $data): Sale
    {
        $validated = $this->validator->validate($data);

        return $this->creator->create($validated, function ($sale, $paymentData) {
            return $this->paymentManager->addPaymentToSale($sale, $paymentData);
        });
    }

    public function updateSale($id, array $data): Sale
    {
        $sale = Sale::findOrFail($id);
        $validated = $this->validator->validate($data, $sale->id);

        return $this->updater->update($sale, $validated, function ($sale, $paymentData) {
            return $this->paymentManager->addPaymentToSale($sale, $paymentData);
        });
    }

    public function deleteSale($id): void
    {
        $sale = $this->getSaleById($id);
        $this->deleter->delete($sale);
    }

    public function addPayment($saleId, array $paymentData)
    {
        $sale = $this->getSaleById($saleId);
        return $this->paymentManager->processPayment($sale, $paymentData);
    }

    public function getPaymentSummary($saleId): array
    {
        $sale = $this->getSaleById($saleId);
        return $this->paymentManager->getPaymentSummary($sale);
    }

    public function getPayments($saleId)
    {
        $sale = $this->getSaleById($saleId);
        return $this->paymentManager->getPayments($sale);
    }

    protected function formatStatusBadge(string $statusLabel): string
    {
        $statusEnum = collect(\App\Enums\SaleStatus::cases())
            ->first(fn($case) => $case->label() === $statusLabel);

        if (!$statusEnum) {
            return "<span class=\"badge-custom badge-custom-pastel-blue\">{$statusLabel}</span>";
        }

        $class = $statusEnum->badgeClass();
        return "<span class=\"{$class}\">{$statusLabel}</span>";
    }

    public function buildOrderItemsHtml(Sale $order): array
    {
        return $order->items->map(function ($item) use ($order) {
            return [
                'html' => view('admin.order.partials._item_row', [
                    'product'   => $item->product,
                    'item'      => $item,
                    'stock'     => $item->product->getStock($order->branch_id),
                    'salePrice' => $item->unit_price,
                    'allowEditPrice' => true,
                ])->render(),
            ];
        })->values()->toArray();
    }
}
