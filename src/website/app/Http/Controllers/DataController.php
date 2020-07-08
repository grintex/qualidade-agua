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
        $term = $request->get('q', '');

        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                dados_coletados
            WHERE
                identificacao_corpo_hidrico LIKE :term OR
                bacia LIKE :term OR
                municipio LIKE :term
            GROUP BY
                identificacao_corpo_hidrico, bacia, municipio, ponto_referencia
            LIMIT 10");
        
        $stmt->bindValue(':term', '%' . $term . '%');
        $stmt->execute();

        $result = [];
        
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
}