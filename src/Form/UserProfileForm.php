<?php

declare(strict_types=1);

namespace App\Form;

use App\Config;
use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use AzuraForms\Field\AbstractField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserProfileForm extends EntityForm
{
    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config,
        Environment $environment
    ) {
        $form_config = $config->get(
            'forms/profile',
            [
                'environment' => $environment,
            ]
        );
        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\User::class;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequest $request, $record = null): object|bool
    {
        $user = $request->getUser();

        $this->getField('password')->addValidator(
            function ($val, AbstractField $field) use ($user) {
                $new_password = $field->getForm()->getField('new_password')->getValue();
                if (!empty($new_password)) {
                    if ($user->verifyPassword($val)) {
                        return true;
                    }

                    return 'Current password could not be verified.';
                }

                return true;
            }
        );

        return parent::process($request, $user);
    }

    public function getView(ServerRequest $request): string
    {
        $user = $request->getUser();

        $viewForm = new Form($this->options['groups']['customization'], $this->normalizeRecord($user));
        return $viewForm->renderView();
    }
}
