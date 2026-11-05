<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('admin/users/Index', [
            'users' => User::query()->with('roles')->orderBy('name')->paginate(20)->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'is_active' => $user->is_active,
                'role' => $user->getRoleNames()->first(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('admin/users/Form', ['user' => null, 'roles' => $this->roles()]);
    }

    public function store(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('create', User::class);
        $data = $this->validated($request);
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => $data['locale'],
            'is_active' => (bool) $data['is_active'],
        ]);
        $user->syncRoles([$data['role']]);
        $activity->log('user.created', 'Created a user', $user, ['role' => $data['role']], $request->user());

        return redirect()->route('admin.users.index')->with('success', __('racode.saved'));
    }

    public function edit(User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('admin/users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'is_active' => $user->is_active,
                'role' => $user->getRoleNames()->first(),
            ],
            'roles' => $this->roles(),
        ]);
    }

    public function update(Request $request, User $user, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('update', $user);
        $data = $this->validated($request, $user->id, false);
        $this->guardLastSuperAdmin($user, $data['role'], (bool) $data['is_active']);
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'locale' => $data['locale'],
            'is_active' => (bool) $data['is_active'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->syncRoles([$data['role']]);
        $activity->log('user.updated', 'Updated a user', $user, ['role' => $data['role']], $request->user());

        return back()->with('success', __('racode.saved'));
    }

    public function destroy(Request $request, User $user, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($request->user()->is($user)) {
            throw ValidationException::withMessages(['role' => __('racode.self_delete')]);
        }

        $this->guardLastSuperAdmin($user, null, false, true);
        $activity->log('user.deleted', 'Deleted a user', null, ['email' => $user->email], $request->user());
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', __('racode.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $id = null, bool $passwordRequired = true): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'locale' => ['required', Rule::in(config('racode.locales'))],
            'is_active' => ['required', 'boolean'],
            'role' => ['required', Rule::in(['super-admin', 'content-manager', 'editor', 'viewer'])],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function roles(): array
    {
        return ['super-admin', 'content-manager', 'editor', 'viewer'];
    }

    private function guardLastSuperAdmin(User $target, ?string $role, bool $active, bool $deleting = false): void
    {
        if (! $target->hasRole('super-admin') || ! $target->is_active) {
            return;
        }

        $others = User::role('super-admin')->where('is_active', true)->whereKeyNot($target->id)->count();
        $keeps = ! $deleting && $active && $role === 'super-admin';

        if ($others === 0 && ! $keeps) {
            throw ValidationException::withMessages([
                'role' => __('racode.last_super_admin'),
            ]);
        }
    }
}
