<?php

namespace App\Exports;

use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        public ?string $startDate,
        public ?string $endDate,
        public bool $isAdmin,
        public ?int $userId,
    ) {}

    public function query(): Builder
    {
        return SaleItem::query()
            ->select('sale_items.*')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->with(['sale.user', 'product'])
            ->where('sales.status', 'paid')
            ->when($this->startDate, fn ($q) => $q->whereDate('sales.sold_at', '>=', $this->startDate))
            ->when($this->endDate, fn ($q) => $q->whereDate('sales.sold_at', '<=', $this->endDate))
            ->when(! $this->isAdmin, fn ($q) => $q->where('sales.user_id', $this->userId))
            ->orderByDesc('sales.sold_at')
            ->orderByDesc('sale_items.id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Date',
            'Reference',
            'Produit',
            'Client',
            'Qte',
            'PU',
            'PA',
            'Total',
            'Vendeur',
            'Devise',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        $currency = $row->product?->currency ?? 'CDF';

        return [
            $row->sale?->sold_at?->format('Y-m-d H:i'),
            $row->sale?->reference,
            $row->product?->name,
            $row->sale?->customer_name ?? 'Comptoir',
            $row->quantity,
            $row->unit_price,
            $row->product?->cost_price,
            $row->line_total,
            $row->sale?->user?->name,
            $currency,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
