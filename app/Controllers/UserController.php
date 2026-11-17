<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\AuditLogger;
use App\Services\AuthService;

class UserController extends Controller
{
    protected User $userModel;
    protected Role $roleModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    public function index(): void
    {
        $page = (int)$this->input('page', 1);
        $search = trim((string)$this->input('search', ''));
        $roleId = $this->input('role_id', '');
        $status = trim((string)$this->input('status', ''));

        $filters = array_filter([
            'search' => $search,
            'role_id' => $roleId,
            'status' => $status
        ], fn($val) => $val !== '' && $val !== null);

        $result = $this->userModel->paginate($page, 10, $filters);

        $this->render('admin/users/index', [
            'title' => 'Manajemen Pengguna - Smart Campus Cash',
            'users' => $result['data'],
            'total' => $result['total'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'roles' => $this->roleModel->all(),
            'search' => $search,
            'selectedRole' => $roleId,
            'selectedStatus' => $status,
            'currentUserId' => AuthService::id(),
            'success' => flash('flash_success'),
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    public function create(): void
    {
        $this->render('admin/users/create', [
            'title' => 'Tambah Pengguna Baru - Smart Campus Cash',
            'roles' => $this->roleModel->all(),
            'error' => flash('flash_error'),
            'old' => $_SESSION['old_input'] ?? []
        ], 'layouts/app');
        unset($_SESSION['old_input']);
    }

    public function store(): void
    {
        $username = trim((string)$this->input('username', ''));
        $email = trim((string)$this->input('email', ''));
        $password = (string)$this->input('password', '');
        $fullName = trim((string)$this->input('full_name', ''));
        $phone = trim((string)$this->input('phone', ''));
        $roleId = (int)$this->input('role_id', 0);
        $status = trim((string)$this->input('status', 'active'));

        $_SESSION['old_input'] = $_POST;
        $fromModal = !empty($this->input('_from_modal'));
        $failRedirect = $fromModal ? url('admin/users') : url('admin/users/create');
        if ($fromModal) {
            $_SESSION['modal_mode'] = 'tambah';
        }

        if (empty($username) || strlen($username) < 3) {
            $_SESSION['flash_error'] = 'Username wajib diisi minimal 3 karakter.';
            $this->redirect($failRedirect);
            return;
        }

        if ($this->userModel->isUsernameTaken($username)) {
            $_SESSION['flash_error'] = "Username '{$username}' sudah digunakan pengguna lain.";
            $this->redirect($failRedirect);
            return;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Format email tidak valid.';
            $this->redirect($failRedirect);
            return;
        }

        if ($this->userModel->isEmailTaken($email)) {
            $_SESSION['flash_error'] = "Email '{$email}' sudah terdaftar dalam sistem.";
            $this->redirect($failRedirect);
            return;
        }

        if (empty($password) || strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Kata sandi minimal 6 karakter.';
            $this->redirect($failRedirect);
            return;
        }

        if ($roleId <= 0) {
            $_SESSION['flash_error'] = 'Silakan pilih peran (Role) yang valid.';
            $this->redirect($failRedirect);
            return;
        }

        $userId = $this->userModel->create([
            'role_id' => $roleId,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'full_name' => $fullName,
            'phone' => $phone,
            'status' => $status
        ]);

        if ($userId) {
            AuditLogger::log('CREATE_USER', 'users', $userId, null, [
                'username' => $username,
                'email' => $email,
                'role_id' => $roleId,
                'status' => $status
            ]);

            unset($_SESSION['old_input']);
            $_SESSION['flash_success'] = "Pengguna '{$fullName}' (@{$username}) berhasil dibuat.";
            $this->redirect(url('admin/users'));
        } else {
            $_SESSION['flash_error'] = 'Gagal menyimpan pengguna baru ke database.';
            $this->redirect(url('admin/users/create'));
        }
    }

    public function edit(): void
    {
        $id = (int)$this->input('id', 0);
        $user = $this->userModel->findWithRole($id);

        if (!$user) {
            $_SESSION['flash_error'] = 'Pengguna tidak ditemukan.';
            $this->redirect(url('admin/users'));
            return;
        }

        $this->render('admin/users/edit', [
            'title' => 'Edit Pengguna - ' . e($user['full_name']),
            'user' => $user,
            'roles' => $this->roleModel->all(),
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    public function update(): void
    {
        $id = (int)$this->input('id', 0);
        $user = $this->userModel->findWithRole($id);

        if (!$user) {
            $_SESSION['flash_error'] = 'Pengguna tidak ditemukan.';
            $this->redirect(url('admin/users'));
            return;
        }

        $username = trim((string)$this->input('username', ''));
        $email = trim((string)$this->input('email', ''));
        $fullName = trim((string)$this->input('full_name', ''));
        $phone = trim((string)$this->input('phone', ''));
        $roleId = (int)$this->input('role_id', 0);
        $status = trim((string)$this->input('status', 'active'));

        $fromModal = !empty($this->input('_from_modal'));
        $failRedirect = $fromModal ? url('admin/users') : url('admin/users/edit?id=' . $id);
        if ($fromModal) {
            $_SESSION['modal_mode'] = 'edit';
            $_SESSION['old_input'] = $_POST;
            $_SESSION['old_input']['_edit_id'] = $id;
        }

        if (empty($username) || empty($email) || empty($fullName) || $roleId <= 0) {
            $_SESSION['flash_error'] = 'Semua field wajib diisi lengkap.';
            $this->redirect($failRedirect);
            return;
        }

        if ($this->userModel->isUsernameTaken($username, $id)) {
            $_SESSION['flash_error'] = "Username '{$username}' sudah digunakan.";
            $this->redirect($failRedirect);
            return;
        }

        if ($this->userModel->isEmailTaken($email, $id)) {
            $_SESSION['flash_error'] = "Email '{$email}' sudah digunakan.";
            $this->redirect($failRedirect);
            return;
        }

        $success = $this->userModel->update($id, [
            'role_id' => $roleId,
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'phone' => $phone,
            'status' => $status
        ]);

        if ($success) {
            AuditLogger::log('UPDATE_USER', 'users', $id, [
                'username' => $user['username'],
                'role_id' => $user['role_id'],
                'status' => $user['status']
            ], [
                'username' => $username,
                'role_id' => $roleId,
                'status' => $status
            ]);

            unset($_SESSION['old_input']);
            unset($_SESSION['modal_mode']);
            $_SESSION['flash_success'] = "Data pengguna '{$fullName}' berhasil diperbarui.";
            $this->redirect(url('admin/users'));
        } else {
            $_SESSION['flash_error'] = 'Gagal memperbarui pengguna.';
            $this->redirect($failRedirect);
        }
    }

    public function toggleStatus(): void
    {
        $id = (int)$this->input('id', 0);
        $user = $this->userModel->find($id);

        if (!$user) {
            $_SESSION['flash_error'] = 'Pengguna tidak ditemukan.';
            $this->redirect(url('admin/users'));
            return;
        }

        if ($id === AuthService::id()) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menonaktifkan akun sendiri.';
            $this->redirect(url('admin/users'));
            return;
        }

        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        $this->userModel->updateStatus($id, $newStatus);

        AuditLogger::log('TOGGLE_USER_STATUS', 'users', $id, ['status' => $user['status']], ['status' => $newStatus]);

        $_SESSION['flash_success'] = "Status akun '{$user['username']}' berhasil diubah menjadi " . strtoupper($newStatus) . ".";
        $this->redirect(url('admin/users'));
    }

    public function resetPassword(): void
    {
        $id = (int)$this->input('id', 0);
        $newPass = trim((string)$this->input('new_password', ''));

        $user = $this->userModel->find($id);
        if (!$user) {
            $_SESSION['flash_error'] = 'Pengguna tidak ditemukan.';
            $this->redirect(url('admin/users'));
            return;
        }

        if (empty($newPass) || strlen($newPass) < 6) {
            $_SESSION['flash_error'] = 'Kata sandi baru minimal 6 karakter.';
            $this->redirect(url('admin/users'));
            return;
        }

        $this->userModel->resetPassword($id, $newPass);

        AuditLogger::log('RESET_PASSWORD', 'users', $id, null, ['status' => 'password_reset']);

        $_SESSION['flash_success'] = "Kata sandi pengguna '{$user['username']}' berhasil direset.";
        $this->redirect(url('admin/users'));
    }

    public function delete(): void
    {
        $id = (int)$this->input('id', 0);

        if ($id === AuthService::id()) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun Anda sendiri.';
            $this->redirect(url('admin/users'));
            return;
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $_SESSION['flash_error'] = 'Pengguna tidak ditemukan.';
            $this->redirect(url('admin/users'));
            return;
        }

        $success = $this->userModel->delete($id);
        if ($success) {
            AuditLogger::log('DELETE_USER', 'users', $id, ['username' => $user['username']], null);
            $_SESSION['flash_success'] = "Pengguna '{$user['username']}' berhasil dihapus.";
        } else {
            $_SESSION['flash_error'] = 'Gagal menghapus pengguna karena memiliki relasi transaksi dalam database.';
        }

        $this->redirect(url('admin/users'));
    }
}
