<?php

namespace App\Features\Memo\Controllers;

use App\Features\Memo\Repositories\MemoRepository;
use App\Features\Memo\Requests\StoreMemoRequest;
use App\Features\Memo\Requests\SubmitMemoRequest;
use App\Features\Memo\Requests\UpdateMemoRequest;
use App\Features\Memo\Requests\UploadAttachmentRequest;
use App\Features\Memo\Services\MemoService;
use App\Http\Controllers\Controller;
use App\Models\Memo;
use App\Models\MemoAttachment;
use App\Models\MemoTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemoController extends Controller
{
    public function __construct(
        protected MemoService $service,
        protected MemoRepository $memoRepository,
    ) {}

    /**
     * List memos for KC user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $memos = $this->memoRepository->listForUser(
            $user,
            $request->input('status'),
            $request->input('per_page', 15),
        );

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
        MemoTemplate::ensureRequiredDefaults();

        $templates = \Illuminate\Support\Facades\Cache::remember('active_memo_templates', now()->addHour(), function () {
            return MemoTemplate::where('is_active', true)->get();
        });

        return Inertia::render('Memo/Create', [
            'templates' => $templates,
        ]);
    }

    /**
     * Store a new memo (as draft).
     */
    public function store(StoreMemoRequest $request)
    {
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

        MemoTemplate::ensureRequiredDefaults();

        $memo->load(['template', 'attachments', 'approvals.approver', 'creator.digitalSignature']);
        $templates = \Illuminate\Support\Facades\Cache::remember('active_memo_templates', now()->addHour(), function () {
            return MemoTemplate::where('is_active', true)->get();
        });

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
    public function update(UpdateMemoRequest $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id) {
            abort(403);
        }

        if (!in_array($memo->status, ['draft', 'rejected'])) {
            return back()->withErrors(['status' => 'Memo tidak bisa diedit.']);
        }

        $memo->update([
            'code' => $memo->code,
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
    public function submit(SubmitMemoRequest $request, Memo $memo)
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
    public function uploadAttachment(UploadAttachmentRequest $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id) {
            abort(403);
        }

        if (!in_array($memo->status, ['draft', 'rejected'])) {
            return back()->withErrors(['status' => 'Tidak bisa menambah lampiran pada memo yang sudah dikirim.']);
        }

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
