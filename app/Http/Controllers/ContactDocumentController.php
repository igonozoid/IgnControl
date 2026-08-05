<?php

namespace App\Http\Controllers;

use App\Models\ContactDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Download de um documento anexado ao contato (ex.: PDF da Busca Básica de
 * CNPJ). ContactDocument já vem filtrado pela empresa ativa
 * (BelongsToCompany), então o route-model-binding sozinho impede baixar o
 * documento de um contato de outra empresa (dá 404).
 */
class ContactDocumentController extends Controller
{
    public function download(ContactDocument $contactDocument): BinaryFileResponse|Response
    {
        abort_unless(Auth::user()->hasModuleAccess('contacts', 'read'), 403);
        abort_unless(Storage::disk('local')->exists($contactDocument->stored_path), 404);

        return Storage::disk('local')->download($contactDocument->stored_path, $contactDocument->original_name);
    }
}
