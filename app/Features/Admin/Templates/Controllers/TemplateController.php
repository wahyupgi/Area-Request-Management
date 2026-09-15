<?php

namespace App\Features\Admin\Templates\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MemoTemplate;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $request->validate([
            'name'                    => 'required|string|max:255',
            'category'                => 'nullable|string|max:100',
            'field_schema'            => 'required|array|min:1',
            'field_schema.*.key'      => 'required|string',
            'field_schema.*.label'    => 'required|string',
            'field_schema.*.type'     => 'required|in:text,textarea,number,date,select',
            'field_schema.*.required' => 'required|boolean',
        ]);

        MemoTemplate::create([
            'name'       => $request->name,
            'category'   => $request->category,
            'field_schema' => $request->field_schema,
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template berhasil dibuat.');
    }

    public function edit(MemoTemplate $template)
    {
        return Inertia::render('Admin/Templates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, MemoTemplate $template)
    {
        $request->validate([
            'name'                    => 'required|string|max:255',
            'category'                => 'nullable|string|max:100',
            'field_schema'            => 'required|array|min:1',
            'field_schema.*.key'      => 'required|string',
            'field_schema.*.label'    => 'required|string',
            'field_schema.*.type'     => 'required|in:text,textarea,number,date,select',
            'field_schema.*.required' => 'required|boolean',
        ]);

        $template->update([
            'name'         => $request->name,
            'category'     => $request->category,
            'field_schema' => $request->field_schema,
        ]);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template berhasil diperbarui.');
    }

    public function toggleActive(MemoTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);

        return back()->with('success', 'Status template berhasil diubah.');
    }
}
