<?php
namespace App\Form;

use App\Entity;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PermissionsForm extends EntityForm
{
    /** @var Entity\Repository\RolePermissionRepository */
    protected $permissions_repo;

    /** @var bool */
    protected $set_permissions = true;

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
        /** @var Entity\Repository\StationRepository $stations_repo */
        $stations_repo = $em->getRepository(Entity\Station::class);

        $form_config = $config->get('forms/role', [
            'all_stations' => $stations_repo->fetchArray(),
        ]);

        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\Role::class;
        $this->permissions_repo = $em->getRepository(Entity\RolePermission::class);
    }

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, $record = null)
    {
        if ($record instanceof Entity\Role && Entity\Role::SUPER_ADMINISTRATOR_ROLE_ID === $record->getId()) {
            $this->set_permissions = false;

            foreach($this->fields as $field_id => $field) {
                $attrs = $field->getAttributes();
                if (isset($attrs['class']) && strpos($attrs['class'], 'permission-select') !== false) {
                    unset($this->fields[$field_id]);
                }
            }
        }

        return parent::process($request, $record);
    }

    /**
     * @inheritdoc
     */
    protected function _denormalizeToRecord($data, $record = null, array $context = []): object
    {
        $record = parent::_denormalizeToRecord($data, $record, $context);

        if ($this->set_permissions) {
            $this->em->persist($record);
            $this->em->flush();

            $this->permissions_repo->setActionsForRole($record, $data);
        }

        return $record;
    }

    /**
     * @inheritdoc
     */
    protected function _normalizeRecord($record, array $context = []): array
    {
        $data = parent::_normalizeRecord($record, $context);

        if ($this->set_permissions) {
            $actions = $this->permissions_repo->getActionsForRole($record);
            return array_merge($data, $actions);
        }

        return $data;
    }
}
