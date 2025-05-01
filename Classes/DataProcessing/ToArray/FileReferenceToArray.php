<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;

class FileReferenceToArray
{

    public function __construct(protected FileReference $fileReference)
    {
        
    }

    public function toArray(): array
    {
        // Check if editor has cropped the image in TYPO3 Backend
        $cropString = '';
        if ($this->fileReference->hasProperty('crop') && $this->fileReference->getProperty('crop')) {
            $cropString = $this->fileReference->getProperty('crop');
        }

        $cropVariantCollection = CropVariantCollection::create((string) $cropString);
        $cropArea = $cropVariantCollection->getCropArea('default');

        $imageService = self::getImageService();
        if ($cropArea->isEmpty() === false) {
            $processingInstructions = [
                'crop' => $cropArea->makeAbsoluteBasedOnFile($this->fileReference),
            ];

            $processedImage = $imageService->applyProcessingInstructions($this->fileReference, $processingInstructions);

            $publicUrl = $imageService->getImageUri($processedImage, true);
        } else {
            $publicUrl = $imageService->getImageUri($this->fileReference, true);
        }

        return [
            'id' => $this->fileReference->getUid(),
            'alt' => $this->fileReference->getAlternative(),
            'title' => $this->fileReference->getTitle(),
            'publicUrl' => $publicUrl,
        ];
    }

    protected static function getImageService(): ImageService
    {
        return GeneralUtility::makeInstance(ImageService::class);
    }
}
