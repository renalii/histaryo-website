<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_settings', function (Blueprint $table): void {
            $table->string('uid')->primary();
            $table->string('role', 32);
            $table->boolean('email_enabled')->default(true);
            $table->text('totp_secret')->nullable();
            $table->text('recovery_code_hashes')->nullable();
            $table->timestamp('totp_enabled_at')->nullable();
            $table->unsignedBigInteger('last_totp_counter')->nullable();
            $table->timestamp('last_recovery_regenerated_at')->nullable();
            $table->timestamps();
            $table->index(['role', 'email_enabled']);
        });

        Schema::create('mfa_challenges', function (Blueprint $table): void {
            $table->id();
            $table->string('uid')->index();
            $table->string('role', 32);
            $table->string('session_hash', 64)->index();
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['uid', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_challenges');
        Schema::dropIfExists('mfa_settings');
    }
};
