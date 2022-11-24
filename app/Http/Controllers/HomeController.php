<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct() {
    }

    public function index() {
        return view('home');
    }

    public function download($file) {
        $filePath = storage_path('app/'.$file);
        if (! file_exists($filePath)) {
            throw new \Exception('File not found.');
        }

        return response()->download($filePath);
    }
}
