<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

abstract class Serializer
{
    public const FILTER_MASKED_KEYS = 'wonolog.log-masked-keys';

    // JSON_OBJECT_AS_ARRAY|JSON_BIGINT_AS_STRING|JSON_INVALID_UTF8_IGNORE
    private const JSON_DEC_FLAGS = 1048579;

    // JSON_PARTIAL_OUTPUT_ON_ERROR|JSON_UNESCAPED_LINE_TERMINATORS|JSON_UNESCAPED_UNICODE
    private const JSON_ENC_FLAGS = 2816;

    private const SECRET_KEYS = [
        'password',
        'post_password',
        'post-password',
        'postpassword',
        'user_password',
        'user-password',
        'userpassword',
        'client_password',
        'client-password',
        'clientpassword',
        'api_password',
        'api-password',
        'apipassword',
        'auth_password',
        'auth-password',
        'authpassword',
        'secret',
        'user_secret',
        'user-secret',
        'usersecret',
        'client_secret',
        'client-secret',
        'clientsecret',
        'api_secret',
        'api-secret',
        'apisecret',
        'auth_secret',
        'auth-secret',
        'authsecret',
        'auth_key',
        'auth-key',
        'authkey',
        'token',
        'user_token',
        'user-token',
        'usertoken',
        'client_token',
        'client-token',
        'clienttoken',
        'api_token',
        'api-token',
        'apitoken',
        'api_key',
        'api-key',
        'apikey',
        'auth_token',
        'auth-token',
        'authtoken',
    ];

    /**
     * @var list<string>|null
     */
    private static ?array $maskedKeys = null;

    /**
     * @param mixed $message
     * @return string
     */
    final public static function serializeMessage(mixed $message): string
    {
        return self::forceString($message);
    }

    /**
     * @param array $context
     * @return array
     */
    final public static function serializeContext(array $context): array
    {
        return self::maybeMaskInput($context);
    }

    /**
     * @param mixed $input
     * @return string
     *
     * phpcs:disable SlevomatCodingStandard.Complexity.Cognitive
     */
    private static function forceString(mixed $input): string
    {
        // phpcs:enable SlevomatCodingStandard.Complexity.Cognitive
        if (is_string($input)) {
            return $input;
        }

        if ($input === null) {
            return 'NULL';
        }

        if (is_bool($input)) {
            return $input ? 'TRUE' : 'FALSE';
        }

        if (is_resource($input)) {
            return sprintf('Resource (%s)', get_resource_type($input));
        }

        if (is_numeric($input)) {
            if (is_infinite((float) $input)) {
                return ($input > 0 ? '' : '-') . 'INF';
            }

            if (is_nan((float) $input)) {
                return 'NaN';
            }

            return (string) $input;
        }

        if (is_array($input) || ($input instanceof \stdClass)) {
            $masked = self::maybeMaskInput((array) $input);

            return (string) json_encode($masked, self::JSON_ENC_FLAGS, 32);
        }

        if (is_object($input)) {
            return self::serializeObject($input, true);
        }

        return gettype($input);
    }

    /**
     * @param iterable $input
     * @param int $level
     * @return array
     *
     * @psalm-suppress MixedArrayOffset
     */
    private static function maybeMaskInput(iterable $input, int $level = 0): array
    {
        if (self::$maskedKeys === null) {
            $maskedKeys = apply_filters(self::FILTER_MASKED_KEYS, self::SECRET_KEYS);
            if (!is_array($maskedKeys)) {
                $maskedKeys = self::SECRET_KEYS;
            }
            /** @var list<string> $maskedKeys */
            self::$maskedKeys = $maskedKeys;
        }

        if ($level > 8) {
            /** @var \Traversable|array $input */
            return $input instanceof \Traversable ? iterator_to_array($input) : $input;
        }

        $out = [];
        foreach ($input as $key => $value) {
            if (in_array($key, self::$maskedKeys, true)) {
                $out[$key] = '***';
                continue;
            }

            $out[$key] = (is_object($value) || is_array($value))
                ? self::maybeMaskInputInner($value, $level)
                : self::forceString($value);
        }

        return $out;
    }

    /**
     * @param mixed $input
     * @param int $level
     * @return array|string
     */
    private static function maybeMaskInputInner(mixed $input, int $level = 0): array|string
    {
        /** @var array|object $input */

        if (is_iterable($input)) {
            return self::maybeMaskInput($input, $level + 1);
        }

        if ($input instanceof \stdClass) {
            return self::maybeMaskInput((array) $input, $level + 1);
        }

        $serialized = self::serializeObject($input, !($input instanceof \JsonSerializable));
        if ($serialized !== null) {
            return $serialized;
        }

        // phpcs:disable WordPress.PHP.NoSilencedErrors
        $json = @json_encode($input, self::JSON_ENC_FLAGS, 32);
        $plain = ($json === false) ? null : @json_decode($json, true, 32, self::JSON_DEC_FLAGS);
        // phpcs:enable WordPress.PHP.NoSilencedErrors

        if (is_iterable($plain)) {
            return self::maybeMaskInput($plain, $level + 1);
        }

        return is_string($json) ? $json : self::forceString($input);
    }

    /**
     * @param object $value
     * @param bool $ensureString
     * @return string|null
     *
     * @psalm-return ($ensureString is true ? string : string|null)
     *
     * phpcs:disable SlevomatCodingStandard.Complexity.Cognitive
     */
    private static function serializeObject(object $value, bool $ensureString): ?string
    {
        // phpcs:enable SlevomatCodingStandard.Complexity.Cognitive

        switch (true) {
            case ($value instanceof \WP_Error):
                return sprintf('%s: %s', get_class($value), $value->get_error_message());
            case ($value instanceof \WP_Post):
            case ($value instanceof \WP_User):
                return sprintf('%s (ID: %s)', get_class($value), $value->ID);
            case ($value instanceof \WP_Term):
                return sprintf('%s (ID: %s)', get_class($value), $value->term_id);
            case ($value instanceof \WP_Comment):
                return sprintf('%s (ID: %s)', get_class($value), $value->comment_ID);
            case ($value instanceof \WP_Meta_Query):
                $args = (array) $value->queries;
                // fallback
            case ($value instanceof \WP_Query):
                /** @psalm-suppress UndefinedPropertyFetch */
                $args = $args ?? $value->query ?: null;
                // fallback
            case ($value instanceof \WP_User_Query):
            case ($value instanceof \WP_Term_Query):
            case ($value instanceof \WP_Comment_Query):
                /** @psalm-suppress UndefinedPropertyFetch */
                $args = self::maybeMaskInput((array) ($args ?? $value->query_vars ?: []), 7);
                $argsStr = json_encode($args, self::JSON_ENC_FLAGS, 8);
                return sprintf('%s (%s)', get_class($value), $argsStr);
            case ($value instanceof \Throwable):
                return sprintf('%s: %s', get_class($value), $value->getMessage());
            case ($value instanceof \DateTimeInterface):
                return sprintf('%s: %s', get_class($value), $value->format('r'));
            case (is_callable([$value, '__toString'])):
                return (string) $value;
        }

        return $ensureString
            ? sprintf('Instance of %s (%s)', get_class($value), spl_object_hash($value))
            : null;
    }
}
