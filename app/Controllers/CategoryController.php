<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Services\AuditLogger;

class CategoryController extends Controller
{
    protected Category $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
    }

    public function index(): void
    {
        $page = (int)$this->input('page', 1);
        $search = trim((string)$this->input('search', ''));
        $type = trim((string)$this->input('type', ''));
        $status = $this->input('is_active', '');

        $filters = array_filter([
            'search' => $search,
            'type' => $type,
            'is_active' => $status
        ], fn($val) => $val !== '' && $val !== null);

        $result = $this->categoryModel->paginate($page, 10, $filters);

        $this->render('admin/categories/index', [
            'title' => 'Master Kategori Transaksi - Smart Campus Cash',
            'categories' => $result['data'],
            'total' => $result['total'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'search' => $search,
            'selectedType' => $type,
            'selectedStatus' => $status,
            'success' => flash('flash_success'),
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    public function create(): void
    {
        $this->render('admin/categories/create', [
            'title' => 'Tambah Kategori Transaksi - Smart Campus Cash',
            'error' => flash('flash_error'),
            'old' => $_SESSION['old_input'] ?? []
        ], 'layouts/app');
        unset($_SESSION['old_input']);
    }

    public function store(): void
    {
        $code = strtoupper(trim((string)$this->input('code', '')));
        $name = trim((string)$this->input('name', '')));
        $type = strtolower(trim((string)$this->input('type', 'income')));
        $desc = trim((string)$this->input('description', ''));
        $status = (int)$this->input('is_active', 1);

        $_SESSION['old_input'] = $_POST;

        if (empty($code)) {
            $_SESSION['flash_error'] = 'Kode kategori wajib diisi (contoh: KAT-SPP, KAT-OPR).';
            $this->redirect(url('admin/categories/create'));
            return;
        }

        if ($this->categoryModel->isCodeTaken($code)) {
            $_SESSION['flash_error'] = "Kode kategori '{$code}' sudah ada. Gunakan kode lain.";
            $this->redirect(url('admin/categories/create'));
            return;
        }

        if (empty($name)) {
            $_SESSION['flash_error'] = 'Nama kategori wajib diisi.';
            $this->redirect(url('admin/categories/create'));
            return;
        }

        if (!in_array($type, ['income', 'expense'])) {
            $_SESSION['flash_error'] = 'Tipe kategori harus pemasukan (income) atau pengeluaran (expense).';
            $this->redirect(url('admin/categories/create'));
            return;
        }

        $id = $this->categoryModel->create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'description' => $desc,
            'is_active' => $status
        ]);

        if ($id) {
            AuditLogger::log('CREATE_CATEGORY', 'categories', $id, null, [
                'code' => $code,
                'name' => $name,
                'type' => $type
            ]);

            unset($_SESSION['old_input']);
            $_SESSION['flash_success'] = "Kategori '{$name}' ({$code}) berhasil ditambahkan.";
            $this->redirect(url('admin/categories'));
        } else {
            $_SESSION['flash_error'] = 'Gagal menyimpan kategori baru.';
            $this->redirect(url('admin/categories/create'));
        }
    }

    public function edit(): void
    {
        $id = (int)$this->input('id', 0);
        $category = $this->categoryModel->find($id);

        if (!$category) {
            $_SESSION['flash_error'] = 'Kategori transaksi tidak ditemukan.';
            $this->redirect(url('admin/categories'));
            return;
        }

        $this->render('admin/categories/edit', [
            'title' => 'Edit Kategori Transaksi - ' . e($category['name']),
            'category' => $category,
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    public function update(): void
    {
        $id = (int)$this->input('id', 0);
        $category = $this->categoryModel->find($id);

        if (!$category) {
            $_SESSION['flash_error'] = 'Kategori transaksi tidak ditemukan.';
            $this->redirect(url('admin/categories'));
            return;
        }

        $code = strtoupper(trim((string)$this->input('code', '')));
        $name = trim((string)$this->input('name', '')));
        $type = strtolower(trim((string)$this->input('type', 'income')));
        $desc = trim((string)$this->input('description', ''));
        $status = (int)$this->input('is_active', 1);

        if (empty($code) || empty($name)) {
            $_SESSION['flash_error'] = 'Kode dan nama kategori wajib diisi.';
            $this->redirect(url('admin/categories/edit?id=' . $id));
            return;
        }

        if ($this->categoryModel->isCodeTaken($code, $id)) {
            $_SESSION['flash_error'] = "Kode kategori '{$code}' sudah digunakan.";
            $this->redirect(url('admin/categories/edit?id=' . $id));
            return;
        }

        $success = $this->categoryModel->update($id, [
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'description' => $desc,
            'is_active' => $status
        ]);

        if ($success) {
            AuditLogger::log('UPDATE_CATEGORY', 'categories', $id, [
                'code' => $category['code'],
                'name' => $category['name'],
                'type' => $category['type']
            ], [
                'code' => $code,
                'name' => $name,
                'type' => $type
            ]);

            $_SESSION['flash_success'] = "Kategori '{$name}' berhasil diperbarui.";
            $this->redirect(url('admin/categories'));
        } else {
            $_SESSION['flash_error'] = 'Gagal memperbarui kategori.';
            $this->redirect(url('admin/categories/edit?id=' . $id));
        }
    }

    public function delete(): void
    {
        $id = (int)$this->input('id', 0);
        $category = $this->categoryModel->find($id);

        if (!$category) {
            $_SESSION['flash_error'] = 'Kategori tidak ditemukan.';
            $this->redirect(url('admin/categories'));
            return;
        }

        if ($this->categoryModel->hasTransactions($id)) {
            $_SESSION['flash_error'] = "Kategori '{$category['name']}' tidak dapat dihapus karena telah digunakan pada catatan transaksi keuangan.";
            $this->redirect(url('admin/categories'));
            return;
        }

        $success = $this->categoryModel->delete($id);

        if ($success) {
            AuditLogger::log('DELETE_CATEGORY', 'categories', $id, [
                'code' => $category['code'],
                'name' => $category['name']
            ], null);

            $_SESSION['flash_success'] = "Kategori '{$category['name']}' berhasil dihapus.";
        } else {
            $_SESSION['flash_error'] = 'Gagal menghapus kategori.';
        }

        $this->redirect(url('admin/categories'));
    }
}
