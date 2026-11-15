<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'in:ar,en'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);
        $request->session()->put('locale', $data['locale']);

        $returnTo = $data['return_to'] ?? '';
        if (! str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//') || str_contains($returnTo, '\\')) {
            $returnTo = $request->user()
                ? route('dashboard', absolute: false)
                : route('login', absolute: false);
        }

        return redirect()->to($returnTo);
    }
}
