<?php

namespace Alfresco\Trello;

class WorkshopActions
{
    /*
     * Send the welcome email - triggered by wordpress cron job
     */
    public function sendWelcomeEmail()
    {
        // Get the custom field details for the card
        $trello = new Client();

        // Get all the cards in the "Send welcome email" list

        // If no cards, bomb out

        // Loop over any retrieved cards
        // For each card, get the custom fields
        // Then check the workshop date and the email sent bool
        // If the date is two weeks or less and the email sent bool is false, send the welcome email and update the email sent bool to true
        // Validate the custom fields are set correctly before send the email

        $cardId = "TEMP";

        try {
            $customFieldDetails = $trello->getCardCustomFields($cardId);
        } catch (\Exception $e) {
            $this->sendErrorEmail("welcome", "System error.", $cardId);
            throw $e;
            return;
        }

        // @TODO Create custom validator to replace the core one here
        try {
            $card = new WorkshopCard($customFieldDetails);
        } catch (\Exception $e) {
            $this->sendErrorEmail("welcome", "Custom fields contain invalid or missing data.", $cardId);
            throw new \Exception('Custom field validation failed: ' . $e->getMessage());
            return;
        }
    }

    /*
     * Send the cancellation policy email when the card is moved to the correct list
     */
    public function sendCancellationPolicyEmail($cardId)
    {
        // Get the custom field details for the card
        $trello = new Client();

        try {
            $customFieldDetails = $trello->getCardCustomFields($cardId);
        } catch (\Exception $e) {
            $this->sendErrorEmail("cancellation policy", "System error.", $cardId);
            throw $e;
            return;
        }

        try {
            $card = new WorkshopCard($customFieldDetails);
            if (!$card->date) {
                throw new \Exception('Workshop date is not set');
            }
        } catch (\Exception $e) {
            $this->sendErrorEmail("cancellation policy", "Custom fields contain invalid or missing data.", $cardId);
            throw new \Exception('Custom field validation failed: ' . $e->getMessage());
            return;
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
    public function sendBookingConfirmationEmail($cardId)
    {
        // Get the custom field details for the card
        $trello = new Client();

        try {
            $customFieldDetails = $trello->getCardCustomFields($cardId);
        } catch (\Exception $e) {
            $this->sendErrorEmail("booking confirmation", "System error.", $cardId);
            throw $e;
            return;
        }

        try {
            $card = new WorkshopCard($customFieldDetails);
            if (!$card->date) {
                throw new \Exception('Workshop date is not set');
            }
        } catch (\Exception $e) {
            $this->sendErrorEmail("booking confirmation", "Custom fields contain invalid or missing data.", $cardId);
            throw new \Exception('Custom field validation failed: ' . $e->getMessage());
            return;
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
            return;
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
        //$headers[] = "Cc: $card->teacherEmail, bookings@alfrescolearning.co.uk";
        $headers[] = "Reply-To: bookings@alfrescolearning.co.uk";
        $headers[] = "From: Alfresco Learning Bookings <info@alfrescolearning.co.uk>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        // @TODO - confirm subject line for booking confirmation email
        $subject = $card->schoolName . " - workshop booking confirmed";
        wp_mail($to, $subject, $content, $headers);
    }

    /*
     * Send email in the event of an error
     */
    private function sendErrorEmail($emailType, $errorMessage, $cardId)
    {
        $to = ["ah.hindle@gmail.com", "bookings@alfrescolearning.co.uk"];
        $subject = "[ERROR] Failed to send automated email";
        $content = "An error occurred while trying to send the $emailType email:\n\n$errorMessage\n\nhttps://trello.com/c/$cardId";

        wp_mail($to, $subject, $content);
    }
}
