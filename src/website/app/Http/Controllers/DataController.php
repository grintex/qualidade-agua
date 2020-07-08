<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    protected $cache_duration_days = 0.1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pdo = DB::connection()->getPdo();
        
        return ['test' => 'dd'];
    }

    /**
     * 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $pdo = DB::connection()->getPdo();
        $input = $request->all();
        
        return [
            ['id' => 1, 'text' => 'Google Cloud Platform', 'icon' => 'https://pbs.twimg.com/profile_images/966440541859688448/PoHJY3K8_400x400.jpg'],
            ['id' => 2, 'text' => 'Amazon', 'icon' => 'https://pbs.twimg.com/profile_images/966440541859688448/PoHJY3K8_400x400.jpg'],
            ['id' => 3, 'text' => 'Docker', 'icon' => 'https://pbs.twimg.com/profile_images/966440541859688448/PoHJY3K8_400x400.jpg']
        ];
    }
}