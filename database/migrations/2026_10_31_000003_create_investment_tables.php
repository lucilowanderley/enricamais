<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('ticker', 30)->nullable();
            $table->string('asset_class', 30); // ver App\Enums\AssetClass
            $table->string('currency', 3)->default('BRL');
            $table->string('institution')->nullable();
            $table->string('rate_description')->nullable(); // ex.: 110% CDI, IPCA + 6%
            $table->date('maturity_date')->nullable();
            $table->decimal('current_price', 20, 8)->nullable();
            $table->timestamp('price_updated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investment_asset_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // buy | sell | income
            $table->date('date');
            $table->decimal('quantity', 24, 8)->default(0);
            $table->decimal('unit_price', 20, 8)->default(0);
            $table->decimal('fees', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0); // valor total (compra/venda) ou provento recebido
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['investment_asset_id', 'date']);
        });

        Schema::create('investor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('risk_profile', 20)->default('moderate'); // conservative | moderate | aggressive
            $table->unsignedSmallInteger('horizon_years')->default(5);
            $table->decimal('monthly_contribution', 15, 2)->default(0);
            $table->unsignedTinyInteger('emergency_fund_months')->default(6);
            $table->text('objectives')->nullable();
            $table->json('target_allocation')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10); // user | assistant
            $table->longText('content');
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('investor_profiles');
        Schema::dropIfExists('investment_transactions');
        Schema::dropIfExists('investment_assets');
    }
};
