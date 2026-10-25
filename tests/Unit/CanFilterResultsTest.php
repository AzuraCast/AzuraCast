<?php

declare(strict_types=1);

namespace Unit;

use App\Controller\Api\Traits\CanFilterResults;
use App\Entity\Enums\PlaylistSources;
use App\Entity\StationPlaylist;
use App\Exception\ValidationException;
use App\Http\ServerRequest;
use App\Tests\Module;
use App\Utilities\ListFilter;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use GuzzleHttp\Psr7\ServerRequest as PsrServerRequest;

class CanFilterResultsTest extends Unit
{
    protected EntityManagerInterface $em;

    protected function _inject(Module $testsModule): void
    {
        $this->em = $testsModule->em;
    }

    public function testContainsSingleValue(): void
    {
        $queryBuilder = $this->applyFilters(
            ['name' => 'Alpha'],
            ['name' => ListFilter::contains('sp.name')]
        );

        self::assertStringContainsString('(sp.name LIKE :filter_name_0)', $queryBuilder->getDQL());
        self::assertSame('%Alpha%', $queryBuilder->getParameter('filter_name_0')?->getValue());
        self::assertQueryIsParseable($queryBuilder);
    }

    public function testContainsMultipleValuesAreOred(): void
    {
        $queryBuilder = $this->applyFilters(
            ['name' => ['Alpha', 'Beta']],
            ['name' => ListFilter::contains('sp.name')]
        );

        self::assertStringContainsString(
            '(sp.name LIKE :filter_name_0 OR sp.name LIKE :filter_name_1)',
            $queryBuilder->getDQL()
        );
        self::assertSame('%Alpha%', $queryBuilder->getParameter('filter_name_0')?->getValue());
        self::assertSame('%Beta%', $queryBuilder->getParameter('filter_name_1')?->getValue());
        self::assertCount(2, $queryBuilder->getParameters());
        self::assertQueryIsParseable($queryBuilder);
    }

    public function testContainsKeepsUserWildcards(): void
    {
        $queryBuilder = $this->applyFilters(
            ['name' => '50%_off'],
            ['name' => ListFilter::contains('sp.name')]
        );

        self::assertSame('%50%_off%', $queryBuilder->getParameter('filter_name_0')?->getValue());
        self::assertQueryIsParseable($queryBuilder);
    }

    public function testBlankValuesAreSkipped(): void
    {
        $queryBuilder = $this->applyFilters(
            ['name' => ['   ', '']],
            ['name' => ListFilter::contains('sp.name')]
        );

        self::assertStringNotContainsString('WHERE', $queryBuilder->getDQL());
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testNonScalarValueIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->applyFilters(
            ['name' => [['nested']]],
            ['name' => ListFilter::contains('sp.name')]
        );
    }

    public function testEqualsSingleValue(): void
    {
        $queryBuilder = $this->applyFilters(
            ['source' => PlaylistSources::Songs->value],
            ['source' => ListFilter::fromEnum('sp.source', PlaylistSources::class)]
        );

        self::assertStringContainsString('sp.source = :filter_source', $queryBuilder->getDQL());
        self::assertSame(
            PlaylistSources::Songs->value,
            $queryBuilder->getParameter('filter_source')?->getValue()
        );
        self::assertQueryIsParseable($queryBuilder);
    }

    public function testEqualsMultipleValuesUseIn(): void
    {
        $queryBuilder = $this->applyFilters(
            ['source' => [PlaylistSources::Songs->value, PlaylistSources::Requests->value]],
            ['source' => ListFilter::fromEnum('sp.source', PlaylistSources::class)]
        );

        self::assertStringContainsString('sp.source IN (:filter_source)', $queryBuilder->getDQL());
        self::assertSame(
            [PlaylistSources::Songs->value, PlaylistSources::Requests->value],
            $queryBuilder->getParameter('filter_source')?->getValue()
        );
        self::assertQueryIsParseable($queryBuilder);
    }

    public function testEqualsRejectsValuesOutsideTheEnum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Allowed values: .*songs/');

        $this->applyFilters(
            ['source' => 'bogus'],
            ['source' => ListFilter::fromEnum('sp.source', PlaylistSources::class)]
        );
    }

    public function testUnknownFilterKeyIsIgnored(): void
    {
        $queryBuilder = $this->applyFilters(
            ['bogus' => 'Alpha'],
            ['name' => ListFilter::contains('sp.name')]
        );

        self::assertStringNotContainsString('WHERE', $queryBuilder->getDQL());
        self::assertQueryIsParseable($queryBuilder);
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, ListFilter> $filterLookup
     */
    private function applyFilters(array $filters, array $filterLookup): QueryBuilder
    {
        $controller = new class {
            use CanFilterResults;

            /**
             * @param array<string, ListFilter> $filterLookup
             */
            public function filter(
                ServerRequest $request,
                QueryBuilder $queryBuilder,
                array $filterLookup
            ): QueryBuilder {
                return $this->filterQueryBuilder($request, $queryBuilder, $filterLookup);
            }
        };

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('sp')
            ->from(StationPlaylist::class, 'sp');

        return $controller->filter($this->makeRequest($filters), $queryBuilder, $filterLookup);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function makeRequest(array $filters): ServerRequest
    {
        return new ServerRequest(
            (new PsrServerRequest('GET', '/'))->withQueryParams(['filter' => $filters])
        );
    }

    private static function assertQueryIsParseable(QueryBuilder $queryBuilder): void
    {
        self::assertNotEmpty($queryBuilder->getQuery()->getSQL());
    }
}
