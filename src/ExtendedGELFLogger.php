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
     * @param bool $flush
     *
     * @throws GELFException
     */
    public function gelfDebug(GELF $message, bool $flush = true): void
    {
        $this->gelfLog(LogLevel::DEBUG, $message, $flush);
    }

    /**
     * @param GELF $message
     * @param bool $flush
     *
     * @throws GELFException
     */
    public function gelfInfo(GELF $message, bool $flush = true): void
    {
        $this->gelfLog(LogLevel::INFO, $message, $flush);
    }

    /**
     * @param GELF $message
     * @param bool $flush
     *
     * @throws GELFException
     */
    public function gelfNotice(GELF $message, bool $flush = true): void
    {
        $this->gelfLog(LogLevel::NOTICE, $message, $flush);
    }

    /**
     * @param GELF $message
     * @param bool $flush
     *
     * @throws GELFException
     */
    public function gelfWarning(GELF $message, bool $flush = true): void
    {
        $this->gelfLog(LogLevel::WARNING, $message, $flush);
    }

    /**
     * @param GELF $message
     * @param bool $flush
     *
     * @throws GELFException
     */
    public function gelfError(GELF $message, bool $flush = true): void
    {
        $this->gelfLog(LogLevel::ERROR, $message, $flush);
    }

    /**
     * @param GELF $message
     * @param bool $flush
     *
     * @throws GELFException
     */
    public function gelfCritical(GELF $message, bool $flush = true): void
    {
        $this->gelfLog(LogLevel::CRITICAL, $message, $flush);
    }

    /**
     * @param GELF $message
     * @param bool $flush
     *
     * @throws GELFException
     */
    public function gelfAlert(GELF $message, bool $flush = true): void
    {
        $this->gelfLog(LogLevel::ALERT, $message, $flush);
    }

    /**
     * @param GELF $message
     * @param bool $flush
     *
     * @throws GELFException
     */
    public function gelfEmergency(GELF $message, bool $flush = true): void
    {
        $this->gelfLog(LogLevel::EMERGENCY, $message, $flush);
    }

    /**
     * Log the GELF object
     *
     * @param string $level LogLevel::*
     * @param GELF   $message
     * @param bool $flush
     *
     * @see LogLevel
     *
     * @throws GELFException
     */
    public function gelfLog(string $level, GELF $message, bool $flush = true): void
    {
        $message->setLevel(static::getGELFLogLevelFromPSRLogLevel($level));
        $message->setContextEntryset('level_label', $level);

        if ($this->host && !$message->getHost()) {
            $message->setHost($this->host);
        }

        $payload = \json_encode($message);

        $this->writer->write($payload, $flush);
    }
}
