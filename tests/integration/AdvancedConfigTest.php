<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Integration;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Configurator;
use Inpsyde\Wonolog\Data\Notice;
use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\HookListener\ActionListener;
use Inpsyde\Wonolog\HookListener\QueryErrorsListener;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\IntegrationTestCase;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\AssertionFailedError;
use Psr\Log\LogLevel;

use function Inpsyde\Wonolog\makeLogger;

/**
 * @runTestsInSeparateProcesses
 */
class AdvancedConfigTest extends IntegrationTestCase
{
    /**
     * @var string
     */
    private $logFile;

    /**
     * @var TestHandler
     */
    private $testHandler;

    /**
     * @param Configurator $configurator
     * @return void
     */
    protected function bootstrapWonolog(Configurator $configurator): void
    {
        $listener = new class implements ActionListener {
            public function listenTo(): array
            {
                return ['listen_to_me'];
            }

            public function update(string $hook, array $args, LogActionUpdater $updater): void
            {
                $updater->update(new Notice('Test hook fired', 'TESTS', $args));
            }
        };

        $dir = vfsStream::setup('root', 0777);
        $defaultHandler = FileHandler::new()
            ->disableBuffering()
            ->withFolder($dir->url() . '/logs')
            ->withFilename('wonolog.log')
            ->withMinimumLevel(Logger::NOTICE);

        $this->logFile = $dir->url() . '/logs/wonolog.log';
        $this->testHandler = new TestHandler();

        $configurator
            ->disableFallbackHandler()
            ->pushHandler($defaultHandler, 'default-handler')
            ->removeHandlerFromChannels('default-handler', Channels::SECURITY)
            ->pushHandlerForChannels($this->testHandler, 'test-handler', Channels::DEBUG, 'TESTS')
            ->disableAllDefaultHookListeners()
            ->addActionListener(new QueryErrorsListener(Logger::NOTICE))
            ->addActionListener($listener, 'test-listener')
            ->registerLogHook('my-plugin.log', 'MY_PLUGIN')
            ->registerLogHook('something.else.happened')
            ->withIgnorePattern('cron job performed in [0-9\.]+ seconds')
            ->disableWpContextProcessor()
            ->pushProcessor('test-processor', static function (array $record): array {
                empty($record['extra']) and $record['extra'] = [];
                $record['extra']['testClass'] = __CLASS__;
                return $record;
            });
    }

    /**
     * @test
     */
    public function testLogFromArrayInBothHandlers(): void
    {
        do_action(
            'wonolog.log',
            [
                'message' => 'Something happened.',
                'channel' => Channels::DEBUG,
                'level' => LogLevel::NOTICE,
                'context' => ['foo'],
            ]
        );

        static::assertTrue($this->testHandler->hasNoticeThatContains('Something happened.'));

        $this->assertLogFileHasLine('Something happened.', Channels::DEBUG, 'notice', ['foo']);
    }

    /**
     * @test
     */
    public function testLogFromArrayInTestHandlerOnlyDueToMinimumLevel(): void
    {
        do_action(
            'wonolog.log',
            [
                'message' => 'Something happened.',
                'level' => LogLevel::DEBUG,
            ]
        );

        static::assertTrue($this->testHandler->hasDebugThatContains('Something happened.'));
        static::assertFalse(file_exists($this->logFile));
    }

    /**
     * @test
     */
    public function testLogFromArrayInDefaultHandlerOnlyDueToChannel(): void
    {
        do_action(
            'wonolog.log',
            [
                'message' => 'Something happened.',
                'channel' => Channels::HTTP,
                'level' => LogLevel::NOTICE
            ]
        );

        static::assertFalse($this->testHandler->hasNoticeThatContains('Something happened.'));

        $this->assertLogFileHasLine('Something happened.', Channels::HTTP, 'NOTICE');
    }

    /**
     * @test
     */
    public function testLogFromArrayNoWhereOnlyDueToChannel(): void
    {
        do_action(
            'wonolog.log',
            [
                'message' => 'Something happened.',
                'channel' => Channels::SECURITY,
                'level' => LogLevel::NOTICE
            ]
        );

        static::assertFalse($this->testHandler->hasNoticeThatContains('Something happened.'));

        static::assertFalse(file_exists($this->logFile));
    }

    /**
     * @test
     */
    public function testLogFromArrayNowhereDueToIgnorePattern(): void
    {
        do_action(
            'wonolog.log',
            [
                'message' => 'cron job performed in 5.0256 seconds',
                'channel' => Channels::DEBUG,
                'level' => LogLevel::NOTICE
            ]
        );

        static::assertFalse($this->testHandler->hasNoticeThatContains('cron job'));
        static::assertFalse(file_exists($this->logFile));
    }

    /**
     * @test
     */
    public function testLogFromArrayInBothHandlersViaHookHandler(): void
    {
        do_action('listen_to_me', 'Hello', 'World');

        static::assertTrue($this->testHandler->hasNoticeThatContains('Test hook fired'));

        $this->assertLogFileHasLine('Test hook fired', 'TESTS', 'notice', ['Hello', 'World']);
    }

    /**
     * @test
     */
    public function testLogFromStringViaAliasedHookWithCustomChannelInTestHandler(): void
    {
        do_action('my-plugin.log.error', 'Hello world');

        $this->assertLogFileHasLine('Hello world', 'MY_PLUGIN', 'ERROR');
    }

    /**
     * @test
     */
    public function testLogFromArrayViaAliasedHookWithCustomChannelOverride(): void
    {
        do_action(
            'my-plugin.log.warning',
            [
                'message' => 'Lorem ipsum',
                'channel' => 'TESTS',
            ]
        );

        $this->assertLogFileHasLine('Lorem ipsum', 'TESTS', 'WARNING');
    }

    /**
     * @test
     */
    public function testLogFromArrayViaAliasedHookInBothHandlers(): void
    {
        do_action(
            'something.else.happened.warning',
            [
                'message' => 'He was an old man who fished in a skiff in the Gulf Stream',
                'channel' => 'TESTS',
            ]
        );

        static::assertTrue($this->testHandler->hasWarningThatContains('old man who fished'));

        $this->assertLogFileHasLine('old man who fished', 'TESTS', 'WARNING');
    }

    /**
     * @test
     */
    public function testLogViaDefaultHookListenerToTestHandlerOnlyDueChannel(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://example.com/foo/';
        global $wp_query;
        $wp_query->is_404 = true;

        $wp = new \WP();
        $wp->query_vars = [];
        $wp->matched_rule = 'rule';
        $wp->query_vars['error'] = 'Error one';

        $expectedMessage = 'Error on frontend request';
        $expectedContext = [
            'error' => ['Error one', '404 Page not found'],
            'query_vars' => ['error' => 'Error one'],
            'matched_rule' => 'rule',
        ];

        do_action('wp', $wp);

        $this->assertLogFileHasLine($expectedMessage, Channels::HTTP, 'NOTICE', $expectedContext);
        static::assertSame([], $this->testHandler->getRecords());
    }

    /**
     * @test
     */
    public function testLogFromArrayInBothHandlersUsingPsrLogger(): void
    {
        $logger = makeLogger('TESTS');

        $logger->notice('Something happened.', ['user_password' => 'bar']);

        static::assertTrue($this->testHandler->hasNoticeThatContains('Something happened.'));

        $this->assertLogFileHasLine(
            'Something happened.',
            'TESTS',
            'notice',
            ['user_password' => '***']
        );
    }

    /**
     * @param string $message
     * @param string $channel
     * @param string $level
     * @param array|null $context
     * @return void
     */
    private function assertLogFileHasLine(
        string $message,
        string $channel,
        string $level,
        ?array $context = null
    ): void {

        if (!file_exists($this->logFile)) {
            throw new AssertionFailedError(
                "Log file does not exist, expected log containing '{$message}'."
            );
        }

        $messageLog = $message;
        $context and $messageLog .= sprintf(' (%s)', json_encode($context));

        $lines = @file($this->logFile) ?: [];
        foreach ((array)$lines as $line) {
            preg_match(
                '~^\[[^\]]+\] (?<channel>[A-Z_-]+)\.(?<level>[A-Z]+): (?<txt>[^\[\{]+) (?<more>.+?)$~',
                trim($line),
                $matches
            );

            if (
                !$matches
                || strtoupper($channel) !== ($matches['channel'] ?? null)
                || strtoupper($level) !== ($matches['level'] ?? null)
            ) {
                continue;
            }

            $more = $matches['more'] ?? '';
            $extra = json_encode(['testClass' => __CLASS__]);
            if (!preg_match('~' . preg_quote($extra, '~') . '~', $more)) {
                continue;
            }

            if (
                $context !== null
                && !preg_match('~' . preg_quote(json_encode($context), '~') . '~', $more)
            ) {
                continue;
            }

            $logText = $matches['txt'] ?? '';
            if (!preg_match('~' . preg_quote($message, '~') . '~', $logText)) {
                continue;
            }

            static::assertTrue(true);

            return;
        }

        $error = "Log line containing '{$messageLog}' not found in channel '{$channel}'.\n\n";
        $error .= "Log file:\n\n" . implode("\n", $lines);
        throw new AssertionFailedError($error);
    }
}
