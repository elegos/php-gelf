<?php

namespace GiacomoFurlan\Graylog\Test;


use GiacomoFurlan\Graylog\GELFException;
use GiacomoFurlan\Graylog\UDPWriter;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class UDPWriterTest extends TestCase
{
    use PHPMock;

    protected $classNamespace;

    public function __construct($name = null, $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $refClass = new \ReflectionClass(UDPWriter::class);
        $this->classNamespace = $refClass->getNamespaceName();
    }

    public function testWriterWillSendMessage(): void
    {
        $address = '127.0.0.1';
        $port = 5099;
        $testData = 'Test message';

        $socketCreateMock = $this->getFunctionMock($this->classNamespace, 'socket_create');
        $socketCreateMock->expects(TestCase::once())->willReturnCallback(function ($domain, $type, $protocol) {
            TestCase::assertEquals(AF_INET, $domain);
            TestCase::assertEquals(SOCK_DGRAM, $type);
            TestCase::assertEquals(SOL_UDP, $protocol);

            return 1;
        });

        $socketSendToMock = $this->getFunctionMock($this->classNamespace, 'socket_sendto');
        $socketSendToMock
            ->expects(TestCase::once())
            ->willReturnCallback(function ($socket, $content, $contentLen, $flags, $fnAddress, $fnPort) use ($address, $port, $testData) {
                TestCase::assertEquals(1, $socket);
                TestCase::assertEquals($testData, $content);
                TestCase::assertEquals(strlen($testData), $contentLen);
                TestCase::assertEquals(0, $flags);
                TestCase::assertEquals($address, $fnAddress);
                TestCase::assertEquals($port, $fnPort);

                return strlen($content);
            });

        $writer = new UDPWriter($address, $port);
        $writer->write($testData);
    }

    public function testWriterWillThrowExceptionSocketCantBeCreated(): void
    {
        $address = '127.0.0.1';
        $port = 5099;
        $testData = 'Test message';

        $socketCreateMock = $this->getFunctionMock($this->classNamespace, 'socket_create');
        $socketCreateMock->expects(TestCase::once())->willReturnCallback(function () {
            return false;
        });

        $this->expectException(GELFException::class);
        $this->expectExceptionCode(GELFException::CODE_CANT_SEND_MESSAGE);

        $writer = new UDPWriter($address, $port);
        $writer->write($testData);
    }

    public function testWriterWillThrowExceptionMessageNotCorrectlySent(): void
    {
        $address = '127.0.0.1';
        $port = 5099;
        $testData = 'Test message';

        $socketCreateMock = $this->getFunctionMock($this->classNamespace, 'socket_create');
        $socketCreateMock->expects(TestCase::once())->willReturnCallback(function ($domain, $type, $protocol) {
            TestCase::assertEquals(AF_INET, $domain);
            TestCase::assertEquals(SOCK_DGRAM, $type);
            TestCase::assertEquals(SOL_UDP, $protocol);

            return 1;
        });

        $socketSendToMock = $this->getFunctionMock($this->classNamespace, 'socket_sendto');
        $socketSendToMock
            ->expects(TestCase::once())
            ->willReturnCallback(function ($socket, $content) use ($address, $port, $testData) {
                return strlen($content) - 5;
            });

        $this->expectException(GELFException::class);
        $this->expectExceptionCode(GELFException::CODE_CANT_SEND_MESSAGE);

        $writer = new UDPWriter($address, $port);
        $writer->write($testData);
    }

    public function testWriterWillEnqueueAndSendMultipleMessages(): void
    {
        $address = '127.0.0.1';
        $port = 5099;

        $socketCreateMock = $this->getFunctionMock($this->classNamespace, 'socket_create');
        $socketCreateMock->expects(TestCase::any())->willReturnCallback(function ($domain, $type, $protocol) {
            return 1;
        });

        $callCounter = 0;
        $messageId = null;
        $seqTot = null;
        $prevSeqNum = null;
        $socketSendToMock = $this->getFunctionMock($this->classNamespace, 'socket_sendto');
        $socketSendToMock
            ->expects(TestCase::any())
            ->willReturnCallback(function ($socket, $content) use (&$callCounter, &$messageId, &$seqTot, &$prevSeqNum) {
                $header = substr($content, 0, 12);
                $fixed = substr($header, 0, 2);
                $msgId = substr($header, 2, 8);

                $seqNum = unpack('C', substr($header, 10, 1));
                $seqNum = array_pop($seqNum);

                if ($seqNum === 0) {
                    if ($seqTot && $prevSeqNum) {
                        TestCase::assertEquals($seqTot, $prevSeqNum + 1); // from 0 to (TOT-1)
                    }

                    $messageId = $msgId;
                    $callCounter = 0;
                    $seqTot = unpack('C', substr($header, 11, 1));
                    $seqTot = array_pop($seqTot);
                }

                $prevSeqNum = $seqNum;

                TestCase::assertEquals("\x1e\x0f", $fixed);
                TestCase::assertEquals($messageId, $msgId);
                TestCase::assertLessThanOrEqual(8192, strlen($content));
                TestCase::assertEquals($callCounter++, $seqNum);
                TestCase::assertLessThanOrEqual($seqTot, $seqNum);

                return strlen($content);
            });

        $writer = new UDPWriter($address, $port);
        for ($i = 0; $i < 200; $i++) {
            $message = \random_bytes(\random_int(6000, 12000));
            $writer->write($message, false);
        }

        $writer->flush();
    }
}