<?php
namespace php\gui\framework;

use Exception;
use php\framework\Logger;
use php\gui\UXApplication;
use php\gui\UXForm;
use php\gui\UXNode;
use php\io\IOException;
use php\io\Stream;
use php\lang\IllegalArgumentException;
use php\lang\Module;
use php\lang\System;
use php\lib\fs;
use php\lib\str;
use php\time\Time;
use php\util\Configuration;

/**
 * Class Application
 * @package php\gui\framework
 *
 * @packages framework
 */
class Application
{
    /** @var Application */
    static protected $instance;

    /** @var string */
    protected $namespace = '';

    /** @var bool */
    protected $launched = false;

    /** @var AbstractForm */
    protected $mainForm = null;

    /** @var string */
    protected $mainFormClass = '';

    /** @var string */
    protected $splashFormClass = '';

    /** @var AbstractForm[] */
    protected $forms = [];

    /** @var AbstractForm */
    protected $splash;

    /**
     * @var Time
     */
    protected $startTime;

    /**
     * @var AbstractFactory[]
     */
    protected $factories = [];

    /** @var AbstractModule[] */
    protected $modules = [];

    /** @var string[] */
    protected $styles = [];

    /**
     * @var null|AbstractModule
     */
    protected $appModule = null;

    /** @var Configuration */
    protected $config;

    /**
     * @var bool
     */
    protected $shutdown;

    /**
     * @param string $configPath
     * @throws Exception
     */
    public function __construct($configPath = null)
    {
        $this->startTime = Time::now();

       // System::setProperty("prism.lcdtext", "false");
        if (Stream::exists('res://.debug/preloader.php')) {
            include 'res://.debug/preloader.php';
        }

        if ($configPath === null) {
            $configPath = 'res://.system/application.conf';
        }

        Logger::info("Application starting ...");

        $functions = "res://php/gui/framework/functions";

        if (Stream::exists($functions . ".phb")) {
            $module = new Module("$functions.phb", true);
            $module->call();
        } else {
            include_once "$functions.php";
        }

        try {
            $this->loadConfig($configPath);
        } catch (IOException $e) {
            throw new Exception("Unable to find the '$configPath' config");
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->config->get('app.name');
    }

    /**
     * @return string
     */
    public function getInstanceId()
    {
        static $id = null;

        if ($id == null) {
            $id = str::uuid();
        }

        return $id;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        if (!$this->config->has('app.uuid')) {
            $this->config->set('app.uuid', str::uuid());
        }

        return $this->config->get('app.uuid');
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->config->get('app.version');
    }

    /**
     * @return int
     */
    public function getVersionHash()
    {
        return (int) $this->config->get('app.hash');
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Time
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Returns user home directory of this app.
     * @return string
     */
    public function getUserHome()
    {
        $name = $this->getName();
        $uuid = $this->getUuid();

        $home = System::getProperty('user.home');

        $result = fs::normalize("$home/.$name/$uuid");

        if (!fs::isDir($result)) {
            if (!fs::makeDir($result)) {
                return null;
            }
        }

        return $result;
    }

    /** @var AbstractForm[] */
    private $formCache = [];
    private $formOriginCache = [];

    /**
     * @param string $name
     */
    public function __cleanCacheForm($name)
    {
        unset($this->formCache[$name]);
        unset($this->formOriginCache[$name]);
    }

    /**
     * @param $name
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function minimizeForm($name)
    {
        if ($form = $this->formCache[$name]) {
            $form->iconified = true;
        }

        return $form;
    }

    /**
     * @param $name
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function restoreForm($name)
    {
        if ($form = $this->formCache[$name]) {
            $form->iconified = false;
        }

        return $form;
    }

    /**
     * @param string $name
     * @param UXForm $origin
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function getForm($name, UXForm $origin = null)
    {
        if ($form = $this->formCache[$name]) {
            return $form;
        }

        return $this->getNewForm($name, $origin, true, true, true);
    }

    /**
     * @param string $name
     * @param UXForm $origin
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function form($name, UXForm $origin = null)
    {
        return $this->getForm($name, $origin);
    }

    /**
     * @param $name
     * @param UXForm|null $origin
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function getOriginForm($name, UXForm $origin = null)
    {
        if ($form = $this->formOriginCache[$name]) {
            return $form;
        }

        return $this->getNewForm($name, $origin, true, true, true);
    }

    /**
     * @param string $name
     * @param UXForm|null $origin
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function originForm($name, UXForm $origin = null)
    {
        return $this->getOriginForm($name, $origin);
    }

    /**
     * @param $name
     * @param UXForm $origin
     * @param bool $loadEvents
     * @param bool $loadBehaviours
     * @param bool $cache
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function getNewForm($name, UXForm $origin = null, $loadEvents = true, $loadBehaviours = true, $cache = false)
    {
        $class = $name;

        if ($this->getNamespace()) {
            $class = $this->getNamespace() . "\\forms\\$name";
        }

        if (!class_exists($class)) {
            Logger::error("Cannot get form '$name', it doesn't exist, class not found");
            return null;
        }

        $form = new $class($origin, $loadEvents, $loadBehaviours);

        if (!$cache) {
            return $form;
        }

        if (!$this->formOriginCache[$name]) {
            $this->formOriginCache[$name] = $form;
        }

        return $this->formCache[$name] = $form;
    }

    /**
     * @param $name
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function showForm($name)
    {
        if ($name instanceof UXForm) {
            $this->getForm($name);
        }

        $form = $this->getForm($name);

        if ($form) {
            $form->show();
        }

        return $form;
    }

    /**
     * @param $name
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function showFormAndWait($name)
    {
        $form = $this->getForm($name);

        if ($form) {
            $form->showAndWait();
        }

        return $form;
    }

    /**
     * @param $name
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function showNewForm($name)
    {
        $form = $this->getNewForm($name);

        if ($form) {
            $form->show();
        }

        return $form;
    }

    /**
     * @param $name
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function showNewFormAndWait($name)
    {
        $form = $this->getNewForm($name);
        $form->show();

        return $form;
    }

    /**
     * @param $name
     * @return AbstractForm
     * @return-dynamic $package\forms\$0
     */
    public function hideForm($name)
    {
        $form = $this->getForm($name);

        if ($form) {
            $form->hide();
        }

        return $form;
    }

    /**
     * @param $prototype
     * @return UXNode
     * @throws IllegalArgumentException
     */
    public function create($prototype)
    {
        list($factory, $id) = str::split($prototype, '.', 2);

        $factory = $this->factory($factory);
        return $factory->create($id);
    }

    /**
     * @param $name
     * @return AbstractFactory
     * @throws IllegalArgumentException
     */
    public function factory($name)
    {
        if ($factory = $this->factories[$name]) {
            return $factory;
        }

        $factoryClass = ($this->getNamespace() ? $this->getNamespace() . "\\factories\\" : "") . $name;

        if (!class_exists($factoryClass)) {
            throw new IllegalArgumentException("Cannot find the '$name' factory, class '$factoryClass' is not exists");
        }

        return $this->factories[$name] = new $factoryClass();
    }

    /**
     * @return null|AbstractModule
     * @return-dynamic $package\modules\AppModule
     */
    public function appModule()
    {
        return $this->appModule;
    }

    /**
     * @return AbstractForm
     */
    public function getMainForm()
    {
        return $this->mainForm;
    }

    public function setMainFormClass($class)
    {
        /*if ($this->getNamespace()) {    TODO Remove It
            $class = $this->getNamespace() . '\\forms\\' . $class;
        }  */
        $this->mainFormClass = $class;
    }

    /**
     * Скрыть заставку.
     */
    public function hideSplash()
    {
        if ($splash = UXApplication::getSplash()) {
            $splash->hide();
        }

        if ($this->splash) {
            $this->splash->hide();
        }
    }

    public function setSplashFormClass($class)
    {
        $this->splashFormClass = $class;
    }

    public function addStyle($resource)
    {
        $this->styles[$resource] = $resource;
    }

    public function getStyles()
    {
        return $this->styles;
    }

    public function removeStyle($resource)
    {
        unset($this->styles[$resource]);
    }

    public function loadModules(array $classes)
    {
        foreach ($classes as $class) {
            /** @var AbstractModule $module */
            if ($class == "{$this->getNamespace()}\\modules\\AppModule") {
                $this->appModule = new $class();
            } else {
                $this->modules[$class] = new $class();
            }
        }
    }

    public function loadConfig($configPath)
    {
        $this->config = new Configuration($configPath);

        $this->namespace = $this->config->get('app.namespace', '');

        if ($this->config->get('app.mainForm')) {
            $this->setMainFormClass($this->config->get('app.mainForm'));
        }

        if ($this->config->get('app.splashForm')) {
            $this->setSplashFormClass($this->config->get('app.splashForm'));
        }

        if ($this->config->has('app.implicitExit')) {
            UXApplication::setImplicitExit($this->config->get('app.implicitExit'));
        }
    }

    /**
     * @param $id
     * @return AbstractModule
     * @return-dynamic $package\modules\$0
     * @throws Exception
     */
    public function module($id)
    {
        $module = $this->modules[$id];

        if (!$module) {
            $module = $this->modules["{$this->namespace}\\modules\\$id"];
        }

        if (!$module) {
            if (class_exists($id)) {
                return new $id();
            }

            $cls = "{$this->namespace}\\modules\\$id";
            if (class_exists($cls)) {
                return new $cls();
            }

            throw new Exception("Unable to find '$id' module");
        }

        return $module;
    }

    public function isLaunched()
    {
        return $this->launched;
    }

    public function launch(callable $handler = null, callable $after = null)
    {
        $mainFormClass = $this->mainFormClass;
        $splashFormClass = $this->splashFormClass;
        $showMainForm  = $this->config->getBoolean('app.showMainForm') && $mainFormClass;

        /*if (!class_exists($mainFormClass)) {    TODO Remove it
            throw new Exception("Unable to start the application without the main form class or the class '$mainFormClass' not found");
        }*/

        $onStart = function () use ($mainFormClass, $splashFormClass, $showMainForm, $handler, $after) {
            static::$instance = $this;

            if ($handler) {
                $handler();
            }

            if ($this->appModule) {
                $this->appModule->apply($this);
            }

            foreach ($this->modules as $module) {
                $module->apply($this);
            }

            $this->launched = true;

            $startMain = function () use ($mainFormClass, $showMainForm, $after) {
                $this->mainForm = $mainFormClass ? $this->getForm($mainFormClass) : null;

                if ($showMainForm && $this->mainForm) {
                    $this->mainForm->show();
                }

                if ($after) {
                    $after();
                }

                if (Stream::exists('res://.debug/bootstrap.php')) {
                    include 'res://.debug/bootstrap.php';
                }

                Logger::debug("Application start is done.");

                if ($oldSplash = UXApplication::getSplash()) {
                    if ($this->getConfig()->getBoolean('app.fx.splash.autoHide')) {
                        $oldSplash->hide();
                    }
                }
            };

            if ($splashFormClass) {
                $this->splash = $this->getForm($splashFormClass);

                if ($this->splash) {
                    Logger::info("Show splash screen ($splashFormClass)");

                    /** @var AbstractForm $form */
                    $form = $this->splash;
                    $form->alwaysOnTop = true;

                    $form->show();
                    $form->toFront();

                    if ($oldSplash = UXApplication::getSplash()) {
                        $oldSplash->hide();
                    }

                    uiLater(function () use ($form, $startMain) {
                        waitAsync(1000, $startMain);
                    });
                    return;
                }
            }

            $startMain();
        };

        UXApplication::launch($onStart);
    }

    /**
     * Exit from application.
     */
    public function shutdown()
    {
        $this->shutdown = true;

        Logger::info("Application shutdown");

        UXApplication::shutdown();
    }

    public function isShutdown()
    {
        return $this->shutdown;
    }

    /**
     * @return bool
     */
    public static function isCreated()
    {
        return !!static::$instance;
    }

    /**
     * @return Application
     * @throws Exception
     */
    public static function get()
    {
        if (!static::$instance) {
            throw new Exception("The application is not created and launched");
        }

        return static::$instance;
    }
}