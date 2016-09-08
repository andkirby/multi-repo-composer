<?php
namespace AndKirby\Composer\MultiRepo;

use AndKirby\Composer\MultiRepo\Downloader\GitMultiRepoDownloader;
use AndKirby\Composer\MultiRepo\Repository\VcsNamespaceRepository;
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
            [
                'config' => [
                    'root_extra_config' => $composer->getPackage()->getExtra(),
                ],
            ]
        );

        $this->initConfig($composer);

        $this->initMultiVcsRepositories($composer, $io);
    }

    /**
     * Init multi VCS repository
     *
     * @param Composer    $composer
     * @param IOInterface $io
     * @return $this
     */
    protected function initMultiVcsRepositories(Composer $composer, IOInterface $io)
    {
        $repoDownloader = new GitMultiRepoDownloader($io, $composer->getConfig());
        foreach ($this->getVcsTypes() as $type) {
            $composer->getDownloadManager()
                ->setDownloader($type, $repoDownloader);
            $composer->getRepositoryManager()
                ->setRepositoryClass($type, $this->getMultiRepositoryClassName());
        }

        return $this;
    }

    /**
     * Reset multi-repository type
     *
     * @param \Composer\Composer $composer
     * @return $this
     */
    protected function initConfig(Composer $composer)
    {
        $repositories = $composer->getConfig()->getRepositories();
        $updated      = false;
        foreach ($repositories as $name => $repo) {
            if (empty($repo['multi-repo-type'])) {
                continue;
            }
            $repo['type']                      = $repo['multi-repo-type'];
            $repositories[$name.'-multi-repo'] = $repo;

            $repositories[$name] = false; // remove old node on merge
            $updated             = true;
        }

        if ($updated) {
            $composer->getConfig()->merge(['repositories' => $repositories]);
        }

        return $this;
    }

    /**
     * Get multi repository class name
     *
     * @return string
     */
    protected function getMultiRepositoryClassName()
    {
        return VcsNamespaceRepository::class;
    }

    /**
     * Get VCS namespace type
     *
     * @return array
     */
    protected function getVcsTypes()
    {
        return VcsNamespaceRepository::getTypes();
    }
}
