<?php
/**
 * Created by PhpStorm.
 * User: kirby
 * Date: 12.01.2015
 * Time: 2:01
 */

namespace AndKirby\Composer\Repository;

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;

/**
 * Same repository as Vcs with the specific driver for GitLab
 *
 * @package AndKirby\Composer\Repository
 */
class GitLabNamespaceRepository extends VcsNamespaceRepository
{
    public function __construct(array $repoConfig, IOInterface $io, Config $config,
                                EventDispatcher $dispatcher = null)
    {
        parent::__construct($repoConfig, $io, $config, $dispatcher, array(
            'gitlab' => 'AndKirby\Composer\Repository\Vcs\GitLabDriver',
        ));
    }
}