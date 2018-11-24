<template>
    <div class="mixer card">
        <h5 class="card-header">
            {{ $t('headers.mixer') }}
        </h5>
        <div class="card-body">
            <div class="d-flex flex-row">
                <div class="flex-shrink-0 pt-1 pr-2">1</div>
                <div class="flex-fill">
                    <input type="range" min="0" max="1" step="0.01" class="custom-range slider" v-model="position">
                </div>
                <div class="flex-shrink-0 pt-1 pl-2">2</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data: function() {
        return {
            "position": 0.5
        };
    },
    watch: {
        position: function(val, oldVal) {
            this.$root.$emit('new-mixer-value', val);
        }
    }
}
</script>
