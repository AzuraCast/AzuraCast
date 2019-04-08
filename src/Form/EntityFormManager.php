<?php
namespace App\Form;

use Doctrine\ORM\EntityManager;
use Pimple\Psr11\ServiceLocator;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityFormManager
{
    /** @var EntityManager */
    protected $em;

    /** @var Serializer */
    protected $serializer;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var ServiceLocator */
    protected $custom_forms;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param ServiceLocator $custom_forms
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        ServiceLocator $custom_forms)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->custom_forms = $custom_forms;
    }

    /**
     * Given a specified entity class and form configuration array, return
     * a configured and initialized EntityForm.
     *
     * @param string $entity_class
     * @param array|null $form_config
     * @return EntityForm
     */
    public function getForm($entity_class, array $form_config = null, array $defaults = null): EntityForm
    {
        if ($this->custom_forms->has($entity_class)) {
            return $this->custom_forms->get($entity_class);
        }

        $form = new EntityForm($this->em, $this->serializer, $this->validator, $form_config, $defaults);
        $form->setEntityClass($entity_class);

        return $form;
    }
}
