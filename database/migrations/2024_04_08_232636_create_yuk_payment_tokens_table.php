<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYukPaymentTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yuk_payment_tokens', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("token");
            $table->string("expires_in");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('yuk_payment_tokens');
    }
}
