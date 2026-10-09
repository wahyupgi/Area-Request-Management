<?php

namespace App\Features\Admin\MasterData\Controllers;

use App\Features\Admin\MasterData\Requests\StoreAreaRequest;
use App\Features\Admin\MasterData\Requests\StoreBranchRequest;
use App\Features\Admin\MasterData\Requests\StoreUserRequest;
use App\Features\Admin\MasterData\Requests\UpdateUserRequest;
use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MasterDataController extends Controller
{
    // ─── Areas ───────────────────────────────────────────────────

    public function areas()
    {
        $areas = Area::with(['parent:id,name', 'kcUsers' => fn ($query) => $query->orderBy('name')])
            ->withCount('branches')
            ->get()
            ->map(function (Area $area) {
                $area->setRelation('kcUsers', $area->kcUsers->map(function (User $kcUser) {
                    $kcUser->setRelation(
                        'assigned_branches',
                        $kcUser->assignedBranches()->orderBy('name')->get(['branches.id', 'name'])
                    );

                    return $kcUser;
                }));

                return $area;
            });
        $kcUsers = User::where('role', 'KC')->orderBy('name')->get(['id', 'name', 'area_id']);

        return Inertia::render('Admin/MasterData/Areas', [
            'areas' => $areas,
            'kcUsers' => $kcUsers,
        ]);
    }

    public function storeArea(StoreAreaRequest $request)
    {
        DB::transaction(function () use ($request) {
            $area = Area::create($request->only('name', 'parent_id'));
            if ($area->parent_id && $request->has('kc_user_ids')) {
                $this->syncAreaKcUsers($area, $request->input('kc_user_ids') ?? []);
            }
            if ($area->parent_id) {
                $this->createAreaBranches($area, $request->input('branch_names') ?? []);
            }
        });

        return back()->with('success', 'Wilayah berhasil ditambahkan.');
    }

    public function updateArea(StoreAreaRequest $request, Area $area)
    {
        DB::transaction(function () use ($request, $area) {
            if ($area->cities()->exists() && $request->filled('parent_id')) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Provinsi yang sudah memiliki kota/kabupaten tidak dapat dipindahkan.',
                ]);
            }
            if ($area->parent_id && !$request->filled('parent_id')
                && ($area->branches()->exists() || $area->kcUsers()->exists())) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Kota/kabupaten dengan cabang atau KC tidak dapat diubah menjadi provinsi.',
                ]);
            }
            if (!$area->parent_id && $request->filled('parent_id')
                && $area->users()->where('role', 'AM')->exists()) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Pindahkan Area Manager ke provinsi terlebih dahulu sebelum menjadikan wilayah ini kota/kabupaten.',
                ]);
            }

            $area->update($request->only('name', 'parent_id'));
            if ($area->parent_id && $request->has('kc_user_ids')) {
                $this->syncAreaKcUsers($area, $request->input('kc_user_ids') ?? []);
            }
            $this->renameAreaBranches($area, $request->input('existing_branches') ?? []);
            if ($area->parent_id) {
                $this->createAreaBranches($area, $request->input('branch_names') ?? []);
            }
        });

        return back()->with('success', 'Wilayah berhasil diperbarui.');
    }

    private function renameAreaBranches(Area $area, array $branchUpdates): void
    {
        $branches = $area->branches()->get()->keyBy('id');
        $names = $branches->mapWithKeys(fn (Branch $branch) => [$branch->id => $branch->name])->all();

        foreach ($branchUpdates as $index => $update) {
            if (!$branches->has((int) $update['id'])) {
                throw ValidationException::withMessages([
                    "existing_branches.$index.id" => 'Cabang tidak termasuk dalam wilayah ini.',
                ]);
            }

            $names[(int) $update['id']] = trim($update['name']);
        }

        $lowered = array_map('mb_strtolower', $names);
        foreach ($branchUpdates as $index => $update) {
            if (count(array_keys($lowered, $lowered[(int) $update['id']], true)) > 1) {
                throw ValidationException::withMessages([
                    "existing_branches.$index.name" => 'Nama cabang sudah digunakan di wilayah ini.',
                ]);
            }
        }

        foreach ($branchUpdates as $update) {
            $branches[(int) $update['id']]->update(['name' => $names[(int) $update['id']]]);
        }
    }

    private function createAreaBranches(Area $area, array $branchNames): void
    {
        $existing = $area->branches()->pluck('name')->map(fn ($name) => mb_strtolower($name))->all();

        foreach ($branchNames as $name) {
            $name = trim($name);
            if ($name === '' || in_array(mb_strtolower($name), $existing, true)) {
                continue;
            }

            $area->branches()->create(['name' => $name]);
            $existing[] = mb_strtolower($name);
        }
    }

    private function syncAreaKcUsers(Area $area, array $kcUserIds): void
    {
        User::where('role', 'KC')
            ->where('area_id', $area->id)
            ->whereNotIn('id', $kcUserIds)
            ->update(['area_id' => null]);

        User::where('role', 'KC')
            ->whereIn('id', $kcUserIds)
            ->update(['area_id' => $area->id]);
    }

    public function deleteArea(Area $area)
    {
        if ($area->cities()->exists()) {
            return back()->with('error', 'Provinsi tidak dapat dihapus selama masih memiliki kota/kabupaten.');
        }

        $area->delete();
        return back()->with('success', 'Area berhasil dihapus.');
    }

    // ─── Branches ─────────────────────────────────────────────────

    public function branches()
    {
        $branches = Branch::with(['area', 'kcUser'])->get();
        $areas    = Area::whereNotNull('parent_id')->with('parent:id,name')->get();
        $kcUsers  = User::where('role', 'KC')->whereNull('branch_id')->get();

        return Inertia::render('Admin/MasterData/Branches', [
            'branches' => $branches,
            'areas'    => $areas,
            'kcUsers'  => $kcUsers,
        ]);
    }

    public function storeBranch(StoreBranchRequest $request)
    {
        $branch = Branch::create($request->only('name', 'area_id', 'kc_user_id'));

        if ($request->kc_user_id) {
            User::where('id', $request->kc_user_id)->update(['branch_id' => $branch->id]);
        }

        return back()->with('success', 'Cabang berhasil ditambahkan.');
    }

    // ─── Users ────────────────────────────────────────────────────

    public function users()
    {
        $users = User::with(['branch', 'area.parent'])->orderBy('name')->get();
        $areas = Area::with('parent:id,name')->orderBy('name')->get();

        return Inertia::render('Admin/MasterData/Users', [
            'users' => $users,
            'areas' => $areas,
        ]);
    }

    public function storeUser(StoreUserRequest $request)
    {
        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
            'branch_id' => null,
            'area_id' => in_array($request->role, ['AM', 'KC'], true) ? $request->area_id : null,
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $validated['branch_id'] = null;
        if (!in_array($validated['role'], ['AM', 'KC'], true)) {
            $validated['area_id'] = null;
        }
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function deleteUser(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}
