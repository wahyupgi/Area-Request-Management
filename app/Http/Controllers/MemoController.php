<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use App\Models\MemoAttachment;
use App\Models\MemoTemplate;
use App\Models\Notification;
use App\Models\Branch;
use App\Models\DigitalSignature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MemoController extends Controller
{
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
            'template_id' => 'required|exists:memo_templates,id',
            'title' => 'required|string|max:255',
            'field_values' => 'nullable|array',
        ]);

        $user = auth()->user();
        $branch = $user->branch;

        if (!$branch) {
            return back()->withErrors(['branch' => 'Anda belum terdaftar di cabang manapun.']);
        }

        // Find the AM for this branch's area
        $areaManager = \App\Models\User::where('role', 'AM')
            ->where('area_id', $branch->area_id)
            ->first();

        $memo = Memo::create([
            'template_id' => $request->template_id,
            'title' => $request->title,
            'field_values' => $request->field_values ?? [],
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'area_manager_id' => $areaManager?->id,
            'status' => 'draft',
        ]);

        $routeParameters = ['memo' => $memo->id];
        if ($request->boolean('submit_after_save')) {
            $routeParameters['submit'] = 1;
        }

        $redirect = redirect()->route('memos.edit', $routeParameters)
            ->with('success', 'Memo berhasil dibuat sebagai draft.');

        return $redirect;
    }

    /**
     * Show memo detail.
     */
    public function show(Memo $memo)
    {
        $this->authorizeAccess($memo);

        $memo->load(['template', 'branch.area', 'creator.digitalSignature', 'areaManager.digitalSignature', 'attachments', 'approvals.approver', 'approvals.signature']);
        $this->loadConfiguredSigners($memo);

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

    private function loadConfiguredSigners(Memo $memo): void
    {
        $ids = collect($memo->template?->signature_schema ?? [])->pluck('user_id')->filter()->unique();
        $people = User::with('digitalSignature')->whereIn('id', $ids)->get()->keyBy('id');
        $memo->template?->setAttribute('signature_people', $people);
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
            'title' => 'required|string|max:255',
            'field_values' => 'nullable|array',
        ]);

        $memo->update([
            'title' => $request->title,
            'field_values' => $request->field_values ?? [],
            'status' => 'draft', // Reset to draft if was rejected
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

        // Validate required fields from template schema
        $template = $memo->template;
        $fieldValues = $memo->field_values ?? [];
        $items = isset($fieldValues['items']) && is_array($fieldValues['items'])
            ? $fieldValues['items']
            : [$fieldValues];
        $errors = [];

        foreach ($items as $itemIndex => $item) {
            foreach ($template->field_schema as $field) {
                if ($field['required'] && empty($item[$field['key']])) {
                    $errors['field_values.items.' . $itemIndex . '.' . $field['key']] = $field['label'] . ' pada item ' . ($itemIndex + 1) . ' wajib diisi.';
                }
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors);
        }

        if (!$memo->area_manager_id) {
            return back()->withErrors(['area_manager' => 'Area Manager belum tersedia untuk area ini.']);
        }

        $request->validate([
            'signature_image' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
        ]);

        $signature = DigitalSignature::where('user_id', $user->id)->first();
        if ($request->hasFile('signature_image')) {
            $path = $request->file('signature_image')->store('signatures', 'public');

            if ($signature) {
                Storage::disk('public')->delete($signature->signature_image);
                $signature->update(['signature_image' => $path]);
            } else {
                $signature = DigitalSignature::create([
                    'user_id' => $user->id,
                    'signature_image' => $path,
                ]);
            }
        }

        if (!$signature) {
            return back()->withErrors(['signature' => 'Tanda tangan digital KC wajib diunggah sebelum memo dikirim ke Area Manager.']);
        }

        DB::transaction(function () use ($memo) {
            $memo->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Notify AM
            Notification::create([
                'user_id' => $memo->area_manager_id,
                'memo_id' => $memo->id,
                'message' => 'Memo baru "' . $memo->title . '" dari ' . $memo->creator->name . ' menunggu persetujuan Anda.',
            ]);
        });

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
            'file' => 'required|file|max:10240', // max 10MB
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

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

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

        // Delete attachments from storage
        foreach ($memo->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $memo->delete();

        return redirect()->route('memos.index')
            ->with('success', 'Memo berhasil dihapus.');
    }

    private function authorizeAccess(Memo $memo)
    {
        $user = auth()->user();

        if ($user->isAdmin()) return;
        if ($user->isKC() && $memo->created_by === $user->id) return;
        if ($user->isAM() && $memo->area_manager_id === $user->id) return;

        abort(403);
    }
}
