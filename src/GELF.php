<?php

namespace GiacomoFurlan\Graylog;

use DateTime;
use JsonSerializable;

class GELF implements JsonSerializable
{
    public const LEVEL_EMERGENCY = 0;
    public const LEVEL_ALERT     = 1;
    public const LEVEL_CRITICAL  = 2;
    public const LEVEL_ERROR     = 3;
    public const LEVEL_WARNING   = 4;
    public const LEVEL_NOTICE    = 5;
    public const LEVEL_INFO      = 6;
    public const LEVEL_DEBUG     = 7;

    /** @var string */
    protected $host;

    /** @var string */
    protected $shortMessage;

    /** @var string|null */
    protected $fullMessage;
    
    /** @var int|null */
    protected $level;
    
    /** @var mixed[] */
    protected $context;

    public function __construct(string $shortMessage, array $context = [])
    {
        $this->shortMessage = $shortMessage;
        $this->context = $context;
    }

    public function setShortMessage(string $shortMessage): self
    {
        $this->shortMessage = $shortMessage;

        return $this;
    }

    public function getShortMessage(): string
    {
        return $this->shortMessage;
    }

    public function setFullMessage(string $fullMessage): self
    {
        $this->fullMessage = $fullMessage;

        return $this;
    }

    public function getFullMessage(): ?string
    {
        return $this->fullMessage;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level ?? 1;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @param string     $var
     * @param string|int $val
     *
     * @return self
     */
    public function setContextEntryset(string $var, $val): self
    {
        $this->context[$var] = $val;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws GELFException
     */
    public function jsonSerialize()
    {
        if (!$this->host) {
            throw new GELFException('Host not set or empty', GELFException::CODE_MISSING_HOST);
        }

        $object = [
            'version' => '1.1',
            'host' => $this->host,
            'short_message' => $this->shortMessage,
            'timestamp' => (new DateTime())->getTimestamp(),
            'level' => $this->getLevel(),
        ];

        if ($this->fullMessage) {
            $object['full_message'] = $this->fullMessage;
        }
        
        foreach ($this->context as $var => $val) {
            $object["_$var"] = $val;
        }

        return $object;
    }
}
