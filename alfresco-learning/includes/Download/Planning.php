<?php

namespace Alfresco\Download;

use Aws\S3\S3Client;
use Alfresco\Outseta as Outseta;

class Planning
{
    /*
     * Check the user has a valid subscription before sending the file
     */
    public function getFileUrl(string $outsetaToken, string $file)
    {
        $outseta = new Outseta();

        $userDetails = $outseta->getUserDetailsFromToken($outsetaToken);
        if ($userDetails === false) {
            throw new \Exception('Failed to get user details.');
        }

        $validSubscription = $outseta->userHasValidSubscription($userDetails['accountId']);
        if ($validSubscription === false) {
            throw new \Exception('User does not have a valid subscription.');
        }

        try {
            $userType = $outseta->getUserType($userDetails['accountId']);
        } catch (\Exception $e) {
            throw new \Exception('Failed to get user type: ' . $e->getMessage());
        }

        if ($this->isUserAccountLocked($userDetails['userId'], $userType)) {
            throw new AccountLockedException('Download limit exceeded.');
        }

        try {
            $fileUrl = $this->getSignedAwsUrl($file, $userDetails['userId']);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->recordFileDownload($userDetails['userId'], $file);
        $outseta->logDownloadEvent($userDetails['userId'], basename($file));
        return $fileUrl;
    }

    /**
     * Get the signed URL from AWS for the given file
     */
    private function getSignedAwsUrl(string $file, string $userId)
    {
        putenv("AWS_SHARED_CREDENTIALS_FILE=/www/alfrescolearning_623/deployment/.aws/credentials");
        putenv("AWS_CONFIG_FILE=/www/alfrescolearning_623/deployment/.aws/config");

        try {
            $s3Client = new S3Client([
                'region' => 'eu-west-1',
            ]);

            $bucket = 'alfresco-downloads';

            $fileExists = $s3Client->doesObjectExistV2($bucket, $file);
        } catch (\Exception $e) {
            error_log('Error checking file exists in S3:' . $e->getMessage());
            return;
        }

        if (!$fileExists) {
            $to = ["ah.hindle@gmail.com", "info@alfrescolearning.co.uk"];
            $subject = "[IMPORTANT] Planning Hub file download failed";
            $content = "File: " . $file . "\n\nUser ID: " . $userId;

            wp_mail($to, $subject, $content);

            throw new \Exception('File does not exist in S3.');
        }

        try {
            $request = $s3Client->createPresignedRequest(
                $s3Client->getCommand('GetObject', [
                    'Bucket' => 'alfresco-downloads',
                    'Key' => $file,
                    'ResponseContentDisposition' => 'attachment; "' . $file . '"',
                    'ResponseContentType' => 'application/pdf',
                ]),
                '+10 minutes',
            );

            $signedUrl = (string) $request->getUri();
        } catch (\Exception $e) {
            error_log('Error getting file from S3:' . $e->getMessage());

            throw new \Exception('Error getting file from S3.');
        }

        return $signedUrl;
    }

    /*
     * Check if the user has exceeded the download limit
     */
    private function isUserAccountLocked(string $userId, string $userType): bool
    {
        if ($userType === Outseta::SCHOOL_USER) {
            return false;
        }

        if ($userType === Outseta::DEFAULT_USER) {
            // Send email to admin with the details
            $to = ["ah.hindle@gmail.com", "info@alfrescolearning.co.uk"];
            $subject = "[INFO] Outseta plan missing from download limit check";
            $content = "A user has downloaded a file using an Outseta plan not defined in the account locking check. User ID: " . $userId . ".\n\n";
            wp_mail($to, $subject, $content);

            return false;
        }

        // Query the al_downloads table to get the number of downloads in the last 24 hours for the given user ID
        global $wpdb;
        $table_name = $wpdb->prefix . 'al_downloads';
        $downloads = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user = %s AND created >= NOW() - INTERVAL 1 DAY",
            $userId
        ));

        if ($downloads >= 30) {
            // Send email to admin with the details
            $to = ["ah.hindle@gmail.com", "info@alfrescolearning.co.uk"];
            $subject = "[INFO] User reached download limit";
            $content = "A user has exceeded the download limit. User ID: " . $userId . ".\n\n";
            wp_mail($to, $subject, $content);

            return true;
        }

        return false;
    }

    /*
     * Record a download for the user
     */
    private function recordFileDownload(string $userId, string $file)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'al_downloads';
        $wpdb->insert(
            $table_name,
            [
                'user' => $userId,
                'file' => $file
            ]
        );
    }
}
