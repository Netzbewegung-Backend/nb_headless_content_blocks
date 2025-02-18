<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileReferenceJsonSerializable implements JsonSerializable
{
    public function __construct(protected FileReference $fileReference)
    {

    }

    public function jsonSerialize(): mixed
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        /** @var \TYPO3\CMS\Core\Site\Entity\Site $site */
        $site = current($siteFinder->getAllSites());
        $apiUrl = rtrim($site->getConfiguration()['base'], '/');
        $frontendUrl = rtrim($site->getConfiguration()['frontendBase'], '/');

        $url = $this->fileReference->getPublicUrl();
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme === null) {
            $url = $apiUrl . $url;
        }

        return [
            'alt' => $this->fileReference->getAlternative(),
            'title' => $this->fileReference->getTitle(),
            'publicUrl' => $url,
        ];
    }
}
