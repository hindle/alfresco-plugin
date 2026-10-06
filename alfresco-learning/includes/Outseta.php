<?php

namespace Alfresco;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

class Outseta
{
    private string $apiKey;
    private string $cert;

    const SCHOOL_USER = 'school';
    const INDIVIDUAL_USER = 'individual';
    const DEFAULT_USER = 'default';

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

    /*
     * Log a custom event in Outseta
     */
    public function logDownloadEvent(string $userId, string $file)
    {
        $url = "https://alfresco-learning.outseta.com/api/v1/activities/customactivity";

        $headers = ['headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $this->apiKey
        ]];

        $client = new Client($headers);
        $body = ['json' => [
            "Title" => "Planning download",
            "Description" => "Downloaded " . $file . " planning file",
            "EntityType" => 2,
            "EntityUid" => $userId
        ]];

        try {
            $response = $client->request('POST', $url, $body);
        } catch (\Exception $e) {
            error_log('Error calling Outseta: ' . $e->getMessage());
        }
    }

    /*
     * Get the user type based on their subscription plan
     */
    public function getUserType(string $accountId)
    {
        $url = "https://alfresco-learning.outseta.com/api/v1/crm/accounts/" . $accountId . "?fields=CurrentSubscription.Plan.Uid";

        $headers = ['headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $this->apiKey
        ]];

        $client = new Client($headers);

        try {
            $response = $client->request('GET', $url);
        } catch (\Exception $e) {
            error_log('Error calling Outseta: ' . $e->getMessage());
            return null;
        }

        $body = (string) $response->getBody();
        $data = json_decode($body);
        $planUid = $data->CurrentSubscription->Plan->Uid ?? null;

        if (is_null($planUid)) {
            throw new \Exception('Plan details not available for user');
        }

        switch ($planUid) {
            case 'B9lwBzQ8':
            case 'wQX0PlQK':
            case 'gWKe6eQp':
            case 'VmAkaD9a':
            case 'xmerwPQV':
                return self::INDIVIDUAL_USER;
            case 'z9MzGKW4':
            case 'DmwAD294':
            case 'A93Vv1Q0':
            case 'nmD6G09y':
            case 'BWzNj3WE':
            case 'ZmNjdwm2':
            case '7maPj1WE':
            case '496n1d9X':
            case 'y9qrM2WA':
            case 'L9PZNnmJ':
            case 'xmeYAPmV':
            case 'jW78YZmq':
            case 'EWByvb9r':
            case 'B9lDRz98':
            case 'rQVoYl96':
            case 'ZmN723Q2':
                return self::SCHOOL_USER;
            default:
                return self::DEFAULT_USER;
        }
    }
}
