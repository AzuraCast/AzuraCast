import Lightbox from "~/components/Common/Lightbox.vue";
import {Directive, inject, InjectionKey, provide, Ref} from "vue";

const provideKey: InjectionKey<Ref<typeof Lightbox>> = Symbol() as InjectionKey<Ref<typeof Lightbox>>;

export function useProvideLightbox(lightboxRef: Ref<typeof Lightbox>): void {
    provide(provideKey, lightboxRef);
}

export function useLightbox() {
    const lightbox: Ref<typeof Lightbox> = inject(provideKey);

    const vLightbox: Directive<HTMLElement, string> = (el: HTMLElement): void => {
        el.addEventListener('click', (e: MouseEvent): void => {
            if (typeof lightbox !== 'undefined') {
                e.preventDefault();
                lightbox.value.show(el.getAttribute('href'));
            }
        });
    };

    return {
        vLightbox
    }
}
