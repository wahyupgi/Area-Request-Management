<?php

namespace App\Features\Admin\BranchAssignments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BranchAssignmentController extends Controller
{
    public function index()
    {
        $kcUsers = User::where('role', 'KC')
            ->with('area:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'area_id', 'branch_id']);

        $branches = Branch::with(['area:id,name', 'kcUser:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'area_id', 'kc_user_id']);

        return Inertia::render('Admin/BranchAssignments', [
            'areas' => Area::query()
                ->select(['id', 'name'])
                ->withCount('branches')
                ->addSelect([
                    'kc_users_count' => User::query()
                        ->selectRaw('count(*)')
                        ->where('role', 'KC')
                        ->where(function ($query) {
                            $query->whereColumn('users.area_id', 'areas.id')
                                ->orWhereHas('branches', fn ($branchQuery) =>
                                    $branchQuery->whereColumn('branches.area_id', 'areas.id')
                                )
                                ->orWhereHas('branch', fn ($branchQuery) =>
                                $branchQuery
                                    ->whereNull('branches.kc_user_id')
                                    ->whereColumn('branches.area_id', 'areas.id')
                                );
                        }),
                ])
                ->orderBy('name')
                ->get(),
            'kcUsers' => $kcUsers->map(function (User $kcUser) use ($branches) {
                $assignedBranches = $branches->filter(fn (Branch $branch) =>
                    $branch->kc_user_id === $kcUser->id ||
                    (!$branch->kc_user_id && $branch->id === $kcUser->branch_id)
                )->values();

                return [
                    'id' => $kcUser->id,
                    'name' => $kcUser->name,
                    'area' => $kcUser->area?->only('id', 'name'),
                    'branch_ids' => $assignedBranches->pluck('id')->all(),
                    'branches' => $assignedBranches->map(fn (Branch $branch) => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                    ])->all(),
                ];
            })->values(),
            'branches' => $branches->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'area_id' => $branch->area_id,
                'kc_user_id' => $branch->kc_user_id,
                'kc_user_name' => $branch->kcUser?->name,
                'area' => $branch->area?->only('id', 'name'),
            ])->values(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'KC', 404);

        $validated = Validator::make($request->all(), [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'branch_ids' => ['present', 'array'],
            'branch_ids.*' => [
                'integer',
                'distinct',
            ],
        ])->validate();

        $areaId = $validated['area_id'];
        $branchIds = $validated['branch_ids'];

        DB::transaction(function () use ($user, $areaId, $branchIds) {
            $branches = $areaId
                ? Branch::where('area_id', $areaId)
                    ->whereIn('id', $branchIds)
                    ->lockForUpdate()
                    ->get()
                : collect();

            if ($branches->count() !== count($branchIds)) {
                throw ValidationException::withMessages([
                    'branch_ids' => 'Pilih cabang yang tersedia di wilayah KC yang dipilih.',
                ]);
            }

            Branch::where('kc_user_id', $user->id)
                ->whereNotIn('id', $branchIds)
                ->update(['kc_user_id' => null]);

            Branch::whereIn('id', $branchIds)
                ->update(['kc_user_id' => $user->id]);

            $user->update([
                'area_id' => $areaId,
                'branch_id' => in_array((int) $user->branch_id, array_map('intval', $branchIds), true)
                    ? $user->branch_id
                    : null,
            ]);
        });

        return back()->with('success', 'Mapping cabang KC berhasil diperbarui.');
    }
}
