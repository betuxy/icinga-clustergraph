<?php


namespace Icinga\Module\Clustergraph\Controllers;

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Module\Clustergraph\Common\ApiZones;
use Icinga\Module\Clustergraph\Common\IcingaDbZones;
use Icinga\Module\Clustergraph\Common\IdoZones;
use Icinga\Web\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        $config = \Icinga\Application\Config::module('clustergraph');

        $ICINGA_API_ENDPOINT = $config->get('api', 'host');
        $ICINGA_API_USER = $config->get('api', 'username');
        $ICINGA_API_PASSWORD = $config->get('api', 'password');


        if (!$ICINGA_API_ENDPOINT || !$ICINGA_API_USER || !$ICINGA_API_PASSWORD) {
            throw new \Exception("Please configure the module in module settings.");
        }

        $this->view->tabs = $this->getTabs();

    }

    public function testAction()
    {
        echo "Test Successful";
        exit;
    }


    public function dataAction()
    {
        // Configuration

        $config = \Icinga\Application\Config::module('clustergraph');
        $ICINGA_API_ENDPOINT = $config->get('api', 'host');
        $ICINGA_API_USER = $config->get('api', 'username');
        $ICINGA_API_PASSWORD = $config->get('api', 'password');


        if (!$ICINGA_API_ENDPOINT || !$ICINGA_API_USER || !$ICINGA_API_PASSWORD) {
            throw new \Exception("Please configure the module in module settings.");
        }


        $api = new ApiZones();
        $zones = $api->sendCommand();

        if (!is_array($zones)) {
            throw new \Exception("Failed to fetch or decode data from the Icinga2 API.");
        }

        $nodes = [];
        foreach ($zones as $zone) {
            $zoneName = $zone['name'];
            $nodes[$zoneName] = [
                'name' => $zoneName,
                'children' => [],
                'endpoints' => $zone['attrs']['endpoints'] ?? []
            ];
        }

        foreach ($zones as $zone) {
            $zoneName = $zone['name'];
            $parentName = $zone['attrs']['parent'];
            if ($parentName && isset($nodes[$parentName])) {
                $nodes[$parentName]['children'][] = &$nodes[$zoneName];
            }
        }

        // Find root node
        $rootZoneName = '';
        foreach ($zones as $zone) {
            if (!$zone['attrs']['parent'] && !$zone['attrs']['global']) {
                $rootZoneName = $zone['name'];
                break;
            }
        }

        $tree = $nodes[$rootZoneName];

        $this->_helper->json($tree);
    }


}

