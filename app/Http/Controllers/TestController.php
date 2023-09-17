<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Models\Share;
use Illuminate\Support\Facades\Cache;

class TestController extends Controller
{
    public function test(Request $request)
    {
        $collection = collect(Storage::allDirectories(auth()->user()->name));

        $directories = $collection->reject(function ($value, $key) {
            return $value == auth()->user()->name."/ZTemp";
        });

        $treeCollection = $directories->map(function ($item) {
            $dir = substr($item, strlen(auth()->user()->name));
            return explode('/', $dir);
        });
        $userRoot = $this->convertPathsToTree($treeCollection)->first();

       // dd($userRoot['children']);

        $html = '<ul id="treeView" class="browser-default left-align">';
        $html .= '<li><span class="folder-tree-root"></span>';
        $html .= '<a class="blue-grey-text text-darken-3"   href="' . route('folder.root', ['current_folder' => '']) . '" data-folder="Root" data-folder-view="Root">Root</a></li>';
        $html .= $this->generateView($userRoot['children']);
        $html .='</ul>';

        return view('test.index', compact('html'));
    }

    //PRIVATE FUNCTIONS
    private function generateView($directories)
    {
       // dd($directories);
        $view = '';   
        foreach ($directories as $directory) {
            $withChildren = count($directory['children']) > 0 ? true : false;
            $view .= '<li>';
            if($withChildren){
                $view .= '<span class="folder-tree"></span>';
                $view .= '<a class="blue-grey-text text-darken-3" href="' . route('folder.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path']. '" data-folder-view ="' . $directory['label'] . '">';
                $view .= $directory['label'] . '</a>';
                $view .= '<ul class="nested browser-default" style="padding-left: 20px;">';
                $view .= $this->generateView($directory['children']);
                $view .= '</ul>';

            }else{
                $view .= '<span class="folder-tree-empty"></span>';
                $view .= '<a class="blue-grey-text text-darken-3" href="' . route('folder.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path']. '" data-folder-view ="' . $directory['label'] . '">';
                $view .= $directory['label'] . '</a>';
            }
            $view .= '</li>';
            
        }

        return $view;
    }

    private function convertPathsToTree($paths, $separator = '/', $parent = null)
    {
        return $paths
            ->groupBy(function ($parts) {
                return $parts[0];
            })->map(function ($parts, $key) use ($separator, $parent) {
                $childrenPaths = $parts->map(function ($parts) {
                    return array_slice($parts, 1);
                })->filter();

                return [
                    'label' => (string) $key,
                    'path' => $parent . $key,
                    'children' => $this->convertPathsToTree(
                        $childrenPaths,
                        $separator,
                        $parent . $key . $separator
                    ),
                ];
            })->values();
    }
}
