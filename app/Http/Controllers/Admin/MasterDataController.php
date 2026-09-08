<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterDataController extends Controller
{
    public function areas()
    {
        $areas = Area::withCount('branches')->get();

        return Inertia::render('Admin/MasterData/Areas', [
            'areas' => $areas,
        ]);
    }

    public function storeArea(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Area::create(['name' => $request->name]);
        return back()->with('success', 'Area berhasil ditambahkan.');
    }

    public function updateArea(Request $request, Area $area)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $area->update(['name' => $request->name]);
        return back()->with('success', 'Area berhasil diperbarui.');
    }

    public function deleteArea(Area $area)
    {
        $area->delete();
        return back()->with('success', 'Area berhasil dihapus.');
    }

    public function branches()
    {
        $branches = Branch::with(['area', 'kcUser'])->get();
        $areas = Area::all();
        $kcUsers = User::where('role', 'KC')->whereNull('branch_id')->get();

        return Inertia::render('Admin/MasterData/Branches', [
            'branches' => $branches,
            'areas' => $areas,
            'kcUsers' => $kcUsers,
        ]);
    }

    public function storeBranch(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'kc_user_id' => 'nullable|exists:users,id',
        ]);

        $branch = Branch::create($request->only('name', 'area_id', 'kc_user_id'));

        if ($request->kc_user_id) {
            User::where('id', $request->kc_user_id)->update(['branch_id' => $branch->id]);
        }

        return back()->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function users()
    {
        $users = User::with(['branch', 'area'])->orderBy('name')->get();
        $areas = Area::all();
        $branches = Branch::all();

        return Inertia::render('Admin/MasterData/Users', [
            'users' => $users,
            'areas' => $areas,
            'branches' => $branches,
        ]);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:KC,AM,ADMIN',
            'branch_id' => 'nullable|exists:branches,id',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
            'branch_id' => $request->branch_id,
            'area_id' => $request->area_id,
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }
}
