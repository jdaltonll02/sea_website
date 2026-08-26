document.addEventListener('DOMContentLoaded', function () {
  if (typeof L !== 'undefined') {
    var singleMap = document.getElementById('churchMap');
    if (singleMap) {
      var lat = parseFloat(singleMap.dataset.lat);
      var lng = parseFloat(singleMap.dataset.lng);
      var map = L.map(singleMap).setView([lat, lng], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
      }).addTo(map);
      L.marker([lat, lng]).addTo(map).bindPopup(singleMap.dataset.name || '');
    }

    var multiMap = document.getElementById('contactMap');
    if (multiMap) {
      var churches = [];
      try {
        churches = JSON.parse(multiMap.dataset.churches || '[]');
      } catch (e) {
        churches = [];
      }
      if (churches.length) {
        var cmap = L.map(multiMap).setView([churches[0].lat, churches[0].lng], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors',
        }).addTo(cmap);
        churches.forEach(function (c) {
          L.marker([c.lat, c.lng]).addTo(cmap).bindPopup(
            '<strong>' + c.name + '</strong><br><a href="' + c.url + '">View Church</a>'
          );
        });
      }
    }
  }

  var form = document.getElementById('newsletterForm');
  if (!form) return;

  var messageBox = document.getElementById('newsletterFormMessage');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    messageBox.textContent = '';

    var formData = new FormData(form);
    var submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    fetch(form.getAttribute('data-action') || (window.APP_BASE_URL + '/pages/newsletter-subscribe.php'), {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        messageBox.textContent = data.message || '';
        messageBox.className = data.success ? 'mt-3 text-white' : 'mt-3 text-warning';
        if (data.success) {
          form.reset();
        }
      })
      .catch(function () {
        messageBox.textContent = 'Something went wrong. Please try again later.';
        messageBox.className = 'mt-3 text-warning';
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  });
});
