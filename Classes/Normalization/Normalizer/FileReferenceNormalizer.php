<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\FileReferenceToArray;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * Normalizes FileReferences (single or as LazyFileReferenceCollection) to the
 * frozen contract {id, alt, title, publicUrl} and - when the Content Block
 * defines image variants in headless.yaml or TypoScript options - an
 * additional "thumbnails" map of variant name => processed URL.
 */
final class FileReferenceNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof FileReference || $value instanceof LazyFileReferenceCollection;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        if ($value instanceof LazyFileReferenceCollection) {
            $normalized = [];
            foreach ($value as $key => $fileReference) {
                $normalized[$key] = $this->normalize($fileReference, $context);
            }

            return $normalized;
        }

        $data = GeneralUtility::makeInstance(FileReferenceToArray::class, $value)->toArray();

        $thumbnails = $this->processThumbnails($value, $context);
        if ($thumbnails !== []) {
            $data['thumbnails'] = $thumbnails;
        }

        return $data;
    }

    /**
     * @return array<string, string> variant name => public URL
     */
    private function processThumbnails(FileReference $fileReference, Context $context): array
    {
        $processing = $this->getProcessingConfiguration($context);
        if ($processing === []) {
            return [];
        }

        $imageService = GeneralUtility::makeInstance(ImageService::class);
        $thumbnails = [];

        foreach ($processing as $variantName => $optionsString) {
            $thumbnails[$variantName] = $imageService->getImageUri(
                $this->processImage($fileReference, $imageService, $optionsString),
                true
            );
        }

        return $thumbnails;
    }

    private function processImage(FileReference $fileReference, ImageService $imageService, string $optionsString): ProcessedFile
    {
        $processingInstructions = $this->parseOptions($optionsString);

        return $imageService->applyProcessingInstructions($fileReference, $processingInstructions);
    }

    /**
     * @return array<string, string> variant name => options string
     */
    private function getProcessingConfiguration(Context $context): array
    {
        return $context->getFileProcessingForCurrentField();
    }

    /**
     * Parses "width=883c,fileExtension=webp" into TYPO3 processing instructions.
     *
     * @return array<string, string|int>
     */
    private function parseOptions(string $optionsString): array
    {
        $instructions = [];
        foreach (GeneralUtility::trimExplode(',', $optionsString, true) as $option) {
            [$key, $value] = GeneralUtility::trimExplode('=', $option, true, 2);
            $instructions[$key] = $value;
        }

        return $instructions;
    }
}
