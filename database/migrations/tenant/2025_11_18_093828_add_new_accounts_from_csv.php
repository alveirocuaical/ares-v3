<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\ChartOfAccount;

class AddNewAccountsFromCsv extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->syncAccountsFromCSV(public_path('csv/new_accounts_ProductionCost.csv'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar las cuentas creadas en esta migración
        $codes = [
            '7205', '720503', '720506', '720512', '720515', '720518', '720521', '720524', '720527',
            '720530', '720533', '720536', '720539', '720542', '720545', '720548', '720551', '720554',
            '720560', '720563', '720566', '720568', '720569', '720570', '720572', '720575', '720578', '720584'
        ];

        ChartOfAccount::whereIn('code', $codes)->delete();
    }

    private function syncAccountsFromCSV($file)
    {
        // Validar si el archivo existe
        if (!file_exists($file)) {
            throw new Exception("El archivo $file no fue encontrado.");
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, 1000, ',');
        $header = array_map('trim', $header);

        // Validar si el archivo tiene el formato correcto
        if (!$header || count($header) < 5) {
            throw new Exception("El archivo CSV no tiene el formato esperado. Asegúrate de incluir las columnas: code, name, type, level, parent_code.");
        }

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $row = array_map('trim', $row);

            // Validar si la fila tiene el número correcto de columnas
            if (count($row) !== count($header)) {
                continue;
            }

            // Validar si el código y el nombre están presentes
            if (empty($row[0]) || empty($row[1])) {
                continue;
            }

            // Validar si el tipo y el nivel son válidos
            if (empty($row[2]) || !is_numeric($row[3])) {
                continue;
            }

            // Validar si el código padre existe en la base de datos
            $parentId = null;
            if (!empty($row[4])) {
                $parentId = ChartOfAccount::where('code', $row[4])->value('id');
                if (!$parentId) {
                    throw new Exception("El código padre {$row[4]} no existe en la base de datos. Verifica el archivo CSV.");
                }
            }

            // Verificar si la cuenta ya existe
            $exists = ChartOfAccount::where('code', $row[0])->exists();
            if ($exists) {
                continue;
            }

            // Preparar los datos para crear la cuenta
            $data = [
                'code' => $row[0],
                'name' => $row[1],
                'type' => $row[2],
                'level' => $row[3],
                'parent_id' => $parentId,
                'status' => true,
            ];

            // Crear la cuenta contable
            ChartOfAccount::create($data);
        }

        fclose($handle);
    }
}
