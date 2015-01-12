<?php
namespace AndKirby\Composer\MultiRepo;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class MultiRepoPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $composer->getRepositoryManager()
            ->setRepositoryClass('gitlab', 'AndKirby\Composer\MultiRepo\Repository\GitLabRepository');
        $composer->getRepositoryManager()
            ->setRepositoryClass('gitlab-namespace', 'AndKirby\Composer\MultiRepo\Repository\GitLabNamespaceRepository');
        $composer->getRepositoryManager()
            ->setRepositoryClass('vcs-namespace', 'AndKirby\Composer\MultiRepo\Repository\VcsNamespaceRepository');

    }
}
