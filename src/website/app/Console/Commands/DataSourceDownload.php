<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\DataSource\GoogleSpreadSheet;

class DataSourceDownload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Baixa as planilhas de dados e as disponibiliza no banco de dados.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $spreadsheets = config('data.spreadsheets');
        $gs = new GoogleSpreadSheet($spreadsheets);

        $this->info("Fetching data from spreadsheets");

        foreach($spreadsheets as $table => $settings) {
            $this->info("* $table");
            $this->comment("  name: ${settings['name']}");
            $this->comment("  url: ${settings['url']}");
        }

        $ok = $gs->fetchAll();

        if(!$ok) {
            $this->error('Unable to fetch data.');
            exit(1);
        }

        $data = $gs->data();
        $this->insertIntoDatabase($data);

        $this->info("Finished successfully");
        exit(0);
    }

    private function slugfyName($name)
    {
        $maxParts = 4;

        $strsToReplace = [
            '(', ')', ' de ', ' do ', ' da ',
            ' pelo ', ' pela ', ' e ', ' a ',
            '/', '\\', '.', ',', ';'
        ];

        // Se tiver parenteses, checa o conteúdo deles
        preg_match_all("/\([^)]+\)/", $name, $matchParenthesis);
        $parenthesisContent = isset($matchParenthesis[0][0]) ? $matchParenthesis[0][0] : '';

        if(strlen($parenthesisContent) > 3) {
            // Muita coisa entre parenteses, removemos o parentes com tudo dentro.
            $name = str_replace($parenthesisContent, '', $name);
        }

        // Remove espaços repetidos
        $name = str_replace($strsToReplace, ' ', $name);
        $normalized = preg_replace('/\s+/', ' ', $name);

        // Garante palavras com menos de X partes
        $parts = explode(' ', $normalized, $maxParts);
        $usefulParts = array_slice($parts, 0, $maxParts - 1) ;
        $slug = implode('_', $usefulParts);
        $slug = Str::slug($slug, '_');

        return trim($slug);
    }

    private function slugfyArray($names)
    {
        if(!is_array($names)) {
            throw new \Exception('Param must be array, something else provided');
        }

        $out = [];

        foreach($names as $value) {
            $out[] = $this->slugfyName($value);
        }

        return $out;
    }

    private function insertIntoDatabase($spreadsheetsData)
    {
        if(count($spreadsheetsData) == 0) {
            throw new \Exception('Unable to insert spreadsheet data into database, invalid source.');
        }

        foreach($spreadsheetsData as $table => $rows) {
            foreach($rows as $row) {
                $names = array_keys($row);
                $values = array_values($row);
                $columns = $this->slugfyArray($names);
            }
        }
    }
}
