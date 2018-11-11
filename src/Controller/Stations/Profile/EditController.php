<?php
namespace App\Controller\Stations\Profile;

use Azura\Cache;
use App\Radio\Configuration;
use AzuraForms\Field\AbstractField;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class EditController
{
    /** @var EntityManager */
    protected $em;

    /** @var Cache */
    protected $cache;

    /** @var Configuration */
    protected $configuration;

    /** @var Entity\Repository\StationRepository */
    protected $station_repo;

    /** @var array */
    protected $form_config;

    /**
     * @param EntityManager $em
     * @param Cache $cache
     * @param Configuration $configuration
     * @param array $form_config
     * @see \App\Provider\StationsProvider
     */
    public function __construct(
        EntityManager $em,
        Cache $cache,
        Configuration $configuration,
        array $form_config
    )
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->configuration = $configuration;
        $this->form_config = $form_config;

        $this->station_repo = $em->getRepository(Entity\Station::class);
    }

    public function __invoke(Request $request, Response $response, $station_id): Response
    {
        $station = $request->getStation();
        $frontend = $request->getStationFrontend();

        $base_form = $this->form_config;
        unset($base_form['groups']['admin']);

        $form = new \AzuraForms\Form($base_form);

        $port_checker = function($value) use ($station) {
            if (!empty($value)) {
                $value = (int)$value;
                $used_ports = $this->configuration->getUsedPorts($station);

                if (isset($used_ports[$value])) {
                    $station_reference = $used_ports[$value];
                    return __('This port is currently in use by the station "%s".', $station_reference['name']);
                }
            }
            return true;
        };

        foreach($form as $field) {
            /** @var AbstractField $field */
            $attrs = $field->getAttributes();

            if (isset($attrs['class']) && strpos($attrs['class'], 'input-port') !== false) {
                $field->addValidator($port_checker);
            }
        }

        $form->populate($this->station_repo->toArray($station));

        if (!empty($_POST) && $form->isValid($_POST)) {

            $data = $form->getValues();

            $old_frontend = $station->getFrontendType();
            $old_backend = $station->getBackendType();

            $this->station_repo->fromArray($station, $data);
            $this->em->persist($station);
            $this->em->flush();

            $frontend_changed = ($old_frontend !== $station->getFrontendType());
            $backend_changed = ($old_backend !== $station->getBackendType());
            $adapter_changed = $frontend_changed || $backend_changed;

            if ($frontend_changed) {
                $this->station_repo->resetMounts($station, $frontend);
            }

            $this->configuration->writeConfiguration($station, $adapter_changed);

            // Clear station cache.
            $this->cache->remove('stations');

            return $response->withRedirect($request->getRouter()->fromHere('stations:profile:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/profile/edit', [
            'form' => $form,
        ]);
    }
}
