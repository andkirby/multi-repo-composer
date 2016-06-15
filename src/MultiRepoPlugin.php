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
        //set config from root package
        $composer->getConfig()->merge(
            array(
                'config' => array(
                    'root_extra_config' => $composer->getPackage()->getExtra(),
                ),
            )
        );

        $this->initMultiRepositories($composer);

        $this->initGitLab($composer);
        $this->initMultiVcsRepository($composer, $io);
        $this->initMultiGitLabRepository($composer, $io);
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
                'gitlab-namespace', 'AndKirby\Composer\MultiRepo\Repository\GitLabNamespaceRepository'
            );

        return $this;
    }

    /**
     * Reset multi-repository type
     *
     * @param \Composer\Composer $composer
     */
    protected function initMultiRepositories(Composer $composer)
    {
        $repositories = $composer->getConfig()->getRepositories();
        $updated      = false;
        foreach ($repositories as $name => $repo) {
            if (empty($repo['multi-repo-type'])) {
                continue;
            }
            $repositories[$name]                 = false; // remove old
            $repo['type']                        = $repo['multi-repo-type'];
            $repositories[$name . '-multi-repo'] = $repo;
            $updated                             = true;
        }

        if ($updated) {
            $composer->getConfig()->merge(array('repositories' => $repositories));
        }
    }
}
