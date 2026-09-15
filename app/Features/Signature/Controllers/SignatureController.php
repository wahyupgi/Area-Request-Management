<?php

namespace App\Features\Signature\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DigitalSignature;
use App\Models\MemoTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SignatureController extends Controller
{
    /**
     * Show the current user's digital signature page.
     */
    public function index()
    {
        $signature = DigitalSignature::where('user_id', auth()->id())->first();

        return Inertia::render('Signature/Index', [
            'signature' => $signature,
        ]);
    }

    /**
     * Upload / replace digital signature.
     */
    public function store(Request $request)
    {
        $request->validate([
            'signature_image' => 'required|file|mimes:png,jpg,jpeg|max:2048',
            'certificate_no'  => 'nullable|string|max:100',
        ]);

        $existing = DigitalSignature::where('user_id', auth()->id())->first();

        if ($existing) {
            Storage::disk('public')->delete($existing->signature_image);
            $existing->delete();
        }

        $path = $request->file('signature_image')->store('signatures', 'public');

        DigitalSignature::create([
            'user_id'        => auth()->id(),
            'signature_image' => $path,
            'certificate_no' => $request->certificate_no,
        ]);

        return back()->with('success', 'Tanda tangan digital berhasil disimpan.');
    }

    /**
     * Delete the current user's digital signature.
     */
    public function destroy()
    {
        $signature = DigitalSignature::where('user_id', auth()->id())->first();

        if ($signature) {
            Storage::disk('public')->delete($signature->signature_image);
            $signature->delete();
        }

        return back()->with('success', 'Tanda tangan digital berhasil dihapus.');
    }

    /**
     * Show signature schema settings for AM.
     */
    public function settings()
    {
        abort_unless(auth()->user()->isAM(), 403);

        return Inertia::render('Signature/Settings', [
            'templates' => MemoTemplate::where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'category', 'signature_schema']),
        ]);
    }

    /**
     * Update signature schema for a template.
     */
    public function updateSettings(Request $request, MemoTemplate $template)
    {
        abort_unless(auth()->user()->isAM(), 403);

        $validated = $request->validate([
            'signature_schema'          => ['nullable', 'array', 'max:6'],
            'signature_schema.*.name'   => ['required', 'string', 'max:255'],
            'signature_schema.*.role'   => ['required', 'string', 'max:100'],
            'signature_schema.*.location' => ['required', 'in:document,bottom_right'],
        ]);

        $template->update([
            'signature_schema' => collect($validated['signature_schema'] ?? [])
                ->map(fn ($slot) => [
                    'name'     => $slot['name'],
                    'role'     => $slot['role'],
                    'location' => $slot['location'],
                ])->values()->all(),
        ]);

        return back()->with('success', 'Pengaturan tanda tangan template berhasil disimpan.');
    }
}
