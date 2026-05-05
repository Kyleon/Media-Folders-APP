<script setup>
import { computed } from 'vue';

const props = defineProps({
  /** Array de { date: 'YYYY-MM-DD', count: N } cubriendo los últimos 365 días */
  data:  { type: Array, default: () => [] },
  /** Texto del label superior */
  label: { type: String, default: 'Subidas último año' },
});

// Etiquetas de meses para el eje X — generadas según los datos reales
const months = computed(() => {
  if (!props.data.length) return [];
  const out = [];
  let lastMonth = null;
  props.data.forEach((d, idx) => {
    const m = d.date.slice(5, 7);
    if (m !== lastMonth) {
      const week = Math.floor(idx / 7);
      out.push({
        week,
        label: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][parseInt(m,10) - 1],
      });
      lastMonth = m;
    }
  });
  return out;
});

const max = computed(() => Math.max(1, ...props.data.map(d => d.count)));

// Devolvemos un array de columnas (semanas), cada una con 7 celdas (días)
// Alineamos para que la PRIMERA semana empiece en lunes (cell index 0 = Lun, 6 = Dom)
const weeks = computed(() => {
  const cells = [];
  // Padding inicial: la primera fecha cae en algún día de la semana.
  if (!props.data.length) return [];
  const firstDate = new Date(props.data[0].date + 'T00:00:00');
  // getDay(): 0=Dom, 1=Lun, ... lo convertimos a 0=Lun, ..., 6=Dom
  let dow = firstDate.getDay();
  dow = dow === 0 ? 6 : dow - 1;
  for (let i = 0; i < dow; i++) cells.push(null);
  props.data.forEach(d => cells.push(d));
  // Padding final hasta completar la última semana
  while (cells.length % 7 !== 0) cells.push(null);

  // Trocear en columnas de 7
  const out = [];
  for (let i = 0; i < cells.length; i += 7) {
    out.push(cells.slice(i, i + 7));
  }
  return out;
});

function intensity(count) {
  if (!count) return 0;
  const r = count / max.value;
  if (r < 0.25) return 1;
  if (r < 0.5)  return 2;
  if (r < 0.75) return 3;
  return 4;
}

const total = computed(() => props.data.reduce((a, d) => a + d.count, 0));
const activeDays = computed(() => props.data.filter(d => d.count > 0).length);

// Racha actual (días consecutivos con al menos una subida, hasta hoy)
const streak = computed(() => {
  let s = 0;
  for (let i = props.data.length - 1; i >= 0; i--) {
    if (props.data[i].count > 0) s++;
    else break;
  }
  return s;
});

const dayLabels = ['L', '', 'X', '', 'V', '', 'D'];
</script>

<template>
  <div class="card heatmap-card">
    <div class="head">
      <span class="card-label">{{ label }}</span>
      <span class="muted small">
        {{ total }} subidas · {{ activeDays }} días activos
        <span v-if="streak > 0">· 🔥 racha {{ streak }}d</span>
      </span>
    </div>

    <div v-if="!data.length" class="empty muted small">Sin datos.</div>

    <div v-else class="hm-wrap">
      <div class="hm-months" :style="{ gridTemplateColumns: `repeat(${weeks.length}, 1fr)` }">
        <span v-for="m in months" :key="m.week + m.label"
          :style="{ gridColumnStart: m.week + 1 }">{{ m.label }}</span>
      </div>

      <div class="hm-grid" :style="{ gridTemplateColumns: `repeat(${weeks.length}, 1fr)` }">
        <!-- Etiquetas de día a la izquierda -->
        <div class="hm-days" aria-hidden="true">
          <span v-for="(d, i) in dayLabels" :key="i">{{ d }}</span>
        </div>
        <div v-for="(week, wi) in weeks" :key="wi" class="hm-week">
          <div v-for="(cell, di) in week" :key="di"
            class="hm-cell"
            :class="cell ? 'lvl-' + intensity(cell.count) : 'lvl-empty'"
            :title="cell ? `${cell.date}: ${cell.count} subida${cell.count === 1 ? '' : 's'}` : ''">
          </div>
        </div>
      </div>

      <div class="hm-legend muted small">
        Menos
        <span class="hm-cell lvl-0"></span>
        <span class="hm-cell lvl-1"></span>
        <span class="hm-cell lvl-2"></span>
        <span class="hm-cell lvl-3"></span>
        <span class="hm-cell lvl-4"></span>
        Más
      </div>
    </div>
  </div>
</template>

<style scoped>
.head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 6px; }
.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-mute); font-weight: 600; }
.small { font-size: 11px; }

.hm-wrap { display: flex; flex-direction: column; gap: 6px; overflow-x: auto; }

.hm-months {
  display: grid;
  font-size: 9px;
  color: var(--text-mute);
  padding-left: 18px;
  margin-bottom: 2px;
}
.hm-months > span {
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: .5px;
}

.hm-grid {
  display: grid;
  gap: 2px;
  position: relative;
  padding-left: 18px;
}
.hm-days {
  position: absolute;
  left: 0;
  top: 0;
  display: grid;
  grid-template-rows: repeat(7, 1fr);
  gap: 2px;
  font-size: 8px;
  color: var(--text-mute);
  height: 100%;
  width: 16px;
  align-items: center;
}
.hm-days span { line-height: 1; }

.hm-week {
  display: grid;
  grid-template-rows: repeat(7, 1fr);
  gap: 2px;
}

.hm-cell {
  width: 100%;
  aspect-ratio: 1;
  min-width: 9px;
  min-height: 9px;
  border-radius: 2px;
  background: var(--s2);
  transition: transform .12s;
}
.hm-cell:hover { transform: scale(1.6); z-index: 2; position: relative; }
.lvl-empty { background: transparent; }
.lvl-0 { background: var(--s2); }
.lvl-1 { background: color-mix(in srgb, var(--accent) 20%, var(--s2)); }
.lvl-2 { background: color-mix(in srgb, var(--accent) 45%, var(--s2)); }
.lvl-3 { background: color-mix(in srgb, var(--accent) 70%, var(--s2)); }
.lvl-4 { background: var(--accent); }

.hm-legend { display: flex; align-items: center; gap: 4px; justify-content: flex-end; margin-top: 4px; font-size: 10px; }
.hm-legend .hm-cell { width: 10px; height: 10px; aspect-ratio: 1; min-width: 0; min-height: 0; }

.empty { padding: 20px; text-align: center; }
</style>
