<?php
namespace WPChecksum;

/**
 * Class WPRepository
 * @package WPChecksum
 */
class WPRepository
{
    /**
     * The URI for the WordPress plugin API
     */
    const WPAPI_PLUGIN_URL = "https://api.wordpress.org/plugins/info/1.0/";

    /**
     * @var mixed
     */
    private $result;

    /**
     * WPRepository constructor.
     *
     * @param $slug
     * @param $type
     */
    public function __construct($slug, $type)
    {
		$apiArgs = (object)array(
		    'slug' => $slug,
			'banners' => false,
			'reviews' => false,
			'downloaded' => false,
			'active_installs' => true,
			'locale' => 'en_US',
			'per_page' => 24,
		);    
    
        $args = array(
            'action' => $type == 'plugin'?'plugin_information':'theme_information',
            'request' => serialize($apiArgs),
        );
        $response = wp_remote_post(self::WPAPI_PLUGIN_URL, $args);
        $this->result = unserialize($response['body']);
    }

    /**
     * Magic get method
     *
     * @param $name
     * @return string|null
     */
    public function __get($name) {
        if (isset($this->result->$name)) {
            return $this->result->$name;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function found()
    {
        return $this->result != null;
    }
    
}
