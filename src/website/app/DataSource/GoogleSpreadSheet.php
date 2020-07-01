<?php

namespace App\DataSource;

use Illuminate\Support\Facades\Cache;

class GoogleSpreadSheet
{
    /**
     * List of spreadhsheets
     *
     * @var array
     */
    protected $sheets = [
    ];

    /**
     * URL to publicly available CSV web export of the spreasheet.
     *
     * @var string
     */
    protected $spreadsheet_url = '';
    
    /**
     * Undocumented function
     *
     * @param string $spreadsheetURL URL to publicly available CSV web export of the spreasheet.
     */
    public function __construct($spreadsheet_url, $sheets = [])
    {
        if(empty($spreadsheet_url)) {
            throw new \Exception('Invalid spreadsheet URL');
        }

        $this->spreadsheet_url = $spreadsheet_url;
        $this->sheets = $sheets;
    }

    public function fetch()
    {
        $url = $this->spreadsheet_url;
        
        $curl = curl_init($url);

        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        
        if (is_dir($caPathOrFile)) {
            curl_setopt($curl, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($curl, CURLOPT_CAINFO, $caPathOrFile);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $spreadsheet_raw_content = curl_exec($curl);

        if($spreadsheet_raw_content === false) {
            throw new \Exception('Unable to fetch spreadsheet data from:' . $url);
        }
        
        $spreadsheet_lines = preg_split('/\r\n|\r|\n/', $spreadsheet_raw_content);
        $csv = array_map('str_getcsv', $spreadsheet_lines);
        $items = [];
        $header = $csv[0];

        foreach($csv as $index => $line) {
            if($index == 0) {
                // ignore header
                continue;
            }

            $items[] = array_combine($header, $line);
        }

        return $items;
    }
}
