<?php

namespace Alfresco\Workshop;

use Alfresco\Trello;

abstract class Workshop
{
    protected $contactName;
    protected $contactEmail;
    protected $schoolName;
    protected $schoolAddress;
    protected $schoolPostcode;
    protected $adminName;
    protected $adminEmail;
    protected $bookingDate;
    protected $bookingDetails;
    protected $spaceOwner;
    protected $spaceOwnerDetails;
    protected $referrer;
    protected $referrerOther;
    protected $referrerFriend;
    protected $workshopName;
    protected $workshopType;

    /*
     * To be used by the constructor to set the core data for the workshop
     */
    final protected function setCoreData($data)
    {
        $this->contactName = $data->contactName;
        $this->contactEmail = $data->contactEmail;
        $this->schoolName = $data->schoolName;
        $this->schoolAddress = $data->schoolAddress;
        $this->schoolPostcode = $data->schoolPostcode;
        $this->adminName = $data->adminName;
        $this->adminEmail = $data->adminEmail;
        $this->bookingDate = $data->bookingDate;
        $this->bookingDetails = $data->bookingDetails;
        $this->spaceOwner = $data->spaceOwner;
        $this->spaceOwnerDetails = $data->spaceOwnerDetails ?? '';
        $this->referrer = $data->referrer;
        $this->referrerOther = $data->referrerOther;
        $this->referrerFriend = $data->referrerFriend;
    }

    /*
     * Get the content for the Trello card
     */
    abstract protected function getTrelloContent();

    protected function getCoreTrelloContent($additionalDetails)
    {
        $spaceOwnerDetails = '';
        if ($this->spaceOwner === 'other') {
            $spaceOwnerDetails = 'Space owner details: ' . $this->spaceOwnerDetails . "\n";
        }

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
            $this->contactName . "\n" .
            $this->contactEmail . "\n" .
            "\n" .
            "**Admin** \n" .
            $this->adminName . "\n" .
            $this->adminEmail . "\n" .
            "\n" .
            "**Date** \n" .
            $this->bookingDate . "\n" .
            "\n" .
            "**Details** \n" .
            $this->bookingDetails . "\n" .
            $additionalDetails .
            "Space owner: " . $this->spaceOwner . "\n" .
            $spaceOwnerDetails .
            "\n" .
            "**Referrer** \n" .
            "Type: " . $this->referrer . "\n" .
            $referrerDetails;

        return $content;
    }

    /*
     * @TODO check all required data is provided or set to safe value
     */
    final public function validateData()
    {
        return true;
    }

    /*
     * Create a Trello card and send the email
     */
    public function saveData()
    {
        // Prepate content for the trello card
        $cardName = $this->schoolName . ' - ' . $this->workshopName;
        $cardContent = $this->getTrelloContent();
        $trello = new Trello\Client();

        // Create the Trello card
        try {
            $cardId = $trello->createCard(Trello\Constants::WORKSHOP_NEW_LIST_ID, $cardName, $cardContent);
        } catch (\Exception $e) {
            throw $e;
        }

        // Update custom field values on the card
        try {
            $trello->updateWorkshopCustomFields($cardId, $this->workshopType, $this->contactName, $this->contactEmail, $this->adminName, $this->adminEmail, $this->schoolName, $this->schoolAddress, $this->schoolPostcode);
        } catch (\Exception $e) {
            throw $e;
        }

        // Send the email notification
        $this->sendEmail($cardId);
    }

    /*
     * Send the email notification
     */
    private function sendEmail($cardId)
    {
        $to = ["bookings@alfrescolearning.co.uk"];
        $subject = "New " . $this->workshopName . " workshop enquiry - " . $this->schoolName;
        $content = "New enquiry added to Trello.\n" .
            "Contact: " . $this->contactName . "\n" .
            "Contact email: " . $this->contactEmail . "\n\n" .
            "School: " . $this->schoolName . "\n" .
            "Address: " . $this->schoolAddress . "\n" .
            "Postcode: " . $this->schoolPostcode . "\n\n" .
            "Date: " . $this->bookingDate . "\n\n" .
            "Details: " . $this->bookingDetails . "\n\n\n" .
            "https://trello.com/c/" . $cardId;

        wp_mail($to, $subject, $content);
    }
}
