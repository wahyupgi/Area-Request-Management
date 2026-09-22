<?php

namespace App\Features\Admin\Templates\Controllers;

use App\Features\Admin\Templates\Requests\StoreTemplateRequest;
use App\Features\Admin\Templates\Requests\UpdateTemplateRequest;
use App\Http\Controllers\Controller;
use App\Models\MemoTemplate;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = MemoTemplate::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Templates/Create');
    }

    public function store(StoreTemplateRequest $request)
    {
        MemoTemplate::create([
            'name' => $request->name,
            'category' => $request->category,
            'field_schema' => $request->field_schema,
            'document_defaults' => $request->document_defaults,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        Cache::forget('active_memo_templates');

        return redirect()->route('admin.templates.index');
    }

    public function edit(MemoTemplate $template)
    {
        return Inertia::render('Admin/Templates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(UpdateTemplateRequest $request, MemoTemplate $template)
    {
        $template->update([
            'name' => $request->name,
            'category' => $request->category,
            'field_schema' => $request->field_schema,
            'document_defaults' => $request->document_defaults,
        ]);

        Cache::forget('active_memo_templates');

        return redirect()->route('admin.templates.index');
    }

    public function toggleActive(MemoTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);

        Cache::forget('active_memo_templates');

        return back()->with('success', 'Status template berhasil diubah.');
    }
}
