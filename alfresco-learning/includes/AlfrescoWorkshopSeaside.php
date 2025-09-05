<?php

class AlfrescoWorkshopSeaside {

    private $contactName;
    private $contactEmail;
    private $schoolName;
    private $schoolAddress;
    private $schoolPostcode;
    private $adminName;
    private $adminEmail;
    private $bookingDate;
    private $bookingDetails;
    private $outdoorTap;
    private $allergies;
    private $spaceOwner;
    private $spaceOwnerDetails;
    private $referrer;
    private $referrerOther;
    private $referrerFriend;

    public function __construct($data) {
        $this->contactName = $data->contactName;
        $this->contactEmail = $data->contactEmail;
        $this->schoolName = $data->schoolName;
        $this->schoolAddress = $data->schoolAddress;
        $this->schoolPostcode = $data->schoolPostcode;
        $this->adminName = $data->adminName;
        $this->adminEmail = $data->adminEmail;
        $this->bookingDate = $data->bookingDate;
        $this->bookingDetails = $data->bookingDetails;
        $this->outdoorTap = $data->outdoorTap;
        $this->allergies = $data->allergies;
        $this->spaceOwner = $data->spaceOwner;
        $this->spaceOwnerDetails = $data->spaceDetails;
        $this->referrer = $data->referrer;
        $this->referrerOther = $data->referrerOther;
        $this->referrerFriend = $data->referrerFriend;
    }
 
    /*
     * @TODO check all required data is provided or set to safe value
     */
    public function validateData() {
        return true;
    }  

    /*
     * Create a Trello card and send the email
     */
    public function saveData() {

        $trelloContent = $this->getTrelloContent();
        $trello = new AlfrescoTrello();

        try {
            $trello->createWorkshopCard($this->schoolName, 'Seaside', $trelloContent);
        } catch (Exception $e) {
            throw $e;
        }

        $this->sendEmail();
    }

    /*
     * Get the content to be used in the trello card description
     */
    private function getTrelloContent() {

        $spaceOwnerDetails = '';
        if ($this->spaceOwner === 'other') {
            $spaceOwnerDetails = 'Space owner details: ' . $this->spaceOwnerDetails . "\n";
        }

        $referrerDetails = '';
        if ($this->referrer === 'other') {
            $referrerDetails = 'Details: ' . $this->referrerOther . "\n";
        } else if ($this->referrer === 'friend') {
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
            "Outdoor tap: " . $this->outdoorTap . "\n" .
            "Allergies: " . $this->allergies . "\n" .
            "Space owner: " . $this->spaceOwner . "\n" .
            $spaceOwnerDetails .
            "\n" .
            "**Referrer** \n" .
            "Type: " . $this->referrer . "\n" .
            $referrerDetails;

        return $content;
    }

    /*
     * Send the email notification
     * 
     * @TODO get the ID of the card from the create call and include a link to the card in the email
     * 
     * @TODO add the correct email addresses in
     */
    private function sendEmail() {
        $to = ["bookings@alfrescolearning.co.uk"];
        $subject = "New Seaside workshop enquiry - " . $this->schoolName;
        $content = "New enquiry added to Trello.\n" .
            "Contact: " . $this->contactName . "\n" .
            "Contact email: " . $this->contactEmail . "\n\n" .
            "School: " . $this->schoolName . "\n" .
            "Address: " . $this->schoolAddress . "\n" .
            "Postcode: " . $this->schoolPostcode . "\n\n" .
            "Date: " . $this->bookingDate . "\n\n" .
            "Details: " . $this->bookingDetails . "\n";

        wp_mail($to, $subject, $content);
    }
}