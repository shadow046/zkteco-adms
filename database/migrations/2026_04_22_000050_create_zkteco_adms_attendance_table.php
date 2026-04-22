<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inout_raw')) {
            return;
        }

        Schema::create('inout_raw', function (Blueprint $table): void {
            $table->string('empno', 15)->index();
            $table->date('txndate')->index();
            $table->time('txntime')->index();
            $table->string('entity03', 10)->default('');
            $table->string('serialno', 20)->default('')->index();
            $table->integer('seqno')->default(0);
            $table->string('punch', 5)->default('');
            $table->string('status', 5)->default('');
            $table->string('stamp')->nullable();
            $table->text('raw_line')->nullable();
            $table->string('client_ip')->nullable();
            $table->timestamps();

            $table->unique(['empno', 'txndate', 'txntime'], 'inout_raw_unique_empno_txn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inout_raw');
    }
};
