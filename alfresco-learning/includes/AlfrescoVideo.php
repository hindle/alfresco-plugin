<?php

namespace Alfresco;

class AlfrescoVideo
{
    /*
     * Get the video URL for a given video ID
     */
    public function getVideoUrl(string $outsetaToken, string $videoId)
    {
        $ousteta = new Outseta();
        $userDetails = $ousteta->getUserDetailsFromToken($outsetaToken);
        if ($userDetails === false) {
            throw new \Exception('Failed to get user details.');
        }

        $validSubscription = $ousteta->userHasValidSubscription($userDetails['accountId']);
        if ($validSubscription === false) {
            throw new \Exception('User does not have a valid subscription.');
        }

        $videoUrl = "https://customer-iftchi2g7fkcrqay.cloudflarestream.com/" . $videoId . "/manifest/video.m3u8";
        return $videoUrl;
    }
}
