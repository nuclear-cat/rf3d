<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;

/**
 * Site bundle extension loader.
 *
 * This is the base bundle you can use to further customise Bolt for your
 * specific site.
 *
 * It is perfectly safe to remove this bundle, just remember to remove the
 * entry from your .bolt.yml or .bolt.php file.
 *
 * For more information on building bundles see https://docs.bolt.cm/extensions
 */
class CustomisationExtension extends SimpleExtension
{
    //https://stackoverflow.com/questions/52754936/overwrite-backend-template-in-bolt-cms
    protected function registerTwigPaths()
    {
        if ($this->getEnd() == 'backend') {
            return [
                'view' => ['position' => 'prepend', 'namespace' => 'bolt']
            ];
        }
        return [];
    }

    private function getEnd()
    {
        $backendPrefix = $this->container['config']->get('general/branding/path');
        $end = $this->container['config']->getWhichEnd();

        switch ($end) {
            case 'backend':
                return 'backend';
            case 'async':
                // we have async request
                // if the request begin with "/admin" (general/branding/path)
                // it has been made on backend else somewhere else
                $url = '/' . ltrim($_SERVER['REQUEST_URI'], $this->container['paths']['root']);
                $adminUrl = '/' . trim($backendPrefix, '/');
                if (strpos($url, $adminUrl) === 0) {
                    return 'backend';
                }
            default:
                return $end;
        }
    }
}
