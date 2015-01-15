<?php
namespace AndKirby\Composer\MultiRepo\Repository\Vcs;

use Composer\Repository\Vcs\GitDriver;

/**
 * Repository driver for vcs-namespace type
 *
 * @package AndKirby\Composer\MultiRepo\Repository\Vcs
 */
class VcsNamespaceDriver extends GitDriver
{
    /**
     * Rewrite preferred repository type
     *
     * @param string $identifier
     * @return array
     */
    public function getSource($identifier)
    {
        $source = parent::getSource($identifier);
        $source['type'] = $this->repoConfig['type'];
        return $source;
    }

}