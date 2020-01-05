<?php
namespace App\Form;

use App\Entity;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomFieldForm extends EntityForm
{
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config
    ) {
        $form_config = $config->get('forms/custom_field');
        parent::__construct($em, $serializer, $validator, $form_config);
        
        $this->entityClass = Entity\CustomField::class;
    }
}
