<?php

namespace App\Services\Integrations;

/**
 * Connects to Instagram API
 */
class Instagram
{
    protected string $userAccessToken;
    protected string $pageAccessToken;
    protected int $pageId;
    protected string $apiVersion = "v18.0";
    protected int $igUserId = 17841406067178184;
    protected string $mediaType = "IMAGE";
    protected string $endPoint;


    public function __construct()
    {
        $this->userAccessToken = config('app.facebook_system_user_access_token');
        $this->pageAccessToken = config('app.facebook_system_page_access_token');
        $this->pageId = 665944966869026;
        $this->apiVersion = "v18.0";
        $this->igUserId = 17841406067178184;
        $this->mediaType = "IMAGE";
        $this->endPoint ="https://graph.facebook.com/".$this->apiVersion."/";
    }

    public function getIgUserId(): int
    {
        return $this->igUserId;
    }

    public function getPageAccessToken(): string
    {
        return $this->pageAccessToken;
    }

    public function uploadPhoto(string $imageUrl, string $caption): int
    {
        $params = [];
        $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$this->igUserId.'/media?media_type='.$this->mediaType.'&image_url='.$imageUrl.'&caption='.$caption.'&access_token='.$this->pageAccessToken;
        $response = $this->makeApiCall($endpoint, 'POST', $params);

        // check if data is not null
        if (!isset($response['data']['id'])) {
            throw new \Exception('No data returned. There was an error posting to Instagram.  Please try again.');
        }

        return $response['data']['id'];
    }

    public function uploadStoryPhoto(string $imageUrl, string $caption): int
    {
        $params = [];
        $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$this->igUserId.'/media?media_type=STORIES&image_url='.$imageUrl.'&access_token='.$this->pageAccessToken;
        $response = $this->makeApiCall($endpoint, 'POST', $params);

        // check if data is not null
        if (!isset($response['data']['id'])) {
            throw new \Exception('No data returned. There was an error posting to Instagram.  Please try again.');
        }

        return $response['data']['id'];
    }

    public function uploadCarouselPhoto(string $imageUrl): int
    {
        $params = [];
        $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$this->igUserId.'/media?media_type='.$this->mediaType.'&image_url='.$imageUrl.'&is_carousel_item=true&access_token='.$this->pageAccessToken;
        $response = $this->makeApiCall($endpoint, 'POST', $params);

        // check if data is not null
        if (!isset($response['data']['id'])) {
            throw new \Exception('No data returned. There was an error posting carousel photo to Instagram.  Please try again.');
        }

        return $response['data']['id'];
    }

    public function createCarousel(array $igIds, string $caption): int
    {
        // build the children string
        $children = '';
        foreach ($igIds as $igId) {
            $children .= $igId.',';
        }
        $children = rtrim($children, ',');
        $children =  urlEncode($children);

        $caption = substr(urlEncode($caption), 0, 2200);
        $params = [];
        $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$this->igUserId.'/media?media_type=CAROUSEL&children='.$children.'&caption='.$caption.'&access_token='.$this->pageAccessToken;

        $response = $this->makeApiCall($endpoint, 'POST', $params);

        // check if data is not null
        if (!isset($response['data']['id'])) {
            throw new \Exception('No data returned. There was an error posting create carousel to Instagram.  Please try again.');
        }

        return $response['data']['id'];
    }


    public function checkStatus(int $igContainerId): bool
    {
        $finished = false;
        $maxCount = 5;
        $count = 0;
        while (!$finished) {
            $params = [];
            $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$igContainerId.'?fields=status_code,status&access_token='.$this->pageAccessToken;
            $response = $this->makeApiCall($endpoint, 'GET', $params);

            if (isset($response['data']['status_code']) && 'FINISHED' == $response['data']['status_code']) {
                $finished = true;
            }
            $count++;
            if ($count > $maxCount) {
                return false;
            }
            sleep(5);
        }

        return true;
    }

    public function publishMedia(int $igContainerId): bool | int
    {
        $params = [];
        $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$this->igUserId.'/media_publish?creation_id='.$igContainerId.'&access_token='.$this->pageAccessToken;
        $response = $this->makeApiCall($endpoint, 'POST', $params);

        // check if data is not null
        if (!isset($response['data']['id'])) {
            return false;
        }

        return $response['data']['id'];
    }

    public function publishStoryMedia(int $igContainerId): bool | int
    {
        $params = [];
        $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$this->igUserId.'/media_publish?creation_id='.$igContainerId.'&access_token='.$this->pageAccessToken;
        $response = $this->makeApiCall($endpoint, 'POST', $params);

        // check if data is not null
        if (!isset($response['data']['id'])) {
            return false;
        }

        return $response['data']['id'];
    }


    /**
     * Curl API call.
     */
    private function makeApiCall(string $endpoint, string $type, array $params): array
    {
        $ch = curl_init();

        // create endpoint with params
        if (empty($params)) {
            $apiEndpoint = $endpoint;
        } else {
            $apiEndpoint = $endpoint.'?'.http_build_query($params);
        }

        // set other curl options
        curl_setopt($ch, CURLOPT_URL, $apiEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // set values based on type
        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($type == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($type == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        // get response
        $response = curl_exec($ch);

        curl_close($ch);

        return [
            'type' => $type,
            'endpoint' => $endpoint,
            'params' => $params,
            'api_endpoint' => $apiEndpoint,
            'data' => json_decode($response, true),
        ];
    }

}
