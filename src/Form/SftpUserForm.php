<?php

declare(strict_types=1);

namespace App\Form;

use App\Config;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SftpUserForm extends EntityForm
{
    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config
    ) {
        $form_config = $config->get('forms/sftp_user');

        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\SftpUser::class;
    }
}
