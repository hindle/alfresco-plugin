<?php

use GuzzleHttp\Client;

class AlfrescoTrello
{
    private $apiKey = '9ced67675ab2b54c7794a55adc34438f';
    private $apiToken = 'ATTA036aa994961fef6dbd7b5b8cb505f12226ae8830bab1710c4b7f22de9af1a53eF8F4245B';
    private $url = 'https://api.trello.com/1/cards';

        const WORKSHOP_NEW_LIST_ID = '5d0e415968525e47da7ae15e';
        const WORKSHOP_CAN_POLICY_SENT_LIST_ID = '5d2f87810695df832741e57a';

        const WORKSHOP_CARD_DATE_FIELD_ID = '69975fb090fe0200fff1a448';
        const WORKSHOP_CARD_TEACHER_NAME_FIELD_ID = '698e4ce64a768b13c01f3360';
        const WORKSHOP_CARD_TEACHER_EMAIL_FIELD_ID = '698e4d0aea1f92d66606eaa5';
        const WORKSHOP_CARD_ADMIN_NAME_FIELD_ID = '698e4d26d5e87f97af6c8990';
        const WORKSHOP_CARD_ADMIN_EMAIL_FIELD_ID = '698e4d30545cb97f530a83fa';
        const WORKSHOP_CARD_SCHOOL_NAME_FIELD_ID = '698e4d3b6fb7c5a64bf7d277';
        const WORKSHOP_CARD_SCHOOL_ADDRESS_FIELD_ID = '698e4d44360716ce1d1da607';
        const WORKSHOP_CARD_SCHOOL_POSTCODE_FIELD_ID = '698e4d52644c5c1db32903c7';
        const WORKSHOP_CARD_WORKSHOP_TYPE_FIELD_ID = '69a3ed65ca03c6c37e6326b8';

        const WORKSHOP_TYPE_CASTLES = 'castles';
        const WORKSHOP_TYPE_SPACE = 'space';
        const WORKSHOP_TYPE_GFOL = 'gfol';
        const WORKSHOP_TYPE_SEASIDE = 'seaside';

    /*
     * Add a trello card for a school subscription invoice request
     */
    public function createInvoiceCard($school, $plan, $content)
    {
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
    public function createTrainingCard($school, $trainingSession, $content)
    {
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

    /*
     * Add a Trello card for workshop feedback
     */
    public function createFeedbackCard($school, $feedbackType, $content)
    {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $client = new Client($headers);

        $params = ['query' => [
            'name' => $feedbackType . ' - ' . $school,
            'desc' => $content,
            'pos' => 'top',
            'idList' => '67332ed4270414b79840edd4',
            'key' => $this->apiKey,
            'token' => $this->apiToken
        ]];

        try {
            $response = $client->request('POST', $this->url, $params);
        } catch (Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new Exception('Failed to create Trello card');
        }
    }

        /*
         * Add a Trello card
         */
    public function createCard($listId, $name, $description)
    {
            $headers = ['headers' => [
                    'Content-Type' => 'application/json',
            ]];

            $client = new Client($headers);

            $params = ['query' => [
                    'name' => $name,
                    'desc' => $description,
                    'pos' => 'top',
                    'idList' => $listId,
                    'key' => $this->apiKey,
                    'token' => $this->apiToken
            ]];

            try {
                    $response = $client->request('POST', $this->url, $params);
            } catch (Exception $e) {
                    echo 'Error calling Trello: ' . $e->getMessage();
                    throw new Exception('Failed to create Trello card');
            }

            // Get card ID from response and return for further updates to the card
            $body = (string) $response->getBody();
            $data = json_decode($body);
            return $data->id;
    }

        /*
         * Update custom field values for a Workshop card
         */
    public function updateWorkshopCustomFields($cardId, $workshopType, $teacherName, $teacherEmail, $adminName, $adminEmail, $schoolName, $schoolAddress, $schoolPostcode)
    {
            $headers = ['headers' => [
                    'Content-Type' => 'application/json',
            ]];

            $client = new Client($headers);

            $url = "https://api.trello.com/1/card/$cardId/customFields";

            $workshopTypeID = self::WORKSHOP_CARD_WORKSHOP_TYPE_FIELD_ID;
            $teacherNameID = self::WORKSHOP_CARD_TEACHER_NAME_FIELD_ID;
            $teacherEmailID = self::WORKSHOP_CARD_TEACHER_EMAIL_FIELD_ID;
            $adminNameID = self::WORKSHOP_CARD_ADMIN_NAME_FIELD_ID;
            $adminEmailID = self::WORKSHOP_CARD_ADMIN_EMAIL_FIELD_ID;
            $schoolNameID = self::WORKSHOP_CARD_SCHOOL_NAME_FIELD_ID;
            $schoolAddressID = self::WORKSHOP_CARD_SCHOOL_ADDRESS_FIELD_ID;
            $schoolPostcodeID = self::WORKSHOP_CARD_SCHOOL_POSTCODE_FIELD_ID;

            $body = <<<REQUESTBODY
					{
						"customFieldItems": [
							{
								"idCustomField": "$workshopTypeID",
								"value": {"text": "$workshopType"}
							},
							{
								"idCustomField": "$teacherNameID",
								"value": {"text": "$teacherName"}
							},
							{
								"idCustomField": "$teacherEmailID",
								"value": {"text": "$teacherEmail"}
							},
							{
								"idCustomField": "$adminNameID",
								"value": {"text": "$adminName"}
							},
							{
								"idCustomField": "$adminEmailID",
								"value": {"text": "$adminEmail"}
							},
							{
								"idCustomField": "$schoolNameID",
								"value": {"text": "$schoolName"}
							},
							{
								"idCustomField": "$schoolAddressID",
								"value": {"text": "$schoolAddress"}
							},
							{
								"idCustomField": "$schoolPostcodeID",
								"value": {"text": "$schoolPostcode"}
							}
						]
					}
				REQUESTBODY;

            $options = [
                'query' => [
                    'key' => $this->apiKey,
                    'token' => $this->apiToken
                ],
                'body' => $body
            ];

            try {
                    $client->request('PUT', $url, $options);
            } catch (Exception $e) {
                    echo 'Error calling Trello: ' . $e->getMessage();
                    throw new Exception('Failed to update Trello card custom fields');
            }
    }

    /*
     * Get custom field values for a Workshop card
     */
    public function getCardCustomFields($cardId)
    {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $client = new Client($headers);

        $url = "https://api.trello.com/1/card/$cardId/customFieldItems";
        $options = [
            'query' => [
                'key' => $this->apiKey,
                'token' => $this->apiToken
            ],
        ];

        try {
            $response = $client->request('GET', $url, $options);
        } catch (Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new Exception('Failed to get Trello card custom fields');
        }

        // Get the custom field values from the response as an associative array
        $body = (string) $response->getBody();
        $values = json_decode($body, true);

        // Debugging the output
        //file_put_contents('/tmp/trello-webhook.log', 'Raw custom field values: ' . print_r($values, true) . "\n", FILE_APPEND);

        // Format the array with the field ID as the key
        // The value sits under the key of the value type e.g.
        // ["fieldId"]["text"] = "Test Teacher"
        $data = [];
        foreach ($values as $value) {
            $data[$value['idCustomField']] = $value['value'];
        }

        return $data;
    }
}
