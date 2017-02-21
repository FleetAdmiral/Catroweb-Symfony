<?php

namespace Catrobat\AppBundle\Commands;

use Catrobat\AppBundle\RecommenderSystem\RecommenderManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Catrobat\AppBundle\Entity\UserManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Helper\ProgressBar;


class RecommenderUserSimilaritiesCommand extends ContainerAwareCommand
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var RecommenderManager
     */
    private $recommender_manager;

    /**
     * @var EntityManager
     */
    private $entity_manager;

    /**
     * @var string
     */
    private $app_root_dir;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var RecommenderFileLock
     */
    private $migration_file_lock;

    public function __construct(UserManager $user_manager, RecommenderManager $recommender_manager, EntityManager $entity_manager, $app_root_dir)
    {
        parent::__construct();
        $this->user_manager = $user_manager;
        $this->recommender_manager = $recommender_manager;
        $this->entity_manager = $entity_manager;
        $this->app_root_dir = $app_root_dir;
        $this->output = null;
        $this->migration_file_lock = null;
    }

    protected function configure()
    {
        $this->setName('catrobat:recommender:compute')
            ->setDescription('Computes and updates user similarities in database needed for user-based (Collaborative Filtering) recommendations');
    }

    public function signalHandler($signal_number)
    {
        $this->output->writeln('[SignalHandler] Called Signal Handler');
        switch ($signal_number) {
            case SIGTERM:
                $this->output->writeln('[SignalHandler] User aborted the process');
                break;
            case SIGHUP:
                $this->output->writeln('[SignalHandler] SigHup detected');
                break;
            case SIGINT:
                $this->output->writeln('[SignalHandler] SigInt detected');
                break;
            case SIGUSR1:
                $this->output->writeln('[SignalHandler] SigUsr1 detected');
                break;
            default:
                $this->output->writeln('[SignalHandler] Signal ' . $signal_number . ' detected');
        }

        $this->migration_file_lock->unlock();
        exit(-1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        declare(ticks = 1);
        $this->migration_file_lock = new RecommenderFileLock($this->app_root_dir, $output);
        $this->output = $output;
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGHUP, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);

        $this->computeUserSimilarities($output);
    }

    private function computeUserSimilarities(OutputInterface $output)
    {
        $computation_start_time = new \DateTime();
        $progress_bar_format_verbose = '%current%/%max% [%bar%] %percent:3s%% | Elapsed: %elapsed:6s% | ' .
                                       'ETA: %estimated:-6s% | Status: %message%';

        $users = $this->user_manager->findAll();
        $rated_users = array_unique(array_filter($users, function ($user) { return (count($user->getLikes()) > 0); }));
        $total_number_of_rated_users = count($rated_users);
        $progress_bar = new ProgressBar($output, $total_number_of_rated_users);
        $progress_bar->setFormat($progress_bar_format_verbose);
        $progress_bar->start();

        //==============================================================================================================
        // (1) lock
        //==============================================================================================================
        $this->migration_file_lock->lock();

        //==============================================================================================================
        // (2) remove all existing user relations
        //==============================================================================================================
        $progress_bar->setMessage('Remove all old user relations!');
        $this->recommender_manager->removeAllUserSimilarityRelations();
        $this->entity_manager->clear();

        //==============================================================================================================
        // (3) compute similarities between users and create user relations!
        //==============================================================================================================
        $this->recommender_manager->computeUserSimilarities($progress_bar);

        $duration = (new \DateTime())->getTimestamp() - $computation_start_time->getTimestamp();
        $progress_bar->setMessage('');
        $progress_bar->finish();
        $output->writeln('');
        $output->writeln('<info>Finished similarity computation for ' . $total_number_of_rated_users .
                         ' users that have rated (Duration: ' . $duration . 's)</info>');

        //==============================================================================================================
        // (4) unlock
        //==============================================================================================================
        $this->migration_file_lock->unlock();
    }
}

class RecommenderFileLock
{
    private $lock_file_path;
    private $lock_file;
    private $output;

    public function __construct($app_root_dir, OutputInterface $output)
    {
        $this->lock_file_path = $app_root_dir . '/' . RecommenderManager::RECOMMENDER_LOCK_FILE_NAME;
        $this->lock_file = null;
        $this->output = $output;
    }

    public function lock()
    {
        $this->lock_file = fopen($this->lock_file_path, 'w+');
        $this->output->writeln('[RecommenderFileLock] Trying to acquire lock...');
        while (flock($this->lock_file, LOCK_EX) == false) {
            $this->output->writeln('[RecommenderFileLock] Waiting for file lock to be released...');
            sleep(1);
        }

        $this->output->writeln('[RecommenderFileLock] Lock acquired...');
        fwrite($this->lock_file, 'User similarity computation in progress...');
    }

    public function unlock()
    {
        if ($this->lock_file == null) {
            return;
        }

        $this->output->writeln('[RecommenderFileLock] Lock released...');
        flock($this->lock_file, LOCK_UN);
        fclose($this->lock_file);
        @unlink($this->lock_file_path);
    }
}
