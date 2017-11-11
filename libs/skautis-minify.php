<?php
//------- WebServiceInterface.php ------
namespace Skautis\Wsdl;

use Skautis\EventDispatcher\EventDispatcherInterface;

interface WebServiceInterface extends EventDispatcherInterface
{
    /**
     * Zavola funkci na Skautisu
     *
     * @param string $functionName Jmeno funkce volane na skautisu
     * @param array $argumentsArgumenty funkce volane na skautisu
     *
     * @return mixed
     */
    public function call($functionName, array $arguments = []);

    /**
     * Zavola funkci na Skautisu
     *
     * @param string $functionName Jmeno funkce volane na skautisu
     * @param array $argumentsArgumenty funkce volane na skautisu
     *
     * @return mixed
     */
    public function __call($functionName, $arguments);
}

//------- WsdlException.php ------
namespace Skautis\Wsdl;

use Skautis;

/**
 * Obecná chyba při komunikaci s webovými službami.
 *
 * @author Hána František <sinacek@gmail.com>
 */
class WsdlException extends \Exception implements Skautis\Exception
{
}

//------- Config.php ------
namespace Skautis;
/**
 * Třída pro uživatelské nastavení
 */
class Config
{
    const CACHE_ENABLED = TRUE;
    const CACHE_DISABLED = FALSE;
    const TESTMODE_ENABLED = TRUE;
    const TESTMODE_DISABLED = FALSE;
    const COMPRESSION_ENABLED = TRUE;
    const COMPRESSION_DISABLED = FALSE;
    const URL_TEST = "https://test-is.skaut.cz/";
    const URL_PRODUCTION = "https://is.skaut.cz/";

    /**
     * @var string
     */
    private $appId;

    /**
     * Používat testovací SkautIS?
     *
     * @var bool
     */
    private $testMode;

    /**
     * Používat kompresi?
     *
     * @var bool
     */
    private $compression;
    /**
     * Cachovat WSDL?
     *
     * @var bool
     */
    protected $cache;

    /**
     * @param string $appId Id aplikace od správce skautISu
     * @param bool $isTestMode používat testovací SkautIS?
     * @param bool $cache použít kompresi?
     * @param bool $compression cachovat WDSL?
     * @throws InvalidArgumentException
     */
    public function __construct($appId, $isTestMode = FALSE, $cache = TRUE, $compression = TRUE)
    {
        if (empty($appId)) {
            throw new InvalidArgumentException("AppId cannot be empty.");
        }
        $this->appId = $appId;
        $this->setTestMode($isTestMode);
        $this->setCache($cache);
        $this->setCompression($compression);
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->testMode;
    }

    /**
     * @param bool $isTestMode
     * @return self
     */
    public function setTestMode($isTestMode = TRUE)
    {
        $this->testMode = (bool)$isTestMode;
        return $this;
    }

    /**
     * Zjistí, jestli je WSDL cachované
     *
     * @return bool
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Vypne/zapne cachovaní WSDL
     *
     * @param bool $enabled
     * @return self
     */
    public function setCache($enabled)
    {
        $this->cache = (bool)$enabled;
        return $this;
    }

    /**
     * Zjistí, jestli se používá komprese dotazů na WSDL
     *
     * @return bool
     */
    public function getCompression()
    {
        return $this->compression;
    }

    /**
     * Vypne/zapne kompresi dotazů na WSDL
     *
     * @param $enabled
     * @return self
     */
    public function setCompression($enabled)
    {
        $this->compression = (bool)$enabled;
        return $this;
    }

    /**
     * Vací začátek URL adresy
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->testMode ? self::URL_TEST : self::URL_PRODUCTION;
    }

    /**
     * Na základě nastavení vrací argumenty pro SoapClient
     *
     * @see \SoapClient
     *
     * @return array
     */
    public function getSoapOptions()
    {
        $soapOptions = [
            'ID_Application' => $this->appId,
            'soap_version' => SOAP_1_2,
            'encoding' => 'utf-8',
        ];
        if ($this->compression) {
            $soapOptions['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
        }
        $soapOptions['cache_wsdl'] = $this->cache ? WSDL_CACHE_BOTH : WSDL_CACHE_NONE;
        return $soapOptions;
    }
}

//------- EventDispatcherInterface.php ------
namespace Skautis\EventDispatcher;
interface EventDispatcherInterface
{
    /**
     * Přidá listener na událost.
     *
     * @param string $eventName
     * @param callable $callback
     */
    public function subscribe($eventName, callable $callback);
}

//------- EventDispatcherTrait.php ------
namespace Skautis\EventDispatcher;
trait EventDispatcherTrait
{
    /** @var callable[] */
    private $listeners = [];

    /**
     * @param string|null $eventName
     * @return bool
     */
    protected function hasListeners($eventName = NULL)
    {
        return $eventName === NULL ? !empty($this->listeners) : !empty($this->listeners[$eventName]);
    }

    /**
     * @param string $eventName
     * @param mixed $data
     */
    protected function dispatch($eventName, $data)
    {
        if (!$this->hasListeners($eventName)) {
            return;
        }
        foreach ($this->listeners[$eventName] as $callback) {
            call_user_func($callback, $data);
        }
    }

    /**
     * @param string $eventName
     * @param callable $callback
     */
    public function subscribe($eventName, callable $callback)
    {
        $this->listeners[$eventName][] = $callback;
    }
}

//------- Exception.php ------
namespace Skautis;
/**
 * @author Petr Morávek <petr@pada.cz>
 */
interface Exception
{
}

//------- Helpers.php ------
namespace Skautis;
/**
 * @author Petr Morávek <petr@pada.cz>
 */
final class Helpers
{
    /**
     * @throws StaticClassException
     */
    final public function __construct()
    {
        throw new StaticClassException;
    }

    /**
     * Parsuje pole dat zaslaných skautISem (například $_SESSION)
     *
     * @param array $data
     * @return array
     * @throws UnexpectedValueException pokud se nepodaří naparsovat datum
     */
    public static function parseLoginData(array $data)
    {
        $loginData = [];
        $loginData[User::ID_LOGIN] = isset($data['skautIS_Token']) ? $data['skautIS_Token'] : NULL;
        $loginData[User::ID_ROLE] = isset($data['skautIS_IDRole']) ? (int)$data['skautIS_IDRole'] : NULL;
        $loginData[User::ID_UNIT] = isset($data['skautIS_IDUnit']) ? (int)$data['skautIS_IDUnit'] : NULL;
        if (isset($data['skautIS_DateLogout'])) {
            $tz = new \DateTimeZone('Europe/Prague');
            $logoutDate = \DateTime::createFromFormat('j. n. Y H:i:s', $data['skautIS_DateLogout'], $tz);
            if ($logoutDate === FALSE) {
                throw new UnexpectedValueException("Could not parse logout date '{$data['skautIS_DateLogout']}'.");
            }
            $loginData[User::LOGOUT_DATE] = $logoutDate;
        } else {
            $loginData[User::LOGOUT_DATE] = NULL;
        }
        return $loginData;
    }
}

//------- HelperTrait.php ------
namespace Skautis;

use Skautis\SessionAdapter\SessionAdapter;
use Skautis\Wsdl\WebServiceFactory;

trait HelperTrait
{
    /**
     * sigleton
     * @var Skautis[]
     */
    private static $instances = [];

    /**
     * Ziska sdilenou instanci Skautis objektu
     *
     * Ano vime ze to neni officialni pattern
     * Jedna se o kockopsa Mezi singletonem a StaticFactory
     * Factory metoda na stride kterou instantizuje a novy objekt vytvari jen 1x za beh
     * Proc to tak je? Ohled na zpetnou kompatibilitu a out of the box pouzitelnost pro amatery
     *
     * @var string $appId nastavení appId (nepovinné)
     * @var bool $testMode funguje v testovacím provozu? - výchozí je testovací mode (nepovinné)
     *
     * @return Skautis Sdilena instance Skautis knihovny pro cely beh PHP skriptu
     */
    public static function getInstance($appId, $testMode = FALSE, $cache = FALSE, $compression = FALSE)
    {
        if (!isset(self::$instances[$appId])) {
            $config = new Config($appId);
            $config->setTestMode($testMode);
            $config->setCache($cache);
            $config->setCompression($compression);
            $webServiceFactory = new WebServiceFactory();
            $wsdlManager = new WsdlManager($webServiceFactory, $config);// Out of box integrace s $_SESSION
            $sessionAdapter = new SessionAdapter();
            $user = new User($wsdlManager, $sessionAdapter);
            self::$instances[$appId] = new self($wsdlManager, $user);
        }
        return self::$instances[$appId];
    }
}

//------- InvalidArgumentException.php ------
namespace Skautis;
/**
 * @author Hána František <sinacek@gmail.com>
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}

//------- AdapterInterface.php ------
namespace Skautis\SessionAdapter;
/**
 * Interface umoznujici vytvoreni adapteru pro ruzne implementace Session
 */
interface AdapterInterface
{
    /**
     * Ulozi data do session
     *
     * @return void
     */
    public function set($name, $object);

    /**
     * Overi existenci dat v session
     *
     * @return bool
     */
    public function has($name);

    /**
     * Ziska data ze session
     *
     * @return mixed
     */
    public function get($name);
}

//------- FakeAdapter.php ------
namespace Skautis\SessionAdapter;
/**
 * Nepersestinenti adapter - vhodne jako stub pro testy nebo kdyz neni potreba ukladat
 */
class FakeAdapter implements AdapterInterface
{
    /**
     * Inmemory storage
     *
     * @var array
     */
    protected $data = [];

    /**
     * @inheritdoc
     */
    public function set($name, $object)
    {
        $this->data[$name] = $object;
    }

    /**
     * @inheritdoc
     */
    public function has($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @inheritdoc
     */
    public function get($name)
    {
        return $this->data[$name];
    }
}

//------- SessionAdapter.php ------
namespace Skautis\SessionAdapter;
/**
 * Adapter pro pouziti $_SESSION ve SkautISu
 */
class SessionAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $session;

    public function __construct()
    {
        if (!isset($_SESSION["__" . __CLASS__])) {
            $_SESSION["__" . __CLASS__] = [];
        }
        $this->session = &$_SESSION["__" . __CLASS__];
    }

    /**
     * @inheritdoc
     */
    public function set($name, $object)
    {
        $this->session[$name] = $object;
    }

    /**
     * @inheritdoc
     */
    public function has($name)
    {
        return isset($this->session[$name]);
    }

    /**
     * @inheritdoc
     */
    public function get($name)
    {
        return $this->session[$name];
    }
}

//------- Skautis.php ------
namespace Skautis;

use Skautis\Wsdl\WebService;
use Skautis\Wsdl\WsdlManager;

/**
 * Třída pro práci se skautISem
 *
 * Sdružuje všechny komponenty a zprostředkovává jejich komunikaci.
 *
 * @author Hána František <sinacek@gmail.com>
 */
class Skautis
{
    use HelperTrait;
    /**
     * @var WsdlManager
     */
    private $wsdlManager;
    /**
     * @var User
     */
    private $user;
    /**
     * @var SkautisQuery[]
     */
    private $log;

    /**
     * @param WsdlManager $wsdlManager
     * @param User $user
     */
    public function __construct(WsdlManager $wsdlManager, User $user)
    {
        $this->wsdlManager = $wsdlManager;
        $this->user = $user;
    }

    /**
     * @return WsdlManager
     */
    public function getWsdlManager()
    {
        return $this->wsdlManager;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->wsdlManager->getConfig();
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Získá objekt webové služby
     *
     * @param string $name
     * @return WebService|mixed
     */
    public function getWebService($name)
    {
        return $this->wsdlManager->getWebService($name, $this->user->getLoginId());
    }

    /**
     * Trocha magie pro snadnější přístup k webovým službám.
     *
     * @param string $name
     * @return WebServiceInterface|mixed
     */
    public function __get($name)
    {
        return $this->getWebService($name);
    }

    /**
     * Vrací URL na přihlášení
     *
     * @param string|null $backlink
     * @return string
     */
    public function getLoginUrl($backlink = NULL)
    {
        $query = [];
        $query['appid'] = $this->getConfig()->getAppId();
        if (!empty($backlink)) {
            $query['ReturnUrl'] = $backlink;
        }
        return $this->getConfig()->getBaseUrl() . "Login/?" . http_build_query($query, '', '&');
    }

    /**
     * Vrací URL na odhlášení
     *
     * @return string
     */
    public function getLogoutUrl()
    {
        $query = [];
        $query['appid'] = $this->getConfig()->getAppId();
        $query['token'] = $this->user->getLoginId();
        return $this->getConfig()->getBaseUrl() . "Login/LogOut.aspx?" . http_build_query($query, '', '&');
    }

    /**
     * Vrací URL k registraci
     *
     * @param string|null $backlink
     * @return string
     */
    public function getRegisterUrl($backlink = NULL)
    {
        $query = [];
        $query['appid'] = $this->getConfig()->getAppId();
        if (!empty($backlink)) {
            $query['ReturnUrl'] = $backlink;
        }
        return $this->getConfig()->getBaseUrl() . "Login/Registration.aspx?" . http_build_query($query, '', '&');
    }

    /**
     * Hromadné nastavení po přihlášení
     *
     * @param array $data
     */
    public function setLoginData(array $data)
    {
        $data = Helpers::parseLoginData($data);
        $this->getUser()->setLoginData($data[User::ID_LOGIN], $data[User::ID_ROLE], $data[User::ID_UNIT], $data[User::LOGOUT_DATE]);
    }

    /**
     * Ověřuje, zda je skautIS odstaven pro údržbu
     *
     * @return boolean
     */
    public function isMaintenance()
    {
        return $this->wsdlManager->isMaintenance();
    }

    /**
     * Zapne logování všech SOAP callů
     */
    public function enableDebugLog()
    {
        if ($this->log !== NULL) {
// Debug log byl již zapnut dříve.
            return;
        }
        $this->log = [];
        $logger = function (SkautisQuery $query) {
            $this->log[] = $query;
        };
        $this->wsdlManager->addWebServiceListener(WebService::EVENT_SUCCESS, $logger);
        $this->wsdlManager->addWebServiceListener(WebService::EVENT_FAILURE, $logger);
    }

    /**
     * Vrací zalogované SOAP cally
     *
     * @return SkautisQuery[]
     */
    public function getDebugLog()
    {
        return ($this->log !== NULL) ? $this->log : [];
    }
}

//------- SkautisQuery.php ------
namespace Skautis;
/**
 * Trida slouzici pro debugovani SOAP pozadvku na servery Skautisu
 */
class SkautisQuery implements \Serializable
{
    /**
     * @var string Nazev funkce volane pomoci SOAP requestu
     */
    public $fname;
    /**
     * @var array Parametry SOAP requestu na server
     */
    public $args;
    /**
     * @var array Zasobnik volanych funkci
     */
    public $trace;
    /**
     * @var int Doba trvani pozadvku
     */
    public $time;
    public $result;
    /**
     * V pripade ze SOAP pozadavek selze
     *
     * Nelze povolit uzivateli primy pristup kvuli serializaci. Ne vsechny exceptions jdou serializovat.
     *
     * @var \Exception|null
     */
    protected $exception = NULL;
    /**
     * Po unserializaci Query s exception je zde jeji trida
     *
     * @var string
     */
    protected $exceptionClass = "";
    /**
     * Po unserializaci je zde text exxception
     *
     * Pouziva __toString() methodu
     *
     * @var string
     */
    protected $exceptionString = "";

    /**
     *
     *
     * @param string $fname Nazev volane funkce
     * @param array $argsArgumenty pozadavku
     * @param string $trace Zasobnik volanych funkci
     */
    public function __construct($fname, array $args = [], array $trace = [])
    {
        $this->fname = $fname;
        $this->args = $args;
        $this->trace = $trace;
        $this->time = -microtime(TRUE);
    }

    public function serialize()
    {
        $data = [
            'fname' => $this->fname,
            'args' => $this->args,
            'trace' => $this->trace,
            'time' => $this->time,
            'result' => $this->result,
            'exception_class' => is_null($this->exception) ? "" : get_class($this->exception),
            'exception_string' => is_null($this->exception) ? "" : (string)$this->exception,
        ];
        return serialize($data);
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->fname = $data['fname'];
        $this->args = $data['args'];
        $this->trace = $data['trace'];
        $this->time = $data['time'];
        $this->result = $data['result'];
        $this->exceptionClass = $data['exception_class'];
        $this->exceptionString = $data['exception_string'];
    }

    /**
     * Oznac pozadavek za dokonceny a uloz vysledek
     *
     * @param mixed $result Odpoved ze serveru
     * @param \Exception Výjimka v pripade problemu
     */
    public function done($result = NULL, \Exception $e = NULL)
    {
        $this->time += microtime(TRUE);
        $this->result = $result;
        $this->exception = $e;
        return $this;
    }

    /**
     * Vrati tridu exception
     *
     * Pouziva se tato metoda protoze SoapFault exception vyhozena SoapClientem nejde serializovat
     *
     * @return string
     */
    public function getExceptionClass()
    {
        if ($this->exception === NULL) {
            return $this->exceptionClass;
        }
        return get_class($this->exception);
    }

    /**
     * Vrati textovou podobu exception
     *
     * @return string
     */
    public function getExceptionString()
    {
        if ($this->exception === NULL) {
            return $this->exceptionString;
        }
        return (string)$this->exception;
    }

    /**
     * Kontrola jestli se pozadavek zdaril
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->exception !== NULL || strlen($this->exceptionClass) > 0;
    }
}

//------- StaticClassException.php ------
namespace Skautis;
/**
 * Vyhozena v případě pokusu o vytvoření instance statické třídy.
 *
 * @author Petr Morávek <petr@pada.cz>
 */
class StaticClassException extends \LogicException implements Exception
{
}

//------- UnexpectedValueException.php ------
namespace Skautis;
/**
 * @author Petr Morávek <petr@pada.cz>
 */
class UnexpectedValueException extends \UnexpectedValueException implements Exception
{
}

//------- User.php ------
namespace Skautis;

use Skautis\SessionAdapter\AdapterInterface;
use Skautis\Wsdl\WsdlManager;

/**
 * @author Petr Morávek <petr@pada.cz>
 */
class User
{
    const ID_LOGIN = "ID_Login";
    const ID_ROLE = "ID_Role";
    const ID_UNIT = "ID_Unit";
    const LOGOUT_DATE = "LOGOUT_Date";
    const AUTH_CONFIRMED = "AUTH_Confirmed";
    const SESSION_ID = "skautis_user_data";
    /**
     * @var WsdlManager
     */
    private $wsdlManager;
    /**
     * @var AdapterInterface
     */
    private $session;
    /**
     * Informace o přihlášení uživatele
     *
     * @var array
     */
    protected $loginData = [];

    /**
     * @param WsdlManager $wsdlManager
     * @param AdapterInterface|null $session
     */
    public function __construct(WsdlManager $wsdlManager, AdapterInterface $session = NULL)
    {
        $this->wsdlManager = $wsdlManager;
        $this->session = $session;
        if ($session !== NULL && $session->has(self::SESSION_ID)) {
            $this->loginData = (array)$session->get(self::SESSION_ID);
        }
    }

    /**
     * @return string|null
     */
    public function getLoginId()
    {
        return isset($this->loginData[self::ID_LOGIN]) ? $this->loginData[self::ID_LOGIN] : NULL;
    }

    /**
     * @return int|null
     */
    public function getRoleId()
    {
        return isset($this->loginData[self::ID_ROLE]) ? $this->loginData[self::ID_ROLE] : NULL;
    }

    /**
     * @return int|null
     */
    public function getUnitId()
    {
        return isset($this->loginData[self::ID_UNIT]) ? $this->loginData[self::ID_UNIT] : NULL;
    }

    /**
     * Vrací datum a čas automatického odhlášení ze skautISu
     *
     * @return \DateTime
     */
    public function getLogoutDate()
    {
        return isset($this->loginData[self::LOGOUT_DATE]) ? $this->loginData[self::LOGOUT_DATE] : NULL;
    }

    /**
     * Hromadné nastavení po přihlášení
     *
     * @param string|null $loginId
     * @param int|null $roleId
     * @param int|null $unitId
     * @param \DateTime|null $logoutDate
     * @return self
     */
    public function setLoginData($loginId = NULL, $roleId = NULL, $unitId = NULL, \DateTime $logoutDate = NULL)
    {
        $this->loginData = [];
        return $this->updateLoginData($loginId, $roleId, $unitId, $logoutDate);
    }

    /**
     * Hromadná změna údajů, bez vymazání stávajících
     *
     * @param string|null $loginId
     * @param int|null $roleId
     * @param int|null $unitId
     * @param \DateTime|null $logoutDate
     * @return self
     */
    public function updateLoginData($loginId = NULL, $roleId = NULL, $unitId = NULL, \DateTime $logoutDate = NULL)
    {
        if ($loginId !== NULL) {
            $this->loginData[self::ID_LOGIN] = $loginId;
        }
        if ($roleId !== NULL) {
            $this->loginData[self::ID_ROLE] = (int)$roleId;
        }
        if ($unitId !== NULL) {
            $this->loginData[self::ID_UNIT] = (int)$unitId;
        }
        if ($logoutDate !== NULL) {
            $this->loginData[self::LOGOUT_DATE] = $logoutDate;
        }
        $this->saveToSession();
        return $this;
    }

    /**
     * Hromadný reset dat po odhlášení
     *
     * @return self
     */
    public function resetLoginData()
    {
        return $this->setLoginData();
    }

    /**
     * Kontoluje, jestli je přihlášení platné.
     * Pro správné fungování je nezbytně nutné, aby byl na serveru nastaven správný čas.
     *
     * @param bool $hardCheck vynutí kontrolu přihlášení na serveru
     * @return bool
     */
    public function isLoggedIn($hardCheck = FALSE)
    {
        if (empty($this->loginData[self::ID_LOGIN])) {
            return FALSE;
        }
        if ($hardCheck || !$this->isAuthConfirmed()) {
            $this->confirmAuth();
        }
        return $this->isAuthConfirmed() && $this->getLogoutDate()->getTimestamp() > time();
    }

    /**
     * Bylo potvrzeno přihlášení dotazem na skautIS?
     *
     * @return bool
     */
    protected function isAuthConfirmed()
    {
        return !empty($this->loginData[self::AUTH_CONFIRMED]);
    }

    /**
     * @param bool $isConfirmed
     */
    protected function setAuthConfirmed($isConfirmed)
    {
        $this->loginData[self::AUTH_CONFIRMED] = (bool)$isConfirmed;
        $this->saveToSession();
    }

    /**
     * Potvrdí (a prodlouží) přihlášení dotazem na skautIS.
     */
    protected function confirmAuth()
    {
        try {
            $this->updateLogoutTime();
            $this->setAuthConfirmed(TRUE);
        } catch (\Exception $e) {
            $this->setAuthConfirmed(FALSE);
        }
    }

    /**
     * Prodloužení přihlášení o 30 min
     *
     * @return self
     * @throws UnexpectedValueException pokud se nepodaří naparsovat datum
     */
    public function updateLogoutTime()
    {
        $loginId = $this->getLoginId();
        if ($loginId === NULL) {
// Nemáme token, uživatel není přihlášen a není, co prodlužovat
            return $this;
        }
        $result = $this->wsdlManager->getWebService('UserManagement', $loginId)->LoginUpdateRefresh(["ID" => $loginId]);
        $logoutDate = preg_replace('/\.(\d*)$/', '', $result->DateLogout); //skautIS vrací sekundy včetně desetinné části
        $tz = new \DateTimeZone('Europe/Prague');
        $logoutDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $logoutDate, $tz);
        if ($logoutDate === FALSE) {
            throw new UnexpectedValueException("Could not parse logout date '{$result->DateLogout}'.");
        }
        $this->loginData[self::LOGOUT_DATE] = $logoutDate;
        $this->saveToSession();
        return $this;
    }

    /**
     * Uloží nastavení do session
     *
     * @return void
     */
    protected function saveToSession()
    {
        if ($this->session !== NULL) {
            $this->session->set(self::SESSION_ID, $this->loginData);
        }
    }
}

//------- AuthenticationException.php ------
namespace Skautis\Wsdl;
/**
 * Neplatné přihlášení spojené se skautISem.
 *
 * @author Hána František <sinacek@gmail.com>
 */
class AuthenticationException extends WsdlException
{
}

//------- AbstractDecorator.php ------
namespace Skautis\Wsdl\Decorator;

use Skautis\Wsdl\WebServiceInterface;

abstract class AbstractDecorator implements WebServiceInterface
{
    /**
     * @WebServiceInterface
     */
    protected $webService;

    /**
     * @inheritdoc
     */
    abstract public function call($functionName, array $arguments = []);

    /**
     * @inheritdoc
     */
    public function __call($functionName, $arguments)
    {
        return $this->call($functionName, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function subscribe($eventName, callable $callback)
    {
        $this->webService->subscribe($eventName, $callback);
    }
}

//------- ArrayCache.php ------
namespace Skautis\Wsdl\Decorator\Cache;
/**
 * Cache v ramci jednoho requestu
 */
class ArrayCache implements CacheInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return NULL;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}

//------- CacheDecorator.php ------
namespace Skautis\Wsdl\Decorator\Cache;

use Skautis\User;
use Skautis\Wsdl\Decorator\AbstractDecorator;
use Skautis\Wsdl\WebServiceInterface;

class CacheDecorator extends AbstractDecorator
{
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var array
     */
    protected static $checkedLoginIds = array();

    /**
     * @param WebServiceInterface $webService
     * @param CacheInterface $cache
     */
    public function __construct(WebServiceInterface $webService, CacheInterface $cache)
    {
        $this->webService = $webService;
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function call($functionName, array $arguments = [])
    {
        $callHash = $this->hashCall($functionName, $arguments);// Pozaduj alespon 1 supesny request na server (zadna Exception)
        if (isset($arguments[User::ID_LOGIN]) && !in_array($arguments[User::ID_LOGIN], static::$checkedLoginIds)) {
            $response = $this->webService->call($functionName, $arguments);
            $this->cache->set($callHash, $response);
            static::$checkedLoginIds[] = $arguments[User::ID_LOGIN];
            return $response;
        }
        $cachedResponse = $this->cache->get($callHash);
        if ($cachedResponse !== NULL) {
            return $cachedResponse;
        }
        $response = $this->webService->call($functionName, $arguments);
        $this->cache->set($callHash, $response);
        return $response;
    }

    /**
     * @var string $functionName
     * @var array $arguments
     */
    protected function hashCall($functionName, array $arguments)
    {
        return $functionName . '?' . http_build_query($arguments);
    }
}

//------- CacheInterface.php ------
namespace Skautis\Wsdl\Decorator\Cache;
interface CacheInterface
{
    /**
     * Ziska data z cache
     *
     * @var string $key
     *
     * @return mixed|null Cachovana hodnota nebo null pokud pro klic neni zadna cache
     */
    public function get($key);

    /**
     * Ulozi data do cache
     *
     * @var string $key
     * @var mixed $value Serializovatelna data
     *
     * @return void
     */
    public function set($key, $value);
}

//------- PermissionException.php ------
namespace Skautis\Wsdl;
/**
 * Nepovolený přístup ze strany SkautISu.
 *
 * @author Hána František <sinacek@gmail.com>
 */
class PermissionException extends WsdlException
{
}

//------- WebService.php ------
namespace Skautis\Wsdl;

use Skautis\EventDispatcher\EventDispatcherTrait;
use Skautis\InvalidArgumentException;
use Skautis\SkautisQuery;
use SoapClient;
use SoapFault;
use stdClass;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class WebService implements WebServiceInterface
{
    use EventDispatcherTrait;
    const EVENT_SUCCESS = 1;
    const EVENT_FAILURE = 2;
    /**
     * základní údaje volané při každém požadavku
     * ID_Application, ID_Login
     * @var array
     */
    protected $init;
    /**
     * @var SoapClient
     */
    protected $soapClient;

    /**
     * @param mixed $wsdl Odkaz na WSDL soubor
     * @param array $soapOpts Nastaveni SOAP requestu
     * Ma pouzivat kompresi na prenasena data?
     * @throws InvalidArgumentException pokud je odkaz na WSDL soubor prázdný
     */
    public function __construct($wsdl, array $soapOpts)
    {
        $this->init = $soapOpts;
        if (empty($wsdl)) {
            throw new InvalidArgumentException("WSDL address cannot be empty.");
        }
        $this->soapClient = new SoapClient($wsdl, $soapOpts);
    }

    /**
     * @inheritdoc
     */
    public function call($functionName, array $arguments = [])
    {
        return $this->soapCall($functionName, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function __call($functionName, $arguments)
    {
        return $this->call($functionName, $arguments);
    }

    /**
     * Metoda provadejici SOAP pozadavek na servery Skautisu
     *
     * @see http://php.net/manual/en/soapclient.soapcall.php
     *
     * @param string $function_name Nazev akce k provedeni na WebService
     * @param array $arguments ([0]=args [1]=cover)
     * @param array $options Nastaveni
     * @param mixed $input_headers Hlavicky pouzite pri odesilani
     * @param array $output_headers Hlavicky ktere prijdou s odpovedi
     * @return mixed
     */
    protected function soapCall($function_name, $arguments, array $options = [], $input_headers = NULL, array &$output_headers = [])
    {
        $fname = ucfirst($function_name);
        $args = $this->prepareArgs($fname, $arguments);
        if ($this->hasListeners()) {
            $query = new SkautisQuery($fname, $args, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        }
        try {
            $soapResponse = $this->soapClient->__soapCall($fname, $args, $options, $input_headers, $output_headers);
            $soapResponse = $this->parseOutput($fname, $soapResponse);
            if ($this->hasListeners()) {
                $this->dispatch(self::EVENT_SUCCESS, $query->done($soapResponse));
            }
            return $soapResponse;
        } catch (SoapFault $e) {
            if ($this->hasListeners()) {
                $this->dispatch(self::EVENT_FAILURE, $query->done(NULL, $e));
            }
            if (preg_match('/Uživatel byl odhlášen/', $e->getMessage())) {
                throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
            }
            if (preg_match('/Nemáte oprávnění/', $e->getMessage())) {
                throw new PermissionException($e->getMessage(), $e->getCode(), $e);
            }
            throw new WsdlException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Z defaultnich parametru a parametru callu vytvori argumenty pro SoapClient::__soapCall
     *
     * @param string $function_name Jmeno funkce volane pres SOAP
     * @param array $argumentsArgumenty k mergnuti s defaultnimy
     *
     * @return array Argumenty pro SoapClient::__soapCall
     */
    protected function prepareArgs($function_name, array $arguments)
    {
        if (!isset($arguments[0]) || !is_array($arguments[0])) {
            $arguments[0] = [];
        }
        $args = array_merge($this->init, $arguments[0]); //k argumentum připoji vlastni informace o aplikaci a uzivateli
        if (!isset($arguments[1]) || $arguments[1] === NULL) {
            $function_name = strtolower(substr($function_name, 0, 1)) . substr($function_name, 1); //nahrazuje lcfirst
            $args = [[$function_name . "Input" => $args]];
            return $args;
        }
//pokud je zadan druhy parametr tak lze prejmenovat obal dat
        $matches = preg_split('~/~', $arguments[1]); //rozdeli to na stringy podle /
        $matches = array_reverse($matches); //pole se budou vytvaret zevnitr ven
        $matches[] = 0; //zakladni obal 0=>...
        foreach ($matches as $value) {
            $args = [$value => $args];
        }
        return $args;
    }

    /**
     * Parsuje output ze SoapClient do jednotného formátu
     *
     * @param string $fname Jméno funkce volané přes SOAP
     * @param mixed $retOdpoveď ze SoapClient::__soapCall
     *
     * @return array
     */
    protected function parseOutput($fname, $ret)
    {
//pokud obsahuje Output tak vždy vrací pole i s jedním prvkem.
        if (!isset($ret->{$fname . "Result"})) {
            return $ret;
        }
        if (!isset($ret->{$fname . "Result"}->{$fname . "Output"})) {
            return $ret->{$fname . "Result"}; //neobsahuje $fname.Output
        }
        if ($ret->{$fname . "Result"}->{$fname . "Output"} instanceof stdClass) { //vraci pouze jednu hodnotu misto pole?
            return [$ret->{$fname . "Result"}->{$fname . "Output"}]; //vraci pole se stdClass
        }
        return $ret->{$fname . "Result"}->{$fname . "Output"}; //vraci pole se stdClass
    }
}

//------- WebServiceFactory.php ------
namespace Skautis\Wsdl;
/**
 * @inheritdoc
 */
class WebServiceFactory implements WebServiceFactoryInterface
{
    /** @var string Třída webové služby */
    protected $class;

    /**
     * @param string $class
     */
    public function __construct($class = '\Skautis\Wsdl\WebService')
    {
        $this->class = $class;
    }

    /**
     * @inheritdoc
     */
    public function createWebService($url, array $options)
    {
        return new $this->class($url, $options);
    }
}

//------- WebServiceFactoryInterface.php ------
namespace Skautis\Wsdl;
/**
 * Interface továrny pro vytváření objektů webových služeb
 */
interface WebServiceFactoryInterface
{
    /**
     * Vytvoř nový objekt webové služby
     *
     * @param string $url Adresa WSDL souboru
     * @param array $options Globální nastavení pro všechny požadavky
     * @return mixed
     */
    public function createWebService($url, array $options);
}

//------- WsdlManager.php ------
namespace Skautis\Wsdl;

use Skautis\Config;
use Skautis\EventDispatcher\EventDispatcherInterface;
use Skautis\User;

/**
 * Třída pro správu webových služeb SkautISu
 */
class WsdlManager
{
    /**
     * @var WebServiceFactoryInterface
     */
    protected $webServiceFactory;
    /**
     * @var Config
     */
    protected $config;
    /**
     * Aliasy webových služeb pro rychlý přístup
     *
     * @var array
     */
    protected $aliases = [
        "user" => "UserManagement",
        "usr" => "UserManagement",
        "org" => "OrganizationUnit",
        "app" => "ApplicationManagement",
        "event" => "Events",
        "events" => "Events",
    ];
    /**
     * Dostupné webové služby SkautISu
     *
     * @var array
     */
    protected $supportedWebServices = [
        "ApplicationManagement",
        "ContentManagement",
        "Evaluation",
        "Events",
        "Exports",
        "GoogleApps",
        "Journal",
        "Material",
        "Message",
        "OrganizationUnit",
        "Power",
        "Reports",
        "Summary",
        "Task",
        "Telephony",
        "UserManagement",
        "Vivant",
        "Welcome",
    ];
    /**
     * @var array
     */
    protected $webServiceListeners = [];
    /**
     * Pole aktivních webových služeb
     *
     * @var array
     */
    protected $webServices = [];

    /**
     * @param WebServiceFactoryInterface $webServiceFactory továrna pro vytváření objektů webových služeb
     * @param Config $config
     */
    public function __construct(WebServiceFactoryInterface $webServiceFactory, Config $config)
    {
        $this->webServiceFactory = $webServiceFactory;
        $this->config = $config;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Získá objekt webové služby
     *
     * @param string $name jméno nebo alias webové služby
     * @param string|null $loginId skautIS login token
     * @return WebServiceInterface
     */
    public function getWebService($name, $loginId = NULL)
    {
        $name = $this->getWebServiceName($name);
        $key = $loginId . '_' . $name . ($this->config->isTestMode() ? '_Test' : '');
        if (!isset($this->webServices[$key])) {
            $options = $this->config->getSoapOptions();
            $options[User::ID_LOGIN] = $loginId;
            $this->webServices[$key] = $this->createWebService($name, $options);
        }
        return $this->webServices[$key];
    }

    /**
     * Vytváří objekt webové služby
     *
     * @param string $name jméno webové služby
     * @param array $options volby pro SoapClient
     * @return WebService|mixed
     */
    public function createWebService($name, array $options = [])
    {
        $webService = $this->webServiceFactory->createWebService($this->getWebServiceUrl($name), $options);
        if ($webService instanceof EventDispatcherInterface) {
// Zaregistruj listenery na vytvořeném objektu webové služby, pokud je to podporováno
            foreach ($this->webServiceListeners as $listener) {
                $webService->subscribe($listener['eventName'], $listener['callback']);
            }
        }
        return $webService;
    }

    /**
     * Vrací celé jméno webové služby
     *
     * @param string $name jméno nebo alias webové služby
     * @return string
     * @throws WsdlException
     */
    protected function getWebServiceName($name)
    {
        if (in_array($name, $this->supportedWebServices)) {
// služba s daným jménem existuje
            return $name;
        }
        if (array_key_exists($name, $this->aliases) && in_array($this->aliases[$name], $this->supportedWebServices)) {
// je definovaný alias pro tuto službu
            return $this->aliases[$name];
        }
        throw new WsdlException("Web service '$name' not found.");
    }

    /**
     * Vrací URL webové služby podle jejího jména
     *
     * @param string $name celé jméno webové služby
     * @return string
     */
    protected function getWebServiceUrl($name)
    {
        return $this->config->getBaseUrl() . "JunakWebservice/" . rawurlencode($name) . ".asmx?WSDL";
    }

    /**
     * Vrací seznam webových služeb, které podporuje
     *
     * @return array
     */
    public function getSupportedWebServices()
    {
        return $this->supportedWebServices;
    }

    /**
     * @return bool
     */
    public function isMaintenance()
    {
        $headers = get_headers($this->getWebServiceUrl("UserManagement"));
        return !in_array('HTTP/1.1 200 OK', $headers);
    }

    /**
     * Přidá listener na spravovaných vytvářených webových služeb.
     *
     * @param string $eventName
     * @param callable $callback
     */
    public function addWebServiceListener($eventName, callable $callback)
    {
        $this->webServiceListeners[] = [
            'eventName' => $eventName,
            'callback' => $callback,
        ];
    }
}
