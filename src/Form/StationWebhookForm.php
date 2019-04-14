<?php
namespace App\Form;

use App\Entity;
use App\Http\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationWebhookForm extends EntityForm
{
    /** @var array */
    protected $config;

    /** @var array */
    protected $forms;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param array $config
     * @param array $forms
     *
     * @see \App\Provider\FormProvider
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        array $config = [],
        array $forms = []
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->config = $config;
        $this->forms = $forms;
        $this->entityClass = Entity\StationWebhook::class;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getForms(): array
    {
        return $this->forms;
    }

    public function process(Request $request, $record = null)
    {
        if (!$record instanceof Entity\StationWebhook) {
            throw new \InvalidArgumentException(sprintf('Record is not an instance of %s', Entity\StationWebhook::class));
        }

        $this->configure($this->forms[$record->getType()]);

        return parent::process($request, $record);
    }
}
