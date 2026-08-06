<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Exibe o logo da empresa (não é download — vai direto num <img src>).
 * Só quem participa da empresa (mesma regra do seletor/edição) pode ver.
 */
class CompanyLogoController extends Controller
{
    public function show(Company $company): BinaryFileResponse|Response
    {
        abort_unless(Auth::user()->companies()->where('companies.id', $company->id)->exists(), 403);
        abort_unless($company->logo_path && Storage::disk('local')->exists($company->logo_path), 404);

        return Storage::disk('local')->response($company->logo_path);
    }
}
