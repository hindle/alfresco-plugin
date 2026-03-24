<?php

namespace Alfresco\Enquiry;

use Alfresco\Trello\Client as Trello;
use Alfresco\Trello\Constants;

class Invoice
{
    private $plan;
    private $schoolName;
    private $schoolAddress;
    private $schoolPostcode;
    private $accountName;
    private $accountEmail;
    private $adminName;
    private $adminEmail;
    private $referrer;
    private $referrerOther;
    private $referrerFriend;

    public function __construct($data)
    {
        $this->plan = $data->plan;
        $this->schoolName = $data->schoolName;
        $this->schoolAddress = $data->schoolAddress;
        $this->schoolPostcode = $data->schoolPostcode;
        $this->accountName = $data->accountName;
        $this->accountEmail = $data->accountEmail;
        $this->adminName = $data->adminName;
        $this->adminEmail = $data->adminEmail;
        $this->referrer = $data->referrer;
        $this->referrerOther = $data->referrerOther;
        $this->referrerFriend = $data->referrerFriend;
    }

    /*
     * @TODO check all required data is provided or set to safe value
     */
    public function validateData()
    {
        return true;
    }

    /*
     * Create a Trello card and send the email
     */
    public function saveData()
    {
        $plan = '';
        switch ($this->plan) {
            case '1-form':
                $plan = '1 Form School';
                break;
            case '2-form':
                $plan = '2 Form School';
                break;
            case '3-form':
                $plan = '3 Form School';
                break;
        }

        $trelloContent = $this->getTrelloContent($plan);
        $cardTitle = $this->schoolName . ' - ' . $plan;
        $trello = new Trello();

        try {
            $cardId = $trello->createCard(Constants::INVOICE_NEW_LIST_ID, $cardTitle, $trelloContent);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->sendEmail($plan, $cardId);
    }

    /*
     * Get the content to be used in the trello card description
     */
    private function getTrelloContent($plan)
    {
        $referrerDetails = '';
        if ($this->referrer === 'other') {
            $referrerDetails = 'Details: ' . $this->referrerOther . "\n";
        } elseif ($this->referrer === 'friend') {
            $referrerDetails = 'Details: ' . $this->referrerFriend . "\n";
        }

        $content = "**School** \n" .
            $this->schoolName . "\n" .
            $this->schoolAddress . "\n" .
            $this->schoolPostcode . "\n" .
            "\n" .
            "**Contact** \n" .
            $this->accountName . "\n" .
            $this->accountEmail . "\n" .
            "\n" .
            "**Admin** \n" .
            $this->adminName . "\n" .
            $this->adminEmail . "\n" .
            "\n" .
            "**Plan** \n" .
            $plan . "\n" .
            "\n" .
            "**Referrer** \n" .
            "Type: " . $this->referrer . "\n" .
            $referrerDetails;

        return $content;
    }

    /*
     * Send the email notification
     */
    private function sendEmail($plan, $cardId)
    {
        $to = ["info@alfrescolearning.co.uk"];
        $subject = "New Invoice enquiry - " . $this->schoolName . " - " . $plan;
        $content = "New enquiry added to Trello.\n" .
            "Plan: " . $plan . "\n\n" .
            "Contact: " . $this->accountName . "\n" .
            "Contact email: " . $this->accountEmail . "\n\n" .
            "School: " . $this->schoolName . "\n" .
            "Address: " . $this->schoolAddress . "\n" .
            "Postcode: " . $this->schoolPostcode . "\n\n\n" .
            "https://trello.com/c/" . $cardId;

        wp_mail($to, $subject, $content);
    }
}
