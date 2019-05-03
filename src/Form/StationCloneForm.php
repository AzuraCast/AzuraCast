<?php
namespace App\Form;

use App\Acl;
use App\Entity;
use App\Http\Request;
use App\Radio\Configuration;
use App\Radio\Frontend\SHOUTcast;
use Azura\Doctrine\Repository;
use DeepCopy;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationCloneForm extends StationForm
{
    /** @var Configuration */
    protected $configuration;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param Acl $acl
     * @param Configuration $configuration
     * @param array $form_config
     *
     * @see \App\Provider\FormProvider
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Acl $acl,
        Configuration $configuration,
        array $form_config
    ) {
        parent::__construct($em, $serializer, $validator, $acl, $form_config);

        $this->configuration = $configuration;
    }

    /**
     * @inheritdoc
     */
    public function process(Request $request, $record = null)
    {
        if (!$record instanceof Entity\Station) {
            throw new \InvalidArgumentException('Record must be a station.');
        }

        $this->populate([
            'name' => $record->getName().' - Copy',
            'description' => $record->getDescription(),
        ]);

        if ($request->isPost() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();

            $copier = new DeepCopy\DeepCopy;
            $copier->addFilter(new DeepCopy\Filter\Doctrine\DoctrineProxyFilter, new DeepCopy\Matcher\Doctrine\DoctrineProxyMatcher);
            $copier->addFilter(new DeepCopy\Filter\KeepFilter, new DeepCopy\Matcher\PropertyMatcher(Entity\StationMedia::class, 'song'));
            $copier->addFilter(new DeepCopy\Filter\KeepFilter, new DeepCopy\Matcher\PropertyMatcher(Entity\RolePermission::class, 'role'));
            $copier->addFilter(new DeepCopy\Filter\KeepFilter, new DeepCopy\Matcher\PropertyMatcher(Entity\StationMediaCustomField::class, 'field'));
            $copier->addFilter(
                new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter,
                new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'history')
            );
            $copier->addFilter(
                new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter,
                new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'mounts')
            );
            $copier->addFilter(
                new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter,
                new DeepCopy\Matcher\PropertyMatcher(Entity\StationPlaylist::class, 'media_items')
            );

            // Unset some properties across all copied record types.
            $global_unsets = ['id', 'station_id'];
            foreach($global_unsets as $prop) {
                $copier->addFilter(new DeepCopy\Filter\SetNullFilter, new DeepCopy\Matcher\PropertyNameMatcher($prop));
            }

            // Unset some values only on Station entities.
            $unset_values = [
                'short_name',
                'radio_base_dir',
                'nowplaying',
                'nowplaying_timestamp',
                'current_streamer_id',
                'current_streamer',
            ];

            if ('share' !== $data['clone_media']) {
                $unset_values[] = 'radio_media_dir';
                $unset_values[] = 'storage_used';
            }

            foreach($unset_values as $prop) {
                $copier->addFilter(new DeepCopy\Filter\SetNullFilter, new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, $prop));
            }

            // Set some properties from the form submission.
            $set_from_data = ['name', 'description'];
            foreach($set_from_data as $prop) {
                $copier->addFilter(new DeepCopy\Filter\ReplaceFilter(function($orig_value) use ($data, $prop) {
                    return $data[$prop];
                }), new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, $prop));
            }

            // Unset booleans
            $set_false_values = [
                'is_streamer_live',
                'needs_restart',
                'has_started',
            ];
            foreach($set_false_values as $prop) {
                $copier->addFilter(
                    new DeepCopy\Filter\ReplaceFilter(function() { return false; }),
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, $prop)
                );
            }

            // Unset ports.
            $copier->addFilter(new DeepCopy\Filter\ReplaceFilter(function($orig_value) {
                $orig_value = (array)$orig_value;
                unset($orig_value['port']);
                return $orig_value;
            }), new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'frontend_config'));

            $copier->addFilter(new DeepCopy\Filter\ReplaceFilter(function($orig_value) {
                $orig_value = (array)$orig_value;
                unset($orig_value['dj_port'], $orig_value['telnet_port']);
                return $orig_value;
            }), new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'backend_config'));

            if (!$data['clone_playlists']) {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter,
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'playlists')
                );
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter,
                    new DeepCopy\Matcher\PropertyMatcher(Entity\StationMedia::class, 'playlists')
                );
            }

            if (!$data['clone_streamers']) {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter,
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'streamers')
                );
            }

            if (!$data['clone_permissions']) {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter,
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'permissions')
                );
            }

            if ('none' === $data['clone_media']) {
                $copier->addFilter(
                    new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter,
                    new DeepCopy\Matcher\PropertyMatcher(Entity\Station::class, 'media')
                );
            }

            // Execute the Doctrine entity copy.
            $copier->addFilter(new DeepCopy\Filter\Doctrine\DoctrineCollectionFilter, new DeepCopy\Matcher\PropertyTypeMatcher(Collection::class));

            /** @var Entity\Station $new_record */
            $new_record = $copier->copy($record);

            $this->station_repo->create($new_record);
            $this->configuration->assignRadioPorts($new_record, true);
            $this->configuration->writeConfiguration($new_record);

            // Copy associated records if applicable.
            if ('copy' === $data['clone_media']) {
                copy($record->getRadioMediaDir(), $new_record->getRadioMediaDir());
            }

            $this->em->flush();
            return $new_record;
        }

        return false;
    }
}
