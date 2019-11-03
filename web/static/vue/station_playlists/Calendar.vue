<template>
    <FullCalendar :plugins="plugins" :locales="locales" :time-zone="stationTimeZone" theme-system="bootstrap4"
                  :now-indicator="true" default-view="agendaWeek" default-timed-event-duration="00:20"
                  :locale="locale" :header="false" :footer="false" height="auto" :now="stationNow"
                  :events="calendarUrl">
    </FullCalendar>
</template>

<script>
  import FullCalendar from '@fullcalendar/vue'
  import allLocales from '@fullcalendar/core/locales-all'

  export default {
    name: 'Calendar',
    components: [
      FullCalendar
    ],
    props: {
      calendarUrl: String,
      stationTimeZone: String,
      locale: String
    },
    data () {
      return {
        locales: allLocales,
        plugins: ['moment-timezone']
      }
    },
    computed: {
      stationNow () {
        return moment().tz(this.stationTimeZone).format('')
      }
    }
  }
</script>

<style lang="scss">
    @import '~@fullcalendar/core/main.css';
</style>