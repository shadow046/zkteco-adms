<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dtr')) {
            return;
        }

        Schema::create('dtr', function (Blueprint $table): void {
            $table->string('empno', 15)->index();
            $table->date('txndate')->index();
            $table->string('shift', 10)->default('')->index();
            $table->string('shift_rest', 10)->default('');
            $table->dateTime('in')->nullable();
            $table->char('in_manual', 1)->default('');
            $table->dateTime('in_manual_date')->nullable();
            $table->dateTime('snack_break1_out')->nullable();
            $table->dateTime('snack_break1_in')->nullable();
            $table->dateTime('break_out')->nullable();
            $table->char('break_out_manual', 1)->default('');
            $table->dateTime('break_out_manual_date')->nullable();
            $table->dateTime('break_in')->nullable();
            $table->char('break_in_manual', 1)->nullable()->default('');
            $table->dateTime('break_in_manual_date')->nullable();
            $table->dateTime('snack_break2_out')->nullable();
            $table->dateTime('snack_break2_in')->nullable();
            $table->dateTime('night_out')->nullable();
            $table->dateTime('night_in')->nullable();
            $table->dateTime('out')->nullable();
            $table->char('out_manual', 1)->default('');
            $table->dateTime('out_manual_date')->nullable();
            $table->char('nextday_out', 1)->nullable()->default('N');
            $table->time('no_hrs')->nullable();
            $table->time('hrs_work')->nullable();
            $table->time('excess_hrs_am')->nullable();
            $table->time('excess_hrs_pm')->nullable();
            $table->time('am_late')->nullable();
            $table->time('pm_late')->nullable();
            $table->time('am_undertime')->nullable();
            $table->time('pm_undertime')->nullable();
            $table->string('lv_code', 10)->default('');
            $table->string('otreg8_code', 10)->default('REGOT8');
            $table->time('otreg')->nullable();
            $table->string('otregx_code', 10)->default('REGOTX');
            $table->time('otreg_excess')->nullable();
            $table->string('otrest8_code', 10)->default('RSTOT8');
            $table->time('otrest')->nullable();
            $table->string('otrestx_code', 10)->default('RSTOTX');
            $table->time('otrest_excess')->nullable();
            $table->string('othol8_code', 10)->default('LEGOT8');
            $table->time('othol')->nullable();
            $table->string('otholx_code', 10)->default('LEGOTX');
            $table->time('othol_excess')->nullable();
            $table->string('otholrest8_code', 10)->nullable()->default('LHROT8');
            $table->time('otholrest')->nullable();
            $table->string('otholrestx_code', 10)->nullable()->default('LHROTX');
            $table->time('otholrest_excess')->nullable();
            $table->string('otspl8_code', 10)->default('SPLOT8');
            $table->time('otspl')->nullable();
            $table->string('otsplx_code', 10)->default('SPLOTX');
            $table->time('otspl_excess')->nullable();
            $table->string('otsplrest8_code', 10)->default('SPROT8');
            $table->time('otsplrest')->nullable();
            $table->string('otsplrestx_code', 10)->default('SPROTX');
            $table->time('otsplrest_excess')->nullable();
            $table->string('otdbl8_code', 10)->default('DBLOT8');
            $table->time('otdbl')->nullable();
            $table->string('otdblx_code', 10)->default('DBLOTX');
            $table->time('otdbl_excess')->nullable();
            $table->string('otdblrest8_code', 10)->default('DBROT8');
            $table->time('otdblrest')->nullable();
            $table->string('otdblrestx_code', 10)->default('DBROTX');
            $table->time('otdblrest_excess')->nullable();
            $table->string('ndiff_code', 10)->default('');
            $table->time('ndiff')->nullable();
            $table->string('ndiff1_code', 10)->default('');
            $table->time('ndiff1')->nullable();
            $table->string('ndiff2_code', 10)->default('');
            $table->time('ndiff2')->nullable();
            $table->string('otndiff_code', 10)->default('');
            $table->time('otndiff')->nullable();
            $table->string('otndiff1_code', 255)->default('');
            $table->time('otndiff1')->nullable();
            $table->string('otndiff2_code', 255)->default('');
            $table->time('otndiff2')->nullable();
            $table->time('apprv_ot_hrs')->nullable();
            $table->char('absent', 1)->default('');
            $table->string('absent_code', 10)->default('');
            $table->string('excused_code', 10)->default('');
            $table->char('holiday', 1)->default('');
            $table->string('remarks', 100)->default('');
            $table->string('txn_remarks', 100)->default('');
            $table->string('txn_remarks1', 100)->default('');
            $table->string('txfer_type', 10)->default('');
            $table->string('txfer_code', 10)->default('');
            $table->timestamps();
            $table->unique(['empno', 'txndate'], 'dtr_unique_empno_txndate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dtr');
    }
};
