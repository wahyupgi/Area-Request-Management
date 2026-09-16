<?php

namespace App\Features\Memo\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Memo\Services\MemoService;
use App\Models\Memo;
use App\Models\MemoAttachment;
use App\Models\MemoTemplate;
use App\Models\DigitalSignature;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemoController extends Controller
{
    public function __construct(protected MemoService $service) {}

    /**
     * List memos for KC user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Memo::with(['template', 'branch', 'areaManager', 'latestApproval', 'attachments']);

        if ($user->isKC()) {
            $query->where('created_by', $user->id);
        } elseif ($user->isAM()) {
            $query->where('area_manager_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $memos = $query->orderBy('updated_at', 'desc')->get();

        return Inertia::render('Memo/Index', [
            'memos' => $memos,
            'filters' => $request->only('status'),
        ]);
    }

    /**
     * Show create memo form.
     */
    public function create()
    {
        $templates = MemoTemplate::where('is_active', true)->get();

        return Inertia::render('Memo/Create', [
            'templates' => $templates,
        ]);
    }

    /**
     * Store a new memo (as draft).
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|max:255|unique:memos,code',
            'template_id' => 'required|exists:memo_templates,id',
            'title' => 'required|string|max:255',
            'field_values' => 'nullable|array',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $memo = $this->service->createDraft($request);

        $routeParameters = ['memo' => $memo->id];
        if ($request->boolean('submit_after_save')) {
            $routeParameters['submit'] = 1;
        }

        return redirect()->route('memos.edit', $routeParameters)
            ->with('success', 'Memo berhasil dibuat sebagai draft.');
    }

    /**
     * Show memo detail.
     */
    public function show(Memo $memo)
    {
        $this->service->authorizeAccess($memo);

        $this->service->markNotificationsRead($memo);

        $memo->load(['template', 'branch.area', 'creator.digitalSignature', 'areaManager.digitalSignature', 'attachments', 'approvals.approver', 'approvals.signature']);
        $this->service->loadConfiguredSigners($memo);

        return Inertia::render('Memo/Show', [
            'memo' => $memo,
        ]);
    }

    /**
     * Show edit form (only for draft/rejected memos).
     */
    public function edit(Request $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id) {
            abort(403);
        }

        if (!in_array($memo->status, ['draft', 'rejected'])) {
            return redirect()->route('memos.show', $memo->id)
                ->with('error', 'Memo tidak bisa diedit.');
        }

        $memo->load(['template', 'attachments', 'approvals.approver', 'creator.digitalSignature']);
        $templates = MemoTemplate::where('is_active', true)->get();

        return Inertia::render('Memo/Edit', [
            'memo' => $memo,
            'templates' => $templates,
            'signature' => $user->digitalSignature,
            'openSignature' => $request->boolean('submit'),
        ]);
    }

    /**
     * Update memo (only draft/rejected).
     */
    public function update(Request $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id) {
            abort(403);
        }

        if (!in_array($memo->status, ['draft', 'rejected'])) {
            return back()->withErrors(['status' => 'Memo tidak bisa diedit.']);
        }

        $request->validate([
            'code' => 'nullable|string|max:255|unique:memos,code,' . $memo->id,
            'title' => 'required|string|max:255',
            'field_values' => 'nullable|array',
        ]);

        $memo->update([
            'code' => $request->code ?? $memo->code,
            'title' => $request->title,
            'field_values' => $request->field_values ?? [],
            'status' => 'draft',
        ]);

        return redirect()->route('memos.edit', $memo->id)
            ->with('success', 'Memo berhasil diperbarui.');
    }

    /**
     * Submit memo to AM.
     */
    public function submit(Request $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id) {
            abort(403);
        }

        if (!in_array($memo->status, ['draft', 'rejected'])) {
            return back()->withErrors(['status' => 'Memo tidak bisa disubmit.']);
        }

        $errors = $this->service->validateRequiredFields($memo);
        if (!empty($errors)) {
            return back()->withErrors($errors);
        }

        if (!$memo->area_manager_id) {
            return back()->withErrors(['area_manager' => 'Area Manager belum tersedia untuk area ini.']);
        }

        $request->validate([
            'signature_image' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
        ]);

        $signature = $this->service->resolveSignature($request, $user);

        if (!$signature) {
            return back()->withErrors(['signature' => 'Tanda tangan digital KC wajib diunggah sebelum memo dikirim ke Area Manager.']);
        }

        $this->service->submitMemo($memo);

        return redirect()->route('memos.show', $memo->id)
            ->with('success', 'Memo berhasil disubmit ke Area Manager.');
    }

    /**
     * Upload attachment to memo.
     */
    public function uploadAttachment(Request $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id) {
            abort(403);
        }

        if (!in_array($memo->status, ['draft', 'rejected'])) {
            return back()->withErrors(['status' => 'Tidak bisa menambah lampiran pada memo yang sudah dikirim.']);
        }

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('attachments/' . $memo->id, 'public');

        MemoAttachment::create([
            'memo_id' => $memo->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        return back()->with('success', 'Lampiran berhasil diupload.');
    }

    /**
     * Delete attachment.
     */
    public function deleteAttachment(Memo $memo, MemoAttachment $attachment)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id) {
            abort(403);
        }

        if ($attachment->memo_id !== $memo->id) {
            abort(404);
        }

        if (!in_array($memo->status, ['draft', 'rejected'])) {
            return back()->withErrors(['status' => 'Tidak bisa menghapus lampiran.']);
        }

        $this->service->deleteAttachment($attachment);

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }

    /**
     * Delete memo (only draft).
     */
    public function destroy(Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id) {
            abort(403);
        }

        if ($memo->status !== 'draft') {
            return back()->withErrors(['status' => 'Hanya memo draft yang bisa dihapus.']);
        }

        $this->service->deleteMemo($memo);

        return redirect()->route('memos.index')
            ->with('success', 'Memo berhasil dihapus.');
    }
}
