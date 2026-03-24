<?php

namespace Alfresco\Workshop;

use Alfresco\Trello\Constants;

class Seaside extends Workshop
{
    private $outdoorTap;
    private $allergies;

    public function __construct($data)
    {
        $this->setCoreData($data);
        $this->outdoorTap = $data->outdoorTap;
        $this->allergies = $data->allergies;
        $this->workshopName = 'Seaside';
        $this->workshopType = Constants::WORKSHOP_TYPE_SEASIDE;
    }

    /*
     * Get the content for the Trello card
     */
    protected function getTrelloContent()
    {
        $additionalDetails = 'Outdoor tap: ' . $this->outdoorTap . "\n" .
            'Allergies: ' . $this->allergies . "\n";
        return $this->getCoreTrelloContent($additionalDetails);
    }
}
