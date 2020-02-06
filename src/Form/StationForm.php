<?php
namespace App\Form;

use App\Acl;
use App\Entity;
use App\Http\ServerRequest;
use App\Radio\Frontend\SHOUTcast;
use App\Config;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationForm extends EntityForm
{
    protected Entity\Repository\StationRepository $station_repo;

    protected Acl $acl;

    protected bool $can_see_administration = false;

    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\StationRepository $station_repo,
        Acl $acl,
        Config $config
    ) {
        $form_config = $config->get('forms/station');

        parent::__construct($em, $serializer, $validator, $form_config);

        $this->acl = $acl;
        $this->entityClass = Entity\Station::class;
        $this->station_repo = $station_repo;
    }

    public function canSeeAdministration(): bool
    {
        return $this->can_see_administration;
    }

    public function process(ServerRequest $request, $record = null)
    {
        // Check for administrative permissions and hide admin fields otherwise.
        $user = $request->getUser();
        $this->can_see_administration = $this->acl->userAllowed($user, Acl::GLOBAL_STATIONS);

        if (!$this->can_see_administration) {
            foreach ($this->options['groups']['admin']['elements'] as $element_key => $element_info) {
                unset($this->fields[$element_key]);
            }
            unset($this->options['groups']['admin']);
        }

        if (!SHOUTcast::isInstalled()) {
            $this->options['groups']['select_frontend_type']['elements']['frontend_type'][1]['description'] = __('Want to use SHOUTcast 2? <a href="%s" target="_blank">Install it here</a>, then reload this page.',
                $request->getRouter()->named('admin:install_shoutcast:index'));
        }

        $create_mode = (null === $record);
        if (!$create_mode) {
            $this->populate($this->_normalizeRecord($record));
        }

        if ('POST' === $request->getMethod() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $record = $this->_denormalizeToRecord($data, $record);

            $errors = $this->validator->validate($record);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
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

            $this->em->persist($record);
            $this->em->flush($record);

            if ($create_mode) {
                return $this->station_repo->create($record);
            }

            return $this->station_repo->edit($record);
        }

        return false;
    }
}
