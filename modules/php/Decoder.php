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

use Bga\Games\zooloretto\Model\TileType;

class Decoder
{
	/**
	 * @deprecated
	 * @param $val int | string
	 */
	public static function Pos(mixed $val): string
	{
		if ($val=="1") return clienttranslate('first');
		else if ($val=="2") return clienttranslate('second');
		else if ($val=="3") return clienttranslate('third');
		else if ($val=="4") return clienttranslate('fourth');
		else if ($val=="5") return clienttranslate('fifth');
		else if ($val=="6") return clienttranslate('sixth');
		else return "";
	}

	/**
	 * @deprecated
	 */
    public static function Animal(string $val): string
	{
		$tt = TileType::from($val);
		return $tt == null ? "" : $tt->translated();
	}
}
