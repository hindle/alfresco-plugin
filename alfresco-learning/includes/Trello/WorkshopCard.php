<?php

namespace Alfresco\Trello;

class WorkshopCard
{
    public string $adminName;
    public string $adminEmail;
    public string $teacherName;
    public string $teacherEmail;
    public string $schoolName;
    public string $schoolAddress;
    public string $schoolPostcode;
    public string $workshopType;
    public string $workshopName;
    public string $date;
    public string $session1;
    public string $session2;
    public string $session3;

    /*
     * Constructor to create a Trello card object with the custom field values from the card
     */
    public function __construct($customFieldDetails)
    {
        try {
            $this->validateCoreCustomFieldValues($customFieldDetails);
        } catch (\Exception $e) {
            throw $e;
            return;
        }
        $this->adminName = $customFieldDetails[Constants::WORKSHOP_CARD_ADMIN_NAME_FIELD_ID]['text'] ?? '';
        $this->adminEmail = $customFieldDetails[Constants::WORKSHOP_CARD_ADMIN_EMAIL_FIELD_ID]['text'] ?? '';
        $this->teacherName = $customFieldDetails[Constants::WORKSHOP_CARD_TEACHER_NAME_FIELD_ID]['text'] ?? '';
        $this->teacherEmail = $customFieldDetails[Constants::WORKSHOP_CARD_TEACHER_EMAIL_FIELD_ID]['text'] ?? '';
        $this->schoolName = $customFieldDetails[Constants::WORKSHOP_CARD_SCHOOL_NAME_FIELD_ID]['text'] ?? '';
        $this->schoolAddress = $customFieldDetails[Constants::WORKSHOP_CARD_SCHOOL_ADDRESS_FIELD_ID]['text'] ?? '';
        $this->schoolPostcode = $customFieldDetails[Constants::WORKSHOP_CARD_SCHOOL_POSTCODE_FIELD_ID]['text'] ?? '';
        $this->session1 = $customFieldDetails[Constants::WORKSHOP_CARD_SESSION_1_FIELD_ID]['text'] ?? '';
        $this->session2 = $customFieldDetails[Constants::WORKSHOP_CARD_SESSION_2_FIELD_ID]['text'] ?? '';
        $this->session3 = $customFieldDetails[Constants::WORKSHOP_CARD_SESSION_3_FIELD_ID]['text'] ?? '';

        $rawDate = $customFieldDetails[Constants::WORKSHOP_CARD_DATE_FIELD_ID]['date'];
        $this->date = date("d/m/Y", strtotime($rawDate));

        $this->workshopType = $customFieldDetails[Constants::WORKSHOP_CARD_WORKSHOP_TYPE_FIELD_ID]['text'];
        switch ($this->workshopType) {
            case 'space':
                $this->workshopName = "Neil Armstrong";
                break;
            case 'seaside':
                $this->workshopName = "Past Seaside Holidays";
                break;
            case 'castles':
                $this->workshopName = "Castles - Kings and Queens";
                break;
            case 'gfol':
                $this->workshopName = "Great Fire or London";
                break;
        }
    }

    /*
     * Validate the core custom field values
     */
    private function validateCoreCustomFieldValues($customFieldDetails)
    {
        $requiredFields = [
            Constants::WORKSHOP_CARD_ADMIN_NAME_FIELD_ID,
            Constants::WORKSHOP_CARD_ADMIN_EMAIL_FIELD_ID,
            Constants::WORKSHOP_CARD_TEACHER_NAME_FIELD_ID,
            Constants::WORKSHOP_CARD_TEACHER_EMAIL_FIELD_ID,
            Constants::WORKSHOP_CARD_SCHOOL_NAME_FIELD_ID,
            Constants::WORKSHOP_CARD_WORKSHOP_TYPE_FIELD_ID
        ];

        foreach ($requiredFields as $fieldId) {
            if (empty($customFieldDetails[$fieldId]['text'] ?? '')) {
                throw new \Exception("Required custom field with ID $fieldId is missing or empty.");
            }
        }
    }
}
