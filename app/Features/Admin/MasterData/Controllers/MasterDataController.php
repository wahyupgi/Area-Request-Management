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
use Inertia\Inertia;

class MasterDataController extends Controller
{
    // ─── Areas ───────────────────────────────────────────────────

    public function areas()
    {
        $areas = Area::withCount('branches')->get();

        return Inertia::render('Admin/MasterData/Areas', [
            'areas' => $areas,
        ]);
    }

    public function storeArea(StoreAreaRequest $request)
    {
        Area::create(['name' => $request->name]);
        return back()->with('success', 'Area berhasil ditambahkan.');
    }

    public function updateArea(StoreAreaRequest $request, Area $area)
    {
        $area->update(['name' => $request->name]);
        return back()->with('success', 'Area berhasil diperbarui.');
    }

    public function deleteArea(Area $area)
    {
        $area->delete();
        return back()->with('success', 'Area berhasil dihapus.');
    }

    // ─── Branches ─────────────────────────────────────────────────

    public function branches()
    {
        $branches = Branch::with(['area', 'kcUser'])->get();
        $areas    = Area::all();
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
        $users    = User::with(['branch', 'area'])->orderBy('name')->get();
        $areas    = Area::all();
        $branches = Branch::all();

        return Inertia::render('Admin/MasterData/Users', [
            'users'    => $users,
            'areas'    => $areas,
            'branches' => $branches,
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
            'branch_id' => $request->branch_id,
            'area_id' => $request->area_id,
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if ($validated['role'] !== 'KC') {
            $validated['branch_id'] = null;
        }
        if ($validated['role'] !== 'AM') {
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
