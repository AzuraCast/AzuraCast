<?php
namespace App\Form;

use App\Entity;
use App\Http\Request;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsForm extends Form
{
    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /**
     * @param EntityManager $em
     * @param array $form_config
     */
    public function __construct(
        EntityManager $em,
        array $form_config)
    {
        parent::__construct($form_config);

        $this->em = $em;
        $this->settings_repo = $em->getRepository(Entity\Settings::class);
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    /**
     * @return Entity\Repository\SettingsRepository
     */
    public function getEntityRepository(): Entity\Repository\SettingsRepository
    {
        return $this->settings_repo;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function process(Request $request): bool
    {
        // Populate the form with existing values (if they exist).
        $this->populate($this->settings_repo->fetchArray(false));

        // Handle submission.
        if ($request->isPost() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $this->settings_repo->setSettings($data);
            return true;
        }

        return false;
    }
}
