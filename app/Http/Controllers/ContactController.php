<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return Contact::query()->orderBy('name')->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'mobile_phone' => ['nullable', 'string'],
            'address_line1' => ['nullable', 'string'],
            'address_line2' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_supplier' => ['boolean'],
            'is_customer' => ['boolean'],
            'is_employee' => ['boolean'],
            'is_other' => ['boolean'],
            'other_role_label' => ['nullable', 'string'],
        ]);

        return response()->json(Contact::query()->create($data), 201);
    }

    public function show(Contact $contact)
    {
        return $contact;
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $contact->update($data);

        return $contact;
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->noContent();
    }
}
