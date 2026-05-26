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
    protected string $apiVersion = "v23.0";
    protected int $igUserId = 17841406067178184;
    protected string $mediaType = "IMAGE";
    protected string $endPoint;

    protected ?string $lastError = null;


    public function __construct()
    {
        $this->userAccessToken = config('app.facebook_system_user_access_token');
        $this->pageAccessToken = config('app.facebook_system_page_access_token');
        $this->pageId = 665944966869026;
        $this->apiVersion = "v23.0";
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

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function describeApiError(array $response): string
    {
        $data = $response['data'] ?? null;
        if (is_array($data) && isset($data['error'])) {
            $err = $data['error'];
            $parts = [];
            if (isset($err['message'])) {
                $parts[] = $err['message'];
            }
            if (isset($err['code'])) {
                $parts[] = 'code='.$err['code'];
            }
            if (isset($err['error_subcode'])) {
                $parts[] = 'subcode='.$err['error_subcode'];
            }
            if (isset($err['error_user_msg'])) {
                $parts[] = $err['error_user_msg'];
            }
            return $parts ? implode('; ', $parts) : 'Unknown Instagram API error.';
        }
        if ($data === null) {
            return 'Empty response from Instagram API.';
        }
        return 'Unexpected Instagram API response: '.substr((string) json_encode($data), 0, 500);
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

    public function uploadStoryPhoto(string $imageUrl, string $caption, ?string $linkUrl = null): int
    {
        $params = [];
        $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$this->igUserId.'/media?media_type=STORIES&image_url='.$imageUrl.'&access_token='.$this->pageAccessToken;
        if ($linkUrl) {
            $endpoint .= '&link_url='.urlencode($linkUrl);
        }
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
        $maxCount = 15;
        $count = 0;
        $lastStatusCode = null;
        $lastStatus = null;
        $lastResponse = null;
        while (!$finished) {
            $params = [];
            $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$igContainerId.'?fields=status_code,status&access_token='.$this->pageAccessToken;
            $response = $this->makeApiCall($endpoint, 'GET', $params);
            $lastResponse = $response;

            $lastStatusCode = $response['data']['status_code'] ?? null;
            $lastStatus = $response['data']['status'] ?? null;

            // If Graph returned an error object (or anything other than the status fields), bail out immediately.
            if ($lastStatusCode === null && $lastStatus === null) {
                $this->lastError = sprintf(
                    'Container %d status check failed: %s',
                    $igContainerId,
                    $this->describeApiError($response)
                );
                return false;
            }

            if ($lastStatusCode === 'FINISHED') {
                $finished = true;
            } elseif ($lastStatusCode === 'ERROR') {
                $this->lastError = sprintf(
                    'Container %d returned ERROR status: %s',
                    $igContainerId,
                    $lastStatus ?? $this->describeApiError($response)
                );
                return false;
            }
            $count++;
            if ($count > $maxCount) {
                $this->lastError = sprintf(
                    'Container %d did not finish after %d polls (last status_code=%s, status=%s, last_response=%s).',
                    $igContainerId,
                    $maxCount,
                    $lastStatusCode ?? 'null',
                    $lastStatus ?? 'null',
                    substr((string) json_encode($lastResponse['data'] ?? null), 0, 500)
                );
                return false;
            }
            sleep(3);
        }

        return true;
    }

    /**
     * Check status of multiple containers in batch
     * More efficient than checking each container individually
     * 
     * @param array $igContainerIds Array of container IDs to check
     * @return bool True if all containers are finished, false otherwise
     */
    public function checkBatchStatus(array $igContainerIds): bool
    {
        if (empty($igContainerIds)) {
            return true;
        }

        $maxAttempts = 5;
        $attempt = 0;
        $sleepTime = 3; // Reduced from 5 to 3 seconds for faster checking

        $lastStatusCode = null;
        $lastStatus = null;
        $lastContainerId = null;
        $lastResponse = null;
        while ($attempt < $maxAttempts) {
            $allFinished = true;

            foreach ($igContainerIds as $igContainerId) {
                $params = [];
                $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$igContainerId.'?fields=status_code,status&access_token='.$this->pageAccessToken;
                $response = $this->makeApiCall($endpoint, 'GET', $params);
                $lastResponse = $response;

                $lastStatusCode = $response['data']['status_code'] ?? null;
                $lastStatus = $response['data']['status'] ?? null;
                $lastContainerId = $igContainerId;

                // If Graph returned an error object (or anything other than the status fields), bail out immediately.
                if ($lastStatusCode === null && $lastStatus === null) {
                    $this->lastError = sprintf(
                        'Container %d status check failed: %s',
                        $igContainerId,
                        $this->describeApiError($response)
                    );
                    return false;
                }

                if ($lastStatusCode === 'ERROR') {
                    $this->lastError = sprintf(
                        'Container %d returned ERROR status: %s',
                        $igContainerId,
                        $lastStatus ?? $this->describeApiError($response)
                    );
                    return false;
                }

                if ($lastStatusCode !== 'FINISHED') {
                    $allFinished = false;
                    break; // Exit early if any container isn't finished
                }
            }

            if ($allFinished) {
                return true;
            }

            $attempt++;
            if ($attempt < $maxAttempts) {
                sleep($sleepTime);
            }
        }

        $this->lastError = sprintf(
            'Batch did not finish after %d attempts (last container=%s, status_code=%s, status=%s, last_response=%s).',
            $maxAttempts,
            $lastContainerId ?? 'null',
            $lastStatusCode ?? 'null',
            $lastStatus ?? 'null',
            substr((string) json_encode($lastResponse['data'] ?? null), 0, 500)
        );
        return false;
    }

    public function publishMedia(int $igContainerId): bool | int
    {
        $params = [];
        $endpoint = 'https://graph.facebook.com/'.$this->apiVersion.'/'.$this->igUserId.'/media_publish?creation_id='.$igContainerId.'&access_token='.$this->pageAccessToken;
        $response = $this->makeApiCall($endpoint, 'POST', $params);

        // check if data is not null
        if (!isset($response['data']['id'])) {
            $this->lastError = 'publishMedia(container '.$igContainerId.'): '.$this->describeApiError($response);
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
            $this->lastError = 'publishStoryMedia(container '.$igContainerId.'): '.$this->describeApiError($response);
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
