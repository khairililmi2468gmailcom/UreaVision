<?php
namespace App\Controllers;

use CodeIgniter\Controller;

class Chart extends Controller
{
    public function index()
    {
        $data = [
            'header' => view('partials/header'),
            'sidebar' => view('partials/sidebar'),
            'navbar' => view('partials/navbar'),
            'chart_view' => view('chart_view'),
            'footer' => view('partials/footer') // Ensure footer is correctly set here

        ];

        return view('layout', $data);
    }
}