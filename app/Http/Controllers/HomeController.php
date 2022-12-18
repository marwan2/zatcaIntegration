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

    public function getPassword() {
        return view('users.password');
    }

    public function postPassword(Request $request) {
        $user = Auth::user();
        $rules = [
            'old_password'=>'required',
            'password'=>'required|min:6|max:16|confirmed',
            'password_confirmation'=>'required|',
        ];

        $this->validate($request, $rules);

        if(\Hash::check($request->get('old_password'), $user->password)) {
            $user->password = \Hash::make($request->get('password'));
            if($user->save()) {
                session()->flash('alert', 'success');
                session()->flash('flash_message', __('Password changed successfully'));
                return redirect('account');
            }
        } else {
            session()->flash('alert', 'danger');
            session()->flash('flash_message', 'Error occured, please try again');
            return redirect()->back();
        }
        return redirect()->to('/');
    }
}
