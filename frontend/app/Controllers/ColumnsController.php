<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CURLRequest;

class ColumnsController extends Controller
{
    public function getColumns()
    {
        $client = \Config\Services::curlrequest();
        $response = $client->get('http://localhost:5003/all-columns');

        $data = json_decode($response->getBody(), true);

        // Check if the columns key exists in the response
        if (isset($data['columns'])) {
            return $this->response->setJSON($data['columns']);
        }

        return $this->response->setJSON([]);
    }
}