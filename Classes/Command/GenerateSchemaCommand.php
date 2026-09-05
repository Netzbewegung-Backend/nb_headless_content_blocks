<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Command;

use Netzbewegung\NbHeadlessContentBlocks\Schema\JsonSchemaGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
    name: 'nbheadlesscontentblocks:generate-schema',
    description: 'Generate JSON Schema files describing the Content Block JSON output.'
)]
final class GenerateSchemaCommand extends Command
{
    public function __construct(
        private readonly JsonSchemaGenerator $jsonSchemaGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'target',
            't',
            InputOption::VALUE_REQUIRED,
            'Target directory for the schema files (absolute or relative to the project root)'
        );
        $this->addOption(
            'id-base',
            null,
            InputOption::VALUE_REQUIRED,
            'Base URL used for the $id fields, e.g. https://cms.example.org/api/schema',
            ''
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = (string)$input->getOption('target');
        if ($target === '') {
            $output->writeln('<error>The --target option is required.</error>');
            return Command::INVALID;
        }

        $targetDir = GeneralUtility::getFileAbsFileName($target);
        if ($targetDir === '') {
            $output->writeln('<error>Could not resolve the target directory: ' . $target . '</error>');
            return Command::INVALID;
        }

        $idBase = (string)$input->getOption('id-base');
        GeneralUtility::mkdir_deep($targetDir);

        $typeNames = $this->jsonSchemaGenerator->getContentElementTypeNames();
        if ($typeNames === []) {
            $output->writeln('<warning>No Content Blocks found for tt_content, nothing to generate.</warning>');
            return Command::SUCCESS;
        }
        foreach ($typeNames as $typeName) {
            $schema = $this->jsonSchemaGenerator->generateForTypeName($typeName, $idBase);
            if ($schema === null) {
                continue;
            }
            $this->writeSchemaFile(rtrim($targetDir, '/') . '/' . $typeName . '.schema.json', $schema);
        }

        $combinedSchema = $this->jsonSchemaGenerator->generateCombined($idBase);
        $this->writeSchemaFile(rtrim($targetDir, '/') . '/content-blocks.schema.json', $combinedSchema);

        $output->writeln(sprintf(
            '<info>Wrote %d content block schemas and the combined schema to %s</info>',
            count($typeNames),
            $targetDir
        ));

        return Command::SUCCESS;
    }

    private function writeSchemaFile(string $absoluteFilePath, array $schema): void
    {
        file_put_contents(
            $absoluteFilePath,
            json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . LF
        );
    }
}
