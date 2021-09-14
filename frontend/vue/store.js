import Vue
  from 'vue';
import Vuex
  from 'vuex';

Vue.use(Vuex);

const player = {
  namespaced: true,
  state: {
    isPlaying: false,
    current: {
      url: null,
      isStream: true
    }
  },
  mutations: {
    toggle (state, payload) {
      let url = payload.url;

      if (state.current.url === url) {
        state.current = {
          url: null,
          isStream: true
        };
      } else {
        state.current = payload;
      }
    },
    startPlaying (state) {
      state.isPlaying = true;
    },
    stopPlaying (state) {
      state.isPlaying = false;
    }
  }
};

const store = new Vuex.Store({
  modules: {
    player: player
  }
});

export default store;
