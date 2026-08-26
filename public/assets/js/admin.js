document.addEventListener('DOMContentLoaded', function () {
  var toggleBtn = document.getElementById('adminSidebarToggle');
  var sidebar = document.getElementById('adminSidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
    });
  }

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  // Draft auto-save: any form with data-autosave-key stores its field values
  // to localStorage every few seconds and restores them on load, until submitted.
  document.querySelectorAll('form[data-autosave-key]').forEach(function (form) {
    var key = 'saa_draft_' + form.dataset.autosaveKey;

    var saved = localStorage.getItem(key);
    if (saved) {
      try {
        var data = JSON.parse(saved);
        Object.keys(data).forEach(function (name) {
          var field = form.elements[name];
          if (field && !field.value) field.value = data[name];
        });
      } catch (e) { /* ignore malformed draft */ }
    }

    setInterval(function () {
      var data = {};
      Array.from(form.elements).forEach(function (field) {
        if (field.name && (field.type === 'text' || field.type === 'textarea' || field.tagName === 'TEXTAREA')) {
          data[field.name] = field.value;
        }
      });
      localStorage.setItem(key, JSON.stringify(data));
    }, 5000);

    form.addEventListener('submit', function () {
      localStorage.removeItem(key);
    });
  });
});
