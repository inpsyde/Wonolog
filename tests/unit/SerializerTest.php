<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey;
use Inpsyde\Wonolog\Serializer;
use Inpsyde\Wonolog\Tests\UnitTestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class SerializerTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider provideMessageExamples
     */
    public function testMessageSerialization(mixed $message, string $expected): void
    {
        $this->assertSame($expected, Serializer::serializeMessage($message));
    }

    /**
     * @test
     */
    public function testMessageSerializationWithResource(): void
    {
        $res = fopen(__FILE__, 'r');
        $this->assertSame('Resource (stream)', Serializer::serializeMessage($res));
    }

    /**
     * @test
     */
    public function testMessageSerializationWithException(): void
    {
        $this->assertSame('Error: Error!', Serializer::serializeMessage(new \Error('Error!')));
    }

    /**
     * @test
     */
    public function testContextSerialization(): void
    {
        Monkey\Filters\expectApplied(Serializer::FILTER_MASKED_KEYS)
            ->once()
            ->andReturnUsing(
                static function (array $keys): array {
                    $keys[] = 'secret_key';
                    return $keys;
                }
            );

        if (!class_exists(\WP_Post::class)) {
            eval('class WP_Post { public $ID = null; }');
        }
        if (!class_exists(\WP_Query::class)) {
            eval('class WP_Query { public $query_vars = [];  public $query = []; }');
        }

        $post1 = new \WP_Post((object) []);
        $post2 = clone $post1;
        $post3 = clone $post1;

        $post1->ID = 1;
        $post2->ID = 2;
        $post3->ID = 3;

        $query = new \WP_Query();
        $query->query_vars = ['post_password' => 'abc', 'post_type' => 'post'];
        $query->query = [];

        $postClass = $post1::class;
        $queryClass = $query::class;

        $throwable = new \Error('Foo');
        $datetime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $jsonScalar = new class implements \JsonSerializable
        {
            public function jsonSerialize(): int
            {
                return 1;
            }
        };

        $jsonObj = new class implements \JsonSerializable
        {
            public function jsonSerialize(): \stdClass
            {
                return (object) ['token' => 'x'];
            }
        };

        $input = [
            'array' => range('a', 'e'),
            'x' => [
                'y' => (object) [
                    'users' => [
                        'z1' => new \ArrayIterator([
                            ['username' => 'foo', 'password' => 's3cr3t1'],
                            ['username' => 'bar', 'password' => 's3cr3t2'],
                        ]),
                        'z2' => new \ArrayIterator([
                            ['username' => 'foo', 'user_password' => 's3cr3t1'],
                            ['username' => 'bar', 'user_password' => 's3cr3t2'],
                        ]),
                    ],
                ],
            ],
            'secrets' => (object) [
                'one' => (object) ['name' => 'one', 'secret_key' => '0n3!'],
                'two' => (object) ['name' => 'two', 'secret_key' => 'Tw0!'],
                'three' => (object) ['name' => 'three', 'secret_key' => 'Thr33!'],
            ],
            'posts' => compact('post1', 'post2', 'post3'),
            'data' => [
                'query' => $query,
                'object' => $this,
                'objects' => compact('throwable', 'datetime'),
            ],
            'json' => [$jsonObj, $jsonScalar],
        ];

        $expected = [
            'array' => range('a', 'e'),
            'x' => [
                'y' => [
                    'users' => [
                        'z1' => [
                            ['username' => 'foo', 'password' => '***'],
                            ['username' => 'bar', 'password' => '***'],
                        ],
                        'z2' => [
                            ['username' => 'foo', 'user_password' => '***'],
                            ['username' => 'bar', 'user_password' => '***'],
                        ],
                    ],
                ],
            ],
            'secrets' => [
                'one' => ['name' => 'one', 'secret_key' => '***'],
                'two' => ['name' => 'two', 'secret_key' => '***'],
                'three' => ['name' => 'three', 'secret_key' => '***'],
            ],
            'posts' => [
                'post1' => "{$postClass} (ID: 1)",
                'post2' => "{$postClass} (ID: 2)",
                'post3' => "{$postClass} (ID: 3)",
            ],
            'data' => [
                'query' => $queryClass . ' ({"post_password":"***","post_type":"post"})',
                'object' => sprintf('Instance of %s (%s)', self::class, spl_object_hash($this)),
                'objects' => [
                    'throwable' => 'Error: ' . $throwable->getMessage(),
                    'datetime' => 'DateTimeImmutable: ' . $datetime->format('r'),
                ],
            ],
            'json' => [['token' => '***'], '1'],
        ];

        $this->assertSame($expected, Serializer::serializeContext($input));
    }

    /**
     * @return \Iterator<int<0, max>, array{mixed, string}>
     */
    public function provideMessageExamples(): \Iterator
    {
        yield ['A message', 'A message'];
        yield [null, 'NULL'];
        yield [true, 'TRUE'];
        yield [false, 'FALSE'];
        yield [1, '1'];
        yield [1.05, '1.05'];
        yield [NAN, 'NaN'];
        yield [INF, 'INF'];
        yield [-INF, '-INF'];
        yield [(object) ['foo' => 'bar'], '{"foo":"bar"}'];
        yield [['foo' => 'bar'], '{"foo":"bar"}'];
    }
}
