<?php

use GuzzleHttp\Client;

class AlfrescoTrello {

    private $apiKey = '9ced67675ab2b54c7794a55adc34438f';
    private $apiToken = 'ATTA036aa994961fef6dbd7b5b8cb505f12226ae8830bab1710c4b7f22de9af1a53eF8F4245B';
    private $url = 'https://api.trello.com/1/cards';

    /*
     * Add a trello card for a workshop enquiry
     */
    public function createWorkshopCard($school, $workshop, $content) {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $client = new Client($headers);

        $params = ['query' => [
            'name' => $school . ' - ' . $workshop,
            'desc' => $content,
            'pos' => 'top',
            'idList' => '5d0e415968525e47da7ae15e',
            'key' => $this->apiKey,
            'token' => $this->apiToken
        ]];

        try {
            $client->request('POST', $this->url, $params);
        } catch (Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new Exception('Failed to create Trello card');
        }
    }

    /*
     * Add a trello card for a school subscription invoice request
     */
    public function createInvoiceCard($school, $plan, $content) {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $client = new Client($headers);

        $params = ['query' => [
            'name' => $school . ' - ' . $plan,
            'desc' => $content,
            'pos' => 'top',
            'idList' => '602a430f3e0edc4a33eba49b',
            'key' => $this->apiKey,
            'token' => $this->apiToken
        ]];

        try {
            $client->request('POST', $this->url, $params);
        } catch (Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new Exception('Failed to create Trello card');
        }
    }

    /*
     * Add a Trello card for a training enquiry
     */
    public function createTrainingCard($school, $trainingSession, $content) {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $client = new Client($headers);

        $params = ['query' => [
            'name' => $school . ' - ' . $trainingSession,
            'desc' => $content,
            'pos' => 'top',
            'idList' => '5e3db65f9760d584384f4730',
            'key' => $this->apiKey,
            'token' => $this->apiToken
        ]];

        try {
            $client->request('POST', $this->url, $params);
        } catch (Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new Exception('Failed to create Trello card');
        }
    }
}