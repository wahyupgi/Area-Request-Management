<?php

namespace App\Features\Signature\Controllers;

use App\Features\Signature\Requests\StoreSignatureRequest;
use App\Features\Signature\Requests\UpdateSignatureSettingsRequest;
use App\Http\Controllers\Controller;
use App\Models\DigitalSignature;
use App\Models\MemoTemplate;
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
    public function store(StoreSignatureRequest $request)
    {
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
    public function updateSettings(UpdateSignatureSettingsRequest $request, MemoTemplate $template)
    {
        abort_unless(auth()->user()->isAM(), 403);

        $validated = $request->validated();

        $template->update([
            'signature_schema' => collect($validated['signature_schema'] ?? [])
                ->map(fn ($slot) => [
                    'name' => $slot['name'],
                    'role' => $slot['role'],
                    'location' => $slot['location'],
                ])->values()->all(),
        ]);

        return back()->with('success', 'Pengaturan tanda tangan template berhasil disimpan.');
    }
}
