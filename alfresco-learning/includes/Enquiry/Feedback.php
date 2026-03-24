<?php

namespace Alfresco\Enquiry;

use Alfresco\Trello\Client as Trello;
use Alfresco\Trello\Constants;

class Feedback
{
    private $feedbackType;
    private $school;
    private $feedback;
    private $workshopRating;
    private $leaderRating;
    private $bookingRating;

    public function __construct($type, $data)
    {
        $this->feedbackType = $type;
        $this->school = $data->school;
        $this->feedback = $data->feedback;

        if ($type === 'NEUTRAL') {
            $this->workshopRating = $data->workshopRating;
            $this->leaderRating = $data->leaderRating;
            $this->bookingRating = $data->bookingRating;
        }
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
        if ($this->feedbackType === 'NEUTRAL') {
            $trelloContent = $this->getNeutralTrelloContent();
        } else {
            $trelloContent = $this->getNegativeTrelloContent();
        }
        $cardTitle = $this->school . ' - ' . $this->feedbackType;

        $trello = new Trello();

        try {
            $cardId = $trello->createCard(Constants::FEEDBACK_NEW_LIST_ID, $cardTitle, $trelloContent);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->sendEmail($cardId);
    }

    /*
     * Get the content to be used in the trello card description
     * for neutral feedback
     */
    private function getNeutralTrelloContent()
    {
        $content = "**School** \n" .
            $this->school . "\n" .
            "\n" .
            "**Workshop experience rating** \n" .
            $this->workshopRating . "\n" .
            "\n" .
            "**Workshop leader rating** \n" .
            $this->leaderRating . "\n" .
            "\n" .
            "**Booking rating** \n" .
            $this->bookingRating . "\n" .
            "\n" .
            "**Feedback**" . "\n" .
            $this->feedback . "\n" .
            "\n";

        return $content;
    }

    /*
     * Get the content to be used in the trello card description
     * for negative feedback
     */
    private function getNegativeTrelloContent()
    {
        $content = "**School** \n" .
            $this->school . "\n" .
            "\n" .
            "**Feedback**" . "\n" .
            $this->feedback . "\n" .
            "\n";

        return $content;
    }

    /*
     * Send the email notification
     */
    private function sendEmail($cardId)
    {
        $to = ["hollie@alfrescolearning.co.uk", "jenny@alfrescolearning.co.uk", "angharad@alfrescolearning.co.uk"];
        $subject = "Workshop feedback - " . $this->feedbackType;
        $content = "New workshop feedback added to Trello.\n" .
            "School: " . $this->school . "\n" .
            "Feedback type: " . $this->feedbackType . "\n\n\n" .
            "https://trello.com/c/" . $cardId;

        wp_mail($to, $subject, $content);
    }
}
