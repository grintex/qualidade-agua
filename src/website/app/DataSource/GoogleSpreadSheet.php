<?php

namespace App\DataSource;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GoogleSpreadSheet
{
    /**
     *
     * @var string
     */
    protected $spreadsheets = [];

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $spreadsheets_data = [];

    
    /**
     * Undocumented function
     *
     * @param string $spreadsheetURL URL to publicly available CSV web export of the spreasheet.
     */
    public function __construct(array $spreadsheets)
    {
        if(count($spreadsheets) == 0) {
            throw new \Exception('Invalid spreadsheets info');
        }

        $this->spreadsheets = $spreadsheets;
    }

    protected function assetSpreadsheetKeyExists($spreadsheet_key)
    {
        if(!isset($this->spreadsheets[$spreadsheet_key])) {
            throw new \Exception("Unknown spreadsheet with key '$spreadsheet_key'.");
        }
    }

    public function data($spreadsheet_key = null)
    {
        if($spreadsheet_key === null) {
            return $this->spreadsheets_data;
        }

        $this->assetSpreadsheetKeyExists($spreadsheet_key);

        $data = $this->spreadsheets_data[$spreadsheet_key];
        return $data;
    }

    protected function getContentFromURL($url)
    {
        $curl = curl_init($url);
    
        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        
        if (is_dir($caPathOrFile)) {
            curl_setopt($curl, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($curl, CURLOPT_CAINFO, $caPathOrFile);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $content = curl_exec($curl);

        return $content;
    }

    public function parseAsTSV($contents)
    {
        $hash = md5($contents);
        $fileName = $hash . '.tsv';

        // Um hack para garantir quebras de linha no conteúdo
        // da planilha. Me julgue.
        Storage::disk('local')->put($fileName, $contents);
        $path = Storage::disk('local')->path($fileName);
        $lines = file($path);

        $csv = [];
        foreach($lines as $line) {
            $cleanLine = str_replace(["\r\n", "\r", "\n"], ' ', $line);
            $csv[] = str_getcsv($cleanLine, "\t");
        }

        return $csv;
    }

    public function fetch($spreadsheet_key)
    {
        $this->assetSpreadsheetKeyExists($spreadsheet_key);
        
        $spreadsheet = $this->spreadsheets[$spreadsheet_key];

        $url = $spreadsheet['url'];
        $headerAt = $spreadsheet['header_at'];
        $dataStartsAt = $spreadsheet['data_starts_at'];
    
        $rawContent = $this->getContentFromURL($url);

        if($rawContent === false) {
            return false;
        }

        $csv = $this->parseAsTSV($rawContent);

        $items = [];
        $header = $csv[$headerAt];

        for($line = $dataStartsAt, $size = count($csv); $line < $size; $line++) {
            if(count($header) != count($csv[$line])) {
                throw new \Exception("Unable to bind header with rows for spreadsheet with key '$spreadsheet_key'.");
            }
            $items[] = array_combine($header, $csv[$line]);
        }

        $this->spreadsheets_data[$spreadsheet_key] = $items;

        return true;
    }

    public function fetchAll()
    {
        $failures = 0;

        if(count($this->spreadsheets) == 0) {
            return false;
        }

        foreach($this->spreadsheets as $key => $spreadsheet) {
            $ok = $this->fetch($key);

            if(!$ok) {
                $failures++;
            }
        }

        return $failures == 0;
    }
}
