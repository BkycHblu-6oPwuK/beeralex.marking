<?php
declare(strict_types=1);
namespace Beeralex\Marking\Services;

use Beeralex\Core\Exceptions\ApiTooManyRequestsException;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Beeralex\Marking\CodeCheckRepository;
use Beeralex\Marking\Entity\Cdn\Host;
use Beeralex\Marking\Entity\Cdn\Hosts;
use Beeralex\Marking\Entity\Codes\CodesCheckResult;
use Beeralex\Marking\Exceptions\CdnTemporarilyUnavailableException;
use Beeralex\Marking\Exceptions\TransborderCheckServiceUnavailableException;

class CodesCheckService extends AuthService
{
    protected readonly CdnService $cdnService;
    protected readonly CodeCheckRepository $codeCheckRepository;
    protected string $fiscalDriveNumber;

    public function __construct(
        CdnService $cdnService,
        CodeCheckRepository $codeCheckRepository,
        ?string $token = null,
        ?string $oauthKey = null,
        ?string $fiscalDriveNumber = null
    ) {
        $this->cdnService = $cdnService;
        $this->codeCheckRepository = $codeCheckRepository;
        parent::__construct($token, $oauthKey);
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
        } catch (ApiTooManyRequestsException | CdnTemporarilyUnavailableException | TransborderCheckServiceUnavailableException $e) {
            $result = $this->getResultCheckCodes($this->cdnService->getCdn(true), $codes);
            $this->saveInDb($result);
            return $result;
        } catch (\Exception $e) {
            $this->log("Error checking codes: " . $e->getMessage());
            throw $e;
        }
    }

    protected function getResultCheckCodes(Hosts $hosts, array $codes): CodesCheckResult
    {
        if ($hosts->transborderServiceUnavailable) {
            $this->log("Cross-border code verification service is not available.");
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
                $this->log("error checking code on host - {$host->url}");
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
            $this->log("error in saveInDb: " . $e->getMessage());
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
