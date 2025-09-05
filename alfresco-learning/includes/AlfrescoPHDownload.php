<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class AlfrescoPHDownload {

    /*
     * Check the user has a valid subscription before sending the file
     */
    public function sendFile($outsetaToken, $file) {
        $userDetails = $this->getUserDetailsFromToken($outsetaToken);
        if ($userDetails === false) {
            throw new Exception('Failed to get user details');
            return;
        }

        $validSubscription = $this->userHasValidSubscription($userDetails);
        if ($validSubscription === false) {
            throw new Exception('User does not have a valid subscription.');
            return;
        }

        $filePath = ABSPATH . '/wp-content/uploads/private/' . basename($file);

        if (!file_exists($filePath)) {
            $to = ["ah.hindle@gmail.com", "hollie@alfrescolearning.co.uk", "jenny@alfrescolearning.co.uk"];
            $subject = "[IMPORTANT] Planning Hub file download failed";
            $content = "File: " . $file;

            wp_mail($to, $subject, $content);
            
            throw new Exception('File not found');
            return;
        }

        $this->logOutsetaEvent($userDetails['userId'], basename($file));
        readfile($filePath);
    }

    /*
     * Get the users details from the token
     */
    private function getUserDetailsFromToken($token) {
        $key = <<<END
-----BEGIN CERTIFICATE----- 
MIIC1jCCAb6gAwIBAgIQAJ9poI8F+R6Mfd7TPn+PZTANBgkqhkiG9w0BAQ0FADAmMSQwIgYDVQQD
DBtkaWdpdGFsLWR1YWxpdHkub3V0c2V0YS5jb20wIBcNMjExMjEzMTMyMTAyWhgPMjEyMTEyMTMx
MzIxMDJaMCYxJDAiBgNVBAMMG2RpZ2l0YWwtZHVhbGl0eS5vdXRzZXRhLmNvbTCCASIwDQYJKoZI
hvcNAQEBBQADggEPADCCAQoCggEBAIP8PXME3xm59gsKvHunOpi5vr0+Xyd/jMZjQpJAq3J69mF0
YvqVD5nwL+uBchMADEBndEmJXGr3wiTcxYUidhWVkMQJlogwSpzfjSoBDxWhEZbx+W0qs8m7gtXg
FSCYCE5sqeeaqUepGtoLnAHzzVRB5lBB8VGeWKPhMUdfJkCiWCMZ+J6jGqS+aUb3lx7/pqyLsaWy
YMVlLn6QWP1AXfWyi/yP/yuVywU2SRs2VckpNalcIXfz+Hax3IULCKoWX+PnjLZXNjvI9f9Tm1KD
EyrLOtlmreaT2KdJkODyQVCCXPi+j8U++xMTY6AjanW8HySpX13xlIL/38njgU50CBUCAwEAATAN
BgkqhkiG9w0BAQ0FAAOCAQEANZaBYxCyRAPONtirVhxCtXxhw/Hka3AIeUWf12FgCT06n622mdGO
j4iuR6vBdfPjzWL17ocuGp8LSRh5pM5K2H87MKzS6CnMrjL0GmHn8Lv+sYttNttRfkPK1osOhjTo
4S9v7+/CablcR77gsfee3nTomxK7y2UaenXnfJuoe9Ed2yr/etlKyUWUMQ4BpJBHqxIbVcxMn5tT
e6aoNfGUgykZLCkO4LGCSMnthKLxUOs3Ya2u4hSh9iegRODa8BBrojRb4Ftb6cgTC1K0Z7ZvTe+w
i5o7fH6bo4im78hyoDKPROYIRX29qEJlXkdyW5RiI94j0HBZE5h+5BOSx3wIWw==
-----END CERTIFICATE----- 
END;

        try {
            $decodedToken = JWT::decode($token, new Key($key, 'RS256'));
        } catch(Exception $e) {
            error_log('JWT decode failed: ' . $e->getMessage());
            return false;
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
     * Check user has valid subscription in Outseta
     */
    private function userHasValidSubscription($userDetails) {
        $apiKey = 'Outseta 594812bb-d3fb-4f55-b560-94ff33591356:28a14318ff70b729549d5e6ec569b15b';
        $url = "https://alfresco-learning.outseta.com/api/v1/crm/accounts/" . $userDetails['accountId'];

        $headers = ['headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $apiKey
        ]];

        $client = new Client($headers);
        
        try {
            $response = $client->request('GET', $url);
        } catch (Exception $e) {
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
     * Log the download event in Outseta
     */
    private function logOutsetaEvent($user, $file) {
        $apiKey = 'Outseta 594812bb-d3fb-4f55-b560-94ff33591356:28a14318ff70b729549d5e6ec569b15b';
        $url = "https://alfresco-learning.outseta.com/api/v1/activities/customactivity";

        $headers = ['headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $apiKey
        ]];

        $client = new Client($headers);
        $body = ['json' => [
            "Title" => "Planning download",
            "Description" => "Downloaded " . $file . " planning file",
            "EntityType" => 2,
            "EntityUid" => $user
        ]];

        try {
            $response = $client->request('POST', $url, $body);
        } catch (Exception $e) {
            error_log('Error calling Outseta: ' . $e->getMessage());
        }
    }

    /*
     * @TODO setup a function to record the download against the user in the database
     * TO be used as a part of the future file locking mechanism
     */
    private function recordFileDownload() {

    }
}