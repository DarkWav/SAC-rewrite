<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class KickTask extends Task
{
  /** @var Main */
  public $Main;
  /** @var String */
  public $Message;
  /** @var Player */
  public $Player;

  /**
   * KickTask constructor.
   * @param Main $mn
   * @param Player $plr
   * @param $msg
   */
  public function __construct(Main $mn, Player $plr, $msg)
  {
    $this->Main    = $mn;
    $this->Message = $msg;
    $this->Player  = $plr;
  }

  /**
   * @param int $currentTick
   */
  public function onRun(int $currentTick) : void
  {
    if ($this->Player != null && $this->Player->isOnline())
    {
      $this->Player->close("", TextFormat::ESCAPE.$this->Main->Colorized . $this->Message);
      $name = $this->Player->getName();
      $msg = "[SAC] > $name was kicked for Cheating. I am always watching you.";
      $this->Main->server->broadcastMessage(TextFormat::ESCAPE.$this->Main->Colorized . $msg);
    }
  }
}
