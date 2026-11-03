
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_modificacion', function (Blueprint $table) {
            $table->id('id_detalle');

            $table->unsignedBigInteger('id_modificacion');

            $table->string('campo_modificado', 100);

            $table->string('valor_anterior', 255);
            $table->string('valor_nuevo', 255);

            $table->foreign('id_modificacion')
                ->references('id_modificacion')
                ->on('registro_modificaciones')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unique(
                ['id_modificacion', 'campo_modificado'],
                'uq_detalle_modificacion_campo'
            );
        });

        DB::statement("
            ALTER TABLE detalle_modificacion
            ADD CONSTRAINT chk_detalle_valores
            CHECK (valor_anterior <> valor_nuevo)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_modificacion');
    }
};
