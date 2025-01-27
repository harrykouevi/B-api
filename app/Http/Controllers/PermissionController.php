<?php
/*
 * File name: PermissionController.php
 * Last modified: 2024.04.18 at 17:22:49
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\PermissionDataTable;
use App\Http\Requests\CreatePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Repositories\PermissionRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;
use Request;
use Response;

class PermissionController extends Controller
{
    /** @var  PermissionRepository */
    private PermissionRepository $permissionRepository;

    public function __construct(PermissionRepository $permissionRepo)
    {
        parent::__construct();
        $this->permissionRepository = $permissionRepo;
    }

    /**
     * Display a listing of the Permission.
     *
     * @param PermissionDataTable $permissionDataTable
     * @return Response
     */
    public function index(PermissionDataTable $permissionDataTable): mixed
    {
        return $permissionDataTable->render('settings.permissions.index');
    }

    public function refreshPermissions(Request $request): void
    {
        Artisan::call('db:seed', ['--class' => 'DemoPermissionsPermissionsTableSeeder']);
        redirect()->back();
    }

    public function givePermissionToRole(Request $request): void
    {
        if (config('installer.demo_app')) {
            Flash::warning('This is only demo app you can\'t change this section ');
        } else {
            $input = Request::all();
            $this->permissionRepository->givePermissionToRole($input);
        }
    }

    public function revokePermissionToRole(Request $request): void
    {
        if (config('installer.demo_app')) {
            Flash::warning('This is only demo app you can\'t change this section ');
        } else {
            $input = Request::all();
            $this->permissionRepository->revokePermissionToRole($input);
        }
    }

    public function roleHasPermission(Request $request): bool|string
    {
        $input = Request::all();
        //dd($input);
        $result = $this->permissionRepository->roleHasPermission($input);
        return json_encode($result);
    }

    /**
     * Store a newly created Permission in storage.
     *
     * @param CreatePermissionRequest $request
     *
     * @return RedirectResponse
     * @throws ValidatorException
     */
    public function store(CreatePermissionRequest $request): RedirectResponse
    {
        if (config('installer.demo_app')) {
            Flash::warning('This is only demo app you can\'t change this section ');
            return redirect(route('permissions.index'));
        }
        $input = $request->all();

        $permission = $this->permissionRepository->create($input);

        Flash::success('Permission saved successfully.');

        return redirect(route('permissions.index'));
    }

    /**
     * Show the form for creating a new Permission.
     *
     * @return View
     */
    public function create(): View
    {
        return view('settings.permissions.create');
    }

    /**
     * Display the specified Permission.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function show(int $id): RedirectResponse|View
    {
        $permission = $this->permissionRepository->findWithoutFail($id);

        if (empty($permission)) {
            Flash::error('Permission not found');

            return redirect(route('permissions.index'));
        }

        return view('settings.permissions.show')->with('permission', $permission);
    }

    /**
     * Show the form for editing the specified Permission.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $permission = $this->permissionRepository->findWithoutFail($id);

        if (empty($permission)) {
            Flash::error('Permission not found');

            return redirect(route('permissions.index'));
        }

        return view('settings.permissions.edit')->with('permission', $permission);
    }

    /**
     * Update the specified Permission in storage.
     *
     * @param int $id
     * @param UpdatePermissionRequest $request
     *
     * @return RedirectResponse
     * @throws ValidatorException
     */
    public function update(int $id, UpdatePermissionRequest $request): RedirectResponse
    {
        if (env('APP_DEMO', false)) {
            Flash::warning('This is only demo app you can\'t change this section ');
            return redirect(route('permissions.index'));
        }
        $permission = $this->permissionRepository->findWithoutFail($id);

        if (empty($permission)) {
            Flash::error('Permission not found');

            return redirect(route('permissions.index'));
        }

        $permission = $this->permissionRepository->update($request->all(), $id);

        Flash::success('Permission updated successfully.');

        return redirect(route('permissions.index'));
    }

    /**
     * Remove the specified Permission from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        if (env('APP_DEMO', false)) {
            Flash::warning('This is only demo app you can\'t change this section ');
            return redirect(route('permissions.index'));
        }
        $permission = $this->permissionRepository->findWithoutFail($id);

        if (empty($permission)) {
            Flash::error('Permission not found');

            return redirect(route('permissions.index'));
        }

        $this->permissionRepository->delete($id);

        Flash::success('Permission deleted successfully.');

        return redirect(route('permissions.index'));
    }
}
