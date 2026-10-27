<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ast_locations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('icon', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'sort_order']);
        });

        Schema::create('ast_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('icon', 50)->nullable();
            $table->string('meter_unit', 20)->nullable();
            $table->boolean('is_vehicle')->default(false);
            $table->string('system_key', 50)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'system_key']);
        });

        Schema::create('ast_assets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained('ast_categories')->cascadeOnDelete();
            $table->foreignUlid('location_id')->nullable()->constrained('ast_locations')->nullOnDelete();
            $table->string('name', 100);
            $table->string('brand', 60)->nullable();
            $table->string('model', 60)->nullable();
            $table->string('serial_number', 80)->nullable();
            $table->string('plate_number', 20)->nullable();
            $table->date('purchase_date')->nullable();
            $table->bigInteger('purchase_price')->nullable();
            $table->string('purchased_from', 100)->nullable();
            $table->ulid('purchase_transaction_id')->nullable();
            $table->string('status', 30)->default('active');
            $table->date('disposed_on')->nullable();
            $table->string('meter_unit', 20)->nullable();
            $table->unsignedInteger('current_meter')->nullable();
            $table->date('meter_updated_on')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'status']);
            $table->index(['household_id', 'category_id']);
            $table->index(['household_id', 'location_id']);
        });

        Schema::create('ast_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('asset_id')->nullable()->constrained('ast_assets')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('title', 100);
            $table->string('number', 80)->nullable();
            $table->string('issuer', 100)->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->text('remind_days')->nullable();
            $table->string('status', 30)->default('active');
            $table->bigInteger('renewal_cost_estimate')->nullable();
            $table->boolean('add_to_finance_plan')->default(false);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'status']);
        });

        Schema::create('ast_vendors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('services')->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('whatsapp', 25)->nullable();
            $table->string('address', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'is_favorite']);
        });

        Schema::create('ast_maintenance_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('asset_id')->constrained('ast_assets')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('trigger', 30);
            $table->smallInteger('interval_value')->nullable();
            $table->string('interval_unit', 20)->nullable();
            $table->unsignedInteger('meter_interval')->nullable();
            $table->date('last_done_on')->nullable();
            $table->unsignedInteger('last_done_meter')->nullable();
            $table->date('next_due_on')->nullable()->index();
            $table->unsignedInteger('next_due_meter')->nullable();
            $table->date('snoozed_until')->nullable();
            $table->bigInteger('estimated_cost')->nullable();
            $table->foreignUlid('vendor_id')->nullable()->constrained('ast_vendors')->nullOnDelete();
            $table->unsignedTinyInteger('remind_days_before')->default(7);
            $table->boolean('add_to_finance_plan')->default(false);
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'is_active']);
            $table->index(['household_id', 'asset_id']);
        });

        Schema::create('ast_issues', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->foreignUlid('asset_id')->nullable()->constrained('ast_assets')->cascadeOnDelete();
            $table->foreignUlid('location_id')->nullable()->constrained('ast_locations')->nullOnDelete();
            $table->string('priority', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->date('scheduled_on')->nullable();
            $table->foreignUlid('vendor_id')->nullable()->constrained('ast_vendors')->nullOnDelete();
            $table->date('resolved_on')->nullable();
            $table->string('resolution_note', 255)->nullable();
            $table->foreignUlid('reported_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'status']);
            $table->index(['household_id', 'priority']);
        });

        Schema::create('ast_service_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('asset_id')->constrained('ast_assets')->cascadeOnDelete();
            $table->foreignUlid('maintenance_plan_id')->nullable()->constrained('ast_maintenance_plans')->nullOnDelete();
            $table->foreignUlid('issue_id')->nullable()->constrained('ast_issues')->nullOnDelete();
            $table->string('type', 30)->default('preventive');
            $table->date('performed_on')->index();
            $table->unsignedInteger('meter_reading')->nullable();
            $table->foreignUlid('vendor_id')->nullable()->constrained('ast_vendors')->nullOnDelete();
            $table->bigInteger('cost')->default(0);
            $table->text('description')->nullable();
            $table->string('parts', 255)->nullable();
            $table->ulid('transaction_id')->nullable();
            $table->foreignUlid('service_warranty_document_id')->nullable()->constrained('ast_documents')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['household_id', 'asset_id']);
        });

        Schema::create('ast_meter_readings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUlid('asset_id')->constrained('ast_assets')->cascadeOnDelete();
            $table->unsignedInteger('raw_reading');
            $table->unsignedInteger('cumulative_offset')->default(0);
            $table->unsignedInteger('reading');
            $table->date('read_on');
            $table->boolean('is_reset')->default(false);
            $table->string('source', 30)->default('manual');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'read_on']);
            $table->index(['household_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ast_meter_readings');
        Schema::dropIfExists('ast_service_records');
        Schema::dropIfExists('ast_issues');
        Schema::dropIfExists('ast_maintenance_plans');
        Schema::dropIfExists('ast_vendors');
        Schema::dropIfExists('ast_documents');
        Schema::dropIfExists('ast_assets');
        Schema::dropIfExists('ast_categories');
        Schema::dropIfExists('ast_locations');
    }
};
