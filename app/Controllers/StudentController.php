<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Student;
use App\Services\AuditLogger;

class StudentController extends Controller
{
    protected Student $studentModel;

    public function __construct()
    {
        $this->studentModel = new Student();
    }

    public function index(): void
    {
        $page = (int)$this->input('page', 1);
        $search = trim((string)$this->input('search', ''));
        $prodi = trim((string)$this->input('study_program', ''));
        $year = trim((string)$this->input('academic_year', ''));
        $status = trim((string)$this->input('status', ''));
        $faculty = trim((string)$this->input('faculty', ''));

        $filters = array_filter([
            'search' => $search,
            'study_program' => $prodi,
            'academic_year' => $year,
            'status' => $status,
            'faculty' => $faculty
        ]);

        $result = $this->studentModel->paginate($page, 10, $filters);

        $this->render('admin/students/index', [
            'title' => 'Master Data Mahasiswa - Smart Campus Cash',
            'students' => $result['data'],
            'total' => $result['total'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'search' => $search,
            'selectedProdi' => $prodi,
            'selectedYear' => $year,
            'selectedStatus' => $status,
            'selectedFaculty' => $faculty,
            'prodiList' => $this->studentModel->getDistinctStudyPrograms(),
            'yearList' => $this->studentModel->getDistinctAcademicYears(),
            'success' => flash('flash_success'),
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    public function create(): void
    {
        $this->render('admin/students/create', [
            'title' => 'Tambah Mahasiswa Baru - Smart Campus Cash',
            'error' => flash('flash_error'),
            'old' => $_SESSION['old_input'] ?? []
        ], 'layouts/app');
        unset($_SESSION['old_input']);
    }

    public function store(): void
    {
        $nim = trim((string)$this->input('nim', ''));
        $name = trim((string)$this->input('full_name', ''));
        $email = trim((string)$this->input('email', ''));
        $phone = trim((string)$this->input('phone', ''));
        $faculty = trim((string)$this->input('faculty', 'Teknik & Informatika'));
        $prodi = trim((string)$this->input('study_program', ''));
        $year = trim((string)$this->input('academic_year', ''));
        $semester = (int)$this->input('current_semester', 1);
        $classGroup = trim((string)$this->input('class_group', 'A'));
        $status = trim((string)$this->input('status', 'active'));

        $_SESSION['old_input'] = $_POST;
        $fromModal = !empty($this->input('_from_modal'));
        $failRedirect = $fromModal ? url('admin/students') : url('admin/students/create');
        if ($fromModal) {
            $_SESSION['modal_mode'] = 'tambah';
        }

        // Server-side validation
        if (empty($nim) || strlen($nim) < 4) {
            $_SESSION['flash_error'] = 'NIM wajib diisi minimal 4 karakter.';
            $this->redirect($failRedirect);
            return;
        }

        if ($this->studentModel->isNimTaken($nim)) {
            $_SESSION['flash_error'] = "NIM '{$nim}' sudah terdaftar dalam sistem. Gunakan NIM lain.";
            $this->redirect($failRedirect);
            return;
        }

        if (empty($name)) {
            $_SESSION['flash_error'] = 'Nama lengkap mahasiswa wajib diisi.';
            $this->redirect($failRedirect);
            return;
        }

        if (empty($prodi)) {
            $_SESSION['flash_error'] = 'Program studi wajib diisi.';
            $this->redirect($failRedirect);
            return;
        }

        if (empty($year)) {
            $_SESSION['flash_error'] = 'Angkatan/Tahun Masuk wajib diisi.';
            $this->redirect($failRedirect);
            return;
        }

        $studentId = $this->studentModel->create([
            'nim' => $nim,
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'faculty' => $faculty,
            'study_program' => $prodi,
            'academic_year' => $year,
            'current_semester' => $semester,
            'class_group' => $classGroup,
            'status' => $status
        ]);

        if ($studentId) {
            AuditLogger::log('CREATE_STUDENT', 'students', $studentId, null, [
                'nim' => $nim,
                'full_name' => $name,
                'study_program' => $prodi
            ]);

            unset($_SESSION['old_input']);
            unset($_SESSION['modal_mode']);
            $_SESSION['flash_success'] = "Mahasiswa '{$name}' (NIM: {$nim}) berhasil ditambahkan ke master data.";
            $this->redirect(url('admin/students'));
        } else {
            $_SESSION['flash_error'] = 'Terjadi kesalahan sistem saat menyimpan data mahasiswa.';
            $this->redirect($failRedirect);
        }
    }

    public function detail(): void
    {
        $id = (int)$this->input('id', 0);
        $student = $this->studentModel->findWithUser($id);

        if (!$student) {
            $_SESSION['flash_error'] = 'Data mahasiswa tidak ditemukan.';
            $this->redirect(url('admin/students'));
            return;
        }

        $this->render('admin/students/detail', [
            'title' => 'Detail Profil Mahasiswa - ' . e($student['full_name']),
            'student' => $student
        ], 'layouts/app');
    }

    public function edit(): void
    {
        $id = (int)$this->input('id', 0);
        $student = $this->studentModel->findWithUser($id);

        if (!$student) {
            $_SESSION['flash_error'] = 'Data mahasiswa tidak ditemukan.';
            $this->redirect(url('admin/students'));
            return;
        }

        $this->render('admin/students/edit', [
            'title' => 'Edit Mahasiswa - ' . e($student['full_name']),
            'student' => $student,
            'error' => flash('flash_error')
        ], 'layouts/app');
    }

    public function update(): void
    {
        $id = (int)$this->input('id', 0);
        $student = $this->studentModel->findWithUser($id);

        if (!$student) {
            $_SESSION['flash_error'] = 'Data mahasiswa tidak ditemukan.';
            $this->redirect(url('admin/students'));
            return;
        }

        $nim = trim((string)$this->input('nim', ''));
        $name = trim((string)$this->input('full_name', ''));
        $email = trim((string)$this->input('email', ''));
        $phone = trim((string)$this->input('phone', ''));
        $faculty = trim((string)$this->input('faculty', 'Teknik & Informatika'));
        $prodi = trim((string)$this->input('study_program', ''));
        $year = trim((string)$this->input('academic_year', ''));
        $semester = (int)$this->input('current_semester', 1);
        $classGroup = trim((string)$this->input('class_group', 'A'));
        $status = trim((string)$this->input('status', 'active'));

        $fromModal = !empty($this->input('_from_modal'));
        $failRedirect = $fromModal ? url('admin/students') : url('admin/students/edit?id=' . $id);
        if ($fromModal) {
            $_SESSION['modal_mode'] = 'edit';
            $_SESSION['old_input'] = $_POST;
            $_SESSION['old_input']['_edit_id'] = $id;
        }

        if (empty($nim)) {
            $_SESSION['flash_error'] = 'NIM mahasiswa tidak boleh kosong.';
            $this->redirect($failRedirect);
            return;
        }

        if ($this->studentModel->isNimTaken($nim, $id)) {
            $_SESSION['flash_error'] = "NIM '{$nim}' sudah digunakan oleh mahasiswa lain.";
            $this->redirect($failRedirect);
            return;
        }

        if (empty($name) || empty($prodi)) {
            $_SESSION['flash_error'] = 'Nama lengkap dan Program Studi wajib diisi.';
            $this->redirect($failRedirect);
            return;
        }

        $success = $this->studentModel->update($id, [
            'nim' => $nim,
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'faculty' => $faculty,
            'study_program' => $prodi,
            'academic_year' => $year,
            'current_semester' => $semester,
            'class_group' => $classGroup,
            'status' => $status
        ]);

        if ($success) {
            AuditLogger::log('UPDATE_STUDENT', 'students', $id, [
                'nim' => $student['nim'],
                'full_name' => $student['full_name'],
                'status' => $student['status']
            ], [
                'nim' => $nim,
                'full_name' => $name,
                'status' => $status
            ]);

            unset($_SESSION['old_input']);
            unset($_SESSION['modal_mode']);
            $_SESSION['flash_success'] = "Data mahasiswa '{$name}' berhasil diperbarui.";
            $this->redirect(url('admin/students'));
        } else {
            $_SESSION['flash_error'] = 'Gagal memperbarui data mahasiswa.';
            $this->redirect($failRedirect);
        }
    }

    public function delete(): void
    {
        $id = (int)$this->input('id', 0);
        $student = $this->studentModel->findWithUser($id);

        if (!$student) {
            $_SESSION['flash_error'] = 'Data mahasiswa tidak ditemukan.';
            $this->redirect(url('admin/students'));
            return;
        }

        $success = $this->studentModel->softDelete($id);

        if ($success) {
            AuditLogger::log('DELETE_STUDENT', 'students', $id, [
                'nim' => $student['nim'],
                'full_name' => $student['full_name']
            ], null);

            $_SESSION['flash_success'] = "Data mahasiswa '{$student['full_name']}' (NIM: {$student['nim']}) berhasil dihapus.";
        } else {
            $_SESSION['flash_error'] = 'Gagal menghapus data mahasiswa.';
        }

        $this->redirect(url('admin/students'));
    }
}
