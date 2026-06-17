import { defineStore } from 'pinia';
import { SlidersAPI } from '../api/endpoints';

/**
 * Genera un id único para un slide nuevo. Se respeta si viene del server,
 * si no tiene se genera localmente. El backend regenerará si está vacío.
 */
const newSlideLocalId = () =>
  'slide_' + Math.random().toString(36).slice(2, 10);

const defaultSettings = () => ({
  autoplay:   true,
  speed:      6000,
  transition: 'slide',     // slide | fade
  navigation: true,
  pagination: 'bullets',   // bullets | progress | none
  loop:       true,
  kenburns:   true,
  height:     '100vh',
});

const defaultSlideStyle = () => ({
  overlay_color:     '#000000',
  overlay_opacity:   0.3,
  text_color:        '#ffffff',
  text_alignment:    'center',  // start | center | end
  vertical_position: 'center',  // top | center | bottom
  kenburns:          true,
});

const newSlide = () => ({
  id:              newSlideLocalId(),
  type:            'image',
  image_id:        0,
  video_id:        0,
  video_embed_url: '',
  title:           '',
  subtitle:        '',
  text:            '',
  location:        '',
  lat:             null,
  lng:             null,
  button_text:     '',
  button_url:      '',
  style:           defaultSlideStyle(),
});

export const useSlidersStore = defineStore('sliders', {
  state: () => ({
    items:    [],         // resúmenes para el listado
    current:  null,       // slider abierto en el editor
    isDirty:  false,      // hay cambios sin guardar
    loading:  false,
    saving:   false,
  }),

  getters: {
    currentSlides: (state) => state.current?.data?.slides ?? [],
    currentSettings: (state) => state.current?.data?.settings ?? defaultSettings(),
    slideById: (state) => (slideId) =>
      state.current?.data?.slides?.find((s) => s.id === slideId) ?? null,
  },

  actions: {
    /* ─────── Listado ─────── */

    async fetchAll() {
      this.loading = true;
      try {
        this.items = await SlidersAPI.list({ per_page: 100 });
      } finally {
        this.loading = false;
      }
    },

    /* ─────── Editor: cargar / guardar / borrar ─────── */

    async fetchOne(id) {
      this.loading = true;
      try {
        this.current = await SlidersAPI.detail(id);
        this.isDirty = false;
      } finally {
        this.loading = false;
      }
    },

    async save() {
      if (!this.current) return null;
      this.saving = true;
      try {
        const body = {
          title: this.current.title,
          data:  this.current.data,
        };
        const updated = await SlidersAPI.update(this.current.id, body);
        this.current = updated;
        this.isDirty = false;
        return updated;
      } finally {
        this.saving = false;
      }
    },

    async create(title = 'Nuevo slider') {
      const created = await SlidersAPI.create({ title });
      // Refresca listado
      await this.fetchAll();
      return created;
    },

    async remove(id, force = false) {
      await SlidersAPI.remove(id, force);
      this.items = this.items.filter((s) => s.id !== id);
      if (this.current?.id === id) this.current = null;
    },

    async duplicate(id) {
      const dup = await SlidersAPI.duplicate(id);
      await this.fetchAll();
      return dup;
    },

    /* ─────── Editor: mutaciones locales (no guardan hasta save()) ─────── */

    setTitle(title) {
      if (!this.current) return;
      this.current.title = title;
      this.isDirty = true;
    },

    updateSettings(patch) {
      if (!this.current) return;
      this.current.data.settings = { ...this.current.data.settings, ...patch };
      this.isDirty = true;
    },

    addSlide() {
      if (!this.current) return;
      this.current.data.slides.push(newSlide());
      this.isDirty = true;
    },

    updateSlide(slideId, patch) {
      if (!this.current) return;
      const slide = this.current.data.slides.find((s) => s.id === slideId);
      if (!slide) return;
      Object.assign(slide, patch);
      this.isDirty = true;
    },

    updateSlideStyle(slideId, patch) {
      if (!this.current) return;
      const slide = this.current.data.slides.find((s) => s.id === slideId);
      if (!slide) return;
      slide.style = { ...slide.style, ...patch };
      this.isDirty = true;
    },

    removeSlide(slideId) {
      if (!this.current) return;
      this.current.data.slides = this.current.data.slides.filter(
        (s) => s.id !== slideId
      );
      this.isDirty = true;
    },

    duplicateSlide(slideId) {
      if (!this.current) return;
      const idx = this.current.data.slides.findIndex((s) => s.id === slideId);
      if (idx < 0) return;
      const copy = structuredClone(this.current.data.slides[idx]);
      copy.id = newSlideLocalId();
      this.current.data.slides.splice(idx + 1, 0, copy);
      this.isDirty = true;
    },

    /** Reemplaza el orden de slides (resultado de drag & drop). */
    reorderSlides(newSlides) {
      if (!this.current) return;
      this.current.data.slides = newSlides;
      this.isDirty = true;
    },

    /** Aplica el estilo del primer slide a todos los demás. */
    applyStyleToAll() {
      if (!this.current?.data?.slides?.length) return;
      const ref = { ...this.current.data.slides[0].style };
      this.current.data.slides.forEach((s, i) => {
        if (i > 0) s.style = { ...ref };
      });
      this.isDirty = true;
    },

    /* ─────── Helpers expuestos ─────── */

    factories: {
      newSlide,
      defaultSettings,
      defaultSlideStyle,
    },
  },
});
