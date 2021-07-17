<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @template TEntity as object
 */
abstract class AbstractApiCrudController
{
    /** @var class-string<TEntity> The fully-qualified (::class) class name of the entity being managed. */
    protected string $entityClass;

    /** @var string The route name used to generate the "self" links for each record. */
    protected string $resourceRouteName;

    public function __construct(
        protected EntityManagerInterface $em,
        protected Serializer $serializer,
        protected ValidatorInterface $validator
    ) {
    }

    protected function listPaginatedFromQuery(
        ServerRequest $request,
        Response $response,
        Query $query,
        callable $postProcessor = null
    ): ResponseInterface {
        $paginator = Paginator::fromQuery($query, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $is_internal = ('true' === $request->getParam('internal', 'false'));

        $postProcessor ??= function ($row) use ($is_bootgrid, $is_internal, $request) {
            $return = $this->viewRecord($row, $request);

            // Older jQuery Bootgrid requests should be "flattened".
            if ($is_bootgrid && !$is_internal) {
                return Utilities\Arrays::flattenArray($return, '_');
            }

            return $return;
        };
        $paginator->setPostprocessor($postProcessor);

        return $paginator->write($response);
    }

    /**
     * @param TEntity $record
     * @param ServerRequest $request
     *
     */
    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->toArray($record);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        if ($record instanceof IdentifiableEntityInterface) {
            $return['links'] = [
                'self' => (string)$router->fromHere(
                    $this->resourceRouteName,
                    ['id' => $record->getIdRequired()],
                    [],
                    !$isInternal
                ),
            ];
        }
        return $return;
    }

    /**
     * @param TEntity $record
     * @param array<string, mixed> $context
     *
     * @return array<mixed>
     */
    protected function toArray(object $record, array $context = []): array
    {
        return (array)$this->serializer->normalize(
            $record,
            null,
            array_merge(
                $context,
                [
                    ObjectNormalizer::ENABLE_MAX_DEPTH => true,
                    ObjectNormalizer::MAX_DEPTH_HANDLER => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) {
                        return $this->displayShortenedObject($innerObject);
                    },
                    ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function (
                        $object,
                        string $format = null,
                        array $context = []
                    ) {
                        return $this->displayShortenedObject($object);
                    },
                ]
            )
        );
    }

    /**
     * @param object $object
     *
     */
    protected function displayShortenedObject(object $object): mixed
    {
        if (method_exists($object, 'getName')) {
            return $object->getName();
        }

        if ($object instanceof IdentifiableEntityInterface) {
            return $object->getIdRequired();
        }

        if ($object instanceof \Stringable) {
            return (string)$object;
        }

        return get_class($object) . ': ' . spl_object_hash($object);
    }

    /**
     * @param array<mixed>|null $data
     * @param TEntity|null $record
     * @param array<string, mixed> $context
     *
     * @return TEntity
     */
    protected function editRecord(?array $data, ?object $record = null, array $context = []): object
    {
        if (null === $data) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $record = $this->fromArray($data, $record, $context);

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            $e = new ValidationException((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        $this->em->persist($record);
        $this->em->flush();

        return $record;
    }

    /**
     * @param array<mixed> $data
     * @param TEntity|null $record
     * @param array<string, mixed> $context
     *
     * @return TEntity
     */
    protected function fromArray(array $data, ?object $record = null, array $context = []): object
    {
        if (null !== $record) {
            $context[ObjectNormalizer::OBJECT_TO_POPULATE] = $record;
        }

        return $this->serializer->denormalize($data, $this->entityClass, null, $context);
    }

    /**
     * @param TEntity $record
     */
    protected function deleteRecord(object $record): void
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $this->em->remove($record);
        $this->em->flush();
    }
}
