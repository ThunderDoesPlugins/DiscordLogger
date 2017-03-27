<?php
/** Created By Thunder33345 **/
namespace Thunder33345\DiscordLogger;

use pocketmine\scheduler\PluginTask;

class PingTask extends PluginTask
{
  private $getMain,$pwd;

  public function __construct(Loader $main,$pwd)
  {
    parent::__construct($main);
    $this->getMain = $main;
    $this->pwd = $pwd;
  }

  public function onRun($tick)
  {
    $this->getMain->handlePing($this->pwd);
  }
}