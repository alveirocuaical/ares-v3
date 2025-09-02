<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use App\Models\System\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigurationController extends Controller
{
    public function record()
    {
        try {
            $configuration = Configuration::first();
            
            return response()->json([
                'success' => true,
                'data' => $configuration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'login_bg_color' => 'required|string|max:50',
                'login_bg_image_name' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $configuration = Configuration::first();
            
            if (!$configuration) {
                $configuration = new Configuration();
            }

            $configuration->login_bg_color = $request->login_bg_color;

            // Solo actualizar la imagen si se envió un nombre
            if ($request->has('login_bg_image_name') && $request->login_bg_image_name) {
                $configuration->login_bg_image = $request->login_bg_image_name;
            } elseif ($request->has('login_bg_image_name') && !$request->login_bg_image_name) {
                // Si se envía vacío, eliminar la imagen actual
                if ($configuration->login_bg_image) {
                    $uploadPath = public_path('storage/uploads/system');
                    if (file_exists($uploadPath . '/' . $configuration->login_bg_image)) {
                        unlink($uploadPath . '/' . $configuration->login_bg_image);
                    }
                }
                $configuration->login_bg_image = null;
            }

            $configuration->save();

            return response()->json([
                'success' => true,
                'message' => 'Configuración guardada correctamente',
                'data' => $configuration
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadImage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no válido',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('file')) {
                $image = $request->file('file');
                $imageName = time() . '_login_bg.' . $image->getClientOriginalExtension();
                
                // Crear directorio si no existe
                $uploadPath = public_path('storage/uploads/system');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                // Mover la imagen
                $image->move($uploadPath, $imageName);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Imagen subida correctamente',
                    'filename' => $imageName
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se recibió ningún archivo'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen: ' . $e->getMessage()
            ], 500);
        }
    }
}
