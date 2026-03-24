<?php

namespace Alfresco\Workshop;

use Alfresco\Trello\Constants;

class GFOL extends Workshop
{
    private $grassSpace;

    public function __construct($data)
    {
        $this->setCoreData($data);
        $this->grassSpace = $data->grassSpace;
        $this->workshopName = 'GFOL';
        $this->workshopType = Constants::WORKSHOP_TYPE_GFOL;
    }

    /*
     * Get the content for the Trello card
     */
    protected function getTrelloContent()
    {
        $additionalDetails = 'Grass space: ' . $this->grassSpace . "\n";
        return $this->getCoreTrelloContent($additionalDetails);
    }
}
