import NProgress from 'nprogress';

export function useNProgress() {
    const showLoading = () => {
        NProgress.start();
    };

    const hideLoading = () => {
        NProgress.done();
    };

    let isAxiosLoading: boolean = false;
    let axiosLoadCount: number = 0;

    const setLoading = (isLoading: boolean) => {
        const prevIsLoading = isAxiosLoading;
        if (isLoading) {
            axiosLoadCount++;
            isAxiosLoading = true;
        } else if (axiosLoadCount > 0) {
            axiosLoadCount--;
            isAxiosLoading = (axiosLoadCount > 0);
        }

        // Handle state changes
        if (!prevIsLoading && isAxiosLoading) {
            showLoading();
        } else if (prevIsLoading && !isAxiosLoading) {
            hideLoading();
        }
    };

    return {
        showLoading,
        hideLoading,
        setLoading
    };
}
