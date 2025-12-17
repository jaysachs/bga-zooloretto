
define([
  'dojo/_base/declare',
  'ebg/core/gamegui',
  'ebg/counter',
  getLibUrl('bga-animations', '1.x'),
  getLibUrl('bga-score-sheet', '1.x'),
], function (declare, gamegui, counter, BgaAnimations, BgaScoreSheet) {
  (window as any).BgaAnimations = BgaAnimations;
  (window as any).BgaScoreSheet = BgaScoreSheet;
  declare('bgagame.zooloretto', ebg.core.gamegui, new ZoolorettoGame(gamegui));
});
