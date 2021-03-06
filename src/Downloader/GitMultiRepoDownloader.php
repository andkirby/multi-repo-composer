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
    /**
     * Delimiter to build multi-repo dir name
     */
    const MULTI_REPO_DELIMITER = '-';

    /**
     * Suffix for multi-repo dirs
     */
    const MULTI_REPO_DIRECTORY_SUFFIX = '-multi-repo';

    /**
     * Config key in extra to set custom parent dir for multi repo dirs
     */
    const KEY_MULTI_REPO_PARENT_DIR = 'multi-repo-parent-dir';

    /**
     * Config key in extra to set custom parent dir in cache-dir-repo
     */
    const KEY_MULTI_REPO_IN_CACHE = 'multi-repo-dir-in-cache'; //default true

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
        if ($this->isPathOfMultiRepository($path)) {
            return $path;
        }
        $packageDir = pathinfo($path, PATHINFO_BASENAME);
        if (!strpos($packageDir, self::MULTI_REPO_DELIMITER)) {
            //package cannot be used in multi-repo
            return null;
        }

        $this->defaultPath = $path;
        $arr = explode(self::MULTI_REPO_DELIMITER, $packageDir);
        $baseName = array_shift($arr);

        $customDir = $this->getParentMultiRepoDir();

        if ($customDir) {
            //get vendor name
            $vendor = pathinfo(dirname($path), PATHINFO_BASENAME);
            //get path based upon custom parent dir
            $newPath = $customDir . DIRECTORY_SEPARATOR . $vendor . DIRECTORY_SEPARATOR
                       . $baseName . self::MULTI_REPO_DIRECTORY_SUFFIX;
            if ($this->io->isVeryVerbose()) {
                $this->io->write('    Multi-repository custom path found.');
            }
        } else {
            //make full path to new general multi-repo directory
            $newPath = str_replace(
                $packageDir,
                $baseName . self::MULTI_REPO_DIRECTORY_SUFFIX, //make repo dir name
                $path
            );
            if ($this->io->isVeryVerbose()) {
                $this->io->write('    Multi-repository path will be within the current vendor directory.');
            }
        }
        $this->filesystem->ensureDirectoryExists($newPath);
        return $newPath;
    }

    /**
     * Get custom parent multi repository directory
     *
     * @return string|null
     */
    protected function getParentMultiRepoDir()
    {
        $rootConfig = $this->config->get('root_extra_config');
        if (!empty($rootConfig[self::KEY_MULTI_REPO_PARENT_DIR])) {
            $rootConfig[self::KEY_MULTI_REPO_PARENT_DIR] = rtrim($rootConfig[self::KEY_MULTI_REPO_PARENT_DIR], '\\/');
            if ($this->io->isVeryVerbose()) {
                $this->io->write('    Multi-repository path will be in custom parent directory.');
            }
            return $rootConfig[self::KEY_MULTI_REPO_PARENT_DIR];
        }
        if (!isset($rootConfig[self::KEY_MULTI_REPO_IN_CACHE]) || $rootConfig[self::KEY_MULTI_REPO_IN_CACHE]) {
            if ($this->io->isVeryVerbose()) {
                $this->io->write('    Multi-repository path will be in "cache-repo-dir".');
            }
            return $this->config->get('cache-repo-dir');
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

        if (is_file($source)) {
            //copy file
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
        if ($this->io->isVerbose()) {
            $this->io->write('    Multi-repository path: ' . $path);
        }
        if (!$this->isRepositoryCloned($path)) {
            $this->io->write('    Multi-repository GIT directory not found. Cloning...');
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
        GitUtil::cleanEnv();

        $path = $this->normalizePath($path);

        if (!$this->isRepositoryCloned($path)) {
            //clone the multi repository if it was removed
            $this->io->write('    Multi-repository GIT directory not found. Cloning...');
            $this->cloneRepository($initial, $path, $url);
        }

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

    /**
     * Check path is being of multi-repository
     *
     * @param string $path
     * @return bool
     */
    protected function isPathOfMultiRepository($path)
    {
        return self::MULTI_REPO_DIRECTORY_SUFFIX == substr(
            $path, -strlen(self::MULTI_REPO_DIRECTORY_SUFFIX)
        );
    }
}
