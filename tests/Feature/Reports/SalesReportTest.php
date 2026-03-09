<?php

namespace Tests\Feature\Reports;

use App\Livewire\Reports\Sales as SalesReport;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_shows_detailed_lines(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = Product::factory()->create([
            'name' => 'Cafe Moulu',
            'cost_price' => 12.5,
            'currency' => 'USD',
        ]);

        $soldAt = now()->startOfDay()->addHours(10)->addMinutes(15);
        $sale = Sale::factory()->create([
            'user_id' => $admin->id,
            'reference' => 'SALE-TEST-1',
            'customer_name' => 'Jean',
            'status' => 'paid',
            'sold_at' => $soldAt,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20,
            'line_total' => 40,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.sales'));

        $response->assertStatus(200);
        $response->assertSee('Details des ventes');
        $response->assertSee('SALE-TEST-1');
        $response->assertSee('Cafe Moulu');
        $response->assertSee('Jean');
        $response->assertSee('2');
        $response->assertSee('20.00 USD');
        $response->assertSee('12.50 USD');
        $response->assertSee('40.00 USD');
        $response->assertSee($soldAt->format('Y-m-d H:i'));
        $response->assertSee($admin->name);
        $response->assertSee('Total global');
        $response->assertSee('40.00 USD');
    }

    public function test_sales_report_can_export_excel(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = Product::factory()->create([
            'name' => 'Farine',
            'cost_price' => 5,
            'currency' => 'CDF',
        ]);

        $soldAt = now()->startOfDay()->addHours(8);
        $sale = Sale::factory()->create([
            'user_id' => $admin->id,
            'reference' => 'SALE-EXPORT-1',
            'customer_name' => 'Amina',
            'status' => 'paid',
            'sold_at' => $soldAt,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 10,
            'line_total' => 30,
        ]);

        Livewire::actingAs($admin)
            ->test(SalesReport::class)
            ->set('startDate', $soldAt->format('Y-m-d'))
            ->set('endDate', $soldAt->format('Y-m-d'))
            ->call('exportSales')
            ->assertFileDownloaded('sales-report.xlsx');
    }
}
