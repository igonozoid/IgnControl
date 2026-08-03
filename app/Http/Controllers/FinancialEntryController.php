<?php

namespace App\Http\Controllers;

use App\Models\FinancialEntry;
use Illuminate\Http\Request;

class FinancialEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialEntry::query()->with(['financialAccount', 'contact', 'category', 'costCenter']);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $query->orderByDesc('due_date')->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense,transfer'],
            'financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'destination_account_id' => ['required_if:type,transfer', 'nullable', 'exists:financial_accounts,id', 'different:financial_account_id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'paid_date' => ['nullable', 'date'],
        ]);

        $data['status'] = ! empty($data['paid_date']) ? 'paid' : 'pending';

        return response()->json(FinancialEntry::query()->create($data), 201);
    }

    public function show(FinancialEntry $financialEntry)
    {
        return $financialEntry->load(['financialAccount', 'contact', 'category', 'costCenter', 'exchangeRate']);
    }

    public function update(Request $request, FinancialEntry $financialEntry)
    {
        $data = $request->validate([
            'description' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['sometimes', 'date'],
            'paid_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'in:pending,paid,canceled'],
        ]);

        $financialEntry->update($data);

        return $financialEntry;
    }

    public function destroy(FinancialEntry $financialEntry)
    {
        $financialEntry->delete();

        return response()->noContent();
    }
}
