<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Share;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use DateTime;

class ShareController extends Controller
{
    public function index()
    {
        $old_shares = Share::where('user_id', auth()->user()->id)->get();

        foreach($old_shares as $share){
            if ($share->expiration < time()) {
                $share->status = 'expired';
                $share->save();
            }
        }
        $shares = Share::where('user_id', auth()->user()->id)->orderBy('expiration', 'desc')->get();

        $path = '/' . auth()->user()->name;
        $directories = Storage::allDirectories(storage_path($path));
        $disk_free_space = round(disk_free_space(storage_path('app/prv/')) / 1073741824, 2);
        $disk_total_space = round(disk_total_space(storage_path('app/prv/')) / 1073741824, 2);
        $quota = round(($disk_total_space - $disk_free_space) * 100 / $disk_total_space, 0);
        return view('share.index', compact('shares', 'quota', 'disk_free_space'));
    }
    public function createFile(Request $request)
    {
        $share_name = substr($request->input('fileshare'), strripos($request->input('fileshare'), '/') + 1);
        $zip_file_name = 'zpd_' . $share_name ."_". time() . ".zip";

        $zip_path = Storage::path(auth()->user()->name . '/ZTemp/' . $zip_file_name);
        $db_zip_path = '/' . auth()->user()->name . '/ZTemp/' . $zip_file_name;

        //Create archive
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add File in ZipArchive
            $zip->addFile(Storage::path($request->input('fileshare')), $share_name);
            // Close ZipArchive     
            $zip->close();
        }

        $share = new Share();
        $share->user_id = auth()->user()->id;
        $share->path = $db_zip_path;
        $share->code = hash('ripemd160', time());
        $share->expiration = time() + 259200;
        $share->status = 'active';
        $share->save();

        return view('share.create', compact('share', 'share_name'));
    }
    public function createFileMulti(Request $request)
    {
        
        $share_name = $request->input("fileshare")[0]."-multi";
        $zip_file_name = 'zpd_' . $share_name ."_". time() . ".zip";

        $zip_path = Storage::path(auth()->user()->name . '/ZTemp/' . $zip_file_name);
        $db_zip_path = '/' . auth()->user()->name . '/ZTemp/' . $zip_file_name;

        //Create archive
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add File in ZipArchive
            foreach($request->input("fileshare") as $file){
                $zip->addFile(Storage::path($request->input("path")."/".$file), $file);
            }            
            // Close ZipArchive     
            $zip->close();
        }

        $share = new Share();
        $share->user_id = auth()->user()->id;
        $share->path = $db_zip_path;
        $share->code = hash('ripemd160', time());
        $share->expiration = time() + 259200;
        $share->status = 'active';
        $share->save();
        return view('share.create', compact('share', 'share_name'));
    }
    public function createFolder(Request $request)
    {
        $path = $request->input('share');
        $share_name = substr($request->input('share'), strripos($request->input('share'), '/') + 1);
        $zip_file_name = 'zpd_' . $share_name ."_". time() . ".zip";

        $zip_path = Storage::path('/' . auth()->user()->name . '/ZTemp/' . $zip_file_name);
        $db_zip_path = '/' . auth()->user()->name . '/ZTemp/' . $zip_file_name;

        $file_full_paths = Storage::allFiles($path);

        $directory_full_paths = Storage::allDirectories($path);

        $zip_directory_paths = [];
        foreach ($directory_full_paths as $dir) {
            array_push($zip_directory_paths, substr($dir, strlen($path)));
        }

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
                $zip->addFile($file['path'], $file['zip_path']);
            }
            // Close ZipArchive     
            $zip->close();
        }


        $share = new Share();
        $share->user_id = auth()->user()->id;
        $share->path = $db_zip_path;
        $share->code = hash('ripemd160', time());
        $share->expiration = time() + 259200;
        $share->status = 'active';
        $share->save();

        return view('share.create', compact('share', 'share_name'));
    }

    public function download(Request $request)
    {
        if ($request->has('code')) {
            $share = Share::where('code', $request->code)->first();
            if (($share->expiration > time()) && ($share->status !== 'used')) {
                $share->status = 'used';
                $share->save();
                return Storage::download($share->path);
            } else {
                return redirect("/")->with("error", "Download link not available!!!");
            }
        } else {
            return redirect("/")->with("error", "Download link not available!!!");
        }
    }

    public function delete(Request $request){
        
        $share = Share::where('path', $request->input('filename'))->first();

        Storage::delete($share->path);

        $share->delete();

        return redirect(route('share.index'))->with('success', 'Shared file/folder removed');
    }

}
