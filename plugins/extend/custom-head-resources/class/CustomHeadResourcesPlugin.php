<?php

namespace SunlightExtend\CustomHeadResources;

use Sunlight\Plugin\Action\PluginAction;
use Sunlight\Plugin\ExtendPlugin;
use Sunlight\Router;
use Sunlight\Util\ConfigurationFile;
use Sunlight\Util\UrlHelper;
use SunlightExtend\CustomHeadResources\Action\ManageResourcesAction;

class CustomHeadResourcesPlugin extends ExtendPlugin
{
    /** @var array[] */
    private $resourcesMapDefault = [
        'css_files' => [],
        'js_files' => [],
        'css_before' => '',
        'css_after' => '',
        'js_before' => '',
        'js_after' => '',
    ];

    /** @var ConfigurationFile */
    private $resourcesMap;

    public function onHead(array $args): void
    {
        $map = $this->getResourcesMap()->toArray();

        $tags = ['css' => 'style', 'js' => 'script'];

        foreach (['css', 'js'] as $type) {
            // register files
            foreach ($map[$type . '_files'] as $path) {
                $path = trim($path);
                if (UrlHelper::isAbsolute($path)) {
                    $args[$type][] = $path;
                } else {
                    $args[$type][] = Router::path($path);
                }
            }
            // add custom wrapped strings
            $tag = $tags[$type];
            foreach (['before', 'after'] as $pos) {
                $posVal = $map[$type . '_' . $pos];
                // remove wrapping tag
                $posVal = preg_replace('/^\<[\/]{0,1}' . $tag . '[^\>]*\>/i', '', $posVal);
                $args[$type . '_' . $pos] .= (!empty($posVal) ? sprintf("<%s>%s</%s>", $tag, $posVal, $tag) : '');
            }
        }
    }
    
    protected function getCustomActionList(): array
    {
        return ['manage' => _lang('admin.plugins.action.do.config')];
    }

    function getAction(string $name): ?PluginAction
    {
        if ($name === 'manage') {
            return new ManageResourcesAction($this);
        }

        return parent::getAction($name);
    }

    protected function getResourceMapPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '../resources_map.php';
    }

    public function getResourcesMap(): ConfigurationFile
    {
        if ($this->resourcesMap === null) {
            $defaults = $this->resourcesMapDefault;
            if (empty($defaults)) {
                throw new \LogicException('To use the configuration file, defaults must be specified by overriding the getConfigDefaults() method');
            }
            $this->resourcesMap = new ConfigurationFile($this->getResourceMapPath(), $defaults);
        }
        return $this->resourcesMap;
    }
}
