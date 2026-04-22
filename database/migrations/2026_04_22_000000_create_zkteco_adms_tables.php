<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('operation_logs')) {
            Schema::create('operation_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('serial_number')->index();
                $table->string('stamp')->nullable();
                $table->string('operation');
                $table->string('operator_id')->nullable();
                $table->dateTime('occurred_at')->nullable()->index();
                $table->string('param_1')->nullable();
                $table->string('param_2')->nullable();
                $table->string('param_3')->nullable();
                $table->string('param_4')->nullable();
                $table->text('raw_line')->nullable();
                $table->string('client_ip')->nullable();
                $table->timestamps();
                $table->unique(['serial_number', 'operation', 'operator_id', 'occurred_at'], 'operation_logs_unique_event');
            });
        }

        if (! Schema::hasTable('attendance_photos')) {
            Schema::create('attendance_photos', function (Blueprint $table): void {
                $table->id();
                $table->string('serial_number')->index();
                $table->string('stamp')->nullable();
                $table->string('pin')->nullable();
                $table->string('filename');
                $table->string('command')->nullable();
                $table->integer('declared_size')->nullable();
                $table->dateTime('captured_at')->nullable()->index();
                $table->string('storage_path');
                $table->string('sha256', 64);
                $table->string('client_ip')->nullable();
                $table->timestamps();
                $table->unique(['serial_number', 'filename'], 'attendance_photos_unique_file');
            });
        }

        if (! Schema::hasTable('adms_device_polls')) {
            Schema::create('adms_device_polls', function (Blueprint $table): void {
                $table->id();
                $table->string('serial_number')->index();
                $table->text('device_info')->nullable();
                $table->text('query_string')->nullable();
                $table->string('client_ip')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('device_commands')) {
            Schema::create('device_commands', function (Blueprint $table): void {
                $table->id();
                $table->string('serial_number')->index();
                $table->string('command_name')->index();
                $table->text('command_text');
                $table->string('status')->default('pending')->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('last_polled_at')->nullable();
                $table->string('client_ip')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('adms_http_logs')) {
            Schema::create('adms_http_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('endpoint')->index();
                $table->string('method', 10);
                $table->string('serial_number')->nullable()->index();
                $table->string('table_name')->nullable()->index();
                $table->text('query_string')->nullable();
                $table->string('content_type')->nullable();
                $table->longText('body_preview')->nullable();
                $table->integer('body_size')->default(0);
                $table->string('client_ip')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('adms_userinfo')) {
            Schema::create('adms_userinfo', function (Blueprint $table): void {
                $table->id();
                $table->string('serial_number')->index();
                $table->string('pin')->index();
                $table->string('name')->nullable();
                $table->string('privilege')->nullable();
                $table->string('password')->nullable();
                $table->string('card')->nullable();
                $table->string('grp')->nullable();
                $table->string('tz')->nullable();
                $table->text('raw_line')->nullable();
                $table->string('client_ip')->nullable();
                $table->timestamps();
                $table->unique(['serial_number', 'pin'], 'adms_userinfo_unique_device_pin');
            });
        }

        if (! Schema::hasTable('adms_fingertmp')) {
            Schema::create('adms_fingertmp', function (Blueprint $table): void {
                $table->id();
                $table->string('serial_number')->index();
                $table->string('pin')->index();
                $table->string('fid', 10)->default('');
                $table->integer('size')->nullable();
                $table->string('valid', 10)->nullable();
                $table->longText('template')->nullable();
                $table->text('raw_line')->nullable();
                $table->string('client_ip')->nullable();
                $table->timestamps();
                $table->unique(['serial_number', 'pin', 'fid'], 'adms_fingertmp_unique_device_pin_fid');
            });
        }

        if (! Schema::hasTable('adms_device_state')) {
            Schema::create('adms_device_state', function (Blueprint $table): void {
                $table->id();
                $table->string('serial_number')->unique();
                $table->string('options')->default('');
                $table->string('pushver')->default('');
                $table->string('language')->default('');
                $table->string('attlogstamp')->default('0');
                $table->string('attlogdate')->default('');
                $table->string('oplogstamp')->default('0');
                $table->string('oplogdate')->default('');
                $table->string('attphotostamp')->default('0');
                $table->string('attphotodate')->default('');
                $table->string('sysdate')->default('');
                $table->integer('seqno')->default(0);
                $table->string('lasttxndatetime')->default('');
                $table->string('txndate')->default('');
                $table->string('brchcode')->default('xxx');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adms_device_state');
        Schema::dropIfExists('adms_fingertmp');
        Schema::dropIfExists('adms_userinfo');
        Schema::dropIfExists('adms_http_logs');
        Schema::dropIfExists('device_commands');
        Schema::dropIfExists('adms_device_polls');
        Schema::dropIfExists('attendance_photos');
        Schema::dropIfExists('operation_logs');
    }
};
