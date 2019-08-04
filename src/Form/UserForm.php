<?php
namespace App\Form;

use App\Entity;
use App\Http\Request;
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
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config
    ) {
        /** @var \Azura\Doctrine\Repository $role_repo */
        $role_repo = $em->getRepository(Entity\Role::class);

        $form_config = $config->get('forms/user', [
            'roles' => $role_repo->fetchSelect()
        ]);

        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\User::class;
    }

    /**
     * @inheritdoc
     */
    public function process(Request $request, $record = null)
    {
        // Check for administrative permissions and hide admin fields otherwise.
        $user = $request->getUser();

        if ($record instanceof Entity\User && $record->getId() === $user->getId()) {
            unset($this->fields['roles']);
        }

        return parent::process($request, $record);
    }
}
