<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:10|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        //Create first admin account
        $admins = User::where('admin', 1)->get();

        if (count($admins) == 0) {
            $user->admin = 1;
            $user->active = 1;
            $user->suadmin = 1;
            //Create local network share folder
            Storage::disk('local')->makeDirectory('NShare');
        } else {
            $user->admin = 0;
            $user->active = 0;
            $user->suadmin = 0;
        }
        $user->save();

        //Create private folder
        Storage::disk('local')->makeDirectory($user->name);
        //Create temp folder for archives
        Storage::disk('local')->makeDirectory($user->name . '/ZTemp');

        event(new Registered($user));

        //Send mail to admins to activate accounts but not for first admin
        if (count($admins) == 0) {
            // No mail sent if no admins     
        } else {
            // Send mails to Prvstorage admins if application EMAIL is configured
            if (env('MAIL_CONFIGURATION') == true) {
                $details = [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_id' => $user->id,
                ];
                $admins = User::where('admin', 1)->get();
                foreach ($admins as $admin) {
                    Mail::to($admin->email)->send(new \App\Mail\NewUser($details));
                }
            }
        }
        Auth::login($user);
        return redirect(RouteServiceProvider::HOME);
    }
}
