import Lightbox from "~/components/Common/Lightbox.vue";
import {Directive, inject, InjectionKey, provide, Ref} from "vue";

const provideKey = Symbol() as InjectionKey<Ref<Lightbox>>;

export function useProvideLightbox(lightboxRef: Ref<Lightbox>) {
    provide(provideKey, lightboxRef);
}

export function useLightbox() {
    const lightbox = inject(provideKey);

    const vLightbox: Directive<HTMLElement, string> = (el) => {
        el.addEventListener('click', (e) => {
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
