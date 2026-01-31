import type { BgaAnimations as BgaAnimationsType } from "./bga-animations";
import type { BgaScoreSheet as BgaScoreSheetType } from './bga-score-sheet';

const BgaAnimations: typeof BgaAnimationsType = await globalThis.importEsmLib('bga-animations', '1.x');
const BgaScoreSheet: typeof BgaScoreSheetType = await globalThis.importEsmLib('bga-score-sheet', '1.x');

export { BgaAnimations, BgaScoreSheet };
