<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\AbstractLogger;

class PsrBridge extends AbstractLogger
{
    /**
     * @var LogActionUpdater
     */
    private $updater;

    /**
     * @var Channels
     */
    private $channels;

    /**
     * @var string|null
     */
    private $defaultChannel;

    /**
     * @var PsrLogMessageProcessor
     */
    private $processor;

    /**
     * @param LogActionUpdater $updater
     * @param Channels $channels
     * @return PsrBridge
     */
    public static function new(LogActionUpdater $updater, Channels $channels): PsrBridge
    {
        return new self($updater, $channels);
    }

    /**
     * @param LogActionUpdater $updater
     * @param Channels $channels
     */
    private function __construct(LogActionUpdater $updater, Channels $channels)
    {
        $this->updater = $updater;
        $this->channels = $channels;
        $this->processor = new PsrLogMessageProcessor(null, true);
    }

    /**
     * @param string $defaultChannel
     * @return static
     */
    public function withDefaultChannel(string $defaultChannel): PsrBridge
    {
        $this->channels->addChannel($defaultChannel);
        $this->defaultChannel = $defaultChannel;

        return $this;
    }

    /**
     * @param mixed $level
     * @param mixed $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if ($message instanceof \Throwable) {
            $context['exception'] = $message;
            $message = $message->getMessage();
        }

        /** @see https://www.php-fig.org/psr/psr-3/#13-context */
        $throwable = $context['exception'] ?? null;
        if ($throwable && !($throwable instanceof \Throwable)) {
            $throwable = null;
        }

        $message = $this->serializeMessage($message);

        $level = LogLevel::normalizeLevel($level);
        if (!$level) {
            $level = $throwable ? LogLevel::CRITICAL : LogLevel::DEBUG;
        }

        $channel = $context[LogData::CHANNEL] ?? null;
        if (!$channel || !is_string($channel)) {
            $channel = $throwable
                ? ($this->defaultChannel ?? Channels::PHP_ERROR)
                : ($this->defaultChannel ?? $this->channels->defaultChannel());
        }
        unset($context[LogData::CHANNEL]);

        $context = (array)$this->maybeSerializeComplex($context, -1, true);
        /** @psalm-suppress InvalidArgument */
        $record = ($this->processor)(compact('message', 'context'));
        array_key_exists('message', $record) and $message = (string)$record['message'];
        array_key_exists('context', $record) and $context = (array)$record['context'];

        $this->updater->update(new Log($message, $level, $channel, $context));
    }

    /**
     * @param mixed $message
     * @param int $level
     * @return string
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    private function serializeMessage($message, int $level = 0): string
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity

        if (is_string($message)) {
            return $message;
        }

        if ($message === null) {
            return 'NULL';
        }

        if (is_bool($message)) {
            return $message ? 'TRUE' : 'FALSE';
        }

        if (is_float($message)) {
            if (is_infinite($message)) {
                return ($message > 0 ? '' : '-') . 'INF';
            }

            if (is_nan($message)) {
                return 'NaN';
            }

            return (string)$message;
        }

        if (is_resource($message)) {
            return sprintf('Resource (%s)', get_resource_type($message));
        }

        if (is_scalar($message)) {
            return (string)$message;
        }

        $message = $this->maybeSerializeComplex($message, $level);
        if (is_string($message)) {
            return $message;
        }

        if (is_array($message)) {
            $encoded = json_encode($message);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $encoded;
            }
        }

        return var_export($message, true);
    }

    /**
     * @param mixed $message
     * @param int $level
     * @param bool $keepArray
     * @return mixed
     */
    private function maybeSerializeComplex($message, int $level = 0, bool $keepArray = false)
    {
        if ($message instanceof \stdClass) {
            $message = (array)$message;
        }

        $isIterable = is_iterable($message);

        if (is_object($message)) {
            try {
                return $this->maybeSerializeObjectMessage($message, !$isIterable);
            } catch (\Throwable $throwable) {
                // noop
            }
        }

        if (!$isIterable) {
            return $message;
        }

        $iterated = [];
        foreach ($message as $key => $value) {
            if ($level > 0) {
                /** @psalm-suppress MixedArrayOffset */
                $iterated[$key] = $keepArray
                    ? $value
                    : $this->serializeMessage($value, $level + 1);
                continue;
            }
            /** @psalm-suppress MixedArrayOffset */
            $iterated[$key] = ($keepArray && !is_resource($value))
                ? $this->maybeSerializeComplex($value, $level + 1, true)
                : $this->serializeMessage($value, $level + 1);
        }

        return $iterated;
    }

    /**
     * @param object $object
     * @param bool $forceString
     * @return mixed
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel.High
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    private function maybeSerializeObjectMessage(object $object, bool $forceString)
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
        // phpcs:enable Inpsyde.CodeQuality.NestingLevel.High
        // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration

        switch (true) {
            case ($object instanceof \DateTimeInterface):
                return $object->format('r');
            case ($object instanceof \Throwable):
                return get_class($object) . ': ' . $object->getMessage();
            case ($object instanceof \WP_Error):
                return $object->get_error_message();
            case ($object instanceof \WP_Post):
                return "WP_Post instance (ID: {$object->ID})";
            case ($object instanceof \WP_Term):
                return "WP_Term instance (ID: {$object->term_id})";
            case ($object instanceof \WP_User):
                return "WP_User instance (ID: {$object->ID})";
            case ($object instanceof \WP_Comment):
                return "WP_Comment instance (ID: {$object->comment_ID})";
            case ($object instanceof \WP_Post_Type):
                return "WP_Post_Type instance ({$object->name})";
            case ($object instanceof \WP_Taxonomy):
                return "WP_Taxonomy instance ({$object->name})";
            case (is_callable([$object, '__toString'])):
                return (string)$object;
            case ($forceString && ($object instanceof \JsonSerializable)):
                // phpcs:disable WordPress.PHP.NoSilencedErrors
                $encoded = @json_encode($object, 32) ?: '{}';
                $plain = @json_decode($encoded, true, 32);
                // phpcs:enable WordPress.PHP.NoSilencedErrors
                if (is_array($plain)) {
                    return $this->maybeSerializeComplex($plain);
                }
        }

        if (!$forceString) {
            throw new \Exception('Not serializable');
        }

        return sprintf('%s instance (#%s)', get_class($object), spl_object_hash($object));
    }
}
