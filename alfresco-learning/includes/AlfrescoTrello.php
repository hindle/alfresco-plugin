<?php

use GuzzleHttp\Client;

class AlfrescoTrello {

    private $apiKey = '9ced67675ab2b54c7794a55adc34438f';
    private $apiToken = 'ATTA036aa994961fef6dbd7b5b8cb505f12226ae8830bab1710c4b7f22de9af1a53eF8F4245B';
    private $url = 'https://api.trello.com/1/cards';

		const WORKSHOP_LIST_ID = '5d0e415968525e47da7ae15e';

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

    /*
     * Add a Trello card for workshop feedback
     */
    public function createFeedbackCard($school, $feedbackType, $content) {
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
		public function createCard($listId, $name, $description) {
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
		public function updateWorkshopCustomFields($cardId, $teacherName, $teacherEmail, $adminName, $adminEmail, $schoolName, $schoolAddress, $schoolPostcode) {
				$headers = ['headers' => [
						'Content-Type' => 'application/json',
				]];

				$client = new Client($headers);

				$url = "https://api.trello.com/1/card/$cardId/customFields";



				$body = <<<REQUESTBODY
					{
						"customFieldItems": [
							{
								"idCustomField": "698e4ce64a768b13c01f3360",
								"value": {"text": "$teacherName"}
							},
							{
								"idCustomField": "698e4d0aea1f92d66606eaa5",
								"value": {"text": "$teacherEmail"}
							},
							{
								"idCustomField": "698e4d26d5e87f97af6c8990",
								"value": {"text": "$adminName"}
							},
							{
								"idCustomField": "698e4d30545cb97f530a83fa",
								"value": {"text": "$adminEmail"}
							},
							{
								"idCustomField": "698e4d3b6fb7c5a64bf7d277",
								"value": {"text": "$schoolName"}
							},
							{
								"idCustomField": "698e4d44360716ce1d1da607",
								"value": {"text": "$schoolAddress"}
							},
							{
								"idCustomField": "698e4d52644c5c1db32903c7",
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
}
