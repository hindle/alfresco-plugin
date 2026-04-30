<?php

namespace Alfresco\Trello;

use GuzzleHttp\Client as Guzzle;

class Client
{
    private string $apiKey;
    private string $apiToken;
    private string $url = 'https://api.trello.com/1/cards';

    public function __construct()
    {
        $envVars = parse_ini_file(plugin_dir_path(__FILE__) . '../../../../../.env');
        $this->apiKey = $envVars['TRELLO_API_KEY'] ?? '';
        $this->apiToken = $envVars['TRELLO_API_TOKEN'] ?? '';

        if (!$this->apiKey || !$this->apiToken) {
            throw new \Exception('Trello API key and token must be set in the .env file');
        }
    }

    /*
     * Add a Trello card
     */
    public function createCard(string $listId, string $name, string $description)
    {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $guzzle = new Guzzle($headers);

        $params = ['query' => [
            'name' => $name,
            'desc' => $description,
            'pos' => 'top',
            'idList' => $listId,
            'key' => $this->apiKey,
            'token' => $this->apiToken
        ]];

        try {
            $response = $guzzle->request('POST', $this->url, $params);
        } catch (\Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new \Exception('Failed to create Trello card');
        }

        // Get card ID from response and return for further updates to the card
        $body = (string) $response->getBody();
        $data = json_decode($body);
        return $data->id;
    }

    /*
     * Update custom field values for a Workshop card
     */
    public function updateWorkshopCustomFields(string $cardId, string $workshopType, string $teacherName, string $teacherEmail, string $adminName, string $adminEmail, string $schoolName, string $schoolAddress, string $schoolPostcode)
    {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $guzzle = new Guzzle($headers);

        $url = "https://api.trello.com/1/card/$cardId/customFields";

        $body = '{
                    "customFieldItems": [
                        {
                            "idCustomField": "' . Constants::WORKSHOP_CARD_WORKSHOP_TYPE_FIELD_ID . '",
                            "value": {"text": "' . $workshopType . '"}
                        },
                        {
                            "idCustomField": "' . Constants::WORKSHOP_CARD_TEACHER_NAME_FIELD_ID . '",
                            "value": {"text": "' . $teacherName . '"}
                        },
                        {
                            "idCustomField": "' . Constants::WORKSHOP_CARD_TEACHER_EMAIL_FIELD_ID . '",
                            "value": {"text": "' . $teacherEmail . '"}
                        },
                        {
                            "idCustomField": "' . Constants::WORKSHOP_CARD_ADMIN_NAME_FIELD_ID . '",
                            "value": {"text": "' . $adminName . '"}
                        },
                        {
                            "idCustomField": "' . Constants::WORKSHOP_CARD_ADMIN_EMAIL_FIELD_ID . '",
                            "value": {"text": "' . $adminEmail . '"}
                        },
                        {
                            "idCustomField": "' . Constants::WORKSHOP_CARD_SCHOOL_NAME_FIELD_ID . '",
                            "value": {"text": "' . $schoolName . '"}
                        },
                        {
                            "idCustomField": "' . Constants::WORKSHOP_CARD_SCHOOL_ADDRESS_FIELD_ID . '",
                            "value": {"text": "' . $schoolAddress . '"}
                        },
                        {
                            "idCustomField": "' . Constants::WORKSHOP_CARD_SCHOOL_POSTCODE_FIELD_ID . '",
                            "value": {"text": "' . $schoolPostcode . '"}
                        }
                    ]
                }';

        $options = [
            'query' => [
                'key' => $this->apiKey,
                'token' => $this->apiToken
            ],
            'body' => $body
        ];

        try {
            $guzzle->request('PUT', $url, $options);
        } catch (\Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new \Exception('Failed to update Trello card custom fields');
        }
    }

    /*
     * Get custom field values for a Workshop card
     */
    public function getCardCustomFields(string $cardId)
    {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $guzzle = new Guzzle($headers);

        $url = "https://api.trello.com/1/card/$cardId/customFieldItems";
        $options = [
            'query' => [
                'key' => $this->apiKey,
                'token' => $this->apiToken
            ],
        ];

        try {
            $response = $guzzle->request('GET', $url, $options);
        } catch (\Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new \Exception('Failed to get Trello card custom fields');
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

    /*
     * Get the list of all cards in the send welcome email list
     */
    public function getCardsInList(string $listId)
    {
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $guzzle = new Guzzle($headers);

        $url = "https://api.trello.com/1/lists/$listId/cards";
        $options = [
            'query' => [
                'key' => $this->apiKey,
                'token' => $this->apiToken
            ],
        ];

        try {
            $response = $guzzle->request('GET', $url, $options);
        } catch (\Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new \Exception('Failed to get Trello cards in list');
        }

        // Get the card details from the response as an associative array
        $body = (string) $response->getBody();
        return json_decode($body, true);
    }

    /*
     * Update a card to show the welcome email has been sent
     */
    public function updateWelcomeEmailSent(string $cardId)
    {
        $url = "https://api.trello.com/1/card/$cardId/customField/" . Constants::WORKSHOP_CARD_WELCOME_EMAIL_SENT_FIELD_ID . "/item";

        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $guzzle = new Guzzle($headers);

        $body = '{
                    "value": {"checked": "true"}
                }';

        $options = [
            'query' => [
                'key' => $this->apiKey,
                'token' => $this->apiToken
            ],
            'body' => $body
        ];

        try {
            $guzzle->request('PUT', $url, $options);
        } catch (\Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new \Exception('Failed to update Trello card custom field for welcome email sent');
        }
    }

    /*
     * Move card to a new list
     */
    public function moveCardToList(string $cardId, string $newListId)
    {
        $url = "https://api.trello.com/1/cards/$cardId";
        $headers = ['headers' => [
            'Content-Type' => 'application/json',
        ]];

        $guzzle = new Guzzle($headers);

        $options = [
            'query' => [
                'key' => $this->apiKey,
                'token' => $this->apiToken,
                'idList' => $newListId
            ],
        ];

        try {
            $guzzle->request('PUT', $url, $options);
        } catch (\Exception $e) {
            echo 'Error calling Trello: ' . $e->getMessage();
            throw new \Exception('Failed to move Trello card to new list');
        }
    }
}
