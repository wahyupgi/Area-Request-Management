<?php

namespace App\Features\BeritaAcara\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\BeritaAcaraAttachment;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BeritaAcaraController extends Controller
{
    /**
     * Halaman form buat BA baru (hanya simpan draft).
     */
    public function create()
    {
        return Inertia::render('BeritaAcara/Create', [
            'branches' => auth()->user()
                ->availableBranchesForDocuments()
                ->with('area:id,name')
                ->orderBy('name')
                ->get(['branches.id', 'branches.name', 'branches.area_id']),
            'signature' => auth()->user()->digitalSignature,
            'templates' => \App\Models\MemoTemplate::where('is_active', true)
                ->where('type', 'ba')
                ->get(),
        ]);
    }

    /**
     * Simpan BA baru sebagai draft, lalu arahkan ke halaman edit.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $branchIds = $user->availableBranchesForDocuments()->pluck('branches.id')->all();
        $validated = $request->validate([
            'branch_id'             => ['required', 'integer', Rule::exists('branches', 'id')->whereIn('id', $branchIds)],
            'meta.template'         => 'required|string',
            'meta.data'             => 'nullable|array',
            'meta.data.rows'        => 'nullable|array',
            'meta.data.rows.*'      => 'array',
            'meta.data.rows.*.*'    => 'nullable|string|max:1000',
            'meta.data.kronologi'   => 'nullable|string|max:10000',
            'meta.direktorat'       => 'nullable|string|max:255',
            'meta.divisi'           => 'nullable|string|max:255',
            'meta.perihal'          => 'nullable|string|max:500',
            'meta.lampiran'         => 'nullable|string|max:255',
            'meta.kepada_nama'      => 'nullable|string|max:255',
            'meta.kepada_jabatan'   => 'nullable|string|max:255',
            'meta.penyetuju_akhir'  => 'nullable|string|max:255',
            'title'                 => 'required|string|max:500',
            'pengantar'             => 'nullable|string|max:10000',
            'rincian_data'          => 'nullable|array',
            'rincian_data.*.label'  => 'nullable|string|max:255',
            'rincian_data.*.value'  => 'nullable|string|max:500',
            'keterangan_tambahan'   => 'nullable|string|max:10000',
            'penutup'               => 'nullable|string|max:10000',
            'attachments'           => 'nullable|array|max:3',
            'attachments.*'         => 'file|mimes:docx,pdf|max:10240',
        ]);

        $branch = Branch::findOrFail($validated['branch_id']);
        $storedPaths = [];

        try {
            $ba = DB::transaction(function () use ($validated, $user, $branch, $request, &$storedPaths) {
                $ba = BeritaAcara::create([
                    'title'               => $validated['title'],
                    'meta'                => $validated['meta'] ?? [],
                    'pengantar'           => $validated['pengantar'] ?? null,
                    'rincian_data'        => $validated['rincian_data'] ?? [],
                    'keterangan_tambahan' => $validated['keterangan_tambahan'] ?? null,
                    'penutup'             => $validated['penutup'] ?? null,
                    'branch_id'           => $branch->id,
                    'created_by'          => $user->id,
                    'area_manager_id'     => $user->areaManagerForBranch($branch)?->id,
                    'status'              => BeritaAcara::STATUS_DRAFT,
                ]);

                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store('berita-acara-attachments/' . $ba->id, 'public');
                    if (!$path) throw new \RuntimeException('Lampiran Berita Acara gagal disimpan.');
                    $storedPaths[] = $path;
                    $ba->attachments()->create([
                        'file_path'     => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }

                return $ba;
            });
        } catch (\Throwable $exception) {
            if ($storedPaths && !Storage::disk('public')->delete($storedPaths)) {
                \Log::warning('Gagal membersihkan lampiran BA setelah penyimpanan gagal.', ['paths' => $storedPaths]);
            }
            throw $exception;
        }

        return redirect()->route('berita-acara.edit', $ba->id)
            ->with('success', 'Draft Berita Acara berhasil disimpan.');
    }

    /**
     * Halaman edit BA (hanya untuk draft/rejected).
     */
    public function edit(Request $request, BeritaAcara $beritaAcara)
    {
        $user = auth()->user();
        abort_unless($beritaAcara->created_by === $user->id, 403);

        if (!in_array($beritaAcara->status, [BeritaAcara::STATUS_DRAFT, BeritaAcara::STATUS_REJECTED])) {
            return redirect()->route('berita-acara.show', $beritaAcara->id)
                ->with('error', 'Berita Acara tidak bisa diedit.');
        }

        $beritaAcara->load(['template', 'attachments', 'creator.digitalSignature']);

        return Inertia::render('BeritaAcara/Edit', [
            'beritaAcara' => $beritaAcara,
            'signature'   => $user->digitalSignature,
            'branches'    => $user->availableBranchesForDocuments()
                ->with('area:id,name')
                ->orderBy('name')
                ->get(['branches.id', 'branches.name', 'branches.area_id']),
            'templates'   => \App\Models\MemoTemplate::where('is_active', true)
                ->where('type', 'ba')
                ->get(),
        ]);
    }

    /**
     * Simpan perubahan draft BA.
     */
    public function update(Request $request, BeritaAcara $beritaAcara)
    {
        $user = auth()->user();
        abort_unless($beritaAcara->created_by === $user->id, 403);

        if (!in_array($beritaAcara->status, [BeritaAcara::STATUS_DRAFT, BeritaAcara::STATUS_REJECTED])) {
            return back()->withErrors(['status' => 'Berita Acara tidak bisa diedit.']);
        }

        $validated = $request->validate([
            'title'                 => 'required|string|max:500',
            'meta'                  => 'nullable|array',
            'meta.template'         => 'nullable|string|max:255',
            'meta.data'             => 'nullable|array',
            'meta.data.rows'        => 'nullable|array',
            'meta.data.rows.*'      => 'array',
            'meta.data.rows.*.*'    => 'nullable|string|max:1000',
            'meta.data.kronologi'   => 'nullable|string|max:10000',
            'meta.direktorat'       => 'nullable|string|max:255',
            'meta.divisi'           => 'nullable|string|max:255',
            'meta.perihal'          => 'nullable|string|max:500',
            'meta.lampiran'         => 'nullable|string|max:255',
            'meta.kepada_nama'      => 'nullable|string|max:255',
            'meta.kepada_jabatan'   => 'nullable|string|max:255',
            'meta.penyetuju_akhir'  => 'nullable|string|max:255',
            'pengantar'             => 'nullable|string|max:10000',
            'rincian_data'          => 'nullable|array',
            'rincian_data.*.label'  => 'nullable|string|max:255',
            'rincian_data.*.value'  => 'nullable|string|max:500',
            'keterangan_tambahan'   => 'nullable|string|max:10000',
            'penutup'               => 'nullable|string|max:10000',
        ]);

        $beritaAcara->update([
            'title'               => $validated['title'],
            'meta'                => $validated['meta'] ?? $beritaAcara->meta,
            'pengantar'           => $validated['pengantar'] ?? null,
            'rincian_data'        => $validated['rincian_data'] ?? [],
            'keterangan_tambahan' => $validated['keterangan_tambahan'] ?? null,
            'penutup'             => $validated['penutup'] ?? null,
        ]);

        return redirect()->route('berita-acara.edit', $beritaAcara->id)
            ->with('success', 'Berita Acara berhasil diperbarui.');
    }

    /**
     * Kirim BA ke Area Manager.
     */
    public function submit(Request $request, BeritaAcara $beritaAcara)
    {
        $user = auth()->user();
        abort_unless($beritaAcara->created_by === $user->id, 403);

        if (!in_array($beritaAcara->status, [BeritaAcara::STATUS_DRAFT, BeritaAcara::STATUS_REJECTED])) {
            return back()->withErrors(['status' => 'Berita Acara tidak bisa disubmit.']);
        }

        // Validasi TTD
        $signature = $user->digitalSignature;
        if (!$signature && $request->hasFile('signature_image')) {
            $request->validate([
                'signature_image' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            ]);
            $path = $request->file('signature_image')->store('signatures', 'public');
            $user->digitalSignature()->updateOrCreate([], ['signature_image' => $path]);
            $signature = $user->fresh()->digitalSignature;
        }

        if (!$signature) {
            return back()->withErrors(['signature' => 'Tanda tangan digital KC wajib diunggah sebelum Berita Acara dikirim ke Area Manager.']);
        }

        if (!$beritaAcara->area_manager_id && $beritaAcara->branch) {
            $beritaAcara->update(['area_manager_id' => $user->areaManagerForBranch($beritaAcara->branch)?->id]);
        }

        if (!$beritaAcara->area_manager_id) {
            return back()->withErrors(['area_manager' => 'Area Manager belum tersedia untuk area ini.']);
        }

        $beritaAcara->update([
            'status'       => BeritaAcara::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return redirect()->route('berita-acara.show', $beritaAcara->id)
            ->with('success', 'Berita Acara berhasil dikirim ke Area Manager.');
    }

    /**
     * Upload lampiran ke BA (hanya draft/rejected).
     */
    public function uploadAttachment(Request $request, BeritaAcara $beritaAcara)
    {
        $user = auth()->user();
        abort_unless($beritaAcara->created_by === $user->id, 403);

        if (!in_array($beritaAcara->status, [BeritaAcara::STATUS_DRAFT, BeritaAcara::STATUS_REJECTED])) {
            return back()->withErrors(['status' => 'Tidak bisa menambah lampiran pada BA yang sudah dikirim.']);
        }

        if ($beritaAcara->attachments()->count() >= 3) {
            return back()->withErrors(['file' => 'Berita Acara maksimal memiliki 3 lampiran.']);
        }

        $request->validate(['file' => 'required|file|mimes:docx,pdf|max:10240']);
        $file = $request->file('file');
        $path = $file->store('berita-acara-attachments/' . $beritaAcara->id, 'public');

        $beritaAcara->attachments()->create([
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        return back()->with('success', 'Lampiran berhasil diupload.');
    }

    /**
     * Hapus lampiran BA.
     */
    public function deleteAttachment(BeritaAcara $beritaAcara, BeritaAcaraAttachment $attachment)
    {
        abort_unless($attachment->berita_acara_id === $beritaAcara->id, 404);
        abort_unless($beritaAcara->created_by === auth()->id(), 403);
        abort_unless(in_array($beritaAcara->status, [BeritaAcara::STATUS_DRAFT, BeritaAcara::STATUS_REJECTED]), 403);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Lampiran Berita Acara berhasil dihapus.');
    }

    /**
     * Halaman preview detail BA (read-only untuk KC/AM/Admin).
     */
    public function show(BeritaAcara $beritaAcara)
    {
        $user = auth()->user();
        abort_unless(
            $user->isAdmin()
                || ($user->isKC() && $beritaAcara->created_by === $user->id)
                || ($user->isAM() && $beritaAcara->area_manager_id === $user->id),
            403
        );

        $beritaAcara->load(['branch.area', 'creator.digitalSignature', 'areaManager', 'attachments']);

        return Inertia::render('BeritaAcara/Show', [
            'beritaAcara' => $beritaAcara,
        ]);
    }

    /**
     * Halaman daftar semua BA milik user KC ini.
     */
    public function index()
    {
        $user = auth()->user();

        $items = BeritaAcara::with(['branch:id,name'])
            ->where('created_by', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('BeritaAcara/Index', [
            'items' => $items,
        ]);
    }
}
