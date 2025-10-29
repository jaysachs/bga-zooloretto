<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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

namespace Bga\Games\zooloretto;

class Decoder
{
    // FIXME: change to enum
	public static function Pos(string $val): string
	{
		if ($val=="1") return clienttranslate('first');
		else if ($val=="2") return clienttranslate('second');
		else if ($val=="3") return clienttranslate('third');
		else if ($val=="4") return clienttranslate('fourth');
		else if ($val=="5") return clienttranslate('fifth');
		else if ($val=="6") return clienttranslate('sixth');
		else return "";
	}

    // FIXME: change to enum
    public static function Animal(string $val): string
	{
		if ($val=="C") return clienttranslate('Camel');
		else if ($val=="CF") return clienttranslate('Female Camel');
		else if ($val=="CM") return clienttranslate('Male Camel');
		else if ($val=="CK") return clienttranslate('Pup Camel');
		else if ($val=="E") return clienttranslate('Elephant');
		else if ($val=="EF") return clienttranslate('Female Elephant');
		else if ($val=="EM") return clienttranslate('Male Elephant');
		else if ($val=="EK") return clienttranslate('Pup Elephant');
		else if ($val=="F") return clienttranslate('Flamingo');
		else if ($val=="FF") return clienttranslate('Female Flamingo');
		else if ($val=="FM") return clienttranslate('Male Flamingo');
		else if ($val=="FK") return clienttranslate('Pup Flamingo');
		else if ($val=="K") return clienttranslate('Kangaroo');
		else if ($val=="KF") return clienttranslate('Female Kangaroo');
		else if ($val=="KM") return clienttranslate('Male Kangaroo');
		else if ($val=="KK") return clienttranslate('Pup Kangaroo');
		else if ($val=="L") return clienttranslate('Leopard');
		else if ($val=="LF") return clienttranslate('Female Leopard');
		else if ($val=="LM") return clienttranslate('Male Leopard');
		else if ($val=="LK") return clienttranslate('Pup Leopard');
		else if ($val=="M") return clienttranslate('Monkey');
		else if ($val=="MF") return clienttranslate('Female Monkey');
		else if ($val=="MM") return clienttranslate('Male Monkey');
		else if ($val=="MK") return clienttranslate('Pup Monkey');
		else if ($val=="P") return clienttranslate('Panda');
		else if ($val=="PF") return clienttranslate('Female Panda');
		else if ($val=="PM") return clienttranslate('Male Panda');
		else if ($val=="PK") return clienttranslate('Pup Panda');
		else if ($val=="Z") return clienttranslate('Zebra');
		else if ($val=="ZF") return clienttranslate('Female Zebra');
		else if ($val=="ZM") return clienttranslate('Male Zebra');
		else if ($val=="ZK") return clienttranslate('Pup Zebra');
		else if ($val=="StallA") return clienttranslate('Kiosk Stall');
		else if ($val=="StallB") return clienttranslate('Barrow Stall');
		else if ($val=="StallC") return clienttranslate('Snacks Stall');
		else if ($val=="StallD") return clienttranslate('Popcorn Stall');
		else if ($val=="Coin") return clienttranslate('Coin');
		else return "";
	}
}
