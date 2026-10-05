<?php

namespace App\Features\BeritaAcara\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use Illuminate\Http\Request;
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
            'branches' => auth()->user()->assignedBranches()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Simpan BA ke database (draft).
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $assignedBranches = $user->assignedBranches();

        $validated = $request->validate([
            'branch_id'             => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(function ($query) use ($user) {
                    $query->where('kc_user_id', $user->id)
                        ->orWhere(function ($legacyQuery) use ($user) {
                            $legacyQuery->where('id', $user->branch_id)
                                ->where(fn ($kcQuery) => $kcQuery->whereNull('kc_user_id')->orWhere('kc_user_id', $user->id));
                        });
                }),
            ],
            'meta.template'         => 'required|in:permohonan_biaya_kost,revisi_absensi,penghapusan_barang_sitaan,lainnya',
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
            'attachment'            => 'nullable|file|mimes:doc,docx|max:10240',
            'submit_after_save'     => 'boolean',
        ]);

        $branchId = $validated['branch_id'] ?? null;

        if (!$branchId && $assignedBranches->count() > 1) {
            return back()->withErrors(['branch_id' => 'Pilih cabang yang mengajukan Berita Acara.']);
        }

        $branch = $branchId
            ? $assignedBranches->whereKey($branchId)->first()
            : $assignedBranches->first();

        if (!$branch) {
            return back()->withErrors(['branch_id' => 'Pilih cabang yang terdaftar pada akun Anda.']);
        }

        $branch->loadMissing('area.areaManager');
        $areaManager = $branch->area?->areaManager;

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file           = $request->file('attachment');
            $attachmentPath = $file->store('berita-acara-attachments', 'public');
            $attachmentName = $file->getClientOriginalName();
        }

        $ba = BeritaAcara::create([
            'title'               => $validated['title'],
            'meta'                => $validated['meta'] ?? [],
            'pengantar'           => $validated['pengantar'] ?? null,
            'rincian_data'        => $validated['rincian_data'] ?? [],
            'keterangan_tambahan' => $validated['keterangan_tambahan'] ?? null,
            'penutup'             => $validated['penutup'] ?? null,
            'attachment_path'     => $attachmentPath,
            'attachment_name'     => $attachmentName,
            'branch_id'           => $branch->id,
            'created_by'          => $user->id,
            'area_manager_id'     => $areaManager?->id,
            'status'              => BeritaAcara::STATUS_DRAFT,
        ]);

        // Jika user pilih "Kirim langsung"
        if ($request->boolean('submit_after_save')) {
            $ba->update([
                'status'       => BeritaAcara::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

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
}
