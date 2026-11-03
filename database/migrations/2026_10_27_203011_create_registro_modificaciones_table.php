
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_modificaciones', function (Blueprint $table) {
            $table->id('id_modificacion');

            $table->unsignedBigInteger('id_membresia_adquirida');
            $table->unsignedBigInteger('id_usuario');

            $table->dateTime('fecha_hora');

            $table->string('motivo', 255);

            $table->foreign('id_membresia_adquirida')
                ->references('id_membresia_adquirida')
                ->on('membresia_adquirida')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuario_administrativo')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index(
                ['id_membresia_adquirida', 'fecha_hora'],
                'idx_modificacion_membresia_fecha'
            );

            $table->index('id_usuario', 'idx_modificacion_usuario');
        });

        // El motivo debe contener al menos un carácter distinto de espacios.
        DB::statement("
            ALTER TABLE registro_modificaciones
            ADD CONSTRAINT chk_modificacion_motivo
            CHECK (CHAR_LENGTH(TRIM(motivo)) > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_modificaciones');
    }
};
