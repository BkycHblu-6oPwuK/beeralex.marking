<?php

namespace Beeralex\Marking\Services;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Beeralex\Marking\CodeCheckRepository;
use Beeralex\Marking\Entity\Cdn\Host;
use Beeralex\Marking\Entity\Cdn\Hosts;
use Beeralex\Marking\Entity\Codes\CodesCheckResult;
use Beeralex\Marking\Exceptions\TooManyRequestsException;
use Beeralex\Marking\Exceptions\TransborderCheckServiceUnavailableException;
use Psr\Log\LoggerInterface;

class CodesCheck extends AuthService
{
    protected readonly CdnService $cdnService;
    protected readonly CodeCheckRepository $codeCheckRepository;
    protected string $fiscalDriveNumber;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?CdnService $cdnService = null,
        ?CodeCheckRepository $codeCheckRepository = null,
        ?string $token = null,
        ?string $oauthKey = null,
        ?string $fiscalDriveNumber = null
    ) {
        if (!$cdnService) {
            $cdnService = new CdnService($logger, $token, $oauthKey);
        }
        if (!$codeCheckRepository) {
            $codeCheckRepository = new CodeCheckRepository();
        }
        $this->cdnService = $cdnService;
        $this->codeCheckRepository = $codeCheckRepository;
        parent::__construct($logger, $token, $oauthKey);
        $this->fiscalDriveNumber = $fiscalDriveNumber ?? $this->options->defaultFiscalDriveNumber;
    }

    public function setFiscalDriveNumber(string $fiscalDriveNumber) : self
    {
        $this->fiscalDriveNumber = $fiscalDriveNumber;
        return $this;
    }

    /**
     * @param string[] $codes
     */
    public function check(array $codes): CodesCheckResult
    {
        if (empty($codes)) {
            throw new \RuntimeException("The codes array is empty");
        }
        try {
            $result = $this->getResultCheckCodes($this->cdnService->getCdn(), $codes);
            $this->saveInDb($result);
            return $result;
        } catch (TooManyRequestsException | CdnTemporarilyUnavailableException | TransborderCheckServiceUnavailableException $e) {
            $result = $this->getResultCheckCodes($this->cdnService->getCdn(true), $codes);
            $this->saveInDb($result);
            return $result;
        } catch (\Exception $e) {
            $this->log(fn() => $this->logger->error("Error checking codes: " . $e->getMessage(), $codes));
            throw $e;
        }
    }

    protected function getResultCheckCodes(Hosts $hosts, array $codes): CodesCheckResult
    {
        if ($hosts->transborderServiceUnavailable) {
            $this->log(fn() => $this->logger->warning("Cross-border code verification service is not available.", $codes));
            return CodesCheckResult::transborderUnavailable($codes);
        }
        $lastException  = null;
        foreach ($hosts->getHosts() as $host) {
            try {
                $response = $this->retryWithTokenRefresh(fn() => $this->makeRequest($host, $codes));
                if (!empty($response['codes'])) {
                    return new CodesCheckResult($response);
                }
            } catch (\Exception $e) {
                $this->log(fn() => $this->logger->warning("error checking code on host - {$host->url}"));
                $lastException  = $e;
            }
        }
        throw $lastException ?? new \RuntimeException("Unable to verify codes on all hosts.");
    }

    private function makeRequest(Host $host, array $codes)
    {
        return $this->post(new Uri("{$host->url}/api/v4/true-api/codes/check"), $this->getData($codes), $this->getHeaders());
    }

    protected function saveInDb(CodesCheckResult $result): void
    {
        try {
            $this->codeCheckRepository->save($result);
        } catch (\Exception $e) {
            $this->log(fn() => $this->logger->error("error in saveInDb.", ['message' => $e->getMessage(), 'result' => $result]));
            throw $e;
        }
    }

    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->getAccessToken(),
        ];
    }

    private function getData(array $codes): mixed
    {
        $data['codes'] = $codes;
        if ($this->fiscalDriveNumber) {
            $data['fiscalDriveNumber'] = $this->fiscalDriveNumber;
        }
        return Json::encode($data);
    }
}
