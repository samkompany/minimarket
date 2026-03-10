<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_default_to_smart_sorting(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lowStock = Product::factory()->create([
            'name' => 'Produit Low',
            'min_stock' => 5,
        ]);

        $highStock = Product::factory()->create([
            'name' => 'Produit High',
            'min_stock' => 5,
        ]);

        Stock::create([
            'product_id' => $lowStock->id,
            'quantity' => 2,
        ]);

        Stock::create([
            'product_id' => $highStock->id,
            'quantity' => 30,
        ]);

        $response = $this->actingAs($admin)->get(route('products.index'));

        $response->assertOk();

        $content = $response->getContent();
        $lowPos = strpos($content, 'Produit Low');
        $highPos = strpos($content, 'Produit High');

        $this->assertIsInt($lowPos);
        $this->assertIsInt($highPos);
        $this->assertTrue($lowPos < $highPos);
    }
}
