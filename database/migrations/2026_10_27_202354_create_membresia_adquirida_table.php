
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membresia_adquirida', function (Blueprint $table) {
            $table->id('id_membresia_adquirida');

            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_tipo_membresia');
            $table->unsignedBigInteger('id_usuario_registro');

            $table->string('nombre_tipo_snapshot', 100);
            $table->unsignedInteger('duracion_dias_snapshot');

            $table->date('fecha_inicio');
            $table->dateTime('fecha_vencimiento');

            $table->decimal('valor_pagado', 10, 2);

            $table->enum('metodo_pago', [
                'efectivo',
                'nequi',
                'bancolombia',
                'otro',
            ]);

            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            // Claves foráneas: conservar los registros históricos.
            $table->foreign('id_cliente')
                ->references('id_cliente')
                ->on('cliente')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('id_tipo_membresia')
                ->references('id_tipo_membresia')
                ->on('tipo_membresia')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('id_usuario_registro')
                ->references('id_usuario')
                ->on('usuario_administrativo')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            // Índices para las consultas habituales.
            $table->index([
                'id_cliente',
                'created_at',
                'id_membresia_adquirida',
            ], 'idx_membresia_cliente_registro');

            $table->index(
                ['id_cliente', 'fecha_inicio'],
                'idx_membresia_cliente_inicio'
            );

            $table->index('fecha_vencimiento', 'idx_membresia_vencimiento');
            $table->index('created_at', 'idx_membresia_registro');
            $table->index('id_tipo_membresia', 'idx_membresia_tipo');
            $table->index('id_usuario_registro', 'idx_membresia_usuario');
        });

        DB::statement("
            ALTER TABLE membresia_adquirida
            ADD CONSTRAINT chk_membresia_duracion_snapshot
            CHECK (duracion_dias_snapshot > 0)
        ");

        DB::statement("
            ALTER TABLE membresia_adquirida
            ADD CONSTRAINT chk_membresia_valor_pagado
            CHECK (valor_pagado > 0)
        ");

        DB::statement("
            ALTER TABLE membresia_adquirida
            ADD CONSTRAINT chk_membresia_vencimiento
            CHECK (
                fecha_vencimiento =
                TIMESTAMP(
                    DATE_ADD(
                        fecha_inicio,
                        INTERVAL (duracion_dias_snapshot - 1) DAY
                    ),
                    '23:59:59'
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('membresia_adquirida');
    }
};
