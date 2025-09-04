// assets/js/bilingual_file_input.js
(function(){
  function init(root){
    const input = root.querySelector('input[type=file]');
    const btnBrowse = root.querySelector('.bf-btn[data-action=browse]');
    const fn = root.querySelector('.bf-filename');
    const btnClear = root.querySelector('.bf-icon[data-action=clear]');
    const lang = (root.getAttribute('data-lang') || 'ar').toLowerCase();
    const i18nAll = window.__BILINGUAL_FILE_INPUT_I18N || {
      ar: { choose:'اختر ملف', none:'لم يتم اختيار ملف', change:'تغيير', remove:'إزالة' },
      en: { choose:'Choose file', none:'No file chosen', change:'Change', remove:'Remove' }
    };
    const t = i18nAll[lang] || i18nAll['ar'];

    // apply default labels
    btnBrowse.textContent = t.choose;
    fn.textContent = t.none;
    btnClear.title = t.remove;

    btnBrowse.addEventListener('click', function(){
      input.click();
    });

    btnClear.addEventListener('click', function(){
      input.value = '';
      fn.textContent = t.none;
      btnBrowse.textContent = t.choose;
      btnClear.style.display = 'none';
      // fire change event so forms can react if needed
      const ev = new Event('change', { bubbles: true });
      input.dispatchEvent(ev);
    });

    input.addEventListener('change', function(){
      if (input.files && input.files.length > 0){
        if (input.multiple){
          // list up to 2 names + count
          const names = Array.from(input.files).map(f => f.name);
          const preview = names.length <= 2 ? names.join(', ') : (names.slice(0,2).join(', ') + ' +' + (names.length-2));
          fn.textContent = preview;
        } else {
          fn.textContent = input.files[0].name;
        }
        btnBrowse.textContent = t.change;
        btnClear.style.display = 'inline';
      } else {
        fn.textContent = t.none;
        btnBrowse.textContent = t.choose;
        btnClear.style.display = 'none';
      }
    });
  }

  function boot(){
    document.querySelectorAll('.bf-input').forEach(init);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
