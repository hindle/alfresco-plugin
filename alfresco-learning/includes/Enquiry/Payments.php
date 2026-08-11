<?php

namespace Alfresco\Enquiry;

class Payments
{
    private string $name;
    private string $accountName;
    private bool $termsAccepted;
    private string $po;
    private string $trelloCardId;

    public function __construct($data)
    {
        $this->name = $data->name;
        $this->accountName = $data->accountName;
        $this->trelloCardId = $data->trelloCardId;
        $this->termsAccepted = $data->termsAccepted;
        $this->po = $data->po;
    }

    /*
     * @TODO check all required data is provided or set to safe value
     */
    public function validateData()
    {
        return true;
    }

    /*
     * Update the Trello card with a comment, send an email to bookings and move to the next column
     */
    public function saveData()
    {
        $trello = new \Alfresco\Trello\Client();

        if (!$this->termsAccepted) {
            throw new \Exception('Payment terms not accepted');
        }

        $comment = "Payment terms accepted by: " . $this->name . ".\n"
            . "Payment account name: " . $this->accountName . ".\n"
            . ($this->po ? 'Purchase Order number: ' . $this->po : '');

        try {
            $trello->addCommentToCard($this->trelloCardId, $comment);
        } catch (\Exception $e) {
            throw new \Exception('Failed to add comment to Trello card');
        }

        try {
            $trello->moveCardToList($this->trelloCardId, \Alfresco\Trello\Constants::WORKSHOP_SEND_BOOKING_CONFIRMATION_LIST_ID); // @TODO replace with actual list ID
        } catch (\Exception $e) {
            throw new \Exception('Failed to move Trello card to send booking confirmatiion list');
        }
    }
}
