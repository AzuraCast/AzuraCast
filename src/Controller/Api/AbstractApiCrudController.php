<?php

namespace App\Controller\Api;

use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator\QueryPaginator;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiCrudController
{
    protected EntityManagerInterface $em;

    protected Serializer $serializer;

    protected ValidatorInterface $validator;

    /** @var string The fully-qualified (::class) class name of the entity being managed. */
    protected string $entityClass;

    /** @var string The route name used to generate the "self" links for each record. */
    protected string $resourceRouteName;

    public function __construct(EntityManagerInterface $em, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    protected function listPaginatedFromQuery(
        ServerRequest $request,
        Response $response,
        Query $query,
        callable $postProcessor = null
    ): ResponseInterface {
        $paginator = new QueryPaginator($query, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $is_internal = ('true' === $request->getParam('internal', 'false'));

        $postProcessor ??= function ($row) use ($is_bootgrid, $is_internal, $request) {
            $return = $this->viewRecord($row, $request);

            // Older jQuery Bootgrid requests should be "flattened".
            if ($is_bootgrid && !$is_internal) {
                return Utilities::flattenArray($return, '_');
            }

            return $return;
        };
        $paginator->setPostprocessor($postProcessor);

        return $paginator->write($response);
    }

    /**
     * @param object $record
     * @param ServerRequest $request
     *
     * @return mixed
     */
    protected function viewRecord($record, ServerRequest $request)
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->toArray($record);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return['links'] = [
            'self' => $router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], !$isInternal),
        ];
        return $return;
    }

    /**
     * @param object $record
     * @param array $context
     *
     * @return mixed[]
     */
    protected function toArray($record, array $context = []): array
    {
        return $this->serializer->normalize($record, null, array_merge($context, [
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
        ]));
    }

    /**
     * @param object $object
     *
     * @return mixed
     */
    protected function displayShortenedObject($object)
    {
        if (method_exists($object, 'getName')) {
            return $object->getName();
        }

        return $object->getId();
    }

    /**
     * @param array|null $data
     * @param object|null $record
     * @param array $context
     */
    protected function editRecord($data, $record = null, array $context = []): object
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
     * @param array $data
     * @param object|null $record
     * @param array $context
     */
    protected function fromArray($data, $record = null, array $context = []): object
    {
        if (null !== $record) {
            $context[ObjectNormalizer::OBJECT_TO_POPULATE] = $record;
        }

        return $this->serializer->denormalize($data, $this->entityClass, null, $context);
    }

    /**
     * @param object $record
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function deleteRecord($record): void
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $this->em->remove($record);
        $this->em->flush();
    }
}
