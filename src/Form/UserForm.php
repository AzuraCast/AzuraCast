<?php
namespace App\Form;

use App\Entity;
use App\Http\ServerRequest;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserForm extends EntityForm
{
    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param Config $config
     * @param Entity\Repository\RoleRepository $roleRepo
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config,
        Entity\Repository\RoleRepository $roleRepo
    ) {
        $form_config = $config->get('forms/user', [
            'roles' => $roleRepo->fetchSelect(),
        ]);

        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\User::class;
    }

    /**
     * @inheritdoc
     */
    public function process(ServerRequest $request, $record = null)
    {
        // Check for administrative permissions and hide admin fields otherwise.
        $user = $request->getUser();

        if ($record instanceof Entity\User && $record->getId() === $user->getId()) {
            unset($this->fields['roles']);
        }

        return parent::process($request, $record);
    }
}
