<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Stringable;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
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
        protected ReloadableEntityManagerInterface $em,
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

        $isBootgrid = $paginator->isFromBootgrid();
        $isInternal = ('true' === $request->getParam('internal', 'false'));

        $postProcessor ??= function ($row) use ($isBootgrid, $isInternal, $request) {
            $return = $this->viewRecord($row, $request);

            // Older jQuery Bootgrid requests should be "flattened".
            if ($isBootgrid && !$isInternal) {
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
                    route_name: $this->resourceRouteName,
                    route_params: ['id' => $record->getIdRequired()],
                    absolute: !$isInternal
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
                    AbstractObjectNormalizer::ENABLE_MAX_DEPTH   => true,
                    AbstractObjectNormalizer::MAX_DEPTH_HANDLER    => function (
                        $innerObject,
                        $outerObject,
                        string $attributeName,
                        string $format = null,
                        array $context = []
                    ) {
                        return $this->displayShortenedObject($innerObject);
                    },
                    AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (
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

        if ($object instanceof Stringable) {
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
            throw ValidationException::fromValidationErrors($errors);
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
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $record;
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
