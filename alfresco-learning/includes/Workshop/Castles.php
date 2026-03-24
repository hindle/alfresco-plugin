<?php

namespace Alfresco\Workshop;

use Alfresco\Trello\Constants;

class Castles extends Workshop
{
    private $flatSpace;

    public function __construct($data)
    {
        $this->setCoreData($data);
        $this->flatSpace = $data->flatSpace;
        $this->workshopName = 'Castles';
        $this->workshopType = Constants::WORKSHOP_TYPE_CASTLES;
    }

    /*
     * Get the content for the Trello card
     */
    protected function getTrelloContent()
    {
        $additionalDetails = 'Flat space: ' . $this->flatSpace . "\n";
        return $this->getCoreTrelloContent($additionalDetails);
    }
}
