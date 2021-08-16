<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FolderController extends Controller
{
    public function index(){
        $directories = Storage::allDirectories(storage_path('app/prv/'.auth()->user()->name));
        //dd($directories);
        $disk_free_space = round(disk_free_space(storage_path('app/prv/'.auth()->user()->name))/1073741824,2);
        $disk_total_space = round(disk_total_space(storage_path('app/prv/'.auth()->user()->name))/1073741824,2);
        return view('folder.index', compact('directories', 'disk_free_space', 'disk_total_space'));
    }

    public function ceva(){
        $directories = Storage::allDirectories(storage_path('app/prv/'.auth()->user()->name));
        dd($directories);
    }
}
