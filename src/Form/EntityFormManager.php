<?php
namespace App\Form;

use Doctrine\ORM\EntityManager;
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

    /** @var EntityForm[] */
    protected $custom_forms;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param array $custom_forms
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        array $custom_forms
    ) {
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
     * @param array|null $defaults
     * @return EntityForm
     */
    public function getForm($entity_class, array $form_config = null, array $defaults = null): EntityForm
    {
        if (isset($this->custom_forms[$entity_class])) {
            return $this->custom_forms[$entity_class];
        }

        $form = new EntityForm($this->em, $this->serializer, $this->validator, $form_config, $defaults);
        $form->setEntityClass($entity_class);

        return $form;
    }
}
