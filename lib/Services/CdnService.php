<?php
declare(strict_types=1);
namespace Beeralex\Marking\Services;

use Bitrix\Main\Web\Uri;
use Beeralex\Core\Dto\CacheSettingsDTO;
use Beeralex\Core\Exceptions\ApiTooManyRequestsException;
use Beeralex\Marking\Entity\Cdn\Host;
use Beeralex\Marking\Entity\Cdn\Hosts;
use Beeralex\Marking\Exceptions\CdnTemporarilyUnavailableException;
use Beeralex\Marking\Exceptions\TransborderCheckServiceUnavailableException;

class CdnService extends AuthService
{
    private CacheSettingsDTO $cacheSettings;

    public function __construct(?string $token = null, ?string $oauthKey = null)
    {
        parent::__construct($token, $oauthKey);
        $this->cacheSettings = new CacheSettingsDTO(3600 * 6, 'marking_cdn', '/marking/cdn');
    }

    public function getCdn(bool $isRefresh = false): Hosts
    {
        if ($isRefresh) {
            $this->cache->clean($this->cacheSettings->key, $this->cacheSettings->dir);
        }
        $hosts = $this->getCached($this->cacheSettings, function () {
            $hosts = $this->getHosts();
            $this->checkAllCdn($hosts);
            if ($hosts->isAllBlocked()) {
                $this->log("All CDN hosts are blocked, we are trying to get and check again.");
                $hosts = $this->getHosts();
                $this->checkAllCdn($hosts);
            }
            if ($hosts->transborderServiceUnavailable) {
                $this->cacheSettings->abortCache = true;
            }
            return $hosts;
        });
        return $hosts;
    }

    private function checkAllCdn(Hosts $hosts): void
    {
        foreach ($hosts->getHosts() as $host) {
            try {
                $this->checkCdn($host);
            } catch (TransborderCheckServiceUnavailableException $e) {
                $this->log("Cross-border code verification service is unavailable: " . $e->getMessage());
                $hosts->transborderServiceUnavailable = true;
                break;
            } catch (ApiTooManyRequestsException | CdnTemporarilyUnavailableException $e) {
                $this->log("Host {$host->url} is blocked: " . $e->getMessage());
                $host->setBlocked();
            } catch (\Throwable $e) {
                $this->log("Host problem {$host->url}: " . $e->getMessage());
            }
        }
    }

    protected function getHosts(): Hosts
    {
        $result = $this->retryWithTokenRefresh(fn() => $this->makeHostsRequest());

        if (empty($result['hosts'])) {
            throw new \RuntimeException("Error getting hosts");
        }

        return new Hosts($result['hosts']);
    }

    protected function checkCdn(Host $host): void
    {
        try {
            $this->attemptCheckCdn($host);
        } catch (ApiTooManyRequestsException | CdnTemporarilyUnavailableException | TransborderCheckServiceUnavailableException) {
            $this->attemptCheckCdn($host);
        }
    }

    private function attemptCheckCdn(Host $host): void
    {
        $result = $this->retryWithTokenRefresh(fn() => $this->makeCheckCdnRequest($host));
        if (!isset($result['avgTimeMs'])) {
            throw new \RuntimeException("attemptCheckCdn error");
        }
        $host->avg = (int)$result['avgTimeMs'];
    }

    private function makeHostsRequest()
    {
        return $this->get(new Uri("{$this->options->baseUrl}/api/v4/true-api/cdn/info"), null, [
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->getAccessToken(),
        ]);
    }
    private function makeCheckCdnRequest(Host $host)
    {
        return $this->get(new Uri("{$host->url}/api/v4/true-api/cdn/health/check"), null, [
            'Content-Type' => 'application/json',
            'Connection' => 'close',
            'X-API-KEY' => $this->getAccessToken(),
        ]);
    }
}