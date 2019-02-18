<?php
namespace App\Form;

use App\Acl;
use App\Entity;
use App\Http\Request;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationForm extends EntityForm
{
    /** @var Entity\Repository\StationRepository */
    protected $station_repo;

    /** @var Acl */
    protected $acl;

    /** @var bool */
    protected $can_see_administration = false;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param Acl $acl
     * @param array $form_config
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Acl $acl,
        array $form_config)
    {
        parent::__construct($em, $serializer, $validator, $form_config);

        $this->acl = $acl;
        $this->entityClass = Entity\Station::class;
        $this->station_repo = $em->getRepository(Entity\Station::class);
    }

    /**
     * @inheritdoc
     */
    public function getEntityRepository(): Repository
    {
        return $this->station_repo;
    }

    /**
     * @return bool
     */
    public function canSeeAdministration(): bool
    {
        return $this->can_see_administration;
    }

    /**
     * @inheritdoc
     */
    public function process(Request $request, $record = null)
    {
        // Check for administrative permissions and hide admin fields otherwise.
        $user = $request->getUser();
        $this->can_see_administration = $this->acl->userAllowed($user, Acl::GLOBAL_STATIONS);

        if (!$this->can_see_administration) {
            foreach($this->options['groups']['admin']['elements'] as $element_key => $element_info) {
                unset($this->fields[$element_key]);
            }
            unset($this->options['groups']['admin']);
        }

        // Add the port checker validator (which depends on the current record) to the appropriate fields.
        $port_checker = function($value) use ($record) {
            if ($this->station_repo->isPortUsed($value, $record)) {
                return __('This port is currently in use by another station.');
            }
            return true;
        };

        foreach($this->fields as $field) {
            $attrs = $field->getAttributes();

            if (isset($attrs['class']) && strpos($attrs['class'], 'input-port') !== false) {
                $field->addValidator($port_checker);
            }
        }

        return parent::process($request, $record);
    }
}
