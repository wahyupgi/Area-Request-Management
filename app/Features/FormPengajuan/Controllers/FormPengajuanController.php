<?php

namespace App\Features\FormPengajuan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FormPengajuan;
use App\Models\FormPengajuanAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FormPengajuanController extends Controller
{
    public function create()
    {
        return Inertia::render('BeritaAcara/Create', [
            'formPengajuanOnly' => true,
            'branches' => auth()->user()
                ->availableBranchesForDocuments()
                ->with('area:id,name')
                ->orderBy('name')
                ->get(['branches.id', 'branches.name', 'branches.area_id']),
            'templates' => \App\Models\MemoTemplate::where('is_active', true)
                ->where('type', 'form')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $branchIds = $user->availableBranchesForDocuments()->pluck('branches.id')->all();
        $template = $request->input('template');
        $template = match ($template) {
            'Form Permohonan Pinjaman (FPP)' => 'form_permohonan_pinjaman',
            'Form Ijin Tidak Masuk Kerja (FITMK)' => 'form_ijin_tidak_masuk_kerja',
            default => $template,
        };
        $request->merge(['template' => $template]);
        $isSubmit = $request->boolean('submit');
        $required = Rule::requiredIf($isSubmit);
        $requiredForLoan = Rule::requiredIf($isSubmit && $template === 'form_permohonan_pinjaman');
        $requiredForLeave = Rule::requiredIf($isSubmit && $template === 'form_ijin_tidak_masuk_kerja');
        $requiredForOtherLeave = Rule::requiredIf(
            $isSubmit
            && $template === 'form_ijin_tidak_masuk_kerja'
            && $request->input('meta.request_data.leave_type') === 'lainnya'
        );
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->whereIn('id', $branchIds)],
            'template' => ['required', 'string', Rule::in(FormPengajuan::TEMPLATES)],
            'meta.request_data' => 'nullable|array:full_name,position,work_location,employment_date,late_months,absence_months,request_number,salary_after_approval,minimum_salary,loan_amount,repayment_months,salary_deduction,loan_type,leave_type,leave_type_other,start_date,duration,reason,handover,substitute,substitute_position,phone',
            'meta.request_data.full_name' => [$required, 'nullable', 'string', 'max:255'],
            'meta.request_data.position' => 'nullable|string|max:255',
            'meta.request_data.work_location' => 'nullable|string|max:255',
            'meta.request_data.employment_date' => 'nullable|date',
            'meta.request_data.late_months' => 'nullable|integer|min:0|max:1200',
            'meta.request_data.absence_months' => 'nullable|integer|min:0|max:1200',
            'meta.request_data.request_number' => 'nullable|string|max:100',
            'meta.request_data.salary_after_approval' => 'nullable|string|max:100',
            'meta.request_data.minimum_salary' => 'nullable|string|max:100',
            'meta.request_data.loan_amount' => [$requiredForLoan, 'nullable', 'string', 'max:100'],
            'meta.request_data.repayment_months' => [$requiredForLoan, 'nullable', 'integer', 'min:1', 'max:1200'],
            'meta.request_data.salary_deduction' => [$requiredForLoan, 'nullable', 'in:Bersedia,Tidak bersedia'],
            'meta.request_data.loan_type' => [$requiredForLoan, 'nullable', 'in:pinjaman_uang,pembelian_barang'],
            'meta.request_data.leave_type' => [$requiredForLeave, 'nullable', 'in:cuti_tahunan,ijin,cuti_melahirkan,cuti_khusus,sakit,lainnya'],
            'meta.request_data.leave_type_other' => [$requiredForOtherLeave, 'nullable', 'string', 'max:255'],
            'meta.request_data.start_date' => [$requiredForLeave, 'nullable', 'date'],
            'meta.request_data.duration' => [$requiredForLeave, 'nullable', 'integer', 'min:1', 'max:365'],
            'meta.request_data.reason' => [$required, 'nullable', 'string', 'max:5000'],
            'meta.request_data.handover' => 'nullable|string|max:255',
            'meta.request_data.substitute' => 'nullable|string|max:255',
            'meta.request_data.substitute_position' => 'nullable|string|max:255',
            'meta.request_data.phone' => 'nullable|string|max:100',
            'attachments' => 'nullable|array|max:3',
            'attachments.*' => 'file|mimes:docx,pdf|max:10240',
            'submit' => 'required|boolean',
            'title' => 'required|string|max:500',
        ]);

        $branch = Branch::findOrFail($validated['branch_id']);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($validated, $user, $branch, $request, &$storedPaths) {
                $form = FormPengajuan::create([
                    'template' => $validated['template'],
                    'title' => $validated['title'],
                    'data' => $validated['meta']['request_data'] ?? [],
                    'branch_id' => $branch->id,
                    'created_by' => $user->id,
                    'area_manager_id' => $user->areaManagerForBranch($branch)?->id,
                    'status' => $validated['submit'] ? FormPengajuan::STATUS_SUBMITTED : FormPengajuan::STATUS_DRAFT,
                    'submitted_at' => $validated['submit'] ? now() : null,
                ]);

                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store('form-pengajuan-attachments/' . $form->id, 'public');
                    if (!$path) {
                        throw new \RuntimeException('Lampiran Form Pengajuan gagal disimpan.');
                    }

                    $storedPaths[] = $path;
                    $form->attachments()->create([
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            if ($storedPaths && !Storage::disk('public')->delete($storedPaths)) {
                \Log::warning('Gagal membersihkan lampiran Form Pengajuan setelah penyimpanan gagal.', [
                    'paths' => $storedPaths,
                ]);
            }

            throw $exception;
        }

        return redirect()->route('form-pengajuan.index')->with(
            'success',
            $validated['submit']
                ? 'Form Pengajuan berhasil dikirim ke Area Manager.'
                : 'Draft Form Pengajuan berhasil disimpan.'
        );
    }

    public function index()
    {
        $items = FormPengajuan::with(['branch:id,name', 'creator:id,name', 'areaManager:id,name'])
            ->where('created_by', auth()->id())
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('FormPengajuan/Index', ['items' => $items]);
    }

    public function history(FormPengajuan $formPengajuan)
    {
        $user = auth()->user();
        abort_unless(
            $user->isAdmin()
                || ($user->isKC() && $formPengajuan->created_by === $user->id)
                || ($user->isAM() && $formPengajuan->area_manager_id === $user->id),
            403
        );

        $formPengajuan->load(['branch.area', 'creator.area', 'creator.digitalSignature', 'areaManager.area', 'areaManager.digitalSignature', 'approver.digitalSignature', 'attachments']);

        return Inertia::render('FormPengajuan/History', ['formPengajuan' => $formPengajuan]);
    }

    public function review(FormPengajuan $formPengajuan)
    {
        abort_unless($formPengajuan->area_manager_id === auth()->id(), 403);
        $formPengajuan->load(['branch.area', 'creator.area', 'creator.digitalSignature', 'areaManager.area', 'areaManager.digitalSignature', 'attachments']);

        return Inertia::render('FormPengajuan/Review', ['formPengajuan' => $formPengajuan]);
    }

    public function approve(FormPengajuan $formPengajuan)
    {
        $user = auth()->user();
        abort_unless($formPengajuan->area_manager_id === $user->id, 403);
        abort_unless($formPengajuan->status === FormPengajuan::STATUS_SUBMITTED, 422, 'Form Pengajuan ini tidak sedang menunggu persetujuan.');
        if (!$user->digitalSignature) {
            return back()->withErrors(['signature' => 'Tanda tangan digital belum terdaftar. Silakan upload terlebih dahulu.']);
        }

        $formPengajuan->update([
            'status' => FormPengajuan::STATUS_APPROVED,
            'approved_by' => $user->id,
            'rejection_notes' => null,
        ]);

        return redirect()->route('approvals.pending')->with('success', 'Form Pengajuan berhasil disetujui.');
    }

    public function reject(Request $request, FormPengajuan $formPengajuan)
    {
        $user = auth()->user();
        abort_unless($formPengajuan->area_manager_id === $user->id, 403);
        abort_unless($formPengajuan->status === FormPengajuan::STATUS_SUBMITTED, 422, 'Form Pengajuan ini tidak sedang menunggu persetujuan.');
        $validated = $request->validate(['notes' => 'required|string|max:1000']);
        $formPengajuan->update([
            'status' => FormPengajuan::STATUS_REJECTED,
            'rejection_notes' => $validated['notes'],
        ]);

        return redirect()->route('approvals.pending')->with('success', 'Form Pengajuan berhasil ditolak.');
    }

    public function deleteAttachment(FormPengajuan $formPengajuan, FormPengajuanAttachment $attachment)
    {
        abort_unless($attachment->form_pengajuan_id === $formPengajuan->id, 404);
        abort_unless($formPengajuan->created_by === auth()->id(), 403);
        abort_unless($formPengajuan->status === FormPengajuan::STATUS_DRAFT, 403);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Lampiran Form Pengajuan berhasil dihapus.');
    }
}
