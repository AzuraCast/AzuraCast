<?php
namespace AzuraCast\Console\Command;

use Entity;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetPassword extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:account:reset-password')
            ->setDescription('Reset the password of the specified account.')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'The user\'s e-mail address.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->di[EntityManager::class];

        $user_email = $input->getArgument('email');

        $user = $em->getRepository(Entity\User::class)
            ->findOneBy(['email' => $user_email ]);

        if ($user instanceof Entity\User) {
            $temp_pw = \App\Utilities::generatePassword(15);

            $user->setAuthPassword($temp_pw);

            $em->persist($user);
            $em->flush();

            $output->writeLn([
                'The account password has been reset. The new temporary password is:',
                ' ',
                $temp_pw,
                ' ',
                'Set a new password using the web interface.',
            ]);
            return true;
        } else {
            $output->writeln('Account not found.');
            return false;
        }
    }
}