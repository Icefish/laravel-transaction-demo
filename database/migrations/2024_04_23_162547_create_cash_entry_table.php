<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashEntryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_entry', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->integer('user_id');
            $table->integer('opcode');
            $table->dateTime('created_at');
            $table->decimal('amount', 16, 4);
            $table->string('memo', 100)->default();
            $table->decimal('balance', 16, 4);
            $table->bigInteger('ref_id')->default(0);

            // Indexes
            $table->primary(['id']);
            $table->index(['user_id','created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_entry');
    }
}
