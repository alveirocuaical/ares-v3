<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BackfillDefaultSupportIntroductionOnUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $intro = <<<'HTML'
            <p><strong>¿Cómo podemos ayudarte?</strong></p>
            <p>Nuestro equipo de soporte técnico está listo para resolver todas tus dudas sobre facturación electrónica, configuración del sistema y más.</p>
            <p><strong>⏰Horarios de Atención</strong></p>
            <p><strong>Lunes a Viernes:</strong> 9:00 AM - 6:00 PM</p>
            <p><strong>Sábados:</strong> 9:00 AM - 1:00 PM</p>
            HTML;

        DB::table('users')
            ->whereNull('introduction')
            ->orWhere('introduction', '')
            ->update(['introduction' => $intro]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $intro = <<<'HTML'
            <p><strong>¿Cómo podemos ayudarte?</strong></p>
            <p>Nuestro equipo de soporte técnico está listo para resolver todas tus dudas sobre facturación electrónica, configuración del sistema y más.</p>
            <p><strong>⏰Horarios de Atención</strong></p>
            <p><strong>Lunes a Viernes:</strong> 9:00 AM - 6:00 PM</p>
            <p><strong>Sábados:</strong> 9:00 AM - 1:00 PM</p>
            HTML;

        // Solo limpia a null los que tengan exactamente el texto por defecto
        DB::table('users')
            ->where('introduction', $intro)
            ->update(['introduction' => null]);
    }
}
