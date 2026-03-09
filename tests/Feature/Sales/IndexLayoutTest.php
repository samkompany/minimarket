<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_is_shown_before_summary_in_sales_sidebar(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('sales.index'));

        $response->assertOk();

        $content = $response->getContent();
        $cartPos = strpos($content, '<h3 class="app-card-title">Panier</h3>');
        $summaryPos = strpos($content, '<h3 class="app-card-title">Resume</h3>');

        $this->assertIsInt($cartPos);
        $this->assertIsInt($summaryPos);
        $this->assertTrue($cartPos < $summaryPos);
        $this->assertStringContainsString('$refs.productSearch?.focus()', $content);
        $this->assertStringContainsString('article(s)', $content);
        $this->assertStringContainsString('sales-mini-total', $content);
        $this->assertStringContainsString('Entrer : Ajouter au panier', $content);
    }
}
