
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente', function (Blueprint $table) {
            $table->id('id_cliente');

            $table->string('nombres', 100);
            $table->string('apellidos', 100);

            $table->string('documento', 30)
                ->collation('utf8mb4_0900_as_ci')
                ->unique();

            $table->string('telefono', 20);

            $table->boolean('activo')->default(true);

            //autorizacion_tratamiento indica si el cliente autorizó el tratamiento de sus datos
            $table->boolean('autorizacion_tratamiento');
            //fecha_autorizacion pueder ser NULL cuando no hay autorización
            $table->dateTime('fecha_autorizacion')->nullable();

            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        DB::statement("
            ALTER TABLE cliente
            ADD CONSTRAINT chk_cliente_autorizacion_fecha
            CHECK (
            (autorizacion_tratamiento = TRUE AND fecha_autorizacion IS NOT NULL)
            OR
            (autorizacion_tratamiento = FALSE AND fecha_autorizacion IS NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente');
    }
};
