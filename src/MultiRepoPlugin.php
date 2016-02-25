<?php
namespace AndKirby\Composer\MultiRepo;

use AndKirby\Composer\MultiRepo\Downloader\GitMultiRepoDownloader;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Class MultiRepoPlugin
 *
 * @package AndKirby\Composer\MultiRepo
 */
class MultiRepoPlugin implements PluginInterface
{
    /**
     * Plug-in activation
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        if ($io->isDebug()) {
            $io->write('Activating MultiRepoPlugin...');
        }

        //set config from root package
        $composer->getConfig()->merge(
            array(
                'config' => array(
                    'root_extra_config' => $composer->getPackage()->getExtra(),
                ),
            )
        );
        $this->initGitLab($composer);
        $this->initMultiVcsRepository($composer, $io);
        $this->initMultiGitLabRepository($composer, $io);

        if ($io->isDebug()) {
            $io->write('Activating MultiRepoPlugin completed.');
        }
    }

    /**
     * Init GitLab repository
     *
     * @param Composer $composer
     * @return $this
     */
    protected function initGitLab(Composer $composer)
    {
        $composer->getRepositoryManager()
            ->setRepositoryClass('gitlab', 'AndKirby\Composer\MultiRepo\Repository\GitLabRepository');

        return $this;
    }

    /**
     * Init multi VCS repository
     *
     * @param Composer    $composer
     * @param IOInterface $io
     * @return $this
     */
    protected function initMultiVcsRepository(Composer $composer, IOInterface $io)
    {
        $composer->getDownloadManager()
            ->setDownloader('vcs-namespace', new GitMultiRepoDownloader($io, $composer->getConfig()));
        $composer->getRepositoryManager()
            ->setRepositoryClass('vcs-namespace', 'AndKirby\Composer\MultiRepo\Repository\VcsNamespaceRepository');

        return $this;
    }

    /**
     * Init multi GitLab repository
     *
     * @param Composer    $composer
     * @param IOInterface $io
     * @return $this
     */
    protected function initMultiGitLabRepository(Composer $composer, IOInterface $io)
    {
        $composer->getDownloadManager()
            ->setDownloader('gitlab-namespace', new GitMultiRepoDownloader($io, $composer->getConfig()));
        $composer->getRepositoryManager()
            ->setRepositoryClass(
                'gitlab-namespace',
                'AndKirby\Composer\MultiRepo\Repository\GitLabNamespaceRepository'
            );

        return $this;
    }
}
