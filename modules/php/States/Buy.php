<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * babylonia implementation : © Jay Sachs <vagabond@covariant.org>
 *
 * Copyright 2025 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 */

declare(strict_types=1);

namespace Bga\Games\zooloretto\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;


/*
    9 => array(
    		"name" => "Buy",
    		"description" => clienttranslate('${actplayer} must buy a tile from an opponent Barn.'),
    		"descriptionmyturn" => clienttranslate('${you} must buy a tile from an opponent Barn.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "Buy", "Back" ),
    		"transitions" => array( "Back" => 2, "NextPlayer" => 4 )
    ),
*/

class Buy extends GameState
{
    function __construct(private Game $game)
    {
        parent::__construct(
            game: $game,
            id: 9,
            type: StateType::ACTIVE_PLAYER,
    		description: clienttranslate('${actplayer} must buy a tile from an opponent Barn.'),
    		descriptionMyTurn => clienttranslate('${you} must buy a tile from an opponent Barn.'),
    }

    public function getArgs(int $active_player_id): array
    {
        return [];
    }

    #[PossibleAction]
    public function actBuy(
        string $pid,
        string $x0,
        string $y0,
        string $x1,
        string $y1
    ): mixed {
		$player_id = $this->game->getCurrentPlayerId();
		$player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$player_id'" );
		$val = $this->game->getUniqueValueFromDB("select val from animals where id ='$tileid'" );
		$donor_player_id = $this->game->getUniqueValueFromDB("select player_id from animals where id ='$tileid'" );
		$donor_player_no = $this->game->getUniqueValueFromDB("select player_no from player where player_id ='$donor_player_id'" );
		$donor_name = $this->game->getUniqueValueFromDB("select player_name from player where player_id ='$donor_player_id'" );

		$sql = "update animals set x = $x1, y = $y1, status='PLAYED', player_id = '$player_id' where id = '$tileid'";
		$this->game->DbQuery( $sql );

		$sql = "update player set money = money - 2 where player_id = '$player_id'";
		$this->game->DbQuery( $sql );

		$sql = "update player set money = money + 1 where player_id = '$donor_player_id'";
		$this->game->DbQuery( $sql );

		$this->game->incStat( 2, "coinsspent", $player_id);
		$this->game->incStat( 1, "coinsreceived", $donor_player_id);

		$donor_money = $this->game->getUniqueValueFromDB("select money from player where player_id ='$donor_player_id'" );

		if (substr($val, 0, 5) != "Stall")
		{
			$this->game->notifyAllPlayers( "Buy", clienttranslate( '${player_name} bought the ${translatedval} from the Barn of ${donor_name} and put it in his ${pos2} enclosure.'),
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'tileid' => $tileid,
				'pid' => $pid,
				'x0' => $x0,
				'y0' => $y0,
				'x1' => $x1,
				'y1' => $y1,
				'val' => $val,
				'translatedval' => $this->DecodeAnimal($val),
				'pos2' => $this->DecodePos($x1),
				'player_name' => $this->game->getCurrentPlayerName(),
				'donor_name' => $donor_name,
				'donor_player_id' => $donor_player_id,
				'donor_player_no' => $donor_player_no,
				'donor_money' => $donor_money,
				'i18n' => array( 'translatedval', 'pos2')
			) );
		}
		else
		{
			$this->game->notifyAllPlayers( "Buy", clienttranslate( '${player_name} bought the ${translatedval} from the Barn of ${donor_name}.'),
			array(
				'player_id' => $player_id,
				'player_no' => $player_no,
				'tileid' => $tileid,
				'pid' => $pid,
				'x0' => $x0,
				'y0' => $y0,
				'x1' => $x1,
				'y1' => $y1,
				'val' => $val,
				'translatedval' => $this->DecodeAnimal($val),
				'pos2' => $this->DecodePos($x1),
				'player_name' => $this->game->getCurrentPlayerName(),
				'donor_name' => $donor_name,
				'donor_player_id' => $donor_player_id,
				'donor_player_no' => $donor_player_no,
				'donor_money' => $donor_money,
				'i18n' => array( 'translatedval', 'pos2')
			) );
		}

		////////////////////////////
		// KIDS
		////////////////////////////

		if (substr($val, 0, 5) != "Stall")
		{
			$enclosures = $this->game->getObjectListFromDB( "SELECT distinct x as x from animals where id = '$tileid'" );
			foreach( $enclosures as $index => $enclosure)
			{
				$males = intval($this->game->getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$females = intval($this->game->getUniqueValueFromDB("select count(*) from animals where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$kids = intval($this->game->getUniqueValueFromDB("select count(*) from animals where val like '_K' and player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$parents = min($males,$females);
				$maxid = intval($this->game->getUniqueValueFromDB("select MAX(id) from animals where id < 300" ));
				$spacesoccupied = intval($this->game->getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'" ));
				$animal = $this->game->getUniqueValueFromDB("select distinct LEFT(val , 1) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "'");
				$spacesleft = 0;
				if ($enclosure['x']=="1") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="2") $spacesleft = 4 - $spacesoccupied;
				else if ($enclosure['x']=="3") $spacesleft = 6 - $spacesoccupied;
				else if ($enclosure['x']=="4") $spacesleft = 5 - $spacesoccupied;
				else if ($enclosure['x']=="5") $spacesleft = 5 - $spacesoccupied;
				$totalkids = $parents; //-$kids;

				$sql = "update animals set id = id + 300 where id < 300 and val like '_M' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				$this->game->DbQuery( $sql );
				$sql = "update animals set id = id + 300 where id < 300 and val like '_F' and player_id='$player_id' and x = '" . $enclosure['x']. "' limit $totalkids";
				$this->game->DbQuery( $sql );

				$newparents = $this->game->getObjectListFromDB( "SELECT concat('tile_',b.player_no,'_',a.id-300,'_',a.val,'_',a.x,'_',a.y) oldparenttile, concat('tile_',b.player_no,'_',a.id,'_',a.val,'_',a.x,'_',a.y) parenttile, a.id, a.val,a.x,a.y, b.player_no FROM `animals` a, player b WHERE a.player_id = b.player_id and a.id > 300");

				for( $x=1; $x<= $totalkids; $x++ )
				{
					if ($spacesleft>0)
					{
						$maxid = $maxid + 1;
						$spacesleft = $spacesleft - 1;
						$spacesoccupied = $spacesoccupied + 1;

						$found = false;
						$spaceid = 0;
						while (!$found)
						{
							$spaceid = $spaceid + 1;
							$check = intval($this->game->getUniqueValueFromDB("select count(*) from animals where player_id='$player_id' and x = '" . $enclosure['x']. "' and y='$spaceid'" ));
							if ($check==0)
							{
								$found = true;
							}
						}

						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKID',".$enclosure['x'].", $spaceid)";
						$this->game->DbQuery( $sql );
						$kids = $this->game->getObjectListFromDB( "SELECT concat('tile_',$player_no,'_',id,'_',val,'_',x,'_',y) kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKID'");
						$kidsstall = "";

						$this->game->notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his enclosure.'),
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => $this->game->getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						$this->game->DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						$this->game->DbQuery( $sql );
					}
					else
					{
						$maxid = $maxid + 1;
						$sql = "insert into animals (id, idsel, idorder, player_id, val, status,x,y) values ('" . $maxid . "',null,0,'$player_id','".$animal."K','THIKINGKIDSTALL',0,0)";
						$this->game->DbQuery( $sql );
						$kids = "";
						$kidsstall = $this->game->getObjectListFromDB( "SELECT concat('tile_',".$player_no.",'_',id,'_',val,'_',x,'_',y) as kidtile, id, val,x,y FROM `animals` WHERE status = 'THIKINGKIDSTALL'");

						$this->game->notifyAllPlayers( "Babies", clienttranslate( '${player_name} received a newborn ${translatedval} that goes into his Barn.'),
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'kids' => $kids,
							'kidsstall' => $kidsstall,
							'player_name' => $this->game->getCurrentPlayerName(),
							'translatedval' => $this->DecodeAnimal($animal."K"),
							'newparents'=>$newparents,
							'i18n' => array( 'translatedval' )
						) );

						$sql = "update animals set status = 'PLAYED' where status = 'THIKINGKID'";
						$this->game->DbQuery( $sql );
						$sql = "update animals set status = 'STALL' where status = 'THIKINGKIDSTALL'";
						$this->game->DbQuery( $sql );
					}
				}

				if ($spacesleft==0)
				{
					$coinsgained = 0;
					if ($enclosure['x']=="1") $coinsgained=2;
					else if ($enclosure['x']=="2") $coinsgained=1;
					else if ($enclosure['x']=="3") $coinsgained=0;
					else if ($enclosure['x']=="4") $coinsgained=1;
					else if ($enclosure['x']=="5") $coinsgained=1;

					if ($coinsgained>0)
					{
						$coinsbefore = intval($this->game->getUniqueValueFromDB("select money from player where player_id = '$player_id'" ));

						$sql = "update player set money = money + " . intval($coinsgained) . " where player_id = '$player_id'";
						$this->game->DbQuery( $sql );

						$this->game->incStat( intval($coinsgained) , "coinsreceived", $player_id);

						$this->game->notifyAllPlayers( "CoinsGained", clienttranslate( '${player_name} gained ${coinsgained} money for completing his ${pos} enclosure.'),
						array(
							'player_id' => $player_id,
							'player_no' => $player_no,
							'coinsgained' => $coinsgained,
							'coinsbefore' => $coinsbefore,
							'enclosure' => $enclosure['x'],
							'player_name' => $this->game->getCurrentPlayerName(),
							'pos' => $this->DecodePos($enclosure['x']),
							'i18n' => array( 'pos' )
						) );
					}
				}
			}
		}
		////////////////////////////
		// KIDS END
		////////////////////////////

        return NextPlayer::class;
    }

    #[PossibleAction]
    public function actBack(): mixed {
        return PlayerTurn::class;
    }

    function zombie(int $playerId): mixed {
        // FIXME
        return "";
    }
}
