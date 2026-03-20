<?php

class AlfrescoTrelloWebhook
{
    /*
     * Handle webhooks for the workshop board
     */
    public function workshopBoardHandler(\WP_REST_Request $request)
    {

        $body = $request->get_body();
        $webhookData = json_decode($body);

        // Only handle the webhook for a card move action
        $action = $webhookData->action->display->translationKey;
        if ($action !== 'action_move_card_from_list_to_list') {
            return;
        }

        $newListId = $webhookData->action->data->listAfter->id;
        $cardId = $webhookData->action->data->card->id;

        // Send the cancelled workshop email if the card has been moved to the "Cancellation policy sent" list
        if ($newListId === AlfrescoTrello::WORKSHOP_CAN_POLICY_SENT_LIST_ID) {
            $this->sendCancellationPolicyEmail($cardId);
        }

        // Send the booking confirmation email if the card has been moved to the "Send booking confirmation" list
        if ($newListId === AlfrescoTrello::WORKSHOP_SEND_BOOKING_CONFIRMATION_LIST_ID) {
            $this->sendBookingConfirmationEmail($cardId);
        }
    }

    /*
     * Send the welcome email - triggered by wordpress cron job
     */
    public function sendWelcomeEmail()
    {
        // Get the custom field details for the card
        $trello = new AlfrescoTrello();

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
            $this->validateCoreCustomFieldValues($customFieldDetails);
        } catch (\Exception $e) {
            $this->sendErrorEmail("welcome", "Custom fields contain invalid or missing data.", $cardId);
            throw new \Exception('Custom field validation failed: ' . $e->getMessage());
            return;
        }
    }

    /*
     * Send the cancellation policy email when the card is moved to the correct list
     */
    private function sendCancellationPolicyEmail($cardId)
    {
        // Get the custom field details for the card
        $trello = new AlfrescoTrello();

        try {
            $customFieldDetails = $trello->getCardCustomFields($cardId);
        } catch (\Exception $e) {
            $this->sendErrorEmail("cancellation policy", "System error.", $cardId);
            throw $e;
            return;
        }

        try {
            $this->validateCoreCustomFieldValues($customFieldDetails);
        } catch (\Exception $e) {
            $this->sendErrorEmail("cancellation policy", "Custom fields contain invalid or missing data.", $cardId);
            throw new \Exception('Custom field validation failed: ' . $e->getMessage());
            return;
        }

        $adminName = $customFieldDetails[AlfrescoTrello::WORKSHOP_CARD_ADMIN_NAME_FIELD_ID]['text'];
        $teacherName = $customFieldDetails[AlfrescoTrello::WORKSHOP_CARD_TEACHER_NAME_FIELD_ID]['text'];

        $teacherEmail = $customFieldDetails[AlfrescoTrello::WORKSHOP_CARD_TEACHER_EMAIL_FIELD_ID]['text'];
        $adminEmail = $customFieldDetails[AlfrescoTrello::WORKSHOP_CARD_ADMIN_EMAIL_FIELD_ID]['text'];

        $schoolName = $customFieldDetails[AlfrescoTrello::WORKSHOP_CARD_SCHOOL_NAME_FIELD_ID]['text'];

        $rawDate = $customFieldDetails[AlfrescoTrello::WORKSHOP_CARD_DATE_FIELD_ID]['date'];
        $date = date("d/m/Y", strtotime($rawDate));

        $workshopType = $customFieldDetails[AlfrescoTrello::WORKSHOP_CARD_WORKSHOP_TYPE_FIELD_ID]['text'];
        $workshopName = "";
        switch ($workshopType) {
            case 'space':
                $workshopName = "Neil Armstrong";
                break;
            case 'seaside':
                $workshopName = "Past Seaside Holidays";
                break;
            case 'castles':
                $workshopName = "Castles - Kings and Queens";
                break;
            case 'gfol':
                $workshopName = "Great Fire or London";
                break;
        }

        // DEBUG
        //file_put_contents('/tmp/trello-webhook.log', 'Custom field details: ' . print_r($customFieldDetails, true) . "\n", FILE_APPEND);

        // Define email content
        $content = "<p>Hi $adminName</p>"
            . "<p>$teacherName has contacted us to book a $workshopName workshop on $date.</p>"
            . "<p>Please can you reply to this email, confirming the following:</p>"
            . "<ul>"
            . "<li>That you have read and agreed with the payment policy linked here: https://www.alfrescolearning.co.uk/cancellation-policy</li>"
            . "<li>The name on the bank account that payment for this workshop will be received from. This will aid us in matching up your future payment, with your booking.</li>"
            . "</ul>"
            . "<p>Following this we will send the booking confirmation email through.</p>"
            . "<p>$teacherName has expressed their preferential date for the workshop to happen. The dates for the workshops are booked on a first come, first served basis. To avoid disappointment and secure the booking, please respond to this email as soon as possible.</p>"
            . "<p>Please be aware that the booking is not secured until we receive the above information.</p>"
            . "<p>If your school requires a Purchase Order (PO) number on your invoice for this workshop, please send us the PO number as soon as you have one, so we can include it.</p>"
            . "<p>Please note that you will not have received the invoice yet as invoices are issued around two months before the workshop delivery date, after your booking is confirmed.</p>"
            . "<p>Many thanks,</p>"
            . "<p>Alfresco Learning</p>";

        // Send the email
        $to = $adminEmail;
        $headers[] = "Cc: $teacherEmail, bookings@alfrescolearning.co.uk";
        $headers[] = "Reply-To: bookings@alfrescolearning.co.uk";
        $headers[] = "From: Alfresco Learning Bookings <info@alfrescolearning.co.uk>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $subject = "[Action Required] $schoolName - workshop booking";
        wp_mail($to, $subject, $content, $headers);
    }

    /*
     * Send the booking confirmation email when the card is moved to the correct list
     */
    private function sendBookingConfirmationEmail($cardId)
    {
        // Get the custom field details for the card
        $trello = new AlfrescoTrello();

        try {
            $customFieldDetails = $trello->getCardCustomFields($cardId);
        } catch (\Exception $e) {
            $this->sendErrorEmail("booking confirmation", "System error.", $cardId);
            throw $e;
            return;
        }

        try {
            $trelloCard = new AlfrescoWorkshopTrelloCard($customFieldDetails);
        } catch (\Exception $e) {
            $this->sendErrorEmail("booking confirmation", "Custom fields contain invalid or missing data.", $cardId);
            throw new \Exception('Custom field validation failed: ' . $e->getMessage());
            return;
        }

        $sessionContent = "";
        if (!empty($trelloCard->session1)) {
            $sessionContent .= "<li>Session 1: " . $trelloCard->session1 . "</li>";
        }
        if (!empty($trelloCard->session2)) {
            $sessionContent .= "<li>Session 2: " . $trelloCard->session2 . "</li>";
        }
        if (!empty($trelloCard->session3)) {
            $sessionContent .= "<li>Session 3: " . $trelloCard->session3 . "</li>";
        }

        if (empty($sessionContent)) {
            $this->sendErrorEmail("booking confirmation", "No session information provided in custom fields.", $cardId);
            throw new \Exception('At least one session custom field must be filled out.');
            return;
        }

        $content = "<p>Hi " . $trelloCard->adminName . ",</p>"
            . "<p>Thank you for responding to our payment policy email and providing us with the requested information.</p>"
            . "<p>This email is to confirm your booking with Alfresco Learning for the " . $trelloCard->workshopName . " on " . $trelloCard->date . ". The timings for these workshops are as follows:</p>"
            . "<ul>"
            . $sessionContent
            . "</ul>"
            . "<p>$trelloCard->teacherName please keep your eyes peeled for the welcome email which will be sent about 2 weeks prior to your workshop booking. This will include your risk benefit assessment, letter to parents and all the details you need to help you, your team and your children prepare for the workshop. This will also contain a reminder of the details of your workshop as discussed.</p>"
            . "<p>Thank you for choosing Alfresco Learning! We look forward to meeting you!</p>";

        // Send the email
        $to = $trelloCard->adminEmail;
        $headers[] = "Cc: $trelloCard->teacherEmail, bookings@alfrescolearning.co.uk";
        $headers[] = "Reply-To: bookings@alfrescolearning.co.uk";
        $headers[] = "From: Alfresco Learning Bookings <info@alfrescolearning.co.uk>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        // @TODO - confirm subject line for booking confirmation email
        $subject = $trelloCard->schoolName . " - workshop booking confirmed";
        wp_mail($to, $subject, $content, $headers);
    }

    /*
     * Validate all required custom fields have the required data
     */
    private function validateCoreCustomFieldValues($customFieldValues)
    {
        $requiredTextFields = [
            AlfrescoTrello::WORKSHOP_CARD_ADMIN_NAME_FIELD_ID,
            AlfrescoTrello::WORKSHOP_CARD_TEACHER_NAME_FIELD_ID,
            AlfrescoTrello::WORKSHOP_CARD_TEACHER_EMAIL_FIELD_ID,
            AlfrescoTrello::WORKSHOP_CARD_ADMIN_EMAIL_FIELD_ID,
            AlfrescoTrello::WORKSHOP_CARD_SCHOOL_NAME_FIELD_ID,
            AlfrescoTrello::WORKSHOP_CARD_WORKSHOP_TYPE_FIELD_ID
        ];

        foreach ($requiredTextFields as $fieldId) {
            if (!isset($customFieldValues[$fieldId]) || empty($customFieldValues[$fieldId]['text'])) {
                throw new Exception("Custom field with ID $fieldId is missing or empty.");
            }
        }

        if (!isset($customFieldValues[AlfrescoTrello::WORKSHOP_CARD_DATE_FIELD_ID]) || empty($customFieldValues[AlfrescoTrello::WORKSHOP_CARD_DATE_FIELD_ID]['date'])) {
            throw new Exception("Custom field with ID " . AlfrescoTrello::WORKSHOP_CARD_DATE_FIELD_ID . " is missing or empty.");
        }
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
