<?php

namespace App\Form;

use App\Acl;
use App\Config;
use App\Entity;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PermissionsForm extends EntityForm
{
    protected Entity\Repository\RolePermissionRepository $permissions_repo;

    protected bool $set_permissions = true;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config,
        Entity\Repository\StationRepository $stations_repo,
        Entity\Repository\RolePermissionRepository $permissions_repo,
        Acl $acl
    ) {
        $form_config = $config->get('forms/role', [
            'all_stations' => $stations_repo->fetchArray(),
            'actions' => $acl->listPermissions(),
        ]);

        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\Role::class;
        $this->permissions_repo = $permissions_repo;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequest $request, $record = null)
    {
        if ($record instanceof Entity\Role && Entity\Role::SUPER_ADMINISTRATOR_ROLE_ID === $record->getId()) {
            $this->set_permissions = false;

            foreach ($this->fields as $field_id => $field) {
                $attrs = $field->getAttributes();
                if (isset($attrs['class']) && strpos($attrs['class'], 'permission-select') !== false) {
                    unset($this->fields[$field_id]);
                }
            }
        }

        return parent::process($request, $record);
    }

    protected function denormalizeToRecord($data, $record = null, array $context = []): object
    {
        $record = parent::denormalizeToRecord($data, $record, $context);

        if ($this->set_permissions) {
            $this->em->persist($record);
            $this->em->flush();

            $this->permissions_repo->setActionsForRole($record, $data);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    protected function normalizeRecord($record, array $context = []): array
    {
        $data = parent::normalizeRecord($record, $context);

        if ($this->set_permissions) {
            $actions = $this->permissions_repo->getActionsForRole($record);
            return array_merge($data, $actions);
        }

        return $data;
    }
}
