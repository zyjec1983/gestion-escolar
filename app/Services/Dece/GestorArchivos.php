<?php

namespace App\Services\Dece;

use App\Models\DeceArchivo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Guarda y elimina evidencias del DECE en el disco publico, dentro de una
 * carpeta por modulo. Mantiene el controlador libre de detalles de Storage.
 */
class GestorArchivos
{
    /**
     * @param  array<int, UploadedFile>  $archivos
     * @return array<int, DeceArchivo>
     */
    public function guardar(Request $request, Model $modelo, array $archivos, string $carpeta): array
    {
        $guardados = [];

        foreach ($archivos as $archivo) {
            if (! $archivo instanceof UploadedFile) {
                continue;
            }

            $guardados[] = $this->guardarUno($request, $modelo, $archivo, $carpeta);
        }

        return $guardados;
    }

    public function guardarUno(Request $request, Model $modelo, UploadedFile $archivo, string $carpeta): DeceArchivo
    {
        $base = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME));
        $base = $base !== '' ? $base : 'evidencia';
        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombre = $base.'-'.Str::random(8).'.'.$extension;
        $directorio = 'dece/'.$carpeta.'/'.now()->format('Y');
        $ruta = null;

        try {
            $ruta = $archivo->storeAs($directorio, $nombre, 'public');

            if (! $ruta) {
                throw new \RuntimeException('No se pudo almacenar la evidencia.');
            }

            return DeceArchivo::create([
                'archivoable_type' => $modelo::class,
                'archivoable_id' => $modelo->getKey(),
                'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255, 'UTF-8'),
                'ruta' => $ruta,
                'mime' => $archivo->getClientMimeType() ?: 'application/octet-stream',
                'tamano' => $archivo->getSize() ?: 0,
                'subido_por' => $request->user()?->id,
            ]);
        } catch (Throwable $e) {
            if ($ruta) {
                Storage::disk('public')->delete($ruta);
            }

            throw $e;
        }
    }

    public function eliminar(DeceArchivo $archivo): void
    {
        Storage::disk('public')->delete($archivo->ruta);
        $archivo->delete();
    }
}
