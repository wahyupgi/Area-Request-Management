<?php

namespace App\Features\KC\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('KC/Branches', [
            'area' => $user->area?->only('id', 'name'),
            'branches' => $user->area_id
                ? Branch::with('kcUser:id,name')
                    ->where('area_id', $user->area_id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'area_id', 'kc_user_id'])
                : [],
            'selectedBranchIds' => $user->area_id
                ? $user->assignedBranches()->where('area_id', $user->area_id)->pluck('branches.id')->all()
                : [],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user->area_id) {
            return back()->withErrors(['area' => 'Wilayah Anda belum ditetapkan oleh admin.']);
        }

        $validated = $request->validate([
            'branch_ids' => ['present', 'array'],
            'branch_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('branches', 'id')->where('area_id', $user->area_id),
            ],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $branches = Branch::where('area_id', $user->area_id)
                ->whereIn('id', $validated['branch_ids'])
                ->lockForUpdate()
                ->get();

            if ($branches->count() !== count($validated['branch_ids'])) {
                throw ValidationException::withMessages([
                    'branch_ids' => 'Satu atau lebih cabang tidak lagi tersedia di wilayah Anda.',
                ]);
            }

            if ($branches->contains(fn (Branch $branch) => $branch->kc_user_id && $branch->kc_user_id !== $user->id)) {
                throw ValidationException::withMessages([
                    'branch_ids' => 'Cabang yang dipilih sedang ditangani KC lain.',
                ]);
            }

            Branch::where('area_id', $user->area_id)
                ->where('kc_user_id', $user->id)
                ->whereNotIn('id', $validated['branch_ids'])
                ->update(['kc_user_id' => null]);

            Branch::where('area_id', $user->area_id)
                ->whereIn('id', $validated['branch_ids'])
                ->update(['kc_user_id' => $user->id]);

            if ($user->branch_id && !in_array($user->branch_id, $validated['branch_ids'], true)) {
                $user->update(['branch_id' => null]);
            }
        });

        return back()->with('success', 'Pilihan cabang berhasil disimpan.');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->area_id) {
            return back()->withErrors(['area' => 'Wilayah Anda belum ditetapkan oleh admin.']);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name')->where('area_id', $user->area_id),
            ],
        ]);

        Branch::create([
            'name' => $validated['name'],
            'area_id' => $user->area_id,
            'kc_user_id' => $user->id,
        ]);

        return back()->with('success', 'Cabang berhasil ditambahkan ke daftar Anda.');
    }
}
