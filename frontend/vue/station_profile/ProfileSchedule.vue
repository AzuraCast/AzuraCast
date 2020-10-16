<template>
    <div id="profile-scheduled">
        <section class="card mb-4 scheduled" role="region" v-if="scheduleItems.length > 0">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title" key="lang_schedule_title" v-translate>Scheduled</h3>
            </div>
            <table class="table table-striped mb-0">
                <tbody>
                <tr v-for="row in scheduleItems">
                    <td>
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <h5 class="m-0">
                                <small>
                                    <template v-if="row.type === 'playlist'">
                                        <translate key="lang_schedule_playlist_name">Playlist</translate>
                                    </template>
                                    <template v-else>
                                        <translate key="lang_schedule_streamer_name">Streamer/DJ</translate>
                                    </template>
                                </small><br>
                                {{ row.name }}
                            </h5>
                            <p class="text-right m-0">
                                <small>{{ row.start_formatted }} - {{ row.end_formatted }}</small>
                                <br>
                                <strong>
                                    <template v-if="row.is_now">
                                        <translate key="lang_schedule_now">Now</translate>
                                    </template>
                                    <template v-else>{{ row.time_until }}</template>
                                </strong>
                            </p>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

<script>
export default {
    props: {
        scheduleItems: Array
    }
};
</script>
