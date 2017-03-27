<?php
/** Created By Thunder33345 **/
namespace Thunder33345\DiscordLogger;

use pocketmine\plugin\PluginBase;

class Loader extends PluginBase
{
  const start = 1;
  const prepReload = 2;
  const reload = 3;
  const stop = 4;
  const phpStop = 5;
  const phpError = 6;
  const phpCrash = 7;
  const ping = 8;

  private static $started = false;
  private $debug = true;
  private $pwd = null;

  public function onLoad()
  {
  }

  public function onEnable()
  {
    //On Enabled Called
    if(self::$started == true) {//is reload
      $this->debugMsg("Server Reloaded");
      $this->send(self::reload);
    } else {//server started
      $this->debugMsg("Server Started");
      $this->send(self::start);
      set_error_handler([$this,'handleError'],E_ALL);
      register_shutdown_function([$this,'shutdown']);
      self::$started = true;
    }
    mt_srand(microtime(true));
    $this->pwd = hash('sha512',mt_rand());
    $ping = new PingTask($this,$this->pwd);
    $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask($ping,10 * 20,30 * 20);
  }

  public function onDisable()
  {
    if($this->getServer()->isRunning() == true) { //prepare reload OR plugin crashed
      $this->debugMsg("Server Prepare Reload / Plugin Crashed");
      $this->send(self::prepReload);
    } else { //server stopping
      $this->debugMsg("Server Stopping");
      $this->send(self::stop);
    }
  }

  private function debugMsg($msg)
  {
    if($this->debug) {
      if(is_object($msg)) $msg = print_r($msg,true);
      $this->getLogger()->notice(" DEBUG: ".$msg);
    }
  }

  private function send($stat,$error = null)
  {
    switch($stat){
      case self::start:
        $desc = "[Server] Starting";
        $color = hexdec("#58db62");
        break;
      case self::prepReload:
        $desc = "[Server] Preparing Reload";
        $color = hexdec("#fcbf5e");
        break;
      case self::reload:
        $desc = "[Server] Reloading";
        $color = hexdec('#e8f329');
        break;
      case self::stop:
        $desc = "[Server] Stopping";
        $color = hexdec('#e10202');
        break;
      case self::phpStop:
        $desc = "[PHP] Shutting Down";
        $color = hexdec('#e10202');
        break;
      case self::phpError:
        $desc = "[PHP] Error\n```".print_r($error,true)."```";
        $color = hexdec("#ff0000");
        break;
      case self::phpCrash:
        $desc = "[PHP] Crash\n```".print_r($error,true)."```";
        $color = hexdec("#ff0000");
        break;
      case self::ping:
        $desc = "[Server] Ping! (".$error['c_players']."\\".$error['c_max'].")\n";
        if($error['c_players'] >= 1) {
          $desc .= "Players:```\n";
          foreach($error['players'] as $data){
            $player = $data[0];
            $nick = $data[1];
            $desc .= $player;
            if(strlen($nick) >= 0 AND $player !== $nick) {
              $desc .= " => $nick";
            }
            $desc .= "\n";
          }
          $desc .= "```";
        } else $desc .= "No Players!";
        $color = hexdec('#20ff00');
        break;
      default:
        return;
        break;
    }
    $key = false;//if your PHP have no cert access
    $url = '';
    $dh = DiscordHook::send(
     new Message(
      new User($url,"Server Logger"),"",
      [new Embed(null,$desc,null,$color)]),
     [CURLOPT_SSL_VERIFYHOST => $key,CURLOPT_SSL_VERIFYPEER => $key]
    );
    $this->debugMsg("Response for ".'"'.$desc.'"'." : ".'"'.print_r($dh,true).'');
  }

  public function handlePing($pwd)
  {
    if($this->pwd !== $pwd) return;
    $info = [];
    $players = $this->getServer()->getOnlinePlayers();
    $info['c_players'] = count($players);
    $info['c_max'] = $this->getServer()->getMaxPlayers();
    $info['players'] = [];
    foreach($players as $player){
      $info['players'][] = [$player->getName(),$player->getDisplayName()];
    }
    $this->debugMsg("Ping! ".print_r($info));
    $this->send(self::ping,$info);
  }

  public function handleError(int $errno,string $errstr,string $errfile,int $errline,array $errcontext)
  {
    $error = [];
    $error['type'] = $this->readableErrorType($errno);
    $error['string'] = $errstr;
    $error['file'] = $errfile;
    $error['line'] = $errline;
    $error['context'] = $errcontext;
    $this->send(self::phpError,$error);
  }

  public function shutdown()
  {
    if(!$this->getServer()->isRunning()) {
      $error = error_get_last();
      if($error['type'] === E_ERROR) {
        $this->send(self::phpCrash,$error);
      } else $this->send(self::phpStop);

    }
  }

  private function readableErrorType($int)
  {
    switch($int){
      case E_ERROR:
        return 'E_ERROR';
      case E_WARNING:
        return 'E_WARNING';
      case E_PARSE:
        return 'E_PARSE';
      case E_NOTICE:
        return 'E_NOTICE';
      case E_CORE_ERROR:
        return 'E_CORE_ERROR';
      case E_CORE_WARNING:
        return 'E_CORE_WARNING';
      case E_COMPILE_ERROR:
        return 'E_COMPILE_ERROR';
      case E_COMPILE_WARNING:
        return 'E_COMPILE_WARNING';
      case E_USER_ERROR:
        return 'E_USER_ERROR';
      case E_USER_WARNING:
        return 'E_USER_WARNING';
      case E_USER_NOTICE:
        return 'E_USER_NOTICE';
      case E_STRICT:
        return 'E_STRICT';
      case E_RECOVERABLE_ERROR:
        return 'E_RECOVERABLE_ERROR';
      case E_DEPRECATED:
        return 'E_DEPRECATED';
      case E_USER_DEPRECATED:
        return 'E_USER_DEPRECATED';
    }
    return "Undefined Error Code: $int";
  }

  /*
	private function runDebug()
	{
		$this->isRunning();
		$this->hasStopped();
	}

	private function isRunning()
	{
		if (is_null($this->getServer()))
			echo "Is Running: (Server Instance Null)\n";
		elseif ($this->getServer() instanceof Server) {
			if ($this->getServer()->isRunning() == true) echo "Is Running: TRUE\n";
			elseif ($this->getServer()->isRunning() == false) echo "Is Running: FALSE\n";
		} else echo "Is Running: (Server Instance Unknown)\n";

	}

	private function hasStopped()
	{
		if (is_null($this->getServer()))
			echo "Has Stopped: (Server Instance Null)\n";
		elseif ($this->getServer() instanceof Server) {
			($hasStopped = (new \ReflectionClass($this->getServer()))->getProperty('hasStopped'))->setAccessible(true);
      $hasStopped = $hasStopped->getValue($this->getServer());
      if ($hasStopped == true) echo "Has Stopped: TRUE\n";
      elseif ($hasStopped == false) echo "Has Stopped: FALSE\n";
    } else echo "Has Stopped: (Server Instance Unknown)\n";

	}
*/
}
