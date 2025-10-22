<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Todos los Terceros</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background: #e6f2ff; }
        .header { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Todos los Terceros</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @forelse($thirds as $third)
                <tr>
                    <td>{{ $third['tipo'] }}</td>
                    <td>{{ $third['nombre'] }}</td>
                    <td>{{ $third['documento'] }}</td>
                    <td>{{ $third['direccion'] }}</td>
                    <td>{{ $third['telefono'] }}</td>
                    <td>{{ $third['email'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay terceros registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>