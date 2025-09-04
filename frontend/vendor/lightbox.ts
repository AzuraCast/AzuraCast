import Lightbox from "~/components/Common/Lightbox.vue";
import {Directive, InjectionKey, provide, ShallowRef} from "vue";
import injectRequired from "~/functions/injectRequired.ts";

export type LightboxTemplateRef = InstanceType<typeof Lightbox>;
type LightboxTemplateRefFull = Readonly<ShallowRef<LightboxTemplateRef | null>>;

const provideKey: InjectionKey<LightboxTemplateRefFull> = Symbol() as InjectionKey<LightboxTemplateRefFull>;

export function useProvideLightbox(lightboxRef: LightboxTemplateRefFull): void {
    provide(provideKey, lightboxRef);
}

export function useLightbox() {
    const lightbox: LightboxTemplateRefFull = injectRequired(provideKey);

    const vLightbox: Directive<HTMLElement, string> = (el: HTMLElement): void => {
        el.addEventListener('click', (e: MouseEvent): void => {
            if (typeof lightbox !== 'undefined') {
                e.preventDefault();

                const href = el.getAttribute('href');
                if (href !== null) {
                    lightbox.value?.show(href);
                }
            }
        });
    };

    return {
        vLightbox
    }
}
