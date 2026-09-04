<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

final class TypolinkNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly LinkFactory $linkFactory,
        private readonly TypoLinkCodecService $typoLinkCodecService,
    ) {}

    public function supports(mixed $value, Context $context): bool
    {
        return $value instanceof TypolinkParameter;
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        if ($value->url === '' || $value->url === '0') {
            return null;
        }

        try {
            $typolink = $this->typoLinkCodecService->encode($value->toArray());
            $linkResult = $this->linkFactory->createUri($typolink);

            return [
                'url' => $linkResult->getUrl(),
                'target' => $linkResult->getTarget(),
                'type' => $linkResult->getType(),
                'title' => $linkResult->getLinkText(),
                'config' => $linkResult->getLinkConfiguration(),
                'attr' => $linkResult->getAttributes(),
            ];
        } catch (UnableToLinkException $unableToLinkException) {
            return [
                'url' => '',
                'target' => '',
                'type' => '',
                'title' => '',
                'config' => [],
                'attr' => [],
                '__errorMessage' => $unableToLinkException->getMessage(),
            ];
        }
    }
}
