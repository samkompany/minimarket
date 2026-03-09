<?php

namespace Tests\Feature;

use App\Livewire\Products\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsFormBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_form_uses_simple_wire_model_bindings(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSeeHtml('wire:model="name"')
            ->assertSeeHtml('wire:model="categoryId"')
            ->assertSeeHtml('wire:model="unit"')
            ->assertSeeHtml('wire:model="sku"')
            ->assertSeeHtml('wire:model="barcode"')
            ->assertSeeHtml('wire:model="cost_price"')
            ->assertSeeHtml('wire:model="sale_price"')
            ->assertSeeHtml('wire:model="currency"')
            ->assertSeeHtml('wire:model="stock_quantity"')
            ->assertSeeHtml('wire:model="min_stock"')
            ->assertSeeHtml('wire:model="reorder_qty"')
            ->assertSeeHtml('wire:model="showArchived"')
            ->assertSeeHtml('wire:model.debounce.300ms="search"')
            ->assertDontSeeHtml('wire:model.live=');
    }
}
