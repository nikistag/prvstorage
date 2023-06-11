<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use ZipArchive;

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

    public function view(User $user)
    {
        if (auth()->user()->id == $user->id) { //Own account
            return view('user.view', compact('user'));
        } else {
            return redirect(route('home'))->with('error', 'This account does not belong to you!!!');
        }
    }

    public function update(Request $request, User $user)
    {
        $initialStatus = $user->active;
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

        $updatedStatus = $user->active;

        if (env('MAIL_CONFIGURATION') == true) {
            if ($updatedStatus != $initialStatus) {
                if ($updatedStatus == 1) {
                    $message = "has been granted access to " . env('APP_NAME') . ' private storage. Click button below to get to your private storage.';
                    $access = 1;
                } else {
                    $message = " no longer has access to " . env('APP_NAME') . ' private storage. Click button below if you wat to contact administrator.';
                    $access = 0;
                }
                $details = [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_id' => $user->id,
                    'message' => $message,
                    'access' => $access,
                ];
                try {
                    Mail::to($user->email)->send(new \App\Mail\AccountStatusNotification($details));
                } catch (Exception $ex) {
                    dd($ex->getMessage());
                }
            }
        }

        return redirect(route('user.index'));
    }

    public function destroy(Request $request)
    {
        //dd($request->input());
        $user = User::where('id', $request->userid)->first();
        if ((auth()->user()->suadmin == 1) && ($user->id != auth()->user()->id)) {
            //Check if needs backup
            if ($request->input('backup') != null) { //Backup of user storage required

                $path = '/' . $request->userName;

                Storage::deleteDirectory($path . "/ZTemp"); //Empty user temp folder

                $file_full_paths = Storage::allFiles($path);

                $directory_full_paths = Storage::allDirectories($path);

                $zip_directory_paths = [];

                foreach ($directory_full_paths as $dir) {
                    array_push($zip_directory_paths, substr($dir, strlen($path) - 1));
                }

                $zipFileName = 'deleted_' . $request->userName . '.zip';

                //Backup folder
                $backupFolder = '/' . auth()->user()->name . '/DeletedAccountsBackup';

                // Check if Backup folder exists and create if needed
                if (Storage::exists($backupFolder)) {
                } else {
                    Storage::makeDirectory($backupFolder);
                }

                $zip_path = Storage::path($backupFolder . '/' . $zipFileName);

                // Creating file names and path names to be archived
                $files_n_paths = [];
                foreach ($file_full_paths as $fl) {
                    array_push($files_n_paths, [
                        'name' => substr($fl, strripos($fl, '/') + 1),
                        'path' => Storage::path($fl),
                        'zip_path' => substr($fl, strlen($path)),
                    ]);
                }

                $zip = new ZipArchive();
                if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    //Add folders to archive
                    foreach ($zip_directory_paths as $zip_directory) {
                        $zip->addEmptyDir($zip_directory);
                    }
                    // Add Files in ZipArchive
                    foreach ($files_n_paths as $file) {
                        $zip->addFile($file['path']);
                    }
                    // Close ZipArchive     
                    $zip->close();
                }
            }
            //Delete user directory
            $user_folder = "/" . $user->name;
            Storage::deleteDirectory($user_folder);
            //Delete user thumb directory
            Storage::disk('public')->deleteDirectory("/thumb".$user_folder);
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

    public function purge(Request $request)
    {
        $user = User::where('id', $request->userid)->first();
        if (auth()->user()->id == $request->userid) { //Check for user tries to purge own account
            //Delete user directory
            $user_folder = "/" . $user->name;
            Storage::deleteDirectory($user_folder);

            //Delete user thumb directory
            Storage::disk('public')->deleteDirectory("/thumb".$user_folder);
            //Delete user shares from DB
            $deleted = DB::table('shares')->where('user_id', $user->id)->delete();
            //Delete user from user table
            $user->delete();
            //Logout user
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect(route('home'))->with('success', 'Account for user ' . $user->name . ' with email ' . $user->email . ' has been purged');
        } else {
            return redirect(route('user.index'))->with('error', 'You don\'t have permission to purge this user account!!!');
        }
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
