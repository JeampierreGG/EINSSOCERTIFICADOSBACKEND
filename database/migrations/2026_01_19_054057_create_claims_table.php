<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique()->comment('Código único de reclamo generado');
            
            // 1. Identificación del Consumidor
            $table->enum('tipo_documento', ['DNI', 'CE', 'Pasaporte']);
            $table->string('numero_documento');
            $table->string('nombres');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable(); // Opcional si el usuario solo pone uno en el form, aunque pedimos apellidos completos
            $table->text('domicilio');
            $table->string('telefono');
            $table->string('email');
            $table->string('padre_nombres')->nullable()->comment('Para menores de edad');

            // 2. Identificación del Bien Contratado
            $table->enum('tipo_bien', ['producto', 'servicio']);
            $table->decimal('monto_reclamado', 10, 2)->nullable();
            $table->text('descripcion_bien');

            // 3. Detalle de la Reclamación
            $table->enum('tipo_reclamacion', ['reclamo', 'queja']);
            $table->text('detalle');
            $table->text('pedido');

            // Metadata interna
            $table->enum('status', ['pendiente', 'en_proceso', 'atendido', 'rechazado'])->default('pendiente');
            $table->text('respuesta_admin')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            
            $table->boolean('acepto_terminos')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
