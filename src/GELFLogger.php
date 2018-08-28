<?php

namespace GiacomoFurlan\Graylog;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class GELFLogger extends AbstractLogger
{
    /** @var WriterInterface */
    protected $writer;

    /** @var string */
    protected $host;

    /**
     * GELFLogger constructor.
     *
     * @param WriterInterface $writer
     * @param string $host host, source or application that send the messages
     */
    public function __construct(WriterInterface $writer, string $host)
    {
        $this->writer = $writer;
        $this->host = $host;
    }

    public static function getGELFLogLevelFromPSRLogLevel(string $level): int
    {
        switch ($level) {
            case LogLevel::DEBUG:
                return GELF::LEVEL_DEBUG;
            case LogLevel::INFO:
                return GELF::LEVEL_INFO;
            case LogLevel::NOTICE:
                return GELF::LEVEL_NOTICE;
            case LogLevel::WARNING:
                return GELF::LEVEL_WARNING;
            case LogLevel::ERROR:
                return GELF::LEVEL_ERROR;
            case LogLevel::CRITICAL:
                return GELF::LEVEL_CRITICAL;
            case LogLevel::ALERT:
                return GELF::LEVEL_ALERT;
            case LogLevel::EMERGENCY:
                return GELF::LEVEL_EMERGENCY;

            default:
                return GELF::LEVEL_ALERT;
        }
    }

    /**
     * @param string $payload
     *
     * @throws GELFException
     */
    protected function checkSize(string $payload)
    {
        if (\strlen($payload) > 1024) {
            throw new GELFException('Message too big', GELFException::CODE_MESSAGE_TOO_BIG);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed|int $level expecting LogLevel::*
     *
     * @throws GELFException
     */
    public function log($level, $message, array $context = []): void
    {
        $gelf = new GELF($message);
        $gelf->setHost($this->host);
        $gelf
            ->setLevel(static::getGELFLogLevelFromPSRLogLevel($level))
            ->setContext($context);

        $payload = \json_encode($gelf);
        $this->checkSize($payload);

        $this->writer->write($payload);
    }
}
