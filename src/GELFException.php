<?php declare(strict_types=1);

namespace GiacomoFurlan\Graylog;

class GELFException extends \Exception
{
    public const CODE_MESSAGE_TOO_BIG   = 5000;
    public const CODE_MISSING_HOST      = 5001;
    public const CODE_CANT_SEND_MESSAGE = 5002;
}
