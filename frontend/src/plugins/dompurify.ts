import DOMPurify from 'dompurify';
import { type App } from 'vue';

export default {
  install: (app: App) => {
    app.directive('safe-html', {
      mounted(el, binding) {
        el.innerHTML = DOMPurify.sanitize(binding.value);
      },
      updated(el, binding) {
        if (binding.value !== binding.oldValue) {
          el.innerHTML = DOMPurify.sanitize(binding.value);
        }
      }
    });
  }
};
