<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkController extends Controller
{
    public function makedir(){
        Storage::disk('local')->makeDirectory('test');

    }
}
