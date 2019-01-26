<?php
namespace App\Form;

use App\Entity;
use App\Http\Request;

class Station extends \AzuraForms\Form
{
    /** @var Entity\Repository\StationRepository */
    protected $station_repo;

    public function __construct(Entity\Repository\StationRepository $station_repo, array $options = [])
    {
        parent::__construct($options);

        $this->station_repo = $station_repo;
    }

    /**
     * @param Request $request
     * @param Entity\Station|null $record
     * @return bool
     */
    public function process(Request $request, Entity\Station $record = null): bool
    {
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

        if (null !== $record) {
            $this->populate($this->station_repo->toArray($record));
        }

        if ($request->isPost() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $this->station_repo->editOrCreate($data, $record);

            return true;
        }

        return false;
    }
}
