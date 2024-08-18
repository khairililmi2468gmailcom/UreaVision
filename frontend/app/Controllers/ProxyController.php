<?php
namespace App\Controllers;

use CodeIgniter\Controller;

class ProxyController extends Controller
{
    public function fetchData()
    {
        // Retrieve parameters from the query string
        $date = $this->request->getVar('date');
        $column = $this->request->getVar('column');

        // Construct the URL for the external API request
        $apiUrl = "http://localhost:5003/data?date={$date}&column={$column}";

        // Initialize a cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Execute the request and get the response
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            return $this->response->setStatusCode(500)->setBody('Error: ' . curl_error($ch));
        }

        // Close the cURL session
        curl_close($ch);

        // Return the response to the client
        return $this->response->setJSON(json_decode($response));
    }
}