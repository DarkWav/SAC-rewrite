<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use pocketmine\block\BlockLegacyIds as BlockIds;
use pocketmine\block\Block;

class Analyzer
{
  #golobal variables
  /** @var Main */
  public Main $Main;
  /** @var Player */
  public Player $Player;
  /** @var String */
  public String $PlayerName;
  /** @var Server */
  public Server $Server;
  /** @var MainLogger */
  public MainLogger $Logger;
  /** @var int */
  public int $Colorized;
  /** @var CheckRegister */
  public CheckRegister $CheckRegister;

  #data

  #combat

  /** @var bool */
  public bool $isPvp; #indicator whether hit was performed in a PVP scenario or not
  /** @var int */
  public int $lastHitTick; #Tick where player was last hit
  /** @var int */
  public int $hitTickDifference; #Tick difference between 2 hits
  /** @var float */
  public float $hitTimeDifference; #Time difference between 2 hits
  /** @var float */
  public float $hitTimeDifferenceSum;
  /** @var float[] */
  public array $hitTimeDifferenceRingBuffer;
  /** @var int */
  public int $hitTimeDifferenceRingBufferSize;
  /** @var int */
  public int $hitTimeDifferenceRingBufferIndex;
  /** @var int */
  public int $analyzedHits; #amount of hits used for heuristic analysis
  /** @var Vector3 */
  public Vector3 $damagedEntityPosition;
  /** @var Vector3 */
  public Vector3 $damagedEntityPositionXZ;
  /** @var Vector3 */
  public Vector3 $playerPosition;
  /** @var Vector3 */
  public Vector3 $playerPositionXZ;
  /** @var Vector3 */
  public Vector3 $playerFacingDirection;
  /** @var Vector3 */
  public Vector3 $playerFacingDirectionXZ;
  /** @var Vector3 */
  public Vector3 $lastPlayerFacingDirectionXZ;
  /** @var Vector3 */
  public Vector3 $directionToTarget;
  /** @var Vector3 */
  public Vector3 $directionToTargetXZ;
  /** @var float */
  public float $hitDistance; #Reach Distance
  /** @var float */
  public float $hitDistanceXZ; #Reach Distance (only X and Z axis)
  /** @var float */
  public float $hitDistanceXZSum;
  /** @var float[] */
  public array $hitDistanceXZRingBuffer;
  /** @var int */
  public int $hitDistanceXZRingBufferIndex;
  /** @var int */
  public int $hitDistanceXZRingBufferSize;
  /** @var float */
  public float $averageHitDistanceXZ; #average Reach distance across a set amount of hits
  /** @var float */
  public float $directionDotProduct;
  /** @var float */
  public float $directionDotProductXZ;
  /** @var float */
  public float $hitAngle; #Hit Angle
  /** @var float */
  public float $hitAngleXZ; #Hit Angle (only X and Z axis)
  /** @var float */
  public float $lastHitAngleXZ;
  /** @var float */
  public float $hitAngleXZSum;
  /** @var float[] */
  public array $hitAngleXZRingBuffer;
  /** @var int */
  public int $hitAngleXZRingBufferIndex;
  /** @var int */
  public int $hitAngleXZRingBufferSize;
  /** @var int */
  public int $alreadyAnalyzedHitAngleXZHits;
  /** @var float */
  public float $averageHitAngleXZ; #Average Hit Angle across a set amount of hits
  /** @var float */
  public float $headMove; #Head movement since last hit
  /** @var float */
  public float $hitAngleXZDifference;
  /** @var float */
  public float $hitAngleXZDifferenceSum;
  /** @var float[] */
  public array $hitAngleXZDifferenceRingBuffer;
  /** @var int */
  public int $hitAngleXZDifferenceRingBufferIndex;
  /** @var int */
  public int $hitAngleXZDifferenceRingBufferSize;
  /** @var int */
  public int $alreadyAnalyzedHitAngleXZDifferenceHits;
  /** @var float */
  public float $averageHitAngleXZDifference; #Average hit angle difference among a set amount of hits where the head has been moved before
  /** @var float */
  public float $averageHitTimeDifference;
  /** @var float */
  public float $averageCPS; #Average Clicks Per Seconds
  /** @var int */
  public int $alreadyAnalyzedHits;

  #movement

  /** @var Vector3 */
  public Vector3 $FromXZPos;
  /** @var Vector3 */
  public Vector3 $ToXZPos;
  /** @var Vector3 */
  public Vector3 $FromYPos;
  /** @var Vector3 */
  public Vector3 $ToYPos;
  /** @var float */
  public float $XZDistance;
  /** @var float */
  public float $YDistance;
  /** @var int */
  public int $PreviousMoveTick;
  /** @var float[] */
  public array $XZTimeRingBuffer;
  /** @var float[] */
  public array $XZDistanceRingBuffer;
  /** @var float[] */
  public array $YTimeRingBuffer;
  /** @var float[] */
  public array $YDistanceRingBuffer;
  /** @var int */
  public int $XZRingBufferSize;
  /** @var int */
  public int $YRingBufferSize;
  /** @var int */
  public int $XZRingBufferIndex;
  /** @var int */
  public int $YRingBufferIndex;
  /** @var float */
  public float $XZTimeSum;
  /** @var float */
  public float $XZDistanceSum;
  /** @var float */
  public float $YTimeSum;
  /** @var float */
  public float $YDistanceSum;
  /** @var float */
  public float $XZSpeed; #Average Travel Speed (XZ-Axis)
  /** @var float */
  public float $YSpeed; #Average Travel Speed (Y-Axis)
  /** @var bool */
  public bool $ignoredMove;
  /** @var float */
  public float $TimeDiff;
  /** @var int */
  public int $lastMotionTick;

  #bow shooting

  /** @var int */
  public int $PreviousShootBowTick;
  /** @var float[] */
  public array $ShootBowRingBuffer;
  /** @var int */
  public int $ShootBowRingBufferSize;
  /** @var int */
  public int $ShootBowRingBufferIndex;
  /** @var float */
  public float $ShootBowTimeSum;
  /** @var float */
  public float $ShootBowAverageLatency;

  /**
   * Analyzer constructor.
   * @param Player $plr
   * @param Main $sac
   */
  public function __construct(Player $plr, Main $sac)
  {

    #initialize basic variables

    $this->Main              = $sac;
    $this->Player            = $plr;
    $this->PlayerName        = $this->Player->getName();
    $this->Server            = $this->Main->server;
    $this->Logger            = $this->Main->logger;
    $this->Colorized         = $this->Main->Colorized;
    $this->CheckRegister     = new CheckRegister($this);
    
    #initialize data variables

    #processPlayerPerformsHit() data

    $this->isPvp                               = false;
    $this->analyzedHits                        = $this->Main->Config->get("CombatHeuristics.AnalyzedHits");
    $this->lastHitTick                         = -1;
    $this->hitTickDifference                   = 0;
    $this->hitTimeDifference                   = 0.0;
    $this->hitTimeDifferenceSum                = 0.0;
    $this->hitTimeDifferenceRingBufferSize     = $this->analyzedHits;
    $this->hitTimeDifferenceRingBuffer         = array_fill(0, $this->hitTimeDifferenceRingBufferSize, 0.0);
    $this->hitTimeDifferenceRingBufferIndex    = 0;
    
    $this->damagedEntityPosition               = new Vector3(0.0, 0.0, 0.0);
    $this->damagedEntityPositionXZ             = new Vector3(0.0, 0.0, 0.0);
    $this->playerPosition                      = new Vector3(0.0, 0.0, 0.0);
    $this->playerPositionXZ                    = new Vector3(0.0, 0.0, 0.0);
    
    $this->playerFacingDirection               = new Vector3(0.0, 0.0, 0.0);
    $this->playerFacingDirectionXZ             = new Vector3(0.0, 0.0, 0.0);
    $this->lastPlayerFacingDirectionXZ         = new Vector3(0.0, 0.0, 0.0);
    $this->directionToTarget                   = new Vector3(0.0, 0.0, 0.0);
    $this->directionToTargetXZ                 = new Vector3(0.0, 0.0, 0.0);
    
    $this->hitDistance                         = 0.0;
    $this->hitDistanceXZ                       = 0.0;
    $this->hitDistanceXZSum                    = 0.0;
    $this->hitDistanceXZRingBufferSize         = $this->analyzedHits;
    $this->hitDistanceXZRingBuffer             = array_fill(0, $this->hitDistanceXZRingBufferSize, 0.0);
    $this->hitDistanceXZRingBufferIndex        = 0;
    
    $this->directionDotProduct                 = 0.0;
    $this->directionDotProductXZ               = 0.0;
    
    $this->hitAngle                            = 0.0; 
    $this->hitAngleXZ                          = 0.0;
    $this->lastHitAngleXZ                      = 0.0;
    $this->hitAngleXZSum                       = 0.0;
    $this->hitAngleXZRingBufferSize            = $this->analyzedHits;
    $this->hitAngleXZRingBuffer                = array_fill(0, $this->hitAngleXZRingBufferSize, 0.0);
    $this->hitAngleXZRingBufferIndex           = 0;
    
    $this->headMove                            = 0.0;
    
    $this->hitAngleXZDifference                = 0.0;
    $this->hitAngleXZDifferenceSum             = 0.0;
    $this->hitAngleXZDifferenceRingBufferSize  = $this->analyzedHits;
    $this->hitAngleXZDifferenceRingBuffer      = array_fill(0, $this->hitAngleXZDifferenceRingBufferSize, 0.0);
    $this->hitAngleXZDifferenceRingBufferIndex = 0;
    
    $this->averageHitDistanceXZ                    = 0.0;
    $this->averageHitAngleXZ                       = 0.0;
    $this->averageHitAngleXZDifference             = 0.0;
    $this->averageHitTimeDifference                = 0.0;
    $this->averageCPS                              = 0.0;

    $this->alreadyAnalyzedHitAngleXZHits           = 0;
    $this->alreadyAnalyzedHitAngleXZDifferenceHits = 0;
    $this->alreadyAnalyzedHits                     = 0;
    
    #processPlayerMoveEvent() data
    
    $this->FromXZPos               = new Vector3(0.0, 0.0, 0.0);
    $this->ToXZPos                 = new Vector3(0.0, 0.0, 0.0);
    $this->FromYPos                = new Vector3(0.0, 0.0, 0.0);
    $this->ToYPos                  = new Vector3(0.0, 0.0, 0.0);
    $this->XZDistance              = 0.0;
    $this->YDistance               = 0.0;
    $this->PreviousMoveTick            = -1;
    $this->XZRingBufferSize        = 8;
    $this->YRingBufferSize         = 8;
    $this->XZRingBufferIndex       = 0;
    $this->YRingBufferIndex        = 0;
    $this->XZTimeRingBuffer        = array_fill(0, $this->XZRingBufferSize, 0.0);
    $this->XZDistanceRingBuffer    = array_fill(0, $this->XZRingBufferSize, 0.0);
    $this->YTimeRingBuffer         = array_fill(0, $this->YRingBufferSize , 0.0);
    $this->YDistanceRingBuffer     = array_fill(0, $this->YRingBufferSize , 0.0);
    $this->XZTimeSum               = 0.0;
    $this->XZDistanceSum           = 0.0;
    $this->YTimeSum                = 0.0;
    $this->YDistanceSum            = 0.0;
    $this->XZSpeed                 = 0.0;
    $this->YSpeed                  = 0.0;
    $this->ignoredMove             = false;
    $this->TimeDiff                = 0;
    $this->lastMotionTick          = -1;

    #processPlayerShootsBow() data

    $this->PreviousShootBowTick    = -1;
    $this->ShootBowTimeSum         = 0.0;
    $this->ShootBowRingBufferSize  = 4;
    $this->ShootBowRingBuffer      = array_fill(0, $this->hitAngleXZRingBufferSize, 0.0);;
    $this->ShootBowRingBufferIndex = 0;
    $this->ShootBowAverageLatency  = 0.0;
  }

  # Event handlers
  # these will work with events redirected from EventListener
  
  public function onPlayerJoin() : void
  {
    $this->Player->sendMessage(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am watching you ...");
    $this->Logger->info(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am watching you ...");
  }

  public function onPlayerRejoin() : void
  {
    $this->Player->sendMessage(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am still watching you ...");
    $this->Logger->info(TextFormat::ESCAPE.$this->Colorized."[SAC] > $this->PlayerName, I am still watching you ...");
  }

  public function onPlayerQuit() : void
  {
    $this->Logger->info(TextFormat::ESCAPE."$this->Colorized" . "[SAC] > $this->PlayerName is no longer watched...");
  }

  /**
   * @param PlayerMoveEvent $event
   */
  public function onPlayerMoveEvent(PlayerMoveEvent $event) : void
  {
    #process event first
    $this->processPlayerMoveEvent($event);
    #then run checks
    $this->CheckRegister->runChecksOnPlayerMoveEvent($event);
  }

  /**
   * @param EntityMotionEvent $event
   */
  public function onPlayerReceivesMotion(EntityMotionEvent $event): void
  {
    if(($event->getVector()->x != 0) || ($event->getVector()->y != 0) || ($event->getVector()->z != 0)) #ignore motions that don't actually move the player
    {
      $this->lastMotionTick = $this->Server->getTick();
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: ".$this->PlayerName."] [Debug: Movement] > Motion Received!");
    }
  }

  /**
   * @param EntityDamageEvent $event
   */
  public function onPlayerGetsHit(EntityDamageEvent $event): void
  {
  
  }

  /**
   * @param EntityDamageByEntityEvent $event
   */
  public function onPlayerPerformsHit(EntityDamageByEntityEvent $event): void
  {
    #process data
    $this->processPlayerPerformsHit($event);
    #then run checks
    $this->CheckRegister->runChecksOnPlayerPerformsHit($event);
  }
  
  /**
   * @param EntityShootBowEvent $event
   */
  public function onPlayerShootsBow(EntityShootBowEvent $event): void
  {
    #process event first
    $this->processPlayerShootsBow($event);
    #then run checks
    $this->CheckRegister->runChecksOnEntityShootBowEvent($event);
  }
  
  /**
   * @param EntityRegainHealthEvent $event
   */
  public function onPlayerRegainsHealth(EntityRegainHealthEvent $event): void
  {
    #process event first
    $this->processPlayerRegainsHealth($event);
    #then run checks
    $this->CheckRegister->runChecksOnEntityRegainHealthEvent($event);
  }

  # processing functions
  # these will process data retrieved from events

  /**
   * @param PlayerMoveEvent $event
   */
  public function processPlayerMoveEvent(PlayerMoveEvent $event) : void
  {
    #calculate distance travelled in the event itself in XZ and Y axis.
    $this->FromXZPos    = new Vector3($event->getFrom()->x, 0.0, $event->getFrom()->z);
    $this->ToXZPos      = new Vector3($event->getTo()->x  , 0.0, $event->getTo()->z  );
    $this->XZDistance   = $this->FromXZPos->distance($this->ToXZPos);
    $this->FromYPos     = new Vector3(0.0, $event->getFrom()->y, 0.0);
    $this->ToYPos       = new Vector3(0.0, $event->getTo()->y  , 0.0);
    $this->YDistance    = $this->FromYPos->distance($this->ToYPos);

    $Tick               = $this->Server->getTick();
    $TPS                = (float)$this->Server->getTicksPerSecond();
    $TickCount          = ($Tick - $this->PreviousMoveTick);
    $this->TimeDiff           = (float)($TickCount) / (float)$TPS;
    if ($TPS > 0.0 and $this->PreviousMoveTick != -1.0)
    {
      if($this->TimeDiff < 2.0) #if move events are divided too far apart each other, ignore the move
      {
        $this->ignoredMove = false;
        #write distances and times into a a ringbuffer
        $this->XZTimeSum                                      = $this->XZTimeSum     - $this->XZTimeRingBuffer    [$this->XZRingBufferIndex] + $this->TimeDiff; #ringbuffer time sum (remove oldest, add new)
        $this->XZDistanceSum                                  = $this->XZDistanceSum - $this->XZDistanceRingBuffer[$this->XZRingBufferIndex] + $this->XZDistance; #ringbuffer distance sum (remove oldest, add new) 
        $this->XZTimeRingBuffer    [$this->XZRingBufferIndex] = $this->TimeDiff; #overwrite oldest delta_t  with the new one
        $this->XZDistanceRingBuffer[$this->XZRingBufferIndex] = $this->XZDistance; #overwrite oldest distance with the new one          
        $this->XZRingBufferIndex++; #Update ringbuffer position
        if ($this->XZRingBufferIndex >= $this->XZRingBufferSize)
        {
          $this->XZRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
        $this->YTimeSum                                      = $this->YTimeSum     - $this->YTimeRingBuffer    [$this->YRingBufferIndex] + $this->TimeDiff; #ringbuffer time sum (remove oldest, add new)
        $this->YDistanceSum                                  = $this->YDistanceSum - $this->YDistanceRingBuffer[$this->YRingBufferIndex] + $this->YDistance; #ringbuffer distance sum (remove oldest, add new) 
        $this->YTimeRingBuffer    [$this->YRingBufferIndex]  = $this->TimeDiff; #overwrite oldest delta_t  with the new one
        $this->YDistanceRingBuffer[$this->YRingBufferIndex]  = $this->YDistance; #overwrite oldest distance with the new one          
        $this->YRingBufferIndex++; #Update ringbuffer position
        if ($this->YRingBufferIndex >= $this->YRingBufferSize)
        {
          $this->YRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
      }
      else
      {
        $this->ignoredMove = true;
      }
      #calculate actual average movement speed
      if ($this->XZTimeSum > 0)
      {
        $this->XZSpeed = (float)$this->XZDistanceSum / (float)$this->XZTimeSum; #speed = distance / time difference:
      }
      else
      {
        $this->XZSpeed = 0.0;
      }
      if ($this->YTimeSum > 0)
      {
        $this->YSpeed = (float)$this->YDistanceSum  / (float)$this->YTimeSum; #speed = distance / time difference:
      }
      else
      {
        $this->YSpeed = 0.0;
      }
    }
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: ".$this->PlayerName."] [Debug: Movement] > XZSpeed: ".$this->XZSpeed);
    $this->PreviousMoveTick = $Tick;
  }

  /**
   * @param EntityShootBowEvent $event
   */
  public function processPlayerShootsBow(EntityShootBowEvent $event) : void
  {
    $Tick                       = $this->Server->getTick();
    $TPS                        = (float)$this->Server->getTicksPerSecond();
    if ($this->PreviousShootBowTick == -1)
    {
      $this->PreviousShootBowTick = $Tick - 30;
    }
    $TickCount                                                = ($Tick - $this->PreviousShootBowTick);
    $DeltaTime                                                = (float)($TickCount) / (float)$TPS;
    $this->ShootBowTimeSum                                    = $this->ShootBowTimeSum - $this->ShootBowRingBuffer[$this->ShootBowRingBufferIndex] + $DeltaTime; #ringbuffer time sum (remove oldest, add new)
    $this->ShootBowRingBuffer[$this->ShootBowRingBufferIndex] = $DeltaTime;
    $this->ShootBowRingBufferIndex++; #Update ringbuffer position
    if ($this->ShootBowRingBufferIndex >= $this->ShootBowRingBufferSize)
    {
      $this->ShootBowRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
    }
    $this->ShootBowAverageLatency = $this->ShootBowTimeSum / $this->ShootBowRingBufferSize; #calculate average latency between bow shots
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: ".$this->PlayerName."] [Debug: Combat] > ShootBowLatency: ".$this->ShootBowAverageLatency);
    $this->PreviousShootBowTick   = $Tick;
  }
  
  /**
   * @param EntityRegainHealthEvent $event
   */
  public function processPlayerRegainsHealth(EntityRegainHealthEvent $event) : void
  {
  
  }
  
  /**
   * @param EntityDamageByEntityEvent $event
   */
  public function processPlayerPerformsHit(EntityDamageByEntityEvent $event) : void
  {
    $damagedEntity = $event->getEntity();
    $TPS           = (float)$this->Server->getTicksPerSecond();
    if ($damagedEntity instanceof Player)
    {
      $this->isPvp = true;
    }
    else
    {
      $this->isPvp = false;
    }
    $name = $this->PlayerName;
    $this->damagedEntityPosition      = new Vector3($damagedEntity->getPosition()->x, $damagedEntity->getPosition()->y, $damagedEntity->getPosition()->z);
    $this->damagedEntityPositionXZ    = new Vector3($damagedEntity->getPosition()->x, 0                     , $damagedEntity->getPosition()->z);
    $this->playerPosition             = new Vector3($this->Player->getPosition()->x , $this->Player->getPosition()->y , $this->Player->getPosition()->z );
    $this->playerPositionXZ           = new Vector3($this->Player->getPosition()->x , 0                     , $this->Player->getPosition()->z );
    $this->playerFacingDirection      = $this->Player->getDirectionVector()->normalize();
    $this->playerFacingDirectionXZ    = $this->Player->getDirectionVector();
    $this->playerFacingDirectionXZ->y = 0;
    $this->playerFacingDirectionXZ    = $this->playerFacingDirectionXZ->normalize();
    $this->directionToTarget          = $this->damagedEntityPosition->subtractVector($this->playerPosition)->normalize();
    $this->directionToTargetXZ        = $this->damagedEntityPositionXZ->subtractVector($this->playerPositionXZ)->normalize();
    $this->headMove                   = $this->playerFacingDirectionXZ->distance($this->lastPlayerFacingDirectionXZ);
    $this->hitDistance                = $this->playerPosition->distance($this->damagedEntityPosition);
    $this->hitDistanceXZ              = $this->playerPositionXZ->distance($this->damagedEntityPositionXZ);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > DistanceXZ: ".$this->hitDistanceXZ);
    #here comes the maths bois!
    $this->directionDotProduct        = $this->playerFacingDirection->dot($this->directionToTarget);
    $this->directionDotProductXZ      = $this->playerFacingDirectionXZ->dot($this->directionToTargetXZ);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > DotProductXZ: ".$this->directionDotProductXZ);
    $this->hitAngle                   = rad2deg(acos($this->directionDotProduct));
    $this->hitAngleXZ                 = rad2deg(acos($this->directionDotProductXZ));
    if($this->lastHitTick > 0)
    {
      $this->hitTickDifference = ($this->Server->getTick() - $this->lastHitTick);
      $this->hitTimeDifference = ($this->hitTickDifference / $TPS);
    }
    $orient = ($this->playerFacingDirectionXZ->x * $this->directionToTargetXZ->z) - ($this->playerFacingDirectionXZ->z * $this->directionToTargetXZ->x);
    if($orient >= 0)
    {
      $this->hitAngleXZDifference       = abs($this->hitAngleXZ - $this->lastHitAngleXZ);
    }
    else
    {
      $this->hitAngleXZDifference       = abs((-$this->hitAngleXZ) - $this->lastHitAngleXZ); //invert AngleXZ if orient is negative
    }
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AngleXZDifference: ".$this->hitAngleXZDifference);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AngleXZ: ".$this->hitAngleXZ);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > HeadMove: ".$this->headMove);
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > TimeDiff: ".$this->hitTimeDifference);

    if ($TPS > 0.0)
    {
      $this->alreadyAnalyzedHits++;
      #compute heuristics-relevant data
      #average reach
      $this->hitDistanceXZSum                                             = $this->hitDistanceXZSum - $this->hitDistanceXZRingBuffer[$this->hitDistanceXZRingBufferIndex] + $this->hitDistanceXZ; #add distance to total distance sum
      $this->hitDistanceXZRingBuffer[$this->hitDistanceXZRingBufferIndex] = $this->hitDistanceXZ; #then write it into ringbuffer
      $this->hitDistanceXZRingBufferIndex++;
      if ($this->hitDistanceXZRingBufferIndex >= $this->hitDistanceXZRingBufferSize)
      {
        $this->hitDistanceXZRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
      }
      $this->averageHitDistanceXZ = $this->hitDistanceXZSum / $this->hitDistanceXZRingBufferSize;
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AVERAGE DistanceXZ: ".$this->averageHitDistanceXZ);

      #average hit speed
      $this->hitTimeDifferenceSum                                                 = $this->hitTimeDifferenceSum - $this->hitTimeDifferenceRingBuffer[$this->hitTimeDifferenceRingBufferIndex] + $this->hitTimeDifference; #add time difference to total difference sum
      $this->hitTimeDifferenceRingBuffer[$this->hitTimeDifferenceRingBufferIndex] = $this->hitTimeDifference; #then write it into ringbuffer
      $this->hitTimeDifferenceRingBufferIndex++;
      if ($this->hitTimeDifferenceRingBufferIndex >= $this->hitTimeDifferenceRingBufferSize)
      {
        $this->hitTimeDifferenceRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
      }
      $this->averageHitTimeDifference = $this->hitTimeDifferenceSum / $this->hitTimeDifferenceRingBufferSize;
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AVERAGE TimeDiff: ".$this->averageHitTimeDifference);
      if($this->averageHitTimeDifference > 0)
      {
        $this->averageCPS = (1 / $this->averageHitTimeDifference);
      }
      
      
      if ($this->headMove >= 0.05) #only re-calculate angle-based data when player actually moved head
      {
        #average angle
        $this->alreadyAnalyzedHitAngleXZHits++;
        $this->hitAngleXZSum                                          = $this->hitAngleXZSum - $this->hitAngleXZRingBuffer[$this->hitAngleXZRingBufferIndex] + $this->hitAngleXZ; #add angle to total angle sum
        $this->hitAngleXZRingBuffer[$this->hitAngleXZRingBufferIndex] = $this->hitAngleXZ; #then write it into ringbuffer
        $this->hitAngleXZRingBufferIndex++;
        if ($this->hitAngleXZRingBufferIndex >= $this->hitAngleXZRingBufferSize)
        {
          $this->hitAngleXZRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
        $this->averageHitAngleXZ = $this->hitAngleXZSum / $this->hitAngleXZRingBufferSize;

        #average angle difference
        $this->alreadyAnalyzedHitAngleXZDifferenceHits++;
        $this->hitAngleXZDifferenceSum                                                    = $this->hitAngleXZDifferenceSum - $this->hitAngleXZDifferenceRingBuffer[$this->hitAngleXZDifferenceRingBufferIndex] + $this->hitAngleXZDifference; #add angle to total angle sum
        $this->hitAngleXZDifferenceRingBuffer[$this->hitAngleXZDifferenceRingBufferIndex] = $this->hitAngleXZDifference; #then write it into ringbuffer
        $this->hitAngleXZDifferenceRingBufferIndex++;
        if ($this->hitAngleXZDifferenceRingBufferIndex >= $this->hitAngleXZDifferenceRingBufferSize)
        {
          $this->hitAngleXZDifferenceRingBufferIndex = 0; #make ringbuffer index reset once its at the end of the ringbuffer
        }
        $this->averageHitAngleXZDifference = $this->hitAngleXZDifferenceSum / $this->hitAngleXZDifferenceRingBufferSize;
      }
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AVERAGE AngleXZ: ".$this->averageHitAngleXZ);
      $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: $name] [Debug: Combat] > $name > AVERAGE AngleXZDifference: ".$this->averageHitAngleXZDifference);
    }
    #update the "previous-value" variables
    $this->lastPlayerFacingDirectionXZ = $this->playerFacingDirectionXZ;
    $this->lastHitAngleXZ              = $this->hitAngleXZ;
    $this->lastHitTick                 = $this->Server->getTick();
  }
  
  #util functions

  /**
   * @param string $message
   */
  public function kickPlayer(string $message) : void
  {
    $this->Main->getScheduler()->scheduleDelayedTask(new KickTask($this->Main, $this->Player, $message), 1);
  }
  
  public function areAllBlocksAboveAir() : bool
  {
    $level       = $this->Player->getWorld();
    $posX        = $this->Player->getPosition()->x;
    $posY        = $this->Player->getPosition()->y + 2;
    $posZ        = $this->Player->getPosition()->z;

    # loop through 3x3 square above player head to check for any non-air blocks
    for ($xidx = $posX-1; $xidx <= $posX+1; $xidx = $xidx + 1)
    {
      for ($zidx = $posZ-1; $zidx <= $posZ+1; $zidx = $zidx + 1)
      {
        $pos   = new Vector3($xidx, $posY, $zidx);
        $block = $level->getBlock($pos)->getId();
        if ($block != BlockIds::AIR)
        {
          $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: ".$this->PlayerName."] [Debug: Movement] > areAllBlocksAboveAir: false");
          return false;
        }
      }
    }
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: ".$this->PlayerName."] [Debug: Movement] > areAllBlocksAboveAir: true");
    return true;
  }

  public function getCurrentFrictionFactor() : float
  {
    $level          = $this->Player->getWorld();
    $posX           = $this->Player->getPosition()->x;
    $posY           = $this->Player->getPosition()->y - 1; #define position of block below player
    $posZ           = $this->Player->getPosition()->z;
    $frictionFactor = $level->getBlock(new Vector3($posX, $posY, $posZ))->getFrictionFactor(); # get friction factor from block
    for ($xidx = $posX-1; $xidx <= $posX+1; $xidx = $xidx + 1)
    {
      for ($zidx = $posZ-1; $zidx <= $posZ+1; $zidx = $zidx + 1)
      {
        $pos        = new Vector3($xidx, $posY, $zidx);
        if($level->getBlock($pos)->getId() != BlockIds::AIR) # only use friction factor if block below isn't air
        {
          if($frictionFactor <= $level->getBlock($pos)->getFrictionFactor()) # use new friction factor only if it has a higher value
          {
            $frictionFactor = $level->getBlock($pos)->getFrictionFactor();
          }
        }
        else # use block that is two blocks below otherwise
        {
          $pos->y = ($this->Player->getPosition()->y - 2);
          if($frictionFactor <= $level->getBlock($pos)->getFrictionFactor())
          {
            $frictionFactor = $level->getBlock($pos)->getFrictionFactor();
          }
        }
      }
    }
    $this->Logger->debug(TextFormat::ESCAPE.$this->Colorized."[SAC] [Player: ".$this->PlayerName."] [Debug: Movement] > Friction Factor: $frictionFactor");
    return $frictionFactor;
  }

}
