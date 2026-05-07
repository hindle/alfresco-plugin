<?php

namespace Alfresco\Trello;

class WorkshopActions
{
    /*
     * Send the welcome emails - triggered by wordpress cron job
     */
    public function sendWelcomeEmails()
    {
        // Get the custom field details for the card
        $trello = new Client();

        // Get all the cards in the "Send welcome email" list
        try {
            $cards = $trello->getCardsInList(Constants::WORKSHOP_SEND_WELCOME_EMAIL_LIST_ID);
        } catch (\Exception $e) {
            $this->sendErrorEmail("welcome", "System error.", "");
            throw $e;
        }

        // If no cards, bomb out
        if (empty($cards)) {
            return;
        }

        foreach ($cards as $card) {
            $cardId = $card['id'];

            try {
                $customFieldDetails = $trello->getCardCustomFields($cardId);
            } catch (\Exception $e) {
                $this->sendErrorEmail("welcome", "System error.", $cardId);
                continue;
            }

            try {
                $workshopCard = new WorkshopCard($customFieldDetails);
                if (!$workshopCard->date) {
                    throw new \Exception('Workshop date is not set');
                }
            } catch (\Exception $e) {
                // Disabling until the data is populated for older cards
                //$this->sendErrorEmail("welcome", "Custom fields contain invalid or missing data.", $cardId);
                continue;
            }

            // Check if the welcome email has already been sent for this card
            if ($workshopCard->welcomeEmailSent) {
                continue;
            }

            // Check if the workshop date is within 2 weeks
            $currentDate = new \DateTime();
            $workshopDate = new \DateTime($workshopCard->rawDate);
            $interval = $currentDate->diff($workshopDate);
            if ($interval->days <= 21 && !$interval->invert) {
                $this->sendWelcomeEmail($workshopCard, $cardId);

                try {
                    $trello->updateWelcomeEmailSent($cardId);
                    $trello->moveCardToList($cardId, Constants::WORKSHOP_WEATHER_CHECK_LIST_ID);
                } catch (\Exception $e) {
                    $this->sendErrorEmail("welcome", "Failed to update Trello card after sending welcome email: " . $e->getMessage(), $cardId);
                    continue;
                }
            }
        }
    }

    /*
     * Send the welcome email
     */
    private function sendWelcomeEmail(WorkshopCard $card, string $cardId)
    {
        $sessionContent = "";
        if (!empty($card->session1)) {
            $sessionContent .= "<li>Session 1: " . $card->session1 . "</li>";
        }
        if (!empty($card->session2)) {
            $sessionContent .= "<li>Session 2: " . $card->session2 . "</li>";
        }
        if (!empty($card->session3)) {
            $sessionContent .= "<li>Session 3: " . $card->session3 . "</li>";
        }

        if (empty($sessionContent)) {
            $this->sendErrorEmail("booking confirmation", "No session information provided in custom fields.", $cardId);
            throw new \Exception('At least one session custom field must be filled out.');
            return;
        }

        $spaceRequirementsContent = "";
        switch ($card->workshopType) {
            case Constants::WORKSHOP_TYPE_SPACE:
                $spaceRequirementsContent = "<li>Neil Armstrong - ensure access to your school field (minimum 20 metres).</li>";
                break;
            case Constants::WORKSHOP_TYPE_SEASIDE:
                $spaceRequirementsContent = "<li>Seaside - ensure access to a level, open space and a working outdoor tap with a hose connector attached.</li>";
                break;
            case Constants::WORKSHOP_TYPE_CASTLES:
                $spaceRequirementsContent = "<li>Castles - ensure access to a flat, open space.</li>";
                break;
            case Constants::WORKSHOP_TYPE_GFOL:
                $spaceRequirementsContent = "<li>Great Fire of London - ensure access to an area of real grass.</li>";
                break;
        }

        $content = "<p>Hi " . $card->teacherName . ",</p>" .
            "<p>Thank you for booking your $card->workshopName workshop with Alfresco Learning on $card->date! We are looking forward to getting you and your children excited about curriculum based outdoor education!</p>" .
            "<p><h2>Your workshop details</h2></p>" .
            "<ul>" .
            $sessionContent .
            "</ul>" .
            "<p><h2>What next?</h2></p>" .
            "<ul>" .
            "<li><b>Send home the parent/guardian information leaflet</b> (linked below) so children come dressed and prepared for outdoor learning (choose the correct letter to reflect the weather forecast and time of year).</li>" .
            "<li><b>Review the risk assessment</b> (linked below). This will need to be signed on the day of the workshop before we begin.</li>" .
            "<li><b>Ensure the class teacher remains with the class for the duration of the workshop.</b> Our Workshop Leader will lead the session, but as they won't know the children or their individual needs, your presence is essential to support behaviour, wellbeing, and inclusion.</li>" .
            "<li><b>Workshop-specific space requirements:</b></li>" .
            "<ul>" .
            $spaceRequirementsContent .
            "</ul>" .
            "<li><b>Inform your site manager</b> about the workshop to ensure there's no grass cutting or other site work that could affect delivery (unless you've arranged for the workshop to be delivered offsite, in which case the workshop will be delivered as planned).</li>" .
            "</ul>" .
            "<p>And finally...</p>" .
            "<p>Relax knowing that your children are going to be inspired and educated by an experienced Workshop Leader who will deliver and resource a fantastic workshop for your topic in your setting!</p>" .
            "<p>Your assigned Workshop Leader will be in touch 1/2 days prior to the workshop to confirm the weather forecast and update you accordingly. Please let them know parking arrangements and access to your outdoor space.</p>" .
            "<h2>Documents</h2>" .
            "<p><a href='https://alfresco-free-downloads.s3.eu-west-1.amazonaws.com/Risk+assessment.pdf'>Risk assessment</a></p>" .
            "<p><a href='https://alfresco-free-downloads.s3.eu-west-1.amazonaws.com/Warm+weather+-+parent+letter.pdf'>Warm weather - parent letter</a></p>" .
            "<p><a href='https://alfresco-free-downloads.s3.eu-west-1.amazonaws.com/Cold+weather+-+parent+letter.pdf'>Cold weather - parent letter</a></p>" .
            "<p>We hope you enjoy a fantastic workshop with your classes!</p>" .
            "<p>Many thanks,</p>" .
            "<p>Team Alfresco Learning</p>";


        // Send the email
        $to = $card->teacherEmail;
        $headers[] = "Cc: bookings@alfrescolearning.co.uk";
        $headers[] = "Reply-To: bookings@alfrescolearning.co.uk";
        $headers[] = "From: Alfresco Learning Bookings <info@alfrescolearning.co.uk>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $subject = "Welcome to you Alfresco Learning workshop";
        wp_mail($to, $subject, $content, $headers);
    }

    public function sendWeatherCheckEmails()
    {
        $trello = new Client();

        // Get all the cards in the "Send welcome email" list
        try {
            $cards = $trello->getCardsInList(Constants::WORKSHOP_WEATHER_CHECK_LIST_ID);
        } catch (\Exception $e) {
            $this->sendErrorEmail("weather check", "System error.", "");
            throw $e;
        }

        // If no cards, bomb out
        if (empty($cards)) {
            return;
        }

        foreach ($cards as $card) {
            $cardId = $card['id'];

            try {
                $customFieldDetails = $trello->getCardCustomFields($cardId);
            } catch (\Exception $e) {
                $this->sendErrorEmail("weather check", "System error.", $cardId);
                continue;
            }

            try {
                $workshopCard = new WorkshopCard($customFieldDetails);
                if (!$workshopCard->date) {
                    throw new \Exception('Workshop date is not set');
                }
                if (!$workshopCard->workshopLeaderEmail || $workshopCard->workshopLeaderEmail === '') {
                    throw new \Exception('Workshop leader email is not set');
                }
            } catch (\Exception $e) {
                // Disabling until the data is populated for older cards
                //$this->sendErrorEmail("weather check", "Custom fields contain invalid or missing data.", $cardId);
                continue;
            }

            // Check if the weather check email has already been sent for this card
            if ($workshopCard->weatherCheckEmailSent) {
                continue;
            }

            // Check if the workshop date is within 2 days
            $currentDate = new \DateTime();
            $workshopDate = new \DateTime($workshopCard->rawDate);
            $interval = $currentDate->diff($workshopDate);
            if ($interval->days <= 2 && !$interval->invert) {
                $this->sendWeatherCheckEmail($workshopCard);

                try {
                    $trello->updateWeatherCheckEmailSent($cardId);
                    $trello->moveCardToList($cardId, Constants::WORKSHOP_WEATHER_CHECK_SENT_LIST_ID);
                } catch (\Exception $e) {
                    $this->sendErrorEmail("weather check", "Failed to update Trello card after sending weather check email: " . $e->getMessage(), $cardId);
                    continue;
                }
            }
        }
    }

    /*
     * Send the weather check email
     */
    private function sendWeatherCheckEmail(WorkshopCard $card)
    {
        $content = "<p>The weather check email is due for " . $card->schoolName . " for the workshop booked on " . $card->date . ". Please send it as soon as possible.</p>"
            . "Remember to check the weather across multiple apps and use the handbooks to support your analysis of suitability for the workshop.</p>"
            . "<p>If your workshop needs to be rescheduled please follow the rescheduling flow chart provided in the workshop handbook and inform the central team via the weather group chat.</p>";

        // Send the email
        $to = $card->workshopLeaderEmail;
        $headers[] = "Reply-To: bookings@alfrescolearning.co.uk";
        $headers[] = "From: Alfresco Learning Bookings <info@alfrescolearning.co.uk>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $subject = "Weather check reminder";
        wp_mail($to, $subject, $content, $headers);
    }

    /*
     * Send the cancellation policy email when the card is moved to the correct list
     */
    public function sendCancellationPolicyEmail(string $cardId)
    {
        // Get the custom field details for the card
        $trello = new Client();

        try {
            $customFieldDetails = $trello->getCardCustomFields($cardId);
        } catch (\Exception $e) {
            $this->sendErrorEmail("cancellation policy", "System error.", $cardId);
            throw $e;
        }

        try {
            $card = new WorkshopCard($customFieldDetails);
            if (!$card->date) {
                throw new \Exception('Workshop date is not set');
            }
        } catch (\Exception $e) {
            $this->sendErrorEmail("cancellation policy", "Custom fields contain invalid or missing data.", $cardId);
            throw new \Exception('Custom field validation failed: ' . $e->getMessage());
        }

        // DEBUG
        // file_put_contents('/tmp/trello-webhook.log', 'Custom field details: ' . print_r($customFieldDetails, true) . "\n", FILE_APPEND);

        // Define email content
        $content = "<p>Hi $card->adminName</p>"
            . "<p>$card->teacherName has contacted us to book a $card->workshopName workshop on $card->date.</p>"
            . "<p>Please can you reply to this email, confirming the following:</p>"
            . "<ul>"
            . "<li>That you have read and agreed with the payment policy linked here: https://www.alfrescolearning.co.uk/cancellation-policy</li>"
            . "<li>The name on the bank account that payment for this workshop will be received from. This will aid us in matching up your future payment, with your booking.</li>"
            . "</ul>"
            . "<p>Following this we will send the booking confirmation email through.</p>"
            . "<p>$card->teacherName has expressed their preferential date for the workshop to happen. The dates for the workshops are booked on a first come, first served basis. To avoid disappointment and secure the booking, please respond to this email as soon as possible.</p>"
            . "<p>Please be aware that the booking is not secured until we receive the above information.</p>"
            . "<p>If your school requires a Purchase Order (PO) number on your invoice for this workshop, please send us the PO number as soon as you have one, so we can include it.</p>"
            . "<p>Please note that you will not have received the invoice yet as invoices are issued around two months before the workshop delivery date, after your booking is confirmed.</p>"
            . "<p>Many thanks,</p>"
            . "<p>Alfresco Learning</p>";

        // Send the email
        $to = $card->adminEmail;
        //$headers[] = "Cc: $card->teacherEmail, bookings@alfrescolearning.co.uk";
        $headers[] = "Reply-To: bookings@alfrescolearning.co.uk";
        $headers[] = "From: Alfresco Learning Bookings <info@alfrescolearning.co.uk>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $subject = "[Action Required] $card->schoolName - workshop booking";
        wp_mail($to, $subject, $content, $headers);
    }

    /*
     * Send the booking confirmation email when the card is moved to the correct list
     */
    public function sendBookingConfirmationEmail(string $cardId)
    {
        // Get the custom field details for the card
        $trello = new Client();

        try {
            $customFieldDetails = $trello->getCardCustomFields($cardId);
        } catch (\Exception $e) {
            $this->sendErrorEmail("booking confirmation", "System error.", $cardId);
            throw $e;
        }

        try {
            $card = new WorkshopCard($customFieldDetails);
            if (!$card->date) {
                throw new \Exception('Workshop date is not set');
            }
        } catch (\Exception $e) {
            $this->sendErrorEmail("booking confirmation", "Custom fields contain invalid or missing data.", $cardId);
            throw new \Exception('Custom field validation failed: ' . $e->getMessage());
        }

        $sessionContent = "";
        if (!empty($card->session1)) {
            $sessionContent .= "<li>Session 1: " . $card->session1 . "</li>";
        }
        if (!empty($card->session2)) {
            $sessionContent .= "<li>Session 2: " . $card->session2 . "</li>";
        }
        if (!empty($card->session3)) {
            $sessionContent .= "<li>Session 3: " . $card->session3 . "</li>";
        }

        if (empty($sessionContent)) {
            $this->sendErrorEmail("booking confirmation", "No session information provided in custom fields.", $cardId);
            throw new \Exception('At least one session custom field must be filled out.');
        }

        $content = "<p>Hi " . $card->adminName . ",</p>"
            . "<p>Thank you for responding to our payment policy email and providing us with the requested information.</p>"
            . "<p>This email is to confirm your booking with Alfresco Learning for the " . $card->workshopName . " on " . $card->date . ". The timings for these workshops are as follows:</p>"
            . "<ul>"
            . $sessionContent
            . "</ul>"
            . "<p>$card->teacherName please keep your eyes peeled for the welcome email which will be sent about 2 weeks prior to your workshop booking. This will include your risk benefit assessment, letter to parents and all the details you need to help you, your team and your children prepare for the workshop. This will also contain a reminder of the details of your workshop as discussed.</p>"
            . "<p>Thank you for choosing Alfresco Learning! We look forward to meeting you!</p>";

        // Send the email
        $to = $card->adminEmail;
        $headers[] = "Cc: $card->teacherEmail, bookings@alfrescolearning.co.uk";
        $headers[] = "Reply-To: bookings@alfrescolearning.co.uk";
        $headers[] = "From: Alfresco Learning Bookings <info@alfrescolearning.co.uk>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $subject = $card->schoolName . " - workshop booking confirmed";
        wp_mail($to, $subject, $content, $headers);

        try {
            $trello->moveCardToList($cardId, Constants::WORKSHOP_SEND_INVOICE_LIST_ID);
        } catch (\Exception $e) {
            $this->sendErrorEmail("booking confirmation", "Failed to move Trello card after sending booking confirmation email: " . $e->getMessage(), $cardId);
        }
    }

    /*
     * Send email in the event of an error
     */
    private function sendErrorEmail(string $emailType, string $errorMessage, string $cardId)
    {
        $trelloCardLink = $cardId !== "" ? "https://trello.com/c/$cardId" : "";

        $to = ["ah.hindle@gmail.com", "bookings@alfrescolearning.co.uk"];
        $subject = "[ERROR] Failed to send automated email";
        $content = "An error occurred while trying to send the $emailType email:\n\n$errorMessage\n\n$trelloCardLink";

        wp_mail($to, $subject, $content);
    }
}
