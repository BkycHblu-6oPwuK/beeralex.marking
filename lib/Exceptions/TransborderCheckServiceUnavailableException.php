<?php
namespace Beeralex\Marking\Exceptions;

use Beeralex\Core\Exceptions\ApiClientException;

/** status 500x + code 5000 */
class TransborderCheckServiceUnavailableException extends ApiClientException {}