<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

class TypolinkParameterJsonSerializable implements JsonSerializable
{
    public function __construct(protected TypolinkParameter $typolinkParameter)
    {

    }

    public function jsonSerialize(): mixed
    {
        if (!$this->typolinkParameter->url) {
            return '';
        }

        try {
            $typolink = $this->getTypoLinkCodecService()->encode($this->typolinkParameter->toArray());
            $linkResult = $this->getLinkFactory()->createUri($typolink);

            return [
                'url' => $linkResult->getUrl(),
                'target' => $linkResult->getTarget(),
                'type' => $linkResult->getType(),
            ];
        } catch (UnableToLinkException $e) {
            return [
                'url' => '',
                'target' => '',
                'type' => '',
                '__errorMessage' => $e->getMessage()
            ];
        }
    }

    protected function getLinkFactory(): LinkFactory
    {
        return GeneralUtility::makeInstance(LinkFactory::class);
    }

    protected function getTypoLinkCodecService(): TypoLinkCodecService
    {
        return GeneralUtility::makeInstance(TypoLinkCodecService::class);
    }
}
