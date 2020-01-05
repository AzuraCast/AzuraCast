<?php
namespace App\Form;

use App\Entity;
use App\Http\ServerRequest;
use App\Service\SFTPGo;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SFTPUserForm extends EntityForm
{
    protected SFTPGo $sftpgo;

    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config,
        SFTPGo $sftpgo
    ) {
        $form_config = $config->get('forms/sftp_user');

        parent::__construct($em, $serializer, $validator, $form_config);

        $this->sftpgo = $sftpgo;
        $this->entityClass = Entity\SFTPUser::class;
    }

    public function process(ServerRequest $request, $record = null)
    {
        $result = parent::process($request, $record);
        if (false !== $result) {
            $this->sftpgo->sync();
        }

        return $result;
    }
}
