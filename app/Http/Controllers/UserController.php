<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

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
        if($request->has('active')){
            $user->active = 1;
        }else{
            //Check if there are more admins
            $admins = User::where('admin', 1)->where('active', 1)->get();
            if((count($admins) <= 1) && ($user->admin == 1)){
                return redirect(route('user.index'))->with('warning', 'There must be at least one admin active!!! Contact Prvstorage admin.');
            }
            $user->active = 0;
        }
        if($request->has('admin')){
            $user->admin = 1;
        }else{
            //Check if there are more admins
            $admins = User::where('admin', 1)->where('active', 1)->get();
            if((count($admins) <= 1) && ($user->admin == 1)){
                return redirect(route('user.index'))->with('warning', 'There must be at least one admin active!!! Contact Prvstorage admin.');
            }
            $user->admin = 0;
        }
        $user->save();
        
        return redirect(route('user.index'));
    }

    public function destroy(User $user)
    {
        $users = User::orderBy('email', 'asc')->get();
        return view('user.index', compact('users'));
    }
    public function admins()
    {
        $admins = User::where('active', 1)->where('admin', 1)->get();
        return view('user.admins', compact('admins'));
    }
}
