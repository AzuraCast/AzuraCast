<?php
namespace App\Form;

use App\Entity;
use App\Config;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationRemoteForm extends EntityForm
{
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config
    ) {
        $form_config = $config->get('forms/remote');
        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\StationRemote::class;
    }
}
