<?php
namespace App\Form;

use App\Acl;
use App\Entity;
use App\Http\Request;

class Station extends \AzuraForms\Form
{
    /** @var Entity\Repository\StationRepository */
    protected $station_repo;

    /** @var Acl */
    protected $acl;

    /** @var bool */
    protected $can_see_administration = false;

    public function __construct(
        Acl $acl,
        Entity\Repository\StationRepository $station_repo,
        array $options = []
    ) {
        parent::__construct($options);

        $this->acl = $acl;
        $this->station_repo = $station_repo;
    }

    /**
     * @param Request $request
     * @param Entity\Station|null $record
     * @return bool
     */
    public function process(Request $request, Entity\Station $record = null): bool
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

        // Populate the form with existing values (if they exist).
        if (null !== $record) {
            $this->populate($this->station_repo->toArray($record));
        }

        // Handle submission.
        if ($request->isPost() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $this->station_repo->editOrCreate($data, $record);

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canSeeAdministration(): bool
    {
        return $this->can_see_administration;
    }
}
