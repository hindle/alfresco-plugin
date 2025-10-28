<?php

class AlfrescoTraining {

    private $contactName;
    private $contactEmail;
    private $schoolName;
    private $schoolAddress;
    private $schoolPostcode;
    private $bookingDate;
    private $bookingDetails;
    private $trainingSession;
    private $referrer;
    private $referrerOther;
    private $referrerFriend;

    public function __construct($data) {
        $this->contactName = $data->contactName;
        $this->contactEmail = $data->contactEmail;
        $this->schoolName = $data->schoolName;
        $this->schoolAddress = $data->schoolAddress;
        $this->schoolPostcode = $data->schoolPostcode;
        $this->bookingDate = $data->bookingDate;
        $this->bookingDetails = $data->bookingDetails;
        $this->trainingSession = $data->trainingSession;
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

        $trainingSession = 'Unsure';
        switch ($this->trainingSession) {
            case 'full-day':
                $trainingSession = 'Full day';
                break;
            case 'half-day':
                $trainingSession = 'Half day';
                break;
        }

        $trelloContent = $this->getTrelloContent($trainingSession);
        $trello = new AlfrescoTrello();

        try {
            $trello->createTrainingCard($this->schoolName, $trainingSession, $trelloContent);
        } catch (Exception $e) {
            throw $e;
        }

        $this->sendEmail($trainingSession);
    }

    /*
     * Get the content to be used in the trello card description
     */
    private function getTrelloContent($trainingSession) {

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
            "**Training session**" . "\n" .
            $trainingSession . "\n" .
            "\n" . 
            "**Date** \n" .
            $this->bookingDate . "\n" .
            "\n" .
            "**Details** \n" .
            $this->bookingDetails . "\n" .
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
     */
    private function sendEmail($trainingSession) {
        $to = ["info@alfrescolearning.co.uk"];
        $subject = "New training enquiry - " . $this->schoolName;
        $content = "New enquiry added to Trello.\n" .
            "Contact: " . $this->contactName . "\n" .
            "Contact email: " . $this->contactEmail . "\n\n" .
            "School: " . $this->schoolName . "\n" .
            "Address: " . $this->schoolAddress . "\n" .
            "Postcode: " . $this->schoolPostcode . "\n\n" .
            "Training session: " . $trainingSession . "\n" .
            "Date: " . $this->bookingDate . "\n\n" .
            "Details: " . $this->bookingDetails . "\n";

        wp_mail($to, $subject, $content);
    }
}