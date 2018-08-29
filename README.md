# PHP GELF logger

This library aims to help the developer with both a PSR-3 compliant and a custom (extended) logger to send data to
Graylog 2 via the Graylog Extended Log Format.

## Class information

### `GiacomoFurlan\Graylog\GELFLogger`
It implements the Psr\Log\LoggerInterface interface, so use it as a standard logger.

It can set the short message, the context and the log level.

### `GiacomoFurlan\Graylog\ExtendedGELFLogger`
It extends the previous class, but add `gelf*` functions to send more complex information.

- gelfLog
- gelfAlert
- gelfCritical
- gelfDebug
- gelfEmergency
- gelfError
- gelfInfo
- gelfNotice
- gelfWarning

These function accept a GELF object and the possibility to flush the messages instantly (default true).

### `GiacomoFurlan\Graylog\GELF`
A data-transfer object used by `ExtendedGELFLogger`. It allows to set the short and full messages, to overwrite
the `host` information and to add variables to the context.

### `GiacomoFurlan\Graylog\GELFException`
It can be thrown trying to send the information.

- Code `GELFException::CODE_MISSING_HOST`: the host is's not set or empty
- Code `GELFException::CODE_CANT_SEND_MESSAGE`: an error occurred trying to send the packet

More information may be gathered reading the exception's message.

## Usage example

```php

use GiacomoFurlan\Graylog\ExtendedGELFLogger;
use GiacomoFurlan\Graylog\GELF;
use GiacomoFurlan\Graylog\GELFException;
use GiacomoFurlan\Graylog\GELFLogger;
use GiacomoFurlan\Graylog\UDPWriter;
use Psr\Log\LogLevel;

// ...

$address = 'ip.or.dns.of.graylog.server';
$port = 12201; // or whatever port is configured in Graylog 2
$hostId = 'MyHost'; // host identifier

// Instantiate the writer
$writer = new UDPWriter($address, $port);
// Instantiate the logger (use one or the other... or both)
$simpleLogger = new GELFLogger($writer, $hostId);
$extendedLogger = new ExtendedGELFLogger($writer, $hostId);

// ...

// GELFLogger usage example
try {
  // ...
} catch (\Throwable $exception) {
    $simpleLogger->log(LogLevel::ERROR, $exception->getMessage(), ['code' => $exception->getCode()]);
    // or
    $simpleLogger->error($exception->getMessage(), ['code' => $exception->getCode()]);
}

// ExtendedGELFLogger usage example
try {
    // ...
} catch (\Throwable $exception) {
    $message = new GELF($exception->getMessage(), ['code' => $exception->getCode()]);
    
    $message->setHost('Overwritten host');
    $message->setFullMessage($exception->getTraceAsString());
    $message->setContextEntryset('fileName', $exception->getFile());
    $message->setContextEntryset('fileLine', $exception->getLine());
    
    $extendedLogger->gelfError($message);
}

```
