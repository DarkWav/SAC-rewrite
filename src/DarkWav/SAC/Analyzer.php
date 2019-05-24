<?php
declare(strict_types=1);

namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2019 DarkWav
 */

use AttachableThreadedLogger;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

use DarkWav\SAC\KickTask;

#import checks

use DarkWav\SAC\Angle;
use DarkWav\SAC\AutoClicker;
use DarkWav\SAC\FastBow;
use DarkWav\SAC\FastBreak;
use DarkWav\SAC\FastPlace;
use DarkWav\SAC\Fly;
use DarkWav\SAC\Glide;
use DarkWav\SAC\Heuristics;
use DarkWav\SAC\NoClip;
use DarkWav\SAC\Reach;
use DarkWav\SAC\Regen;
use DarkWav\SAC\Speed;
use DarkWav\SAC\Spider;
use DarkWav\SAC\VClip;


class Analyzer
{
    #global variables

    /**
     * @var Main $Main
     */
    public $Main;

    /**
     * @var Player $Player
     */
    public $Player;

    /**
     * @var string $PlayerName
     */
    public $PlayerName;

    /**
     * @var Server $Server
     */
    public $Server;

    /**
     * @var AttachableThreadedLogger $Logger
     */
    public $Logger;

    /**
     * @var string $Colorized
     */
    public $Colorized;

    /**
     * Analyzer constructor.
     * @param Player $player
     * @param Main $sac
     */
    public function __construct(Player $player, Main $sac)
    {
        #initialize basic variables
        $this->Main = $sac;
        $this->Player = $player;
        $this->PlayerName = $this->Player->getName();
        $this->Server = $this->Main->server;
        $this->Logger = $this->Main->logger;
        $this->Colorized = "3";
    }

    public function onPlayerJoin() : void
    {
        $this->Player->sendMessage(TextFormat::ESCAPE . $this->Colorized . "[SAC] > $this->PlayerName, I am watching you ...");
        $this->Logger->info(TextFormat::ESCAPE . $this->Colorized . "[SAC] > $this->PlayerName, I am watching you ...");
    }

    public function onPlayerRejoin() : void
    {
        $this->Player->sendMessage(TextFormat::ESCAPE . $this->Colorized . "[SAC] > $this->PlayerName, I am still watching you ...");
        $this->Logger->info(TextFormat::ESCAPE . $this->Colorized . "[SAC] > $this->PlayerName, I am still watching you ...");
    }

    public function onPlayerQuit() : void
    {
        $this->Logger->info(TextFormat::ESCAPE . "$this->Colorized" . "[SAC] > $this->PlayerName is no longer watched...");
    }
}
