<?php
namespace WPChecksum;

/**
 * Class PluginChecker
 * @package WPChecksum
 */
class PluginChecker extends BaseChecker
{

    /**
     * PluginChecker constructor.
     * @param bool $localCache
     */
    public function __construct($localCache = false)
    {
        $this->basePath = WP_PLUGIN_DIR;
        $this->localCache = $localCache;

        parent::__construct();
    }

    /**
     * Check a single plugin
     *
     * @param $id
     * @param $plugin
     * @return array
     */
    public function check($id, $plugin)
    {
        $parts = explode('/', $id);
        $slug = $parts[0];
        $ret = array();
        $ret['type'] = 'plugin';
        $ret['slug'] = $slug;
        $ret['version'] = $plugin['Version'];

        $original = $this->getOriginalChecksums('plugin', $slug, $plugin['Version']);
        if ($original) {
            $local = $this->getLocalChecksums($this->basePath . "/$slug");
            $changeSet = $this->getChangeSet($original, $local);
            $ret['status'] = 'checked';
            $ret['message'] = '';
            $ret['changeset'] = $changeSet;
        } else {
            $ret['status'] =  'unchecked';
            $ret['message'] = 'Plugin original not found';
            $ret['changeset'] = array();
        }

        return $ret;
    }
}