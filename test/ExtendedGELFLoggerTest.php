<?php

namespace GiacomoFurlan\Graylog\Test;

use GiacomoFurlan\Graylog\ExtendedGELFLogger;
use GiacomoFurlan\Graylog\GELF;
use GiacomoFurlan\Graylog\GELFException;
use GiacomoFurlan\Graylog\GELFLogger;
use GiacomoFurlan\Graylog\WriterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LogLevel;

class ExtendedGELFLoggerTest extends TestCase
{
    /**
     * @throws \GiacomoFurlan\Graylog\GELFException
     */
    public function testGelfDebugLevelIsCorrect(): void
    {
        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals(GELF::LEVEL_DEBUG, $json->level);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'host');
        $logger->gelfDebug(new GELF('Whatever!'));
    }

    /**
     * @throws \GiacomoFurlan\Graylog\GELFException
     */
    public function testGelfInfoLevelIsCorrect(): void
    {
        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals(GELF::LEVEL_INFO, $json->level);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'host');
        $logger->gelfInfo(new GELF('Whatever!'));
    }

    /**
     * @throws \GiacomoFurlan\Graylog\GELFException
     */
    public function testGelfNoticeLevelIsCorrect(): void
    {
        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals(GELF::LEVEL_NOTICE, $json->level);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'host');
        $logger->gelfNotice(new GELF('Whatever!'));
    }

    /**
     * @throws \GiacomoFurlan\Graylog\GELFException
     */
    public function testGelfWarningLevelIsCorrect(): void
    {
        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals(GELF::LEVEL_WARNING, $json->level);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'host');
        $logger->gelfWarning(new GELF('Whatever!'));
    }

    /**
     * @throws \GiacomoFurlan\Graylog\GELFException
     */
    public function testGelfErrorLevelIsCorrect(): void
    {
        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals(GELF::LEVEL_ERROR, $json->level);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'host');
        $logger->gelfError(new GELF('Whatever!'));
    }

    /**
     * @throws \GiacomoFurlan\Graylog\GELFException
     */
    public function testGelfCriticalLevelIsCorrect(): void
    {
        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals(GELF::LEVEL_CRITICAL, $json->level);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'host');
        $logger->gelfCritical(new GELF('Whatever!'));
    }

    /**
     * @throws \GiacomoFurlan\Graylog\GELFException
     */
    public function testGelfInfoAlertIsCorrect(): void
    {
        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals(GELF::LEVEL_ALERT, $json->level);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'host');
        $logger->gelfAlert(new GELF('Whatever!'));
    }

    /**
     * @throws \GiacomoFurlan\Graylog\GELFException
     */
    public function testGelfEmergencyAlertIsCorrect(): void
    {
        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals(GELF::LEVEL_EMERGENCY, $json->level);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'host');
        $logger->gelfEmergency(new GELF('Whatever!'));
    }

    /**
     * @throws GELFException
     */
    public function testLoggerWillThrowExceptionHostNotSet(): void
    {
        $writer = $this->prophesize(WriterInterface::class)->reveal();

        $logger = new ExtendedGELFLogger($writer, '');
        $this->expectException(GELFException::class);
        $this->expectExceptionCode(GELFException::CODE_MISSING_HOST);

        $logger->gelfError(new GELF('Whatever'));
    }

    public function testLoggerWillSendGELFToWriter(): void
    {
        $gelf = new GELF('short message', ['var' => 'val']);
        $gelf
            ->setHost('myHost' /* precedence over logger host*/)
            ->setLevel(1 /* will be ignored */)
            ->setFullMessage('This is the full message');

        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) use ($gelf) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals($gelf->getHost(), $json->host);
            TestCase::assertEquals(GELFLogger::getGELFLogLevelFromPSRLogLevel(LogLevel::ERROR), $json->level);
            TestCase::assertEquals($gelf->getShortMessage(), $json->short_message);
            TestCase::assertEquals($gelf->getFullMessage(), $json->full_message);
            foreach ($gelf->getContext() as $var => $val) {
                TestCase::assertEquals($val, $json->{"_$var"});
            }
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), 'this will be ignored');
        $logger->gelfError($gelf);
    }

    public function testLoggerWillSetHostInMessage(): void
    {
        $gelf = new GELF('short message', ['var' => 'val']);
        $host = 'myHost';
        $gelf
            ->setLevel(1 /* will be ignored */)
            ->setFullMessage('This is the full message');

        $writer = $this->prophesize(WriterInterface::class);
        $writer->write(Argument::type('string'))->will(function (array $arguments) use ($host) {
            $json = \json_decode($arguments[0]);

            TestCase::assertEquals($host, $json->host);
        })->shouldBeCalledTimes(1);

        $logger = new ExtendedGELFLogger($writer->reveal(), $host);
        $logger->gelfInfo($gelf);
    }
}
