<?php 

class AlfrescoAJAX {
    
    /*
     * Register functions with hooks
     */
    public function register() {
        add_action('wp_ajax_nopriv_ALFRESCO_PH_DOWNLOAD', [$this, 'handlePHDownload']);
        add_action('wp_ajax_ALFRESCO_PH_DOWNLOAD', [$this, 'handlePHDownload']);

    }

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
        // Wrap in try catch
        try {
            $phDownload->sendFile($outsetaToken, $file);
        } catch (Exception $e) {
            echo 'Error downloading file';
			http_response_code(400);
			exit();
        }
        
    }
}