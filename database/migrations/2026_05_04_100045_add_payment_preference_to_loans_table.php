<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Can be 'half_month' or 'whole_month'. Defaults to half for existing loans.
            $table->enum('payment_preference', ['half_month', 'whole_month'])->default('half_month')->after('no_of_months');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('payment_preference');
        });
    }
};