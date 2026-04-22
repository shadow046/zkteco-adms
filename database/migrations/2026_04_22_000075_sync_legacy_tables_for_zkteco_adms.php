<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureInoutRawColumns();
        $this->ensureDtrColumns();
    }

    public function down(): void
    {
        // Intentionally left empty. This migration adapts existing legacy tables
        // for ADMS usage and should not remove live production columns on rollback.
    }

    private function ensureInoutRawColumns(): void
    {
        if (! Schema::hasTable('inout_raw')) {
            return;
        }

        Schema::table('inout_raw', function (Blueprint $table): void {
            if (! Schema::hasColumn('inout_raw', 'punch')) {
                $table->string('punch', 5)->default('')->after('seqno');
            }

            if (! Schema::hasColumn('inout_raw', 'stamp')) {
                $table->string('stamp')->nullable()->after('status');
            }

            if (! Schema::hasColumn('inout_raw', 'raw_line')) {
                $table->text('raw_line')->nullable()->after('stamp');
            }

            if (! Schema::hasColumn('inout_raw', 'client_ip')) {
                $table->string('client_ip')->nullable()->after('raw_line');
            }

            if (! Schema::hasColumn('inout_raw', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('client_ip');
            }

            if (! Schema::hasColumn('inout_raw', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    private function ensureDtrColumns(): void
    {
        if (! Schema::hasTable('dtr')) {
            return;
        }

        Schema::table('dtr', function (Blueprint $table): void {
            if (! Schema::hasColumn('dtr', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('dtr', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }
};
