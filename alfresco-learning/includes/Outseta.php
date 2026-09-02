<?php

namespace Alfresco;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

class Outseta
{
    private string $apiKey;
    private string $cert;

    public function __construct()
    {
        $this->setupOutseta();
    }

    /*
     * Setup Outseta config
     */
    private function setupOutseta()
    {
        $envVars = parse_ini_file(plugin_dir_path(__FILE__) . '../../../../.env');
        $this->apiKey = $envVars['OUTSETA_API_KEY'] ?? '';
        $this->cert = $envVars['OUTSETA_CERT'] ?? '';

        if (!$this->apiKey || !$this->cert) {
            throw new \Exception('Outseta API key and certificate must be set in the .env file');
        }
    }

    /*
     * Get user details from Outseta token
     */
    public function getUserDetailsFromToken(string $token)
    {
        try {
            $decodedToken = JWT::decode($token, new Key($this->cert, 'RS256'));
        } catch (\Exception $e) {
            error_log('JWT decode failed: ' . $e->getMessage());
            throw new \Exception('JWT decode failed: ' . $e->getMessage());
        }

        $userData = [
            "email" => $decodedToken->email,
            "accountId" => $decodedToken->{'outseta:accountUid'},
            "name" => $decodedToken->name,
            "userId" => $decodedToken->nameid
        ];

        return $userData;
    }

    /*
     * Check if user has a valid subscription in Outseta
     */
    public function userHasValidSubscription(string $accountId)
    {
        $url = "https://alfresco-learning.outseta.com/api/v1/crm/accounts/" . $accountId;

        $headers = ['headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $this->apiKey
        ]];

        $client = new Client($headers);

        try {
            $response = $client->request('GET', $url);
        } catch (\Exception $e) {
            error_log('Error calling Outseta: ' . $e->getMessage());
            return false;
        }

        $body = (string) $response->getBody();
        $data = json_decode($body);
        $stage = $data->AccountStage;

        if ($stage === 2 || $stage === 3 || $stage === 4 || $stage === 8) {
            return true;
        }

        return false;
    }
}
