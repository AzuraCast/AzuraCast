<?php

namespace App\Form;

use App\Config;
use App\Entity;
use App\Http\Router;
use App\Http\ServerRequest;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationWebhookForm extends EntityForm
{
    protected array $config;

    protected array $forms;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Settings $settings,
        Config $config,
        Router $router
    ) {
        $webhook_config = $config->get('webhooks');

        $webhook_forms = [];
        $config_injections = [
            'router' => $router,
            'triggers' => $webhook_config['triggers'],
            'app_settings' => $settings,
        ];

        foreach ($webhook_config['webhooks'] as $webhook_key => $webhook_info) {
            $webhook_forms[$webhook_key] = $config->get('forms/webhook/' . $webhook_key, $config_injections);
        }

        parent::__construct($em, $serializer, $validator);

        $this->config = $webhook_config;
        $this->forms = $webhook_forms;
        $this->entityClass = Entity\StationWebhook::class;
    }

    /**
     * @return mixed[]
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return mixed[]
     */
    public function getForms(): array
    {
        return $this->forms;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequest $request, $record = null)
    {
        if (!$record instanceof Entity\StationWebhook) {
            throw new InvalidArgumentException(sprintf(
                'Record is not an instance of %s',
                Entity\StationWebhook::class
            ));
        }

        $this->configure($this->forms[$record->getType()]);

        return parent::process($request, $record);
    }
}
