<?php
namespace App\Form;

use App\Entity;
use App\Entity\Station;
use App\Radio\Adapters;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface;
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
     * @param Config $config
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config
    ) {
        $form_configs = [
            Adapters::FRONTEND_ICECAST => $config->get('forms/mount/icecast'),
            Adapters::FRONTEND_SHOUTCAST => $config->get('forms/mount/shoutcast2'),
        ];

        parent::__construct($em, $serializer, $validator);

        $this->entityClass = Entity\StationMount::class;
        $this->form_configs = $form_configs;
    }

    public function process(ServerRequestInterface $request, $record = null)
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
