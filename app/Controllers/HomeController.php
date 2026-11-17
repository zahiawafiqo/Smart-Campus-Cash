<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class HomeController extends Controller
{
    public function index(): void
    {
        $dbCheck = Database::checkConnection();

        $this->render('home/index', [
            'title' => 'Smart Campus Cash - Sistem Kas Keuangan Kampus',
            'dbStatus' => $dbCheck,
        ], 'layouts/guest');
    }
}
