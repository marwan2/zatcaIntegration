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

    public function showUser(Request $req) {
        return view('users.show');
    }

    public function refreshToken(Request $req) {
        $token = \Str::random(80);
        auth()->user()->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return redirect()->to('account')->with(['token'=>$token]);
    }
}
