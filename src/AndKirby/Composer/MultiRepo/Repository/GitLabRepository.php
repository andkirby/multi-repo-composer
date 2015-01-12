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
    public function __construct(array $repoConfig, IOInterface $io, Config $config,
                                EventDispatcher $dispatcher = null)
    {
        parent::__construct($repoConfig, $io, $config, $dispatcher, array(
            'gitlab' => 'AndKirby\Composer\MultiRepo\Repository\Vcs\GitLabDriver',
        ));
    }
}