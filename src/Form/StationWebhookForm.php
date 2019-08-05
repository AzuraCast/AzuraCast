<?php
namespace App\Form;

use App\Entity;
use App\Http\Router;
use Azura\Config;
use Azura\Settings;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface;
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
     * @param Config $config
     * @param Router $router
     * @param Settings $settings
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config,
        Router $router,
        Settings $settings
    ) {
        $webhook_config = $config->get('webhooks');

        $webhook_forms = [];
        $config_injections = [
            'router' => $router,
            'app_settings' => $settings,
            'triggers' => $webhook_config['triggers'],
        ];
        foreach($webhook_config['webhooks'] as $webhook_key => $webhook_info) {
            $webhook_forms[$webhook_key] = $config->get('forms/webhook/'.$webhook_key, $config_injections);
        }

        parent::__construct($em, $serializer, $validator);

        $this->config = $webhook_config;
        $this->forms = $webhook_forms;
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

    public function process(ServerRequestInterface $request, $record = null)
    {
        if (!$record instanceof Entity\StationWebhook) {
            throw new \InvalidArgumentException(sprintf('Record is not an instance of %s', Entity\StationWebhook::class));
        }

        $this->configure($this->forms[$record->getType()]);

        return parent::process($request, $record);
    }
}
