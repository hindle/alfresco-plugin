<?php

namespace Alfresco\Enquiry;

use Alfresco\Trello\Client as Trello;
use Alfresco\Trello\Constants;

class Training
{
    private string $contactName;
    private string $contactEmail;
    private string $schoolName;
    private string $schoolAddress;
    private string $schoolPostcode;
    private string $bookingDate;
    private string $bookingDetails;
    private string $trainingSession;
    private string $referrer;
    private string $referrerOther;
    private string $referrerFriend;
    private array $trainingOutcome;
    private array $outdoorProvision;
    private array $outdoorSpace;
    private string $hubMember;

    public function __construct(object $data)
    {
        $this->contactName = $data->contactName;
        $this->contactEmail = $data->contactEmail;
        $this->schoolName = $data->schoolName;
        $this->schoolAddress = $data->schoolAddress;
        $this->schoolPostcode = $data->schoolPostcode;
        $this->bookingDate = $data->bookingDate;
        $this->bookingDetails = $data->bookingDetails;
        $this->trainingSession = $data->trainingSession;
        $this->referrer = $data->referrer;
        $this->referrerOther = $data->referrerOther ?? '';
        $this->referrerFriend = $data->referrerFriend ?? '';
        $this->trainingOutcome = $data->trainingOutcome ?? [];
        $this->outdoorProvision = $data->outdoorProvision ?? [];
        $this->outdoorSpace = $data->outdoorSpace ?? [];
        $this->hubMember = $data->hubMember ?? '';
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
        $trainingSession = 'Unsure';
        switch ($this->trainingSession) {
            case 'staff':
                $trainingSession = 'Staff meeting';
                break;
            case 'twilight':
                $trainingSession = 'Twilight';
                break;
            case 'half-day-core':
                $trainingSession = 'Half day inset (core subjects)';
                break;
            case 'half-day-foundation':
                $trainingSession = 'Half day inset (foundation subjects)';
                break;
            case 'full-day':
                $trainingSession = 'Full day inset';
                break;
        }

        $trainingOutcome = "";
        if (!empty($this->trainingOutcome)) {
            foreach ($this->trainingOutcome as $outcome) {
                switch ($outcome) {
                    case 'build-confidence':
                        $trainingOutcome .= "- Build staff confidence in teaching outdoors.\n";
                        break;
                    case 'practical-ideas':
                        $trainingOutcome .= "- Give staff practical ideas they can use straight away.\n";
                        break;
                    case 'develop-outdoor-learning':
                        $trainingOutcome .= "Develop outdoor learning across the whole school.\n";
                        break;
                    case 'better-use-grounds':
                        $trainingOutcome .= "- Make better use of our school grounds.\n";
                        break;
                    case 'improve-engagement':
                        $trainingOutcome .= "- Improve engagement and inclusion for children.\n";
                        break;
                    case 'develop-existing-provision':
                        $trainingOutcome .= "- Develop our existing outdoor learning provision.\n";
                        break;
                    case 'other':
                        $trainingOutcome .= "- Something else\n";
                        break;
                }
            }
        }

        $outdoorProvision = "";
        if (!empty($this->outdoorProvision)) {
            foreach ($this->outdoorProvision as $provision) {
                switch ($provision) {
                    case 'just-starting':
                        $outdoorProvision .= "- We're just getting started and don't currently teach much outdoors.\n";
                        break;
                    case 'inconsistent':
                        $outdoorProvision .= "- Some teachers are confident, but practice is inconsistent across the school.\n";
                        break;
                    case 'developing':
                        $outdoorProvision .= "- We already teach outdoors but would like to develop this further.\n";
                        break;
                    case 'established':
                        $outdoorProvision .= "- We have established outdoor provision but want to embed more curriculum learning.\n";
                        break;
                    case 'forest-school':
                        $outdoorProvision .= "- We've previously had Forest School or outdoor learning provision and want to take a different approach.\n";
                        break;
                    case 'other':
                        $outdoorProvision .= "- Other / not sure.\n";
                        break;
                }
            }
        }

        $outdoorSpace = "";
        if (!empty($this->outdoorSpace)) {
            foreach ($this->outdoorSpace as $space) {
                switch ($space) {
                    case 'school-field':
                        $outdoorSpace .= "- School field\n";
                        break;
                    case 'playground':
                        $outdoorSpace .= "- Playground\n";
                        break;
                    case 'grass-natural-areas':
                        $outdoorSpace .= "- Grass or natural areas\n";
                        break;
                    case 'woodland':
                        $outdoorSpace .= "- Woodland\n";
                        break;
                    case 'outdoor-classroom':
                        $outdoorSpace .= "- Outdoor classroom / dedicated outdoor area\n";
                        break;
                    case 'very-limited':
                        $outdoorSpace .= "- Very limited outdoor space\n";
                        break;
                    case 'nearby-green-space':
                        $outdoorSpace .= "- Access to nearby green space\n";
                        break;
                    case 'other':
                        $outdoorSpace .= "- Other / not sure.\n";
                        break;
                }
            }
        }


        $trelloContent = $this->getTrelloContent($trainingSession, $trainingOutcome, $outdoorProvision, $outdoorSpace);
        $cardTitle = $this->schoolName . ' - ' . $trainingSession;

        $trello = new Trello();

        try {
            $cardId = $trello->createCard(Constants::TRAINING_NEW_LIST_ID, $cardTitle, $trelloContent);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->sendEmail($trainingSession, $cardId);
    }

    /*
     * Get the content to be used in the trello card description
     */
    private function getTrelloContent(string $trainingSession, string $trainingOutcome, string $outdoorProvision, string $outdoorSpace)
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
            "**Training outcome** \n" .
            $trainingOutcome .
            "\n" .
            "**Outdoor provision** \n" .
            $outdoorProvision .
            "\n" .
            "**Outdoor space** \n" .
            $outdoorSpace .
            "\n" .
            "**Hub member** \n" .
            $this->hubMember . "\n" .
            "\n" .
            "**Referrer** \n" .
            "Type: " . $this->referrer . "\n" .
            $referrerDetails;

        return $content;
    }

    /*
     * Send the email notification
     */
    private function sendEmail(string $trainingSession, string $cardId)
    {
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
            "Details: " . $this->bookingDetails . "\n\n\n" .
            "https://trello.com/c/" . $cardId;

        wp_mail($to, $subject, $content);
    }
}
