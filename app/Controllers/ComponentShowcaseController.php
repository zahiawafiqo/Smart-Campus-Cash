<?php

namespace App\Controllers;

use App\Core\Controller;

class ComponentShowcaseController extends Controller
{
    /**
     * Tampilkan dokumentasi dan contoh penggunaan seluruh reusable UI components
     */
    public function index(): void
    {
        $role = $this->input('role', 'admin');

        $this->render('showcase/index', [
            'title' => 'UI Component Showcase - Smart Campus Cash',
            'pageTitle' => 'UI Components Showcase',
            'activeRole' => $role
        ], 'layouts/app');
    }
}
