<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFirmDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('firms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tranding_as');
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('subrub');
            $table->string('city');
            $table->string('province');
            $table->string('vat_no');
            $table->string('registration_no');
            $table->integer('bank_id')->unsigned();
            $table->integer('account_type');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->foreign('bank_id')->references('id')->on('bank_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('firm_details');
    }
}
