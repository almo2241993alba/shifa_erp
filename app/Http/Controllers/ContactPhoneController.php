<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContactPhoneController extends Controller
{
    public function index(Contact $contact): JsonResponse
    {
        $this->authorizeContact($contact);

        return response()->json(['data' => $contact->phones()->latest()->get()]);
    }

    public function store(Request $request, Contact $contact): JsonResponse
    {
        $this->authorizeContact($contact);
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:255', Rule::unique('contact_phones', 'phone')->where('contact_id', $contact->id)],
            'phone_type' => ['sometimes', Rule::in(['mobile', 'work', 'whatsapp'])],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $phone = DB::transaction(function () use ($contact, $data): ContactPhone {
            if ($data['is_primary'] ?? false) {
                $contact->phones()->update(['is_primary' => false]);
            }

            return $contact->phones()->create($data);
        });

        return response()->json(['data' => $phone], 201);
    }

    public function update(Request $request, Contact $contact, ContactPhone $phone): JsonResponse
    {
        $this->authorizeContact($contact);
        abort_unless($phone->contact_id === $contact->id, 404);
        $data = $request->validate([
            'phone' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('contact_phones', 'phone')->where('contact_id', $contact->id)->ignore($phone->id)],
            'phone_type' => ['sometimes', Rule::in(['mobile', 'work', 'whatsapp'])],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($contact, $phone, $data): void {
            if ($data['is_primary'] ?? false) {
                $contact->phones()->where('id', '!=', $phone->id)->update(['is_primary' => false]);
            }
            $phone->fill($data)->save();
        });

        return response()->json(['data' => $phone->refresh()]);
    }

    public function destroy(Contact $contact, ContactPhone $phone): JsonResponse
    {
        $this->authorizeContact($contact);
        abort_unless($phone->contact_id === $contact->id, 404);
        $phone->delete();

        return response()->json(status: 204);
    }

    private function authorizeContact(Contact $contact): void
    {
        $user = request()->user();
        abort_if(
            ($user->branch_id === null && $user->role !== 'admin')
                || ($user->branch_id !== null && (int) $contact->branch_id !== (int) $user->branch_id),
            404,
        );
    }
}
