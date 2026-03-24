<?php

namespace Alfresco\Workshop;

use Alfresco\Trello\Constants;

class Space extends Workshop
{
    private $field;

    public function __construct($data)
    {
        $this->setCoreData($data);
        $this->field = $data->field;
        $this->workshopName = 'Space';
        $this->workshopType = Constants::WORKSHOP_TYPE_SPACE;
    }

    /*
     * Get the content for the Trello card
     */
    protected function getTrelloContent()
    {
        $additionalDetails = 'Field: ' . $this->field . "\n";
        return $this->getCoreTrelloContent($additionalDetails);
    }
}
