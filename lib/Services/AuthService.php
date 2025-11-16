<?php
declare(strict_types=1);
namespace Beeralex\Marking\Services;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Beeralex\Core\Dto\CacheSettingsDto;
use Beeralex\Core\Exceptions\ApiClientUnauthorizedException;

class AuthService extends ApiService
{
    private ?string $token = null;
    private ?string $oauthKey = null;
    private readonly bool $authByApi;
    private CacheSettingsDto $cacheSettings;

    public function __construct(?string $token = null, ?string $oauthKey = null)
    {
        parent::__construct();
        if($oauthKey) {
            $this->setOauthKey($oauthKey);
        } else {
            $this->setTokenDefault($token ?? $this->options->token);
        }
        $this->cacheSettings = new CacheSettingsDto(1800, 'marking_access_token', '/marking/token');
    }

    public function setOauthKey(string $oauthKey)
    {
        $this->oauthKey = $oauthKey;
        $this->authByApi = true;
        return $this;
    }

    public function setTokenDefault(string $token)
    {
        $this->token = $token;
        $this->authByApi = false;
        return $this;
    }

    /** 
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function getAccessToken(): string
    {
        if (!$this->token) {
            $this->setToken();
        }
        return $this->token;
    }

    /** 
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function refreshToken()
    {
        $this->setToken(true);
    }

    protected function retryWithTokenRefresh(callable $callback)
    {
        if (!$this->authByApi) {
            return $callback();
        }

        try {
            return $callback();
        } catch (ApiClientUnauthorizedException) {
            $this->refreshToken();
            return $callback();
        }
    }

    private function setToken(bool $isRefresh = false): void
    {
        if(!$this->authByApi) return;
        $result = [];

        if ($isRefresh) {
            $this->cache->clean($this->cacheSettings->key, $this->cacheSettings->dir);
        }

        $this->makeRequest();

        if (!isset($result['access_token'], $result['expires_in'])) {
            throw new \RuntimeException('Error getting token');
        }

        if ($result['expires_in'] && $result['expires_in'] < time()) {
            $this->setToken(true);
            return;
        }

        $this->token = $result['access_token'];
    }

    private function makeRequest()
    {
        return $this->post(new Uri("{$this->options->baseUrl}/api/v3/true-api/auth/permissive-access"), $this->getData(), $this->getHeaders(), $this->cacheSettings);
    }

    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function getData(): mixed
    {
        return Json::encode([
            'data' => $this->oauthKey ?? $this->options->oauthKey,
        ]);
    }
}
