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
            'url'    => $this->_getBranchTarArchiveUrl($identifier),
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
        return $this->getUrl() . '/repository/archive.tar.gz?ref=' . $branchOrTag;
    }

}