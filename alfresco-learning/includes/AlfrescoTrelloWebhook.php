<?php

class AlfrescoTrelloWebhook {

	/*
	 * Handle webhooks for the workshop board
	 */
	public function workshopBoardHandler(\WP_REST_Request $request) {

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
	}

	/*
	 * Send the cancellation policy email when the card is moved to the correct list
	 */
	private function sendCancellationPolicyEmail($cardId) {
		// Get the custom field details for the card
		$trello = new AlfrescoTrello();

		try {
			$customFieldDetails = $trello->getCardCustomFields($cardId);
		} catch (Exception $e) {
			$this->sendErrorEmail('System error.', $cardId);
			throw $e;
			return;
		}

		try {
			$this->validateCustomFieldValues($customFieldDetails);
		} catch (Exception $e) {
			$this->sendErrorEmail('Custom fields contain invalid or missing data.', $cardId);
			throw new Exception('Custom field validation failed: ' . $e->getMessage());
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
			. "<p>Many thanks,</p>"
			. "<p>Alfresco Learning</p>";

		// Send the email
		$to = $adminEmail;
		$headers[] = "Cc: $teacherEmail";
		$headers[] = "Reply-To: bookings@alfrescolearning.com";
		$headers[] = "From: Alfresco Learning Bookings <info@alfrescolearning.com>";
		$headers[] = "Content-Type: text/html; charset=UTF-8";
		$subject = "[Action Required] $schoolName - workshop booking";
		wp_mail($to, $subject, $content, $headers);
	}

	/*
	 * Validate all required custom fields have the required data
	 */
	private function validateCustomFieldValues($customFieldValues) {
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
	private function sendErrorEmail($errorMessage, $cardId) {
		$to = ["ah.hindle@gmail.com", "bookings@alfrescolearning.com"];
		$subject = "[ERROR] Failed to send cancellation policy email";
		$content = "An error occurred while trying to send the cancellation policy email:\n\n$errorMessage\n\nhttps://trello.com/c/$cardId";

		wp_mail($to, $subject, $content);
	}
}
