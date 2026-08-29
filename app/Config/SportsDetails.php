<?php

namespace App\Controllers;
require APPPATH . 'ThirdParty/PhpSpreadsheet/vendor/autoload.php';

use CodeIgniter\Config\Services;
use PhpOffice\PhpSpreadsheet\IOFactory;  
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SportsDetails extends BaseController
{
    public function __construct()
    {
        helper(['form', 'url']);
        date_default_timezone_set('Asia/Kolkata');
    }
    
    public function index()
    {
        return view('sports_list');
    }

    public function getSports()
    {
        $db = Database::connect();
        $query = $db->query("SELECT * FROM sport_variants ORDER BY id DESC");
        $data = $query->getResultArray();

        return $this->response->setJSON([
            'status' => true,
            'data' => $data
        ]);
    }

    public function addSport()
    {
        $request = $this->request->getJSON(true);
        $db = Database::connect();

        $builder = $db->table('sport_variants');
        $insertData = [
            'sport_id'   => $request['sport_id'],
            'ground_name'=> $request['ground_name'],
            'location'   => $request['location'],
            'mapUrl'     => $request['mapUrl'],
            'address'    => $request['address'],
            'rate'       => $request['rate'],
            'image'      => $request['image'],
        ];
        $builder->insert($insertData);

        return $this->response->setJSON(['status' => true, 'message' => 'Sport added successfully']);
    }

}