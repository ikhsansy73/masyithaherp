<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master kelompok aset (doc 02 §7). asset_account_id memetakan
        // ke 1-2100…1-2500; is_depreciable = false untuk tanah/buku.
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('PERL, MBL, BUKU, GED, TNH, ELK');
            $table->string('name');
            $table->unsignedSmallInteger('useful_life_months')->nullable()->comment('Default masa manfaat (bulan)');
            $table->foreignId('asset_account_id')->constrained('accounts')->restrictOnDelete()->comment('1-2100…1-2500');
            $table->boolean('is_depreciable')->default(true)->comment('Tanah = false');
            $table->timestamps();
        });

        // Satu baris = satu unit fisik (doc 07 §1) — quantity hidup di
        // dunia nyata, bukan di database, agar opname dan penghapusan jujur.
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Label inventaris: INV-Aset/2026/000007');
            $table->string('name');
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->date('acquisition_date');
            $table->unsignedBigInteger('acquisition_cost');
            $table->foreignId('fund_id')->constrained('funds')->restrictOnDelete()->comment('Dimensi akuntansi');
            $table->string('funding_source')->comment('Enum: FundingSource (bos/yayasan/komite/hibah)');
            $table->string('brand_model')->nullable();
            $table->string('serial_no')->nullable();
            $table->string('condition')->comment('Enum: AssetCondition (baik/rusak_ringan/rusak_berat)');
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('custodian_id')->nullable()->constrained('employees')->nullOnDelete()->comment('Penanggung jawab');
            $table->string('status')->comment('Enum: AssetStatus (aktif/dijual/dihapuskan/hilang)');
            $table->unsignedSmallInteger('useful_life_months')->nullable()->comment('Override default kelompok');
            $table->unsignedBigInteger('salvage_value')->default(0);
            $table->date('disposal_date')->nullable();
            $table->string('disposal_method')->nullable()->comment('Enum: DisposalMethod (dijual/dihapuskan/hilang)');
            $table->unsignedBigInteger('disposal_proceeds')->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('JE #12 akuisisi');
            $table->foreignId('disposal_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('JE #14/#15 penghapusan');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('location_id');
        });

        // Penyusutan bulanan per aset. unique(asset, period) membuat run
        // idempotent (doc 07 §2).
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('accumulated_amount')->comment('Total akumulasi setelah run ini');
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete()->comment('JE run (dibagi semua baris)');
            $table->timestamps();

            $table->unique(['asset_id', 'accounting_period_id'], 'asset_depreciations_asset_period_unique');
        });

        // Perawatan/perbaikan — biaya dibebankan ke 5-1800, tidak
        // dikapitalisasi (doc 07 §4).
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->string('type')->comment('Enum: MaintenanceType (perawatan/perbaikan)');
            $table->string('description');
            $table->unsignedBigInteger('cost')->default(0)->comment('0 = catatan tanpa jurnal');
            $table->string('vendor')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('JE beban 5-1800');
            $table->timestamps();

            $table->index('asset_id', 'asset_maintenances_asset_index');
        });

        Schema::create('asset_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('"Opname Semester 1 2026"');
            $table->date('opname_date');
            $table->foreignId('conducted_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_opname_id')->constrained('asset_opnames')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->boolean('found');
            $table->string('condition')->nullable()->comment('Konfirmasi ulang; Enum: AssetCondition');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['asset_opname_id', 'asset_id'], 'asset_opname_items_pair_unique');
        });

        // Barang habis pakai (ATK) — weighted-average costing (doc 07 §6).
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->comment('"Kertas HVS A4"');
            $table->string('unit')->comment('Enum: InventoryUnit (pcs/rim/box/lusin)');
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0)->comment('Ambang widget stok menipis');
            $table->unsignedBigInteger('avg_cost')->default(0)->comment('Harga rata-rata tertimbang per unit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->date('movement_date');
            $table->string('type')->comment('Enum: StockMovementType (masuk/keluar)');
            $table->decimal('quantity', 10, 2);
            $table->unsignedBigInteger('unit_cost')->comment('Biaya per unit saat pergerakan');
            $table->unsignedBigInteger('total_cost');
            $table->string('purpose')->nullable()->comment('Tujuan/peruntukan untuk keluar');
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('JE #7 masuk / #8 keluar');
            $table->timestamps();

            $table->index(['inventory_item_id', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('asset_opname_items');
        Schema::dropIfExists('asset_opnames');
        Schema::dropIfExists('asset_maintenances');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
