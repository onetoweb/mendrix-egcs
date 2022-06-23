<?php

namespace Onetoweb\Mendrix\Egcs;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use Onetoweb\Mendrix\Egcs\Token;
use DateTime;

/**
 * Mendrix Api Client.
 *
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb. B.V.
 */
class Client
{
    const BASE_URI = 'https://www.egcs-mendrix.nl';
    
    /**
     * Methods
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    
    /**
     * @var int
     */
    private $clientId;
    
    /**
     * @var string
     */
    private $secret;
    
    /**
     * @var string
     */
    private $token;
    
    /**
     * @var callable
     */
    private $updateTokenCallback;
    
    /**
     * @var callable
     */
    private $rateLimitCallback;
    
    /**
     * @var GuzzleClient
     */
    private $client;
    
    /**
     * @param int $clientId
     * @param string $secret
     */
    public function __construct(int $clientId, string $secret)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
        
        $this->client = new GuzzleClient([
            'base_uri' => self::BASE_URI,
            'http_errors' => false,
        ]);
    }
    
    /**
     * @param callable $updateTokenCallback
     */
    public function setUpdateTokenCallback(callable $updateTokenCallback): void
    {
        $this->updateTokenCallback = $updateTokenCallback;
    }
    
    /**
     * @param callable $rateLimitCallback
     */
    public function setRateLimitCallback(callable $rateLimitCallback): void
    {
        $this->rateLimitCallback = $rateLimitCallback;
    }
    
    /**
     * @param Token $token
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }
    
    /**
     * @return void
     */
    private function requestAccessToken(): void
    {
        // build options
        $options = [
            RequestOptions::AUTH => [
                $this->clientId,
                $this->secret
            ],
            RequestOptions::FORM_PARAMS => [
                'grant_type' => 'client_credentials',
            ]
        ];
        
        // token request
        $response = $this->client->post('/oauth/token', $options);
        
        // get contents
        $contents = json_decode($response->getBody()->getContents(), true);
        
        // get expires
        $expires = (new DateTime())->setTimestamp(time() + $contents['expires_in']);
        
        // set token
        $this->token = new Token($contents['access_token'], $expires);
        
        // token update callback
        if ($this->updateTokenCallback) {
            
            ($this->updateTokenCallback)($this->token);
        }
    }
    
    /**
     * @param string $endpoint
     * @param array $query = []
     *
     * @return array
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request(self::METHOD_GET, $endpoint, $query);
    }
    
    /**
     * @param string $endpoint
     * @param array $data = []
     *
     * @return array
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request(self::METHOD_POST, $endpoint, [], $data);
    }
    
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $query = []
     * @param array $data = []
     *
     * @return array
     */
    public function request(string $method, string $endpoint, array $query = [], array $data = []): array
    {
        if ($this->token === null or $this->token->isExpired()) {
            
            $this->requestAccessToken();
        }
        
        // build options
        $options = [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$this->token->getValue()}",
            ],
            RequestOptions::FORM_PARAMS => $data,
            RequestOptions::QUERY => $query
        ];
        
        // do the request
        $response = $this->client->request($method, '/api/' . ($endpoint !== 'user' ? "mendrix/$endpoint" : $endpoint) , $options);
        
        // rate limit callback
        if ($this->rateLimitCallback) {
            
            ($this->rateLimitCallback)(
                (int) $response->getHeaderLine('X-RateLimit-Limit'),
                (int) $response->getHeaderLine('X-RateLimit-Remaining')
            );
        }
        
        if ($response->getHeaderLine('Content-Type') === 'application/pdf') {
            
            if ($response->hasHeader('Content-Disposition')) {
                
                $contents['filename'] = str_replace(['attachment; filename="', '"'], '', $response->getHeaderLine('Content-Disposition'));
            }
            
            // build contents
            $contents['data'] = base64_encode($response->getBody()->getContents());
            
        } else {
            
            // get contents
            $contents = json_decode($response->getBody()->getContents(), true);
            
        }
        
        return $contents;
    }
}