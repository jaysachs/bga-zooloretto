<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * zooloretto implementation : © Jay Sachs <vagabond@covariant.org>
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

namespace Bga\Games\zooloretto\Model;

class Delivery implements Serializable {
    public function __construct(
        public int $truck_pos,
        public int $enclosure_id,
        public int $enclosure_pos,
    ) {}

    /** @return array{truck_pos:int,enclosure_id:int,enclosure_pos:int} */
    public function serialize(): array {
        return [
            'truck_pos' => $this->truck_pos,
            'enclosure_id' => $this->enclosure_id,
            'enclosure_pos' => $this->enclosure_pos,
        ];
    }

    /**
     * @param array{truck_pos:int, enclosure_id:int, enclosure_pos:int} $json
     */
    public static function deserialize(array $json): Delivery {
        return new Delivery(intval($json['truck_pos']), intval($json['enclosure_id']), intval($json['enclosure_pos']));
    }

    public function __toString(): string
    {
        return "Delivery{truck_pos:{$this->truck_pos},enclosure_id:{$this->enclosure_id},enclosure_pos:{$this->enclosure_pos}}";
    }
}
