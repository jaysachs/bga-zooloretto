class Animations {

    private animationManager: AnimationManager;
    constructor(animationManager: AnimationManager) {
        this.animationManager = animationManager;
    }

    wait(millis: number): Promise<any> {
        return this.animationManager.base.wait(millis);
    }

    slideAndAttach(elem: HTMLElement, newParent: HTMLElement, settings?: SlideAnimationSettings | undefined): Promise<any> {
        if (elem.parentElement == newParent) {
            return Promise.resolve();
        }
        return this.animationManager.slideAndAttach(elem, newParent, settings ?? {});
    }

    slideOutAndDestroy(elem?: HTMLElement | undefined,
        toElement?: HTMLElement | undefined,
        settings?: FloatingElementAnimationSettings | undefined): Promise<any> {
        if (!elem) {
            return Promise.resolve();
        }
        return this.animationManager.slideOutAndDestroy(elem, toElement, settings ?? {});
    }

    flash(css: string, elems: (HTMLElement | undefined)[], iterations: number = 2): Promise<any> {
        let on = () => {
            elems.forEach(e => e?.classList.add(css));
            return this.wait(250);
        }
        let off =  () => {
            elems.forEach(e => e?.classList.remove(css));
            return this.wait(250);
        }
        let anims: AnimationList = [];
        for (let i = 0; i < iterations; ++i) {
            anims.push(on, off);
        }
        return this.animationManager.playSequentially(anims);
    }


    async flip(front, back: HTMLElement, lift: string = 'scale(1.3,1.3) translate(-2.3vw,2.3vw)'): Promise<any> {
        if (!this.animationManager.animationsActive()) {
            return Promise.resolve(null);
        }
        const noflip = '';
        const revflip = ' rotateY(-180deg)';
        const fwdflip = ' rotateY(180deg)';

        // Initial states: the back of the tile is not flipped but the front face is
        back.style.transform = noflip;
        front.style.transform = revflip;
        const flipStyles = {
            'backface-visibility': 'hidden',
            transition: 'transform 0.5s',
        };
        Object.assign(back.style, flipStyles);
        Object.assign(front.style, flipStyles);

        await this.wait(1)
            // First just "lift" the tile faces up. flips are same as initial
            .then(_ => Promise.all([
                this.animateTransform(front, lift + revflip),
                this.animateTransform(back, lift + noflip),
            ]))
            // Now flip the front and back faces
            .then(_ => Promise.all([
                this.animateTransform(front, lift + noflip),
                this.animateTransform(back, lift + fwdflip),
            ]))
            // Then return them, flipped, to original location and size
            .then(_ => Promise.all([
                this.animateTransform(front, noflip),
                this.animateTransform(back, fwdflip),
            ]))
    }

    transitionEndPromise(element: HTMLElement): Promise<any> {
        return new Promise(resolve => {
            element.addEventListener('transitionend', function f(event) {
                if (event.target !== element) return;
                element.removeEventListener('transitionend', f);
                resolve(null);
            });
        });
    }

    private requestAnimationFramePromise(): Promise<any> {
        return new Promise(resolve => requestAnimationFrame(resolve));
    }

    animateTransform(element: HTMLElement, transform: string) {
        Object.assign(element.style, { transform: transform });
        return this.transitionEndPromise(element)
        // .then(_ => requestAnimationFramePromise(element));
    }

}