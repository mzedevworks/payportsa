<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTempEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_employee', function (Blueprint $table) {
            $table->increments('id');
            $table->string('employee_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('address');
            $table->string('contact_number');
            $table->string('id_no');
            $table->string('salary');
            $table->string('registration_no');
            $table->integer('bank_name');
            $table->integer('account_type');
            $table->string('account_number');
            $table->string('reference');
            $table->integer('added_by')->unsigned();
            $table->integer('firm_id')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_employee');
    }
}
