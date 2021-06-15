<template>
    <div class="card-body alert-info d-flex align-items-center" role="alert">
        <div class="flex-shrink-0 mr-2">
            <icon icon="info"/>
        </div>
        <div class="flex-fill">
            <slot></slot>
        </div>
    </div>
</template>

<script>
import Icon from './Icon';

export default {
    name: 'InfoCard',
    components: { Icon }
};
</script>
