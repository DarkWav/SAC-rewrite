<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class KickTask extends Task
{
  /** @var Main */
  public Main $Main;
  /** @var String */
  public String $Message;
  /** @var Player */
  public Player $Player;

  /**
   * KickTask constructor.
   * @param Main $mn
   * @param Player $plr
   * @param String $msg
   */
  public function __construct(Main $mn, Player $plr, String $msg)
  {
    $this->Main    = $mn;
    $this->Message = $msg;
    $this->Player  = $plr;
  }

  /**
   * @param int $currentTick
   */
  public function onRun() : void
  {
    if ($this->Player != null && $this->Player->isOnline())
    {
      $this->Player->kick(TextFormat::ESCAPE.$this->Main->Colorized . $this->Message, "");
      $name = $this->Player->getName();
      $this->Main->logger->info(TextFormat::ESCAPE.$this->Main->Colorized . $name .": ". $this->Message);
      $msg = "[SAC] > $name was kicked for Cheating. I am always watching you.";
      $this->Main->server->broadcastMessage(TextFormat::ESCAPE.$this->Main->Colorized . $msg);
    }
  }
}
