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
     * @param array<mixed> $context
     * @return array<mixed>
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
     * @param iterable<mixed> $input
     * @param int $level
     * @return array<mixed>
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
            return ($input instanceof \Traversable) ? iterator_to_array($input) : $input;
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
     * @param object|array<mixed> $input
     * @param int $level
     * @return array<mixed>|string
     */
    private static function maybeMaskInputInner(object|array $input, int $level = 0): array|string
    {
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
     * @phpstan-return ($ensureString is true ? string : string|null)
     * phpcs:disable SlevomatCodingStandard.Complexity.Cognitive
     */
    private static function serializeObject(object $value, bool $ensureString): ?string
    {
        // phpcs:enable SlevomatCodingStandard.Complexity.Cognitive

        switch (true) {
            case ($value instanceof \WP_Post):
            case ($value instanceof \WP_User):
                return self::serializeObjectById($value, 'ID');
            case ($value instanceof \WP_Term):
                return self::serializeObjectById($value, 'term_id');
            case ($value instanceof \WP_Comment):
                return self::serializeObjectById($value, 'comment_ID');
            case ($value instanceof \WP_Meta_Query):
                return self::serializeObjectByProp($value, 'queries');
            case ($value instanceof \WP_Query):
            case ($value instanceof \WP_User_Query):
            case ($value instanceof \WP_Term_Query):
            case ($value instanceof \WP_Comment_Query):
                return self::serializeObjectByProp($value, 'query_vars');
            case ($value instanceof \WP_Error):
                return sprintf('%s: %s', get_class($value), $value->get_error_message());
            case ($value instanceof \Throwable):
                return sprintf('%s: %s', get_class($value), $value->getMessage());
            case ($value instanceof \DateTimeInterface):
                return sprintf('%s: %s', get_class($value), $value->format('r'));
            case ($value instanceof \Stringable):
                return (string) $value;
        }

        if ($ensureString) {
            return sprintf('Instance of %s (%s)', get_class($value), spl_object_hash($value));
        }

        return null;
    }

    /**
     * @param object $object
     * @param non-empty-string $prop
     * @return string
     */
    private static function serializeObjectById(object $object, string $prop): string
    {
        $id = ($object->{$prop} ?? 0);
        if (is_bool($id)) {
            $id = 0;
        }
        if (!is_scalar($id)) {
            $id = 0;
        }

        return sprintf('%s (ID: %d)', get_class($object), $id);
    }

    /**
     * @param object $object
     * @param non-empty-string $prop
     * @return string
     */
    private static function serializeObjectByProp(object $object, string $prop): string
    {
        $args = self::maybeMaskInput((array) ($object->{$prop} ?? []), 7);
        $argsStr = json_encode($args, self::JSON_ENC_FLAGS, 8);

        return sprintf('%s (%s)', get_class($object), $argsStr);
    }
}
