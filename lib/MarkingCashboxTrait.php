<?php
declare(strict_types=1);
namespace Beeralex\Marking;

use Bitrix\Sale\Cashbox\Check;
use Beeralex\Marking\CodeCheckRepository;
use Beeralex\Marking\Entity\Codes\CodesCheckResult;
use Beeralex\Marking\Factory\CodesCheckServiceFactory;

trait MarkingCashboxTrait
{
    /**
     * @var CodesCheckResult[] keyed by CIS
     */
    protected array $checkResults = [];

    public function buildCheckQuery(Check $check): array
    {
        $data = $check->getDataForCheck();
        $codes = [];

        foreach ($data['items'] as $item) {
            if (!empty($item['marking_code'])) {
                $codes[] = $item['marking_code'];
            }
        }

        if (!empty($codes)) {
            $results = service(CodeCheckRepository::class)->findByCisList($codes);

            if (!$results) {
                $results = [service(CodesCheckServiceFactory::class)->createDefault()->check($codes)];
            }

            foreach ($results as $result) {
                foreach ($result->codes as $cis => $_) {
                    $this->checkResults[$cis] = $result;
                }
            }
        }

        $result = parent::buildCheckQuery($check);

        return $result;
    }

    protected function buildPosition(array $checkData, array $item): array
    {
        $result = parent::buildPosition($checkData, $item);

        if (empty($item['marking_code'])) {
            return $result;
        }

        $cis = $item['marking_code'];
        $checkResult = $this->checkResults[$cis] ?? null;

        if (!$checkResult) {
            throw new \Exception("not find code in repository - {$cis}");
        }

        $code = $checkResult->get($cis);

        if (!$code || !$code->verified) {
            throw new \Exception("not valid code - {$cis}");
        }

        if (!$checkResult->transborderServiceUnavailable) {
            $result['sectoral_item_props'] = [
                $this->buildSectoralItemProps($checkResult)
            ];
        }

        return $result;
    }

    protected function buildSectoralItemProps(CodesCheckResult $checkResult): array
    {
        return [
            "federal_id" => '030',
            "date" => '21.11.2023',
            "number" => '1944',
            "value" => "UUID={$checkResult->reqId}&Time={$checkResult->reqTimestamp}",
        ];
    }
}
