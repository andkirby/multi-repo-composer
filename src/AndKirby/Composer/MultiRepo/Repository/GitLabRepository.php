<?php
namespace AndKirby\Composer\MultiRepo\Repository;

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Repository\VcsRepository;

/**
 * Same repository as Vcs with the specific driver for GitLab
 *
 * @package AndKirby\Composer\Repository
 */
class GitLabRepository extends VcsRepository
{
    /**
     * Repository type
     */
    const TYPE = 'gitlab';

    /**
     * Initialize GITLab downloader
     *
     * @param array $repoConfig
     * @param IOInterface $io
     * @param Config $config
     * @param EventDispatcher $dispatcher
     * @param array $drivers
     */
    public function __construct(array $repoConfig, IOInterface $io,
                                Config $config, EventDispatcher $dispatcher = null,
                                array $drivers = null)
    {
        $drivers[self::TYPE] = 'AndKirby\Composer\MultiRepo\Repository\Vcs\GitLabDriver';
        parent::__construct($repoConfig, $io, $config, $dispatcher, $drivers);
    }
}