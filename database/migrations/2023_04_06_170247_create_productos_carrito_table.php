<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('productos_carrito');
        Schema::create('productos_carrito', function (Blueprint $table) {
            $table->bigInteger("referencia_id")->unsigned();
            $table->bigInteger("cliente_id")->unsigned();
            $table->foreign("referencia_id")->references("id")->on("referencias");
            $table->foreign("cliente_id")->references("id")->on("clientes");
            $table->primary(["referencia_id","cliente_id"]);
            $table->smallInteger("unidades")->unsigned()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('productos_carrito');
    }
};
