<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Exibe a foto do contato (não é download — vai direto num <img src>).
 * Contact já vem filtrado pela empresa ativa (BelongsToCompany), então o
 * route-model-binding sozinho impede ver a foto de um contato de outra
 * empresa (dá 404).
 */
class ContactPhotoController extends Controller
{
    public function show(Contact $contact): BinaryFileResponse|Response
    {
        abort_unless(Auth::user()->hasModuleAccess('contacts', 'read'), 403);
        abort_unless($contact->photo_path && Storage::disk('local')->exists($contact->photo_path), 404);

        return Storage::disk('local')->response($contact->photo_path);
    }
}
