<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->default(0)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('min_stock', 10, 3)->default(0)->change();
            $table->decimal('reorder_qty', 10, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('min_stock')->default(0)->change();
            $table->integer('reorder_qty')->default(0)->change();
        });
    }
};
