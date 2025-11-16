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
        $this->oauthKey = $options['MARKING_OAUTH_KEY'];
        $this->token = $options['MARKING_TOKEN'];
        $this->defaultFiscalDriveNumber = $options['MARKING_DEFAULT_FISKAL_DRIVE_NUMBER'];
        $this->isTest = $options['MARKING_TEST'] === 'Y';
        $this->logsEnable = $options['MARKING_LOGS'] === 'Y';
        $this->baseUrl = $this->isTest ? $options['MARKING_BASE_TEST_URL'] : $options['MARKING_BASE_PROD_URL'];
    }

    public function getModuleId(): string
    {
        return 'beeralex.marking';
    }
}
