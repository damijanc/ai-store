<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Tests\Document\Transformer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\Document\Transformer\ChainTransformer;
use Symfony\AI\Store\Document\TransformerInterface;
use Symfony\Component\Uid\Uuid;

#[CoversClass(TransformerInterface::class)]
final class ChainTransformerTest extends TestCase
{
    #[Test]
    public function chainTransformerAppliesAllTransformersInOrder(): void
    {
        $transformerA = new class implements TransformerInterface {
            public function __invoke(iterable $documents, array $options = []): iterable
            {
                foreach ($documents as $document) {
                    yield new TextDocument($document->id, $document->content.'-A');
                }
            }
        };

        $transformerB = new class implements TransformerInterface {
            public function __invoke(iterable $documents, array $options = []): iterable
            {
                foreach ($documents as $document) {
                    yield new TextDocument($document->id, $document->content.'-B');
                }
            }
        };

        $chain = new ChainTransformer([$transformerA, $transformerB]);
        $documents = [
            new TextDocument(Uuid::v4(), 'foo'),
            new TextDocument(Uuid::v4(), 'bar'),
        ];

        $result = iterator_to_array($chain->__invoke($documents));

        $this->assertSame('foo-A-B', $result[0]->content);
        $this->assertSame('bar-A-B', $result[1]->content);
    }

    #[Test]
    public function chainTransformerWithNoTransformersReturnsInput(): void
    {
        $chain = new ChainTransformer([]);
        $documents = [new TextDocument(Uuid::v4(), 'baz')];

        $result = iterator_to_array($chain->__invoke($documents));

        $this->assertSame('baz', $result[0]->content);
    }
}
