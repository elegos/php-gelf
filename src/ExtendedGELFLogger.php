<?php declare(strict_types=1);

namespace GiacomoFurlan\Graylog;

use Psr\Log\LogLevel;

/**
 * Class ExtendedGELFLogger
 * Allows to log a more detailed information via $logger->gelf*
 * @package GiacomoFurlan\Graylog
 */
class ExtendedGELFLogger extends GELFLogger
{
    /**
     * @param GELF $message
     *
     * @throws GELFException
     */
    public function gelfDebug(GELF $message): void
    {
        $this->gelfLog(LogLevel::DEBUG, $message);
    }

    /**
     * @param GELF $message
     *
     * @throws GELFException
     */
    public function gelfInfo(GELF $message): void
    {
        $this->gelfLog(LogLevel::INFO, $message);
    }

    /**
     * @param GELF $message
     *
     * @throws GELFException
     */
    public function gelfNotice(GELF $message): void
    {
        $this->gelfLog(LogLevel::NOTICE, $message);
    }

    /**
     * @param GELF $message
     *
     * @throws GELFException
     */
    public function gelfWarning(GELF $message): void
    {
        $this->gelfLog(LogLevel::WARNING, $message);
    }

    /**
     * @param GELF $message
     *
     * @throws GELFException
     */
    public function gelfError(GELF $message): void
    {
        $this->gelfLog(LogLevel::ERROR, $message);
    }

    /**
     * @param GELF $message
     *
     * @throws GELFException
     */
    public function gelfCritical(GELF $message): void
    {
        $this->gelfLog(LogLevel::CRITICAL, $message);
    }

    /**
     * @param GELF $message
     *
     * @throws GELFException
     */
    public function gelfAlert(GELF $message): void
    {
        $this->gelfLog(LogLevel::ALERT, $message);
    }

    /**
     * @param GELF $message
     *
     * @throws GELFException
     */
    public function gelfEmergency(GELF $message): void
    {
        $this->gelfLog(LogLevel::EMERGENCY, $message);
    }

    /**
     * Log the GELF object
     *
     * @param string $level LogLevel::*
     * @param GELF   $message
     *
     * @see LogLevel
     *
     * @throws GELFException
     */
    public function gelfLog(string $level, GELF $message): void
    {
        $message->setLevel(static::getGELFLogLevelFromPSRLogLevel($level));

        if ($this->host && !$message->getHost()) {
            $message->setHost($this->host);
        }

        $payload = \json_encode($message);
        $this->checkSize($payload);

        $this->writer->write($payload);
    }
}
