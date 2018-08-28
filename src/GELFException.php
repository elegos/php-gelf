<?php

namespace GiacomoFurlan\Graylog;

class GELFException extends \Exception
{
    public const CODE_MESSAGE_TOO_BIG = 5000;
    public const CODE_MISSING_HOST = 5001;
}
