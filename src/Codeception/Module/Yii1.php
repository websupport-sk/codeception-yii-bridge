<?php
namespace Websupport\CodeceptionYiiBridge\Codeception\Module;

use Codeception\Lib\Framework;
use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Interfaces\PartedModule;
use Codeception\TestInterface;
use Websupport\CodeceptionYiiBridge\Codeception\Lib\Connector\Yii1 as Yii1Connector;
use Codeception\Util\ReflectionHelper;
use Yii;

class Yii1 extends Framework implements PartedModule
{

    /**
     * Application path and url must be set always
     * @var array
     */
    protected $requiredFields = ['appPath', 'url'];

    /**
     * Application settings array('class'=>'YourAppClass','config'=>'YourAppArrayConfig');
     * @var array
     */
    private $appSettings;

    private $_appConfig;

    public function _initialize()
    {
        if (!file_exists($this->config['appPath'])) {
            throw new ModuleConfigException(
                __CLASS__,
                "Couldn't load application config file {$this->config['appPath']}\n" .
                "Please provide application bootstrap file configured for testing"
            );
        }

        $this->appSettings = include($this->config['appPath']); //get application settings in the entry script

        // get configuration from array or file
        if (is_array($this->appSettings['config'])) {
            $this->_appConfig = $this->appSettings['config'];
        } else {
            if (!file_exists($this->appSettings['config'])) {
                throw new ModuleConfigException(
                    __CLASS__,
                    "Couldn't load configuration file from Yii app file: {$this->appSettings['config']}\n" .
                    "Please provide valid 'config' parameter"
                );
            }
            $this->_appConfig = include($this->appSettings['config']);
        }

        if (!defined('YII_ENABLE_EXCEPTION_HANDLER')) {
            define('YII_ENABLE_EXCEPTION_HANDLER', false);
        }
        if (!defined('YII_ENABLE_ERROR_HANDLER')) {
            define('YII_ENABLE_ERROR_HANDLER', false);
        }

        $_SERVER['SCRIPT_NAME'] = parse_url($this->config['url'], PHP_URL_PATH);
        $_SERVER['SCRIPT_FILENAME'] = $this->config['appPath'];

        Yii::$enableIncludePath = false;
        Yii::setApplication(null);
        Yii::createApplication($this->appSettings['class'], $this->_appConfig);
    }

    /*
     * Create the client connector. Called before each test
     */
    public function _createClient()
    {
        $this->client = new Yii1Connector();
        $this->client->setServerParameter("HTTP_HOST", parse_url($this->config['url'], PHP_URL_HOST));
        $this->client->setServerParameter("REMOTE_ADDR", '127.0.0.1');
        $this->client->appPath = $this->config['appPath'];
        $this->client->url = $this->config['url'];
        $this->client->appSettings = [
            'class'  => $this->appSettings['class'],
            'config' => $this->_appConfig,
        ];
    }

    public function _before(TestInterface $test)
    {
        $this->_createClient();
    }

    public function _after(TestInterface $test)
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];
        Yii::app()->session->close();
        parent::_after($test);
    }

    /**
     * Getting domain regex from rule template and parameters
     *
     * @param string $template
     * @param array $parameters
     * @return string
     */
    private function getDomainRegex($template, $parameters = [])
    {
        if ($host = parse_url($template, PHP_URL_HOST)) {
            $template = $host;
        }
        if (strpos($template, '<') !== false) {
            $template = str_replace(['<', '>'], '#', $template);
        }
        $template = preg_quote($template);
        foreach ($parameters as $name => $value) {
            $template = str_replace("#$name#", $value, $template);
        }
        return '/^' . $template . '$/u';
    }


    /**
     * Returns a list of regex patterns for recognized domain names
     *
     * @return array
     */
    public function getInternalDomains()
    {
        $domains = [$this->getDomainRegex(Yii::app()->request->getHostInfo())];
        if (Yii::app()->urlManager->urlFormat === 'path') {
            $parent = Yii::app()->urlManager instanceof \CUrlManager ? '\CUrlManager' : null;
            $rules = ReflectionHelper::readPrivateProperty(Yii::app()->urlManager, '_rules', $parent);
            foreach ($rules as $rule) {
                if ($rule->hasHostInfo === true) {
                    $domains[] = $this->getDomainRegex($rule->template, $rule->params);
                }
            }
        }
        return array_unique($domains);
    }

    public function _parts()
    {
        return ['init', 'initialize'];
    }
}
