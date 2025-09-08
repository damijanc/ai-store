<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Document\Transformer;

use Symfony\AI\Store\Document\TransformerInterface;

/**
 * Trims whitespace from document content.
 *
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final readonly class TextTrimTransformer implements TransformerInterface
{
    public function transform(iterable $documents, array $options = []): iterable
    {
        foreach ($documents as $document) {
            yield $document->withContent(trim($document->content));
        }
    }
}
