<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CraeteEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('address');
            $table->string('contact_number');
            $table->string('salary');
            $table->string('employee_number');
            $table->string('id_no');
            $table->string('registration_no');
            $table->integer('bank_id')->unsigned();
            $table->integer('firm_id')->unsigned();
            $table->integer('deleted_by')->unsigned()->default(0);
            $table->integer('account_type');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('reference');
            $table->integer('status')->default(0);
            $table->integer('is_deleted')->defalut(0);
            $table->datetime('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('bank_id')->references('id')->on('bank_details')->onDelete('cascade');
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
