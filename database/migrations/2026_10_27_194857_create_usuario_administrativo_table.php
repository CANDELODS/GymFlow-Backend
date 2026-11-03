
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_administrativo', function (Blueprint $table) {
            //id Crea una clave primaria autoincremental BIGINT UNSIGNED
            $table->id('id_usuario');
            //usuario distingue acentos pero no distingue mayúsculas de minúsculas
            $table->string('usuario', 100)->collation('utf8mb4_0900_as_ci')->unique();
            $table->string('password', 255);

            $table->enum('rol', ['dueno', 'empleado']);

            $table->boolean('activo')->default(true);
            //intentos_fallidos debe ser mayor o igual que 0
            $table->unsignedInteger('intentos_fallidos')->default(0);
            $table->dateTime('bloqueado_hasta')->nullable();

            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_administrativo');
    }
};
