<?php

namespace App\Http\Controllers;

use App\Models\DigitalSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SignatureController extends Controller
{
    public function index()
    {
        $signature = DigitalSignature::where('user_id', auth()->id())->first();

        return Inertia::render('Signature/Index', [
            'signature' => $signature,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'signature_image' => 'required|file|mimes:png,jpg,jpeg|max:2048',
            'certificate_no' => 'nullable|string|max:100',
        ]);

        $existing = DigitalSignature::where('user_id', auth()->id())->first();

        if ($existing) {
            Storage::disk('public')->delete($existing->signature_image);
            $existing->delete();
        }

        $path = $request->file('signature_image')->store('signatures', 'public');

        DigitalSignature::create([
            'user_id' => auth()->id(),
            'signature_image' => $path,
            'certificate_no' => $request->certificate_no,
        ]);

        return back()->with('success', 'Tanda tangan digital berhasil disimpan.');
    }

    public function destroy()
    {
        $signature = DigitalSignature::where('user_id', auth()->id())->first();

        if ($signature) {
            Storage::disk('public')->delete($signature->signature_image);
            $signature->delete();
        }

        return back()->with('success', 'Tanda tangan digital berhasil dihapus.');
    }
}
