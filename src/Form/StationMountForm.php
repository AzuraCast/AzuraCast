<?php
namespace App\Form;

use App\Entity;
use App\Entity\Station;
use App\Http\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationMountForm extends EntityForm
{
    /** @var array */
    protected $form_configs;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param array $options
     * @param array|null $defaults
     * @param array $form_configs
     *
     * @see \App\Provider\FormProvider
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        array $options = [],
        ?array $defaults = null,
        array $form_configs = []
    ) {
        parent::__construct($em, $serializer, $validator, $options, $defaults);

        $this->entityClass = Entity\StationMount::class;
        $this->form_configs = $form_configs;
    }

    public function process(Request $request, $record = null)
    {
        $record = parent::process($request, $record);

        if ($record instanceof Entity\StationMount && $record->getIsDefault()) {
            foreach($this->station->getMounts() as $mount) {
                /** @var Entity\StationMount $mount */
                if ($mount->getId() !== $record->getId()) {
                    $mount->setIsDefault(false);
                    $this->em->persist($mount);
                }
            }

            $this->em->flush();
        }

        return $record;
    }

    public function setStation(Station $station): void
    {
        parent::setStation($station);

        $frontend_type = $station->getFrontendType();

        if (isset($this->form_configs[$frontend_type])) {
            $this->configure($this->form_configs[$frontend_type]);
        }
    }
}
