<?php

namespace Alfresco\Trello;

class WebhookHandler
{
    public function workshopBoard(\WP_REST_Request $request)
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
        if ($newListId === Constants::WORKSHOP_CAN_POLICY_SENT_LIST_ID) {
            $actions = new WorkshopActions();
            $actions->sendCancellationPolicyEmail($cardId);
        }

        // Send the booking confirmation email if the card has been moved to the "Send booking confirmation" list
        if ($newListId === Constants::WORKSHOP_SEND_BOOKING_CONFIRMATION_LIST_ID) {
            $actions = new WorkshopActions();
            $actions->sendBookingConfirmationEmail($cardId);
        }
    }
}
