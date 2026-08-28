<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\FieldTransformer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;

/**
 * Applies the first supporting FieldValueTransformer to a string value.
 */
final class FieldValueTransformerChain implements FieldValueTransformerInterface
{
    /**
     * @param iterable<FieldValueTransformerInterface> $transformers
     */
    public function __construct(
        private readonly iterable $transformers,
    ) {}

    public function supports(FieldTypeInterface $field): bool
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($field)) {
                return true;
            }
        }

        return false;
    }

    public function transform(string $value, FieldTypeInterface $field, Context $context): string
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($field)) {
                return $transformer->transform($value, $field, $context);
            }
        }

        return $value;
    }
}
