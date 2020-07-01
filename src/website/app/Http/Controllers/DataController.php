<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\DataSource\GoogleSpreadSheet;

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
        $cacheDurationSeconds = $this->cache_duration_days * 24 * 60 * 60;

        $url = config('data.spreadsheet_url');
        $gs = new GoogleSpreadSheet($url);

        $items = $gs->fetch();

        //$items = Cache::remember('spreadhsheet_data', $cacheDurationSeconds, function () {
        //});

        return $items;
    }
}