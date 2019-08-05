<?php
namespace App\Form;

use App\Acl;
use App\Entity;
use App\Radio\Frontend\SHOUTcast;
use Azura\Config;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
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
     * @param Config $config
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Acl $acl,
        Config $config
    ) {
        $form_config = $config->get('forms/station');

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
    public function process(ServerRequestInterface $request, $record = null)
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

        if (!SHOUTcast::isInstalled()) {
            $this->options['groups']['select_frontend_type']['elements']['frontend_type'][1]['description'] = __('Want to use SHOUTcast 2? <a href="%s" target="_blank">Install it here</a>, then reload this page.', $request->getRouter()->named('admin:install:shoutcast'));
        }

        $create_mode = (null === $record);
        if (!$create_mode) {
            $this->populate($this->_normalizeRecord($record));
        }

        if ($request->isPost() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $record = $this->_denormalizeToRecord($data, $record);

            $errors = $this->validator->validate($record);
            if (count($errors) > 0) {
                foreach($errors as $error) {
                    /** @var ConstraintViolation $error */
                    $field_name = $error->getPropertyPath();

                    if (isset($this->fields[$field_name])) {
                        $this->fields[$field_name]->addError($error->getMessage());
                    } else {
                        $this->addError($error->getMessage());
                    }
                }
                return false;
            }

            if ($create_mode) {
                $this->station_repo->create($record);
            } else {
                $this->station_repo->edit($record);
            }

            $this->em->persist($record);
            $this->em->flush($record);
            return $record;
        }

        return false;
    }
}
