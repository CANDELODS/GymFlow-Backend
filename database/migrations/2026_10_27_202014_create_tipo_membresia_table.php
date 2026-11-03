
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_membresia', function (Blueprint $table) {
            $table->id('id_tipo_membresia');

            $table->string('nombre', 100)
                ->collation('utf8mb4_0900_as_ci')
                ->unique();

            $table->unsignedInteger('duracion_dias');

            $table->decimal('precio', 10, 2);

            $table->boolean('activo')->default(true);

            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        DB::statement("
            ALTER TABLE tipo_membresia
            ADD CONSTRAINT chk_tipo_duracion
            CHECK (duracion_dias > 0)
        ");

        DB::statement("
            ALTER TABLE tipo_membresia
            ADD CONSTRAINT chk_tipo_precio
            CHECK (precio > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_membresia');
    }
};
