<?php
namespace AndKirby\Composer\MultiRepo\Downloader;

use Composer\Config;
use Composer\Downloader\FilesystemException;
use Composer\Downloader\GitDownloader;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Composer\Util\Git as GitUtil;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class GitMultiRepoDownloader extends GitDownloader
{
    const MULTI_REPO_DELIMITER = '-';
    const MULTI_REPO_DIRECTORY_SUFFIX = '-multi-repo';
    /**
     * Git Utility
     *
     * @var GitUtil
     */
    protected $gitUtil;

    /**
     * Default path of package
     *
     * @var string
     */
    protected $defaultPath;

    /**
     * Init GitUtil
     *
     * @param IOInterface $io
     * @param Config $config
     * @param ProcessExecutor $process
     * @param Filesystem $fs
     */
    public function __construct(IOInterface $io, Config $config, ProcessExecutor $process = null, Filesystem $fs = null)
    {
        parent::__construct($io, $config, $process, $fs);
        $this->initGitUtil();
    }

    /**
     * Normalize path
     *
     * Return path of general multi-repository if possible
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath($path)
    {
        $path = parent::normalizePath($path);
        return $this->getMultiRepositoryPath($path) ?: $path;
    }

    /**
     * Get multi repository directory path
     *
     * @param string $path
     * @return string
     */
    protected function getMultiRepositoryPath($path)
    {
        $pathExploded = explode(PATH_SEPARATOR, $path);
        $last = array_pop($pathExploded);
        if (strpos($last, self::MULTI_REPO_DELIMITER)) {
            $this->defaultPath = $path;
            $last = explode(self::MULTI_REPO_DELIMITER, $last);
            array_pop($last); //remove last element in array
            $last = implode(self::MULTI_REPO_DELIMITER, $last);
            $pathExploded[] = $last; //set back last folder
            return implode(PATH_SEPARATOR, $pathExploded) . self::MULTI_REPO_DIRECTORY_SUFFIX;
        }
        return null;
    }

    /**
     * Copy files to default package directory
     *
     * @param $source
     * @return $this
     */
    protected function copyFilesToDefaultPath($source)
    {
        $target = $this->defaultPath;
        if (!$target || $target == $source) {
            return $this;
        }

        if (!is_dir($source)) {
            copy($source, $target);
            return $this;
        }

        $it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);

        $this->cleanUpDir($target);

        /** @var \SplFileInfo $file */
        foreach ($ri as $file) {
            if ('.git' . DIRECTORY_SEPARATOR == substr($ri->getSubPathName(), 0, 5)
                || '.git' == $ri->getSubPathName()
            ) {
                //skip .git directory
                continue;
            }
            $targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathName();
            if ($file->isDir()) {
                $this->filesystem->ensureDirectoryExists($targetPath);
            } else {
                copy($file->getPathname(), $targetPath);
            }
        }
        return $this;
    }

    /**
     * Downloads specific package into specific folder
     *
     * VCS repository will be created in a separated folder.
     *
     * @param PackageInterface $package
     * @param string $path
     * @param string $url
     * @throws FilesystemException
     */
    public function doDownload(PackageInterface $package, $path, $url)
    {
        GitUtil::cleanEnv();
        $path = $this->normalizePath($path);

        if (!$this->isRequiredRepository($path, $url)) {
            throw new FilesystemException('Unknown repository installed into ' . $path);
        }

        $ref = $package->getSourceReference();
        if (!$this->isRepositoryCloned($path)) {
            $this->cloneRepository($package, $path, $url);
        } else {
            $this->io->write('    Multi-repository GIT directory found. Fetching changes...');
            $this->fetchRepositoryUpdates($package, $path, $url);
        }

        if ($newRef = $this->updateToCommit($path, $ref, $package->getPrettyVersion(), $package->getReleaseDate())) {
            if ($package->getDistReference() === $package->getSourceReference()) {
                $package->setDistReference($newRef);
            }
            $package->setSourceReference($newRef);
        }

        //copy file into required directory
        $this->copyFilesToDefaultPath($path);
    }

    public function doUpdate(PackageInterface $initial, PackageInterface $target, $path, $url)
    {
        parent::doUpdate($initial, $target, $path, $url);

        //copy file into required directory
        $this->copyFilesToDefaultPath($this->normalizePath($path));
    }

    /**
     * Init GitUtil object
     *
     * @return $this
     */
    protected function initGitUtil()
    {
        $this->gitUtil = new GitUtil($this->io, $this->config, $this->process, $this->filesystem);
        return $this;
    }

    /**
     * @return string
     */
    protected function getCloneCommand()
    {
        $flag = defined('PHP_WINDOWS_VERSION_MAJOR') ? '/D ' : '';
        return 'git clone --no-checkout %s %s && cd ' . $flag . '%2$s && git remote add composer %1$s && git fetch composer';
    }

    /**
     * Get command callback for cloning
     *
     * @param string $path
     * @param string $ref
     * @param string $command
     * @return callable
     */
    protected function getCloneCommandCallback($path, $ref, $command)
    {
        return function ($url) use ($ref, $path, $command) {
            return sprintf($command, ProcessExecutor::escape($url), ProcessExecutor::escape($path), ProcessExecutor::escape($ref));
        };
    }

    /**
     * Get fetch command
     *
     * @return string
     */
    protected function getFetchCommand()
    {
        return 'git remote set-url composer %s && git fetch composer && git fetch --tags composer';
    }

    /**
     * Fetch remote VCS repository updates
     *
     * @param PackageInterface $package
     * @param string $path
     * @param string $url
     * @return $this
     */
    protected function fetchRepositoryUpdates(PackageInterface $package, $path, $url)
    {
        /**
         * Copy-pasted from doUpdate
         *
         * @see GitDownloader::doUpdate()
         */
        $this->io->write('    Checking out ' . $package->getSourceReference());
        $command = $this->getFetchCommand();
        $commandCallable = function ($url) use ($command) {
            return sprintf($command, ProcessExecutor::escape($url));
        };
        $this->gitUtil->runCommand($commandCallable, $url, $path);
        return $this;
    }

    /**
     * Clone repository
     *
     * @param PackageInterface $package
     * @param string $path
     * @param string $url
     * @return $this
     */
    protected function cloneRepository(PackageInterface $package, $path, $url)
    {
        $command = $this->getCloneCommand();
        $this->io->write("    Cloning " . $package->getSourceReference());

        $commandCallable = $this->getCloneCommandCallback($path, $package->getSourceReference(), $command);

        $this->gitUtil->runCommand($commandCallable, $url, $path, true);
        if ($url !== $package->getSourceUrl()) {
            $url = $package->getSourceUrl();
            $this->process->execute(sprintf('git remote set-url origin %s', ProcessExecutor::escape($url)), $output, $path);
        }
        $this->setPushUrl($path, $url);
        return $this;
    }

    /**
     * @param string $path
     * @return string
     */
    protected function isRepositoryCloned($path)
    {
        return is_dir($path . '/.git');
    }

    /**
     * Check mismatch of exists repository URL in remote origin
     *
     * @param string $path
     * @param string $url
     * @return bool
     */
    protected function isRequiredRepository($path, $url)
    {
        if ($this->isMultiRepository() && $this->isRepositoryCloned($path)) {
            $this->process->execute(sprintf('git config --get remote.origin.url'), $output, $path);
            return $url == trim($output);
        }
        return true; //empty directory
    }

    /**
     * Check repository is multiple
     *
     * @return string
     */
    protected function isMultiRepository()
    {
        return $this->defaultPath;
    }

    /**
     * Clean up directory
     *
     * @param string $directory
     * @return $this
     */
    protected function cleanUpDir($directory)
    {
        $this->filesystem->removeDirectory($directory);
        $this->filesystem->ensureDirectoryExists($directory);
        return $this;
    }
}