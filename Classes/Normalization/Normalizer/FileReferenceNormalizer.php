<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * Normalizes FileReferences (single or as LazyFileReferenceCollection) to the
 * frozen contract {id, alt, title, publicUrl} (publicUrl respects manual
 * crops) and - when the Content Block defines image variants in headless.yaml
 * or TypoScript options - an additional "thumbnails" map.
 */
final class FileReferenceNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

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

        $data = $this->getBaseData($value);

        $thumbnails = $this->processThumbnails($value, $context);
        if ($thumbnails !== []) {
            $data['thumbnails'] = $thumbnails;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function getBaseData(FileReference $fileReference): array
    {
        // Respect manual crops from the TYPO3 Backend for the public URL
        $cropString = '';
        if ($fileReference->hasProperty('crop') && $fileReference->getProperty('crop')) {
            $cropString = (string)$fileReference->getProperty('crop');
        }

        $cropVariantCollection = CropVariantCollection::create($cropString);
        $cropArea = $cropVariantCollection->getCropArea('default');

        if ($cropArea->isEmpty() === false) {
            $publicUrl = $this->imageService->getImageUri(
                $this->imageService->applyProcessingInstructions(
                    $fileReference,
                    ['crop' => $cropArea->makeAbsoluteBasedOnFile($fileReference)]
                ),
                true
            );
        } else {
            $publicUrl = $this->imageService->getImageUri($fileReference, true);
        }

        return [
            'id' => $fileReference->getUid(),
            'alt' => $fileReference->getAlternative(),
            'title' => $fileReference->getTitle(),
            'publicUrl' => $publicUrl,
        ];
    }

    /**
     * @return array<string, string> variant name => public URL
     */
    private function processThumbnails(FileReference $fileReference, Context $context): array
    {
        $processing = $context->getFileProcessingForCurrentField();
        if ($processing === []) {
            return [];
        }

        $thumbnails = [];
        foreach ($processing as $variantName => $optionsString) {
            $thumbnails[$variantName] = $this->imageService->getImageUri(
                $this->imageService->applyProcessingInstructions(
                    $fileReference,
                    $this->parseOptions($optionsString)
                ),
                true
            );
        }

        return $thumbnails;
    }

    /**
     * Parses "width=883c,fileExtension=webp" into TYPO3 processing instructions.
     *
     * @return array<string, string>
     */
    private function parseOptions(string $optionsString): array
    {
        $instructions = [];
        foreach (array_filter(explode(',', $optionsString)) as $option) {
            [$key, $value] = array_pad(explode('=', trim($option), 2), 2, '');
            $instructions[trim($key)] = trim($value);
        }

        return $instructions;
    }
}
