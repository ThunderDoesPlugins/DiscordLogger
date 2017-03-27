<?php
/** Created By Thunder33345 **/
namespace Thunder33345\DiscordLogger;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class SendTask extends AsyncTask
{
  private $desc,$message,$curlOpts;

  public function __construct($desc,Message $message,$curlOpts)
  {
    $this->desc = $desc;
    $this->message = $message;
    $this->curlOpts = serialize($curlOpts);
  }

  public function onRun()
  {
    $result = DiscordHook::send($this->message,unserialize($this->curlOpts));
    $this->setResult($result,true);
  }

  public function onCompletion(Server $server)
  {
    $discordLogger = $server->getPluginManager()->getPlugin("DiscordLogger");
    if($discordLogger instanceof Loader AND $discordLogger->isEnabled()) {
      $discordLogger->debugMsg("[Response](async) '".$this->desc."' : ".print_r($this->getResult(),true));
    } else {
      $server->getLogger()->warning('DiscordLogger plugin not found!');
      $server->getLogger()->notice('DiscordLogger response for '.$this->desc.' :'.print_r($this->getResult(),true));
    }
  }
}