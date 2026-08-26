<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Normalizes LazyRecordCollections. For sys_category collections the reduced
 * uid/pid/title representation is used (frozen contract), everything else is
 * resolved record by record via the chain.
 */
final class RecordCollectionNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ?TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof LazyRecordCollection;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        $data = [];
        foreach ($value as $key => $record) {
            $recordMainType = $record->getRawRecord()->getMainType();

            if ($recordMainType === 'sys_category') {
                $categoryArray = $record->toArray();
                $data[$key] = [
                    'uid' => $categoryArray['uid'],
                    'pid' => $categoryArray['pid'],
                    'title' => $categoryArray['title'],
                ];
                continue;
            }

            $recordContext = $this->tcaSchemaFactory !== null && $this->tcaSchemaFactory->has($recordMainType)
                ? $context->withTcaSchema($this->tcaSchemaFactory->get($recordMainType))
                : $context->withTcaSchema(null);

            $data[$key] = $context->getChain()->normalize($record, $recordContext);
        }

        return $data;
    }
}
