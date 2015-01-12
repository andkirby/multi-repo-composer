<?php
namespace AndKirby\Composer\MultiRepo\Repository\Vcs;

use Composer\Repository\Vcs\GitDriver;

class GitLabDriver extends GitDriver
{
    /**
     * Get GitLab archive info
     *
     * @param string $identifier
     * @return array
     */
    public function getDist($identifier)
    {
        return array(
            'type'   => 'tar',
            'url'    => $this->_getBranchTarArchiveUrl(
                            $this->_getBranchNameByHash($identifier)
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
    protected function _getBranchTarArchiveUrl($branchOrTag)
    {
        return $this->_getBaseArchiveUrl() . '/repository/archive?ref=' . $branchOrTag;
    }

    /**
     * Get branch or tag name by commit hash
     *
     * @param string $hash
     * @return mixed
     */
    protected function _getBranchNameByHash($hash)
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
    protected function _getBaseArchiveUrl()
    {
        $url = $this->getUrl();
        if ('.git' == substr($url, -4)) {
            return substr($url, 0, -4);
        }
        return $url;
    }

}