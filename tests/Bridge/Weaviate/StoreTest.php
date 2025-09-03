<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Tests\Bridge\Weaviate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Bridge\Weaviate\Store;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\Uid\Uuid;

#[CoversClass(Store::class)]
#[UsesClass(VectorDocument::class)]
#[UsesClass(Vector::class)]
final class StoreTest extends TestCase
{
    public function testStoreCannotSetupWithExtraOptions()
    {
        $store = new Store(
            new MockHttpClient(),
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No supported options.');
        $this->expectExceptionCode(0);
        $store->setup([
            'foo' => 'bar',
        ]);
    }

    public function testStoreCannotSetupOnInvalidResponse()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'error' => [
                    'message' => 'foo',
                ],
            ], [
                'http_code' => 422,
            ]),
        ], 'http://127.0.0.1:8080');

        $store = new Store(
            $httpClient,
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('HTTP 422 returned for "http://127.0.0.1:8080/v1/schema".');
        $this->expectExceptionCode(422);
        $store->setup();
    }

    public function testStoreCanSetup()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'class' => 'test',
            ], [
                'http_code' => 200,
            ]),
        ], 'http://127.0.0.1:8080');

        $store = new Store(
            $httpClient,
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $store->setup();

        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testStoreCannotDropOnInvalidResponse()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'error' => [
                    'message' => 'foo',
                ],
            ], [
                'http_code' => 422,
            ]),
        ], 'http://127.0.0.1:8080');

        $store = new Store(
            $httpClient,
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('HTTP 422 returned for "http://127.0.0.1:8080/v1/schema/test".');
        $this->expectExceptionCode(422);
        $store->drop();
    }

    public function testStoreCanDrop()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([], [
                'http_code' => 200,
            ]),
        ], 'http://127.0.0.1:8080');

        $store = new Store(
            $httpClient,
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $store->drop();

        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testStoreCannotAddOnInvalidResponse()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'error' => [
                    'message' => 'foo',
                ],
            ], [
                'http_code' => 422,
            ]),
        ], 'http://127.0.0.1:8080');

        $store = new Store(
            $httpClient,
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('HTTP 422 returned for "http://127.0.0.1:8080/v1/batch/objects".');
        $this->expectExceptionCode(422);
        $store->add(new VectorDocument(Uuid::v4(), new Vector([0.1, 0.2, 0.3])));
    }

    public function testStoreCanAdd()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'objects' => [
                    [
                        'class' => 'test',
                        'id' => Uuid::v4()->toRfc4122(),
                        'vector' => [0.1, 0.2, 0.3],
                    ],
                ],
            ], [
                'http_code' => 200,
            ]),
        ], 'http://127.0.0.1:8080');

        $store = new Store(
            $httpClient,
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $store->add(new VectorDocument(Uuid::v4(), new Vector([0.1, 0.2, 0.3])));

        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testStoreCannotQueryOnInvalidResponse()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'error' => [
                    'message' => 'foo',
                ],
            ], [
                'http_code' => 422,
            ]),
        ], 'http://127.0.0.1:8080');

        $store = new Store(
            $httpClient,
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('HTTP 422 returned for "http://127.0.0.1:8080/v1/graphql".');
        $this->expectExceptionCode(422);
        $store->query(new Vector([0.1, 0.2, 0.3]));
    }

    public function testStoreCanQuery()
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse([
                'data' => [
                    'Get' => [
                        'test' => [
                            [
                                'uuid' => Uuid::v4()->toRfc4122(),
                                'vector' => [0.1, 0.2, 0.3],
                                '_metadata' => json_encode(['foo' => 'bar']),
                            ],
                            [
                                'uuid' => Uuid::v4()->toRfc4122(),
                                'vector' => [0.1, 0.2, 0.3],
                                '_metadata' => json_encode(['foo' => 'bar']),
                            ],
                        ],
                    ],
                ],
            ], [
                'http_code' => 200,
            ]),
        ], 'http://127.0.0.1:8080');

        $store = new Store(
            $httpClient,
            'http://127.0.0.1:8080',
            'test',
            'test',
        );

        $results = $store->query(new Vector([0.1, 0.2, 0.3]));

        $this->assertCount(2, $results);
        $this->assertSame(1, $httpClient->getRequestsCount());
    }
}
