import Lightbox from "~/components/Common/Lightbox.vue";
import {Directive, inject, InjectionKey, provide, Ref} from "vue";

export type LightboxTemplateRef = InstanceType<typeof Lightbox> | null;

const provideKey: InjectionKey<Ref<LightboxTemplateRef>> = Symbol() as InjectionKey<Ref<LightboxTemplateRef>>;

export function useProvideLightbox(lightboxRef: Ref<LightboxTemplateRef>): void {
    provide(provideKey, lightboxRef);
}

export function useLightbox() {
    const lightbox: Ref<LightboxTemplateRef> = inject(provideKey);

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
