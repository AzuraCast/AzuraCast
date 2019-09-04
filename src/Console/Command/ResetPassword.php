<?php
namespace App\Console\Command;

use App\Entity;
use App\Utilities;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetPassword extends CommandAbstract
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
        $io = new SymfonyStyle($input, $output);
        $io->title('Reset Account Password');

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        $user_email = $input->getArgument('email');

        $user = $em->getRepository(Entity\User::class)
            ->findOneBy(['email' => $user_email]);

        if ($user instanceof Entity\User) {
            $temp_pw = Utilities::generatePassword(15);

            $user->setAuthPassword($temp_pw);
            $user->setTwoFactorSecret(null);

            $em->persist($user);
            $em->flush();

            $io->text([
                'The account password has been reset. The new temporary password is:',
                '',
                '    ' . $temp_pw,
                '',
                'Log in using this temporary password and set a new password using the web interface.',
                '',
            ]);
            return 0;
        }

        $io->error('Account not found.');
        return 1;
    }
}
