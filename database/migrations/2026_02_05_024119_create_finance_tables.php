<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('borrowers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('office_id')->constrained();
            $table->string('co_maker')->nullable();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')->constrained();
            $table->string('type');
            
            // NEW FIELDS
            $table->string('control_number')->unique(); // Stores YY-MM-001
            $table->date('date_of_application'); // Previously date_of_loan
            
            $table->decimal('amount_granted', 15, 2);
            $table->decimal('service_fee', 15, 2)->default(0);
            $table->decimal('interest_rate', 15, 2)->default(0); // "Interest" field in form
            $table->decimal('surcharge', 15, 2)->default(0);
            $table->decimal('net_proceeds', 15, 2);
            
            $table->date('payment_start');
            $table->date('payment_end');
            $table->integer('no_of_months'); // Auto-generated
            
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained();
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('interest', 15, 2)->default(0);
            $table->string('or_number')->nullable();
            $table->date('payment_date');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('borrowers');
        Schema::dropIfExists('offices');
    }
};