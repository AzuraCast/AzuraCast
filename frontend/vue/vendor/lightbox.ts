import Lightbox from "~/components/Common/Lightbox.vue";
import {Directive, inject, InjectionKey, provide, Ref} from "vue";

const provideKey: InjectionKey<Ref<Lightbox>> = Symbol() as InjectionKey<Ref<Lightbox>>;

export function useProvideLightbox(lightboxRef: Ref<Lightbox>): void {
    provide(provideKey, lightboxRef);
}

export function useLightbox() {
    const lightbox: Ref<Lightbox> = inject(provideKey);

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
