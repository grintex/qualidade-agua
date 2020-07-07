<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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
            $this->info("- $table");
            $this->comment("  name: ${settings['name']}");
            $this->comment("  url: ${settings['url']}");
        }

        $ok = $gs->fetchAll();

        if(!$ok) {
            $this->error('Unable to fetch data.');
            exit(1);
        }

        $data = $gs->data();
        
        $this->info("\nPlacing data into database");
        $this->insertIntoDatabase($data, $spreadsheets);

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

    private function getSchema($tableName, $config)
    {
        $schema = [
            'type' => [],
            'indexes' => []
        ];

        if(isset($config[$tableName]['schema'])) {
            $schema = $config[$tableName]['schema'];
        }

        return $schema;
    }

    private function castValuesUsingSchema($columns, $values, $schema)
    {
        return $values;
    }

    private function mvTable($oldTable, $newTable)
    {
        $pdo = DB::connection()->getPdo();

        $newTableTemp = $newTable . '__REMOVE';

        $pdo->beginTransaction();

        $this->comment("  Renaming table $newTable to $newTableTemp");
        $sql = "ALTER TABLE $newTable RENAME TO $newTableTemp";
        $pdo->exec($sql);

        $this->comment("  Renaming table $oldTable to $newTable");
        $sql = "ALTER TABLE $oldTable RENAME TO $newTable";
        $pdo->exec($sql);

        $this->comment('  Dropping table ' . $newTableTemp);
        $sql = "DROP TABLE IF EXISTS $newTableTemp";
        $pdo->exec($sql);

        $pdo->commit();
    }

    private function dropThenCreateTable($name, $columns, $schema)
    {
        $pdo = DB::connection()->getPdo();

        $this->comment('  Dropping table ' . $name);
        $sql = 'DROP TABLE IF EXISTS '. $name;
        $pdo->exec($sql);

        $this->createTable($name, $columns, $schema);
    }

    private function createTable($name, $columns, $schema)
    {
        $pdo = DB::connection()->getPdo();

        $items = [];

        foreach($columns as $column) {
            $type = isset($schema['types'][$column]) ? $schema['types'][$column] : 'TEXT';
            $def = "$column $type NOT NULL";

            $items[] = $def;
        }

        $this->comment('  Creating table ' . $name);
        $sql = 'CREATE TABLE IF NOT EXISTS '. $name .' ('. implode(', ', $items).');';
        $pdo->exec($sql);
    }

    private function insertIntoTable($table, $columns, $rows)
    {
        $pdo = DB::connection()->getPdo();

        $insertColumns = implode(', ', $columns);
            
        $insertQuestionMarks = str_repeat('?,', count($columns));
        $insertQuestionMarks = substr($insertQuestionMarks, 0, -1);

        $sql = "INSERT INTO $table ($insertColumns) VALUES ($insertQuestionMarks)";
        $stmt = $pdo->prepare($sql);
        
        $this->comment(sprintf('  Adding %d rows', count($rows)));

        $pdo->beginTransaction();

        foreach($rows as $values) {
            $stmt->execute($values);
        }

        $pdo->commit();
    }

    private function insertIntoDatabase($spreadsheetsData, $config)
    {
        $pdo = DB::connection()->getPdo();

        if(count($spreadsheetsData) == 0) {
            throw new \Exception('Unable to insert spreadsheet data into database, invalid source.');
        }

        foreach($spreadsheetsData as $table => $rows) {
            $schema = $this->getSchema($table, $config);
            $insertValues = [];
             
            foreach($rows as $row) {
                $names = array_keys($row);
                $columns = $this->slugfyArray($names);

                $values = array_values($row);
                $values = $this->castValuesUsingSchema($columns, $values, $schema);
                
                $insertValues[] = $values;
            }

            $this->info("- $table");

            // Se for a primeirissima vez que estamos sincronizando, precisamos criar
            // a tabela (se ela não existir), porque o banco pode estar vazio
            $this->createTable($table, $columns, $schema);


            // Inserimos todos os dados em uma tabela temporária, depois usamos
            // ela para substituir a tabela oficial.
            $tableTemp = $table . '__TEMP';

            $this->dropThenCreateTable($tableTemp, $columns, $schema);
            $this->insertIntoTable($tableTemp, $columns, $insertValues);
            $this->mvTable($tableTemp, $table);
        }
    }
}
