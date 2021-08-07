<?php

declare(strict_types=1);

namespace App\Form;

use App\Config;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomFieldForm extends EntityForm
{
    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config
    ) {
        $form_config = $config->get('forms/custom_field');
        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\CustomField::class;
    }
}
