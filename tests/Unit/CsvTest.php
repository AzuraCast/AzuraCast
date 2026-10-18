<?php

declare(strict_types=1);

namespace Unit;

use App\Utilities\Csv;
use App\Utilities\File;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use League\Csv\Reader;
use Symfony\Component\Filesystem\Filesystem;

final class CsvTest extends Unit
{
    /**
     * @return array<string, array{string}>
     */
    public static function formulaCellProvider(): array
    {
        return [
            'equals sign' => ['=1+1'],
            'plus sign' => ['+1+1'],
            'minus sign' => ['-1+1'],
            'at sign' => ['@SUM(1+1)'],
            'tab' => ["\t=1+1"],
            'carriage return' => ["\r=1+1"],
        ];
    }

    #[DataProvider('formulaCellProvider')]
    public function testExportWriterEscapesFormulaCells(string $cell): void
    {
        $exportFile = $this->exportFile([$cell]);

        self::assertSame(["'{$cell}"], Reader::from($exportFile)->first());
    }

    #[DataProvider('formulaCellProvider')]
    public function testImportReaderRestoresExportedFormulaCells(string $cell): void
    {
        $filesystem = new Filesystem();
        $importPath = File::generateTempPath('import.csv');
        $filesystem->dumpFile($importPath, Reader::from($this->exportFile([$cell]))->toString());

        $importedRecord = Csv::importReader($importPath)->first();
        $filesystem->remove($importPath);

        self::assertSame([$cell], $importedRecord);
    }

    public function testExportWriterKeepsNumbersAndPlainText(): void
    {
        $exportFile = $this->exportFile(['Song', -1.5, -3, null]);

        self::assertSame(['Song', '-1.5', '-3', ''], Reader::from($exportFile)->first());
    }

    /**
     * @return resource
     */
    private function exportFile(array $record)
    {
        $exportFile = tmpfile();
        self::assertIsResource($exportFile);

        Csv::exportWriter($exportFile)->insertOne($record);

        return $exportFile;
    }
}
