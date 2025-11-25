<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateNoteConceptsNullableAndInsert extends Migration
{
    public function up()
    {
        $fk_exists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'co_note_concepts' 
            AND CONSTRAINT_NAME = 'co_note_concepts_type_document_id_foreign'
        ");
        if ($fk_exists) {
            DB::statement('ALTER TABLE co_note_concepts DROP FOREIGN KEY co_note_concepts_type_document_id_foreign');
        }

        Schema::table('co_note_concepts', function (Blueprint $table) {
            $table->unsignedInteger('type_document_id')->nullable()->default(null)->change();
        });

        Schema::table('co_note_concepts', function (Blueprint $table) {
            $table->foreign('type_document_id')
                ->references('id')->on('co_type_documents')
                ->onDelete('set null');
        });

        DB::table('co_note_concepts')->insert([
            ['type_document_id' => null, 'name' => 'Devolución parcial de los bienes y/o no aceptación parcial del servicio', 'code' => 1],
            ['type_document_id' => null, 'name' => 'Anulación del documento soporte en adquisiciones efectuadas a sujetos no obligados a expedir factura de venta o documento equivalente', 'code' => 2],
            ['type_document_id' => null, 'name' => 'Rebaja o descuento parcial o total', 'code' => 3],
            ['type_document_id' => null, 'name' => 'Ajuste de precio', 'code' => 4],
            ['type_document_id' => null, 'name' => 'Otros', 'code' => 5],
        ]);
    }

    public function down()
    {
        DB::table('co_note_concepts')
            ->whereNull('type_document_id')
            ->whereIn('code', [1,2,3,4,5])
            ->delete();

        $fk_exists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'co_note_concepts' 
            AND CONSTRAINT_NAME = 'co_note_concepts_type_document_id_foreign'
        ");
        if ($fk_exists) {
            DB::statement('ALTER TABLE co_note_concepts DROP FOREIGN KEY co_note_concepts_type_document_id_foreign');
        }

        Schema::table('co_note_concepts', function (Blueprint $table) {
            $table->unsignedInteger('type_document_id')->nullable(false)->default(1)->change();
        });

        Schema::table('co_note_concepts', function (Blueprint $table) {
            $table->foreign('type_document_id')
                ->references('id')->on('co_type_documents')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });
    }
}