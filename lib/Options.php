<?php
declare(strict_types=1);
namespace Beeralex\Marking;

use Beeralex\Core\Config\AbstractOptions;

final class Options extends AbstractOptions
{
    /** url для авторизации и получения cdn */
    public readonly string $baseUrl;
    /** любой документ подписанный с помощью УКЭП в base64 */
    public readonly string $oauthKey;
    /** токен полученный через лк, если oauthKey пустой, то используется этот токен */
    public readonly string $token;
    public readonly string $defaultFiscalDriveNumber;
    public readonly bool $isTest;
    public readonly bool $logsEnable;

    protected function mapOptions(array $options): void
    {
        $this->oauthKey = $options['marking_oauth_key'];
        $this->token = $options['marking_token'];
        $this->defaultFiscalDriveNumber = $options['marking_default_fiskal_drive_number'];
        $this->isTest = $options['marking_test'] === 'Y';
        $this->logsEnable = $options['marking_logs'] === 'Y';
        $this->baseUrl = $this->isTest ? $options['marking_base_test_url'] : $options['marking_base_prod_url'];
    }

    public function getModuleId(): string
    {
        return 'beeralex.marking';
    }
}
