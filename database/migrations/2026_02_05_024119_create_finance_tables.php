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
            
            $table->string('control_number')->unique(); 
            $table->date('date_of_application'); 
            
            $table->decimal('amount_granted', 15, 2);
            $table->decimal('service_fee', 15, 2)->default(0);
            $table->decimal('interest_rate', 15, 2)->default(0); 
            $table->decimal('surcharge', 15, 2)->default(0);
            $table->decimal('net_proceeds', 15, 2);
            
            $table->date('payment_start');
            $table->date('payment_end');
            
            $table->integer('no_of_months'); 
            $table->integer('actual_months')->nullable(); 
            
            $table->timestamps();
        });

        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete(); 
            
            $table->date('period_start');   
            $table->date('period_end');     
            
            $table->decimal('principal_due', 15, 2);
            $table->decimal('interest_due', 15, 2);
            $table->decimal('total_due', 15, 2);
            $table->decimal('balance_after', 15, 2);
            
            $table->boolean('is_paid')->default(false); 
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('interest', 15, 2)->default(0);
            $table->string('or_number')->nullable();
            $table->date('payment_date');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('loan_schedules'); 
        Schema::dropIfExists('loans');
        Schema::dropIfExists('borrowers');
        Schema::dropIfExists('offices');
    }
};