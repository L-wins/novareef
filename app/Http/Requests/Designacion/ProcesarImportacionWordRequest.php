<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use Illuminate\Foundation\Http\FormRequest;
use PhpOffice\PhpWord\Shared\ZipArchive;

class ProcesarImportacionWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se centraliza en el controlador / middleware.
    }

    public function rules(): array
    {
        return [
            'idTorneo'    => ['required', 'integer'],
            'idFormato'   => ['required', 'integer'],
            // Por extensión, no por sniffing de contenido (mimes:docx): un
            // .docx válido puede no disparar la firma exacta que libmagic
            // reconoce como OOXML según el generador/entorno (confirmado
            // contra un .docx real generado por PhpWord, que finfo detecta
            // como application/octet-stream). La extensión sola es
            // spoofeable, así que se complementa con esEstructuraDocxValida()
            // abajo: abre el zip y confirma que exista word/document.xml,
            // el marcador real de un documento OOXML de Word.
            'archivoWord' => [
                'required', 'file', 'extensions:docx', 'max:10240',
                function (string $attribute, $value, \Closure $fail): void {
                    if ($value instanceof \Illuminate\Http\UploadedFile
                        && $value->isValid()
                        && ! $this->esEstructuraDocxValida($value->getRealPath())
                    ) {
                        $fail($this->mensajeNoEsDocx());
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archivoWord.extensions' => $this->mensajeNoEsDocx(),
        ];
    }

    private function mensajeNoEsDocx(): string
    {
        return 'El archivo debe ser un Word en formato .docx (no .doc). '
            . 'En Word: Archivo > Guardar como > Word (.docx), y vuelve a subirlo.';
    }

    private function esEstructuraDocxValida(string $ruta): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($ruta) !== true) {
            return false;
        }

        $documentoPrincipal = $zip->getFromName('word/document.xml');
        $zip->close();

        return $documentoPrincipal !== false;
    }
}
