<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('email', 'asc')->get();
        return view('user.index', compact('users'));
    }

    public function edit(User $user)
    {
        return view('user.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $user->email = $request->input('email');

        if ($request->has('active')) {
            $user->active = 1;
        } else {
            $user->active = 0;
        }

        if ($request->has('admin')) {
            $user->admin = 1;
        } else {
            $user->admin = 0;
        }

        $user->save();

        return redirect(route('user.index'));
    }

    public function destroy(Request $request)
    {        
        $user = User::where('id', $request->userid)->first();
        if ((auth()->user()->suadmin == 1) && ($user->id != auth()->user()->id)) {
            //Purge user folders
            $user_folder = "/" . $user->name;
            Storage::deleteDirectory($user_folder);
            //Delete user shares from DB
            $deleted = DB::table('shares')->where('user_id', $user->id)->delete();
            //Delete user from user table
            $user->delete();
            return redirect(route('user.index'))->with('success', 'User ' . $user->name . ' with email ' . $user->email . ' has been deleted');
        } else {
            return redirect(route('user.index'))->with('error', 'You don\'t have permissions to delete this user!!!');
        }

        $users = User::orderBy('email', 'asc')->get();
        return view('user.index', compact('users'));
    }

    public function admins()
    {
        $admins = User::where('active', 1)->where('admin', 1)->get();
        return view('user.admins', compact('admins'));
    }

    public function emailTest()
    {
        $user = auth()->user();
        if (env('MAIL_CONFIGURATION') == true) {
            $details = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_id' => $user->id,
            ];
            try {
                Mail::to($user->email)->send(new \App\Mail\Test($details));
                return back()->with('success', 'Email successfully sent.!!!');
            } catch (Exception $ex) {
                // Debug via $ex->getMessage();
                return back()->with('error', 'Email configuration in .env file is incorrect or email service provider blocked this email!!!');
            }
        } else {
            return back()->with('error', 'Email configuration is .env file is set to null!!!');
        }
    }
}
