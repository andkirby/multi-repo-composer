<?php
namespace AndKirby\Composer\MultiRepo\Repository\Vcs;

class GitLabDriver extends VcsNamespaceDriver
{
    /**
     * Get GitLab archive info
     *
     * @param string $identifier
     * @return array
     */
    public function getDist($identifier)
    {
        if (false === strpos($this->repoConfig['type'], 'gitlab')) {
            return parent::getDist($identifier);
        }

        return array(
            'type'   => $this->getArchiveType(),
            'url'    => $this->getBranchTarArchiveUrl(
                            $this->getBranchNameByHash($identifier)
                        ),
            'reference' => $identifier,
            'shasum' => ''
        );
    }

    /**
     * Get branch or tag TAR archive URL
     *
     * @param string $branchOrTag
     * @return string
     */
    protected function getBranchTarArchiveUrl($branchOrTag)
    {
        return $this->getBaseArchiveUrl() . '/repository/archive'
            . $this->getArchiveUrlType() . '?ref=' . $branchOrTag;
    }

    /**
     * Get branch or tag name by commit hash
     *
     * @param string $hash
     * @return mixed
     */
    protected function getBranchNameByHash($hash)
    {
        $name = array_search($hash, $this->getTags());
        if (!$name) {
            $name = array_search($hash, $this->getBranches());
        }
        return $name;
    }

    /**
     * Get base GitLab archive URL
     *
     * @return string
     */
    protected function getBaseArchiveUrl()
    {
        $url = $this->getUrl();
        if ('.git' == substr($url, -4)) {
            return substr($url, 0, -4);
        }
        return $url;
    }

    /**
     * Get archive type from repository config
     *
     * @return string
     */
    protected function getArchiveType()
    {
        return isset($this->repoConfig['multi-repo']['archive-type'])
            ? $this->repoConfig['multi-repo']['archive-type'] : 'zip';
    }

    /**
     * Get archive URL type from repository config
     *
     * @return string
     */
    protected function getArchiveUrlType()
    {
        $type = $this->getArchiveType();
        return '.' . ($type == 'tar' ? 'tar.gz' : $type);
    }

}
