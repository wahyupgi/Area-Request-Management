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
     * Halaman form buat BA baru.
     */
    public function create()
    {
        return Inertia::render('BeritaAcara/Create', [
            'branches' => auth()->user()
                ->availableBranchesForDocuments()
                ->with('area:id,name')
                ->orderBy('name')
                ->get(['branches.id', 'branches.name', 'branches.area_id']),
            'templates' => \App\Models\MemoTemplate::where('is_active', true)
                ->where('type', 'ba')
                ->get(),
        ]);
    }

    /**
     * Simpan BA ke database (draft).
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
            'meta.data.kronologi'   => 'nullable|string',
            'meta.direktorat'       => 'nullable|string|max:255',
            'meta.divisi'           => 'nullable|string|max:255',
            'meta.perihal'          => 'nullable|string|max:500',
            'meta.lampiran'         => 'nullable|string|max:255',
            'meta.kepada_nama'      => 'nullable|string|max:255',
            'meta.kepada_jabatan'   => 'nullable|string|max:255',
            'meta.penyetuju_akhir'  => 'nullable|string|max:255',
            'title'                 => 'required|string|max:500',
            'pengantar'             => 'nullable|string',
            'rincian_data'          => 'nullable|array',
            'rincian_data.*.label'  => 'nullable|string|max:255',
            'rincian_data.*.value'  => 'nullable|string|max:500',
            'keterangan_tambahan'   => 'nullable|string',
            'penutup'               => 'nullable|string',
            'attachments'            => 'nullable|array|max:3',
            'attachments.*'          => 'file|mimes:docx,pdf|max:10240',
            'submit_after_save'     => 'boolean',
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
                    if (!$path) {
                        throw new \RuntimeException('Lampiran Berita Acara gagal disimpan.');
                    }

                    $storedPaths[] = $path;
                    $ba->attachments()->create([
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }

                if ($request->boolean('submit_after_save')) {
                    $ba->update([
                        'status'       => BeritaAcara::STATUS_SUBMITTED,
                        'submitted_at' => now(),
                    ]);
                }

                return $ba;
            });
        } catch (\Throwable $exception) {
            if ($storedPaths && !Storage::disk('public')->delete($storedPaths)) {
                \Log::warning('Gagal membersihkan lampiran Berita Acara setelah penyimpanan gagal.', [
                    'paths' => $storedPaths,
                ]);
            }

            throw $exception;
        }

        // Jika user pilih "Kirim langsung"
        if ($request->boolean('submit_after_save')) {
            return redirect()->route('berita-acara.index')
                ->with('success', 'Berita Acara berhasil dikirim ke Area Manager.');
        }

        return redirect()->route('berita-acara.index')
            ->with('success', 'Draft Berita Acara berhasil disimpan.');
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

    public function deleteAttachment(BeritaAcara $beritaAcara, BeritaAcaraAttachment $attachment)
    {
        abort_unless($attachment->berita_acara_id === $beritaAcara->id, 404);
        abort_unless($beritaAcara->created_by === auth()->id(), 403);
        abort_unless($beritaAcara->status === BeritaAcara::STATUS_DRAFT, 403);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Lampiran Berita Acara berhasil dihapus.');
    }
}
