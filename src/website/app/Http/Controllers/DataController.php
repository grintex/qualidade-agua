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
        $spreadsheets = config('data.spreadsheets');
        $gs = new GoogleSpreadSheet($spreadsheets);

        $ok = $gs->fetchAll();

        if(!$ok) {
            throw new \Exception('Unable to fetch data.');
        }

        return $gs->data();
    }
}