<?php 

class AlfrescoAJAX {
    
    /*
     * Register functions with hooks
     */
    public function register() {
        add_action('wp_ajax_nopriv_ALFRESCO_PH_DOWNLOAD', [$this, 'handlePHDownload']);
        add_action('wp_ajax_ALFRESCO_PH_DOWNLOAD', [$this, 'handlePHDownload']);

        add_action('wp_ajax_nopriv_ALFRESCO_WORKSHOP_GFOL', [$this, 'handleWorkshopFormGFoL']);
        add_action('wp_ajax_ALFRESCO_WORKSHOP_GFOL', [$this, 'handleWorkshopFormGFoL']);

        add_action('wp_ajax_nopriv_ALFRESCO_WORKSHOP_SPACE', [$this, 'handleWorkshopFormSpace']);
        add_action('wp_ajax_ALFRESCO_WORKSHOP_SPACE', [$this, 'handleWorkshopFormSpace']);

        add_action('wp_ajax_nopriv_ALFRESCO_WORKSHOP_CASTLES', [$this, 'handleWorkshopFormCastles']);
        add_action('wp_ajax_ALFRESCO_WORKSHOP_CASTLES', [$this, 'handleWorkshopFormCastles']);

        add_action('wp_ajax_nopriv_ALFRESCO_WORKSHOP_SEASIDE', [$this, 'handleWorkshopFormSeaside']);
        add_action('wp_ajax_ALFRESCO_WORKSHOP_SEASIDE', [$this, 'handleWorkshopFormSeaside']);

        add_action('wp_ajax_nopriv_ALFRESCO_INVOICE', [$this, 'handleInvoiceForm']);
        add_action('wp_ajax_ALFRESCO_INVOICE', [$this, 'handleInvoiceForm']);

        add_action('wp_ajax_nopriv_ALFRESCO_TRAINING', [$this, 'handleTrainingForm']);
        add_action('wp_ajax_ALFRESCO_TRAINING', [$this, 'handleTrainingForm']);

        add_action('wp_ajax_nopriv_ALFRESCO_FEEDBACK_NEUTRAL', [$this, 'handleFeedbackFormNeutral']);
        add_action('wp_ajax_ALFRESCO_FEEDBACK_NEUTRAL', [$this, 'handleFeedbackFormNeutral']);

        add_action('wp_ajax_nopriv_FEEDBACK_NEGATIVE', [$this, 'handleFeedbackFormNegative']);
        add_action('wp_ajax_ALFRESCO_FEEDBACK_NEGATIVE', [$this, 'handleFeedbackFormNegative']);
    }

    /*
     * Handle Planning Hub file download
     */
    public function handlePHDownload() {
        // retrieve request body and validate correct data has been sent
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody);

        if(!$data->outsetaToken || $data->outsetaToken == null) {
            echo 'Missing Outseta token';
			http_response_code(400);
			exit();
        }

        if(!$data->file || $data->file === null) {
            echo 'Missing requested file name';
			http_response_code(400);
			exit();
        }

        $outsetaToken = $data->outsetaToken;
        $file = $data->file;
        
        $phDownload = new AlfrescoPHDownload();

        try {
            $phDownload->sendFile($outsetaToken, $file);
        } catch (Exception $e) {
            echo 'Error downloading file';
			http_response_code(400);
			exit();
        }
        
    }

    /*
     * Handle GFoL workshop enquiry
     */
    public function handleWorkshopFormGFoL() {
        $this->handleWorkshopForm('GFOL');
    }

    /*
     * Handle Space workshop enquiry
     */
    public function handleWorkshopFormSpace() {
        $this->handleWorkshopForm('SPACE');
    }

    /*
     * Handle Castles workshop enquiry
     */
    public function handleWorkshopFormCastles() {
        $this->handleWorkshopForm('CASTLES');
    }

    /*
     * Handle Seaside workshop enquiry
     */
    public function handleWorkshopFormSeaside() {
        $this->handleWorkshopForm('SEASIDE');
    }

    /**
     * Handle workshop enquiry form submissions
     */
    public function handleWorkshopForm($workshop) {
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody);

        $workshopHandler = false;

        switch ($workshop) {
            case 'GFOL':
                $workshopHandler = new AlfrescoWorkshopGFOL($data);
                break;
            case 'SPACE':
                $workshopHandler = new AlfrescoWorkshopSpace($data);
                break;
            case 'CASTLES':
                $workshopHandler = new AlfrescoWorkshopCastles($data);
                break;
            case 'SEASIDE':
                $workshopHandler = new AlfrescoWorkshopSeaside($data);
                break;
            default:
                echo 'Invalid workshop handler';
                http_response_code(500);
		    	exit();
        }

        $dataValid = $workshopHandler->validateData();
        if (!$dataValid) {
            echo 'Invalid data';
            http_response_code(400);
			exit();
        }

        try {
            $workshopHandler->saveData();
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo 'Error saving data';
			http_response_code(400);
			exit();
        }

        echo 'Data saved';
    }

    /*
     * Handle Invoice enqiry submission
     */
    public function handleInvoiceForm() {
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody);

        $invoiceHandler = new AlfrescoInvoice($data);

        $dataValid = $invoiceHandler->validateData();
        if (!$dataValid) {
            echo 'Invalid data';
            http_response_code(400);
			exit();
        }

        try {
            $invoiceHandler->saveData();
        } catch (Exception $e) {
            echo 'Error saving data';
			http_response_code(400);
			exit();
        }

        echo 'Data saved';
    }

    /*
     * Handle Training enqiry submission
     */
    public function handleTrainingForm() {
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody);

        $trainingHandler = new AlfrescoTraining($data);

        $dataValid = $trainingHandler->validateData();
        if (!$dataValid) {
            echo 'Invalid data';
            http_response_code(400);
			exit();
        }

        try {
            $trainingHandler->saveData();
        } catch (Exception $e) {
            echo 'Error saving data';
			http_response_code(400);
			exit();
        }

        echo 'Data saved';
    }

    /*
     * Handle neutral feedback submission
     */
    public function handleFeedbackFormNeutral() {
        $this->handleFeedbackForm('NEUTRAL');
    }

    /*
     * Handle negative feedback submission
     */
    public function handleFeedbackFormNegative() {
        $this->handleFeedbackForm('NEGATIVE');
    }

    /*
     * Handle feedback submission
     */
    public function handleFeedbackForm($type) {
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody);

        $feedbackHandler = new AlfrescoFeedback($type, $data);

        $dataValid = $feedbackHandler->validateData();
        if (!$dataValid) {
            echo 'Invalid data';
            http_response_code(400);
			exit();
        }

        try {
            $feedbackHandler->saveData();
        } catch (Exception $e) {
            echo 'Error saving data';
			http_response_code(400);
			exit();
        }

        echo 'Data saved';
    }
}