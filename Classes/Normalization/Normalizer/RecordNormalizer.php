<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Domain\Record;

/**
 * Normalizes a resolved Record: strips system fields, converts every value
 * via the chain and sorts the result alphabetically (frozen contract).
 */
final class RecordNormalizer implements NormalizerInterface
{
    private const SYSTEM_FIELDS = ['uid', 'pid', 'colPos', 'CType', 'foreign_table_parent_uid', 'tx_container_parent'];

    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof Record;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        try {
            $array = $value->toArray();
        } catch (\TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException $fileDoesNotExistException) {
            return ['__errorMessage' => $fileDoesNotExistException->getMessage()];
        }

        foreach (self::SYSTEM_FIELDS as $systemField) {
            unset($array[$systemField]);
        }

        $data = [];
        foreach ($array as $key => $fieldValue) {
            $data[$key] = $context->getChain()->normalize($fieldValue, $context);
        }

        ksort($data);

        return $data;
    }
}
