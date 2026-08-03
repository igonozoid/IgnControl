<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use Illuminate\Http\Request;

class FinancialAccountController extends Controller
{
    public function index()
    {
        return FinancialAccount::query()->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash,bank'],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'opening_balance' => ['numeric'],
            'bank_name' => ['nullable', 'string'],
            'bank_agency' => ['nullable', 'string'],
            'bank_account_number' => ['nullable', 'string'],
        ]);

        return response()->json(FinancialAccount::query()->create($data), 201);
    }

    public function show(FinancialAccount $financialAccount)
    {
        return $financialAccount;
    }

    public function update(Request $request, FinancialAccount $financialAccount)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:cash,bank'],
            'currency_code' => ['sometimes', 'string', 'size:3', 'exists:currencies,code'],
            'opening_balance' => ['sometimes', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $financialAccount->update($data);

        return $financialAccount;
    }

    public function destroy(FinancialAccount $financialAccount)
    {
        $financialAccount->delete();

        return response()->noContent();
    }
}
