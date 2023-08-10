import {Router} from "vue-router";
import NProgress from 'nprogress';

export default function installRouterLoading(router: Router): void {
    router.beforeResolve((to, from, next) => {
        if (to.name) {
            NProgress.start();
        }
        next();
    });

    router.afterEach(() => {
        NProgress.done();
    });
}
