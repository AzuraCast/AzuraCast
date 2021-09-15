import '~/vendor/luxon.js';

import {
  Chart,
  registerables
} from 'chart.js';

import 'chartjs-adapter-luxon';

import colorSchemesPlugin
  from './chartjs-colorschemes/plugin.colorschemes.js';

import zoomPlugin
  from 'chartjs-plugin-zoom';

Chart.register(...registerables);

Chart.register(colorSchemesPlugin);

Chart.register(zoomPlugin);
